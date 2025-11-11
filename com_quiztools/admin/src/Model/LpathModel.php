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
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Learning Path model.
 *
 * @since  1.6
 */
class LpathModel extends AdminModel
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
    public $typeAlias = 'com_quiztools.lpath';

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

			$registry = new Registry($item->lpath_items);
			$item->lpath_items = $registry->toArray();
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
        $form = $this->loadForm('com_quiztools.lpath', 'lpath', ['control' => 'jform', 'load_data' => $loadData]);

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
        $data = Factory::getApplication()->getUserState('com_quiztools.edit.lpath.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

	    // Plugin's folder 'quiztools': event 'onContentPrepareData'
        $this->preprocessData('com_quiztools.lpath', $data, 'quiztools');

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

        /** @var QuizModel $model_quiz */
        $model_quiz = Factory::getApplication()->bootComponent('com_quiztools')->getMVCFactory()
            ->createModel('Quiz', 'Administrator', ['ignore_request' => true]);

        $data['catid'] = $model_quiz->createCategoryByCatId($data['catid'], 'com_quiztools.lpath');

		if (parent::save($data)) {
			return true;
		}

		return false;
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
}
