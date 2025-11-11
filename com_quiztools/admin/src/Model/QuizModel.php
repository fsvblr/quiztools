<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Component\Categories\Administrator\Helper\CategoriesHelper;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Quiz model.
 *
 * @since  1.6
 */
class QuizModel extends AdminModel
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $text_prefix = 'COM_QUIZTOOLS';

    /**
     * The type alias for this content type.
     *
     * @var    string
     * @since  3.2
     */
    public $typeAlias = 'com_quiztools.quiz';

    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
     *
     * @since   1.6
     */
    protected function canDelete($record)
    {
        if (empty($record->id)) {
            return false;
        }

        if (!empty($record->catid)) {
            return $this->getCurrentUser()->authorise('core.delete', 'com_quiztools.category.' . (int) $record->catid);
        }

        return parent::canDelete($record);
    }

    /**
     * Method to test whether a record can have its state changed.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to change the state of the record.
     *                   Defaults to the permission set in the component.
     *
     * @since   1.6
     */
    protected function canEditState($record)
    {
        $user = $this->getCurrentUser();

        if (!empty($record->catid)) {
            return $user->authorise('core.edit.state', 'com_quiztools.category.' . (int) $record->catid);
        }

        return $user->authorise('core.edit.state', 'com_quiztools');
    }

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk)) {
			// Convert the params field to an array. => in parent
			//$registry = new Registry($item->params);
			//$item->params = $registry->toArray();

			$registry = new Registry($item->question_pool_categories);
			$item->question_pool_categories = $registry->toArray();

			$registry = new Registry($item->feedback_final_msg);
			$item->feedback_final_msg = $registry->toArray();
		}

		return $item;
	}

    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  \Joomla\CMS\Form\Form|boolean  A Form object on success, false on failure
     *
     * @since   1.6
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_quiztools.quiz', 'quiz', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.6
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_quiztools.edit.quiz.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

	    // Plugin's folder 'quiztools': event 'onContentPrepareData'
        $this->preprocessData('com_quiztools.quiz', $data, 'quiztools');

        return $data;
    }

	/**
	 * Method to validate the form data.
	 *
	 * @param   Form    $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  array|boolean  Array of filtered data if valid, false otherwise.
	 *
	 * @see     \Joomla\CMS\Form\FormRule
	 * @see     \Joomla\CMS\Filter\InputFilter
	 * @since   3.7.0
	 */
	public function validate($form, $data, $group = null)
	{
		if (!$this->getCurrentUser()->authorise('core.admin', 'com_quiztools')) {
			if (isset($data['rules'])) {
				unset($data['rules']);
			}
		}

		return parent::validate($form, $data, $group);
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	public function save($data)
	{
		$filter = InputFilter::getInstance();

		if (isset($data['metatitle'])) {
			$data['metatitle'] = $filter->clean($data['metatitle'], 'TRIM');
		}

		if (isset($data['metadesc'])) {
			$data['metadesc'] = $filter->clean($data['metadesc'], 'TRIM');
		}

		if (isset($data['metakey'])) {
			$data['metakey'] = $filter->clean($data['metakey'], 'TRIM');
		}

		$data['catid'] = $this->createCategoryByCatId($data['catid']);

		if (parent::save($data)) {
			return true;
		}

		return false;
	}

	/**
	 * Delete all quiz related entities when deleting a quiz
	 *
	 * @param   array  $pks  The primary key related to the contents that was deleted.
	 *
	 * @return  boolean
	 *
	 * @since   3.7.0
	 */
	public function delete(&$pks)
	{
		$return = parent::delete($pks);

		if ($return) {
			// Deleting all quiz(zes) questions.
			// Question options will be deleted on the 'onTableAfterDelete' event when deleting a question.
			$db = $this->getDatabase();
			$query = $db->createQuery()
				->select($db->qn('id'))
				->from($db->qn('#__quiztools_questions'))
				->where($db->qn('quiz_id') . " IN ('" . implode("','", $pks) . "')");
			try {
				$questions_ids = $db->setQuery($query)->loadColumn();
			} catch (ExecutionFailureException $e) {
				return false;
			}

			if (!empty($questions_ids)) {
				/** @var \Qt\Component\Quiztools\Administrator\Model\QuestionModel $model_question */
				$model_question = Factory::getApplication()->bootComponent('com_quiztools')
					->getMVCFactory()->createModel('Question', 'Administrator', ['ignore_request' => true]);

				$model_question->delete($questions_ids);
			}
		}

		return $return;
	}

	/**
	 * A protected method to get a set of ordering conditions.
	 *
	 * @param   object  $table  A record object.
	 *
	 * @return  array  An array of conditions to add to ordering queries.
	 *
	 * @since   1.6
	 */
	protected function getReorderConditions($table)
	{
		return [
			$this->getDatabase()->qn('catid') . ' = ' . (int) $table->catid,
		];
	}

	/**
	 * Is the user allowed to create an on the fly category?
	 *
	 * @return  boolean
	 *
	 * @since   3.6.1
	 */
	private function canCreateCategory()
	{
		return $this->getCurrentUser()->authorise('core.create', 'com_quiztools');
	}

	/**
	 * @param $catid
	 * @param $extension
	 * @param $language
	 *
	 * @return \Joomla\CMS\MVC\Model\State|mixed|null
	 * @throws \Exception
	 */
	public function createCategoryByCatId($catid = null, $extension = 'com_quiztools', $language = '*')
	{
		$create_category = true;

		if (\is_null($catid)) {
			// When there is no catid passed don't try to create one
			$create_category = false;
		}

		// If category ID is provided, check if it's valid.
		if (is_numeric($catid) && $catid) {
			$create_category = !CategoriesHelper::validateCategoryId($catid, $extension);
		}

		// Save New Category
		if ($create_category && $this->canCreateCategory()) {
			$category = [
				// Remove #new# prefix, if exists.
				'title'     => strpos($catid, '#new#') === 0 ? substr($catid, 5) : $catid,
				'parent_id' => 1,
				'extension' => $extension,
				'language'  => $language,
				'published' => 1,
			];

			/** @var \Joomla\Component\Categories\Administrator\Model\CategoryModel $model_category */
			$model_category = Factory::getApplication()->bootComponent('com_categories')
				->getMVCFactory()->createModel('Category', 'Administrator', ['ignore_request' => true]);

			// Create new category.
			try {
				$model_category->save($category);
			} catch (\RuntimeException $e) {
				throw new \Exception($e->getMessage(), 500, $e);
			}

			// Get the Category ID.
			$catid = $model_category->getState('category.id');
		}

		return $catid;
	}
}
