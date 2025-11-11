<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Model;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Question model.
 *
 * @since  1.6
 */
class QuestionModel extends AdminModel
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
	public $typeAlias = 'com_quiztools.question';

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @return  void
     *
     * @since   1.6
     */
    protected function populateState()
    {
        $app = Factory::getApplication();

        $pk = $app->getInput()->getInt('id');

        if (!$pk) {
            if ($type = $app->getUserState('com_quiztools.add.question.type')) {
                $this->setState('question.type', $type);
            }
        } else {
            $this->setState('question.type', null);
            $app->setUserState('com_quiztools.add.question.type', null);
        }

        $this->setState('question.id', $pk);

        // Load the parameters.
        $value = ComponentHelper::getParams($this->option);
        $this->setState('params', $value);
    }

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
        $pk = (!empty($pk)) ? (int) $pk : (int) $this->getState('question.id');

		if ($item = parent::getItem($pk)) {
            if (!$pk) {
                if ($type = $this->getState('question.type')) {
                    $item->type = $type;
                }
            }

			// Convert the params field to an array. => in parent
			//$registry = new Registry($item->params);
			//$item->params = $registry->toArray();

			if (!empty($item->type)) {
				// Getting data from a question type plugin:
				$dispatcher = $this->getDispatcher();
				PluginHelper::importPlugin('quiztools', null, true, $dispatcher);
				$item = $dispatcher->dispatch(
					'onQuestionOptionsGetAdminData',
					new Model\PrepareDataEvent('onQuestionOptionsGetAdminData', [
						'context' => 'com_quiztools.admin.question.typeData',
						'data'    => $item,
						'subject' => new \stdClass(),
					])
				)->getArgument('result', $item);
			}
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
		$form = $this->loadForm('com_quiztools.question', 'question', ['control' => 'jform', 'load_data' => $loadData]);

		if (empty($form)) {
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   string   $name      Form name.
	 * @param   string   $path      Path to the form file.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \Joomla\CMS\Form\Form|boolean  A Form object on success, false on failure
	 *
	 * @since   1.6
	 */
	public function getFormQuestionType($name = '', $path = '', $loadData = true)
	{
		if (empty($name) || empty($path)) {
			return false;
		}

		$form = $this->loadForm($name, $path, ['control' => 'jform', 'load_data' => $loadData]);

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
		$data = Factory::getApplication()->getUserState('com_quiztools.edit.question.data', []);

		if (empty($data)) {
			$data = $this->getItem();
		}

		// TIP: Plugin's folder 'quiztools': event 'onContentPrepareData'.
		$this->preprocessData('com_quiztools.question', $data, 'quiztools');

		return $data;
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
        $question_id = !empty($data['id']) ? $data['id'] : 0;

        // Get related quizzes BEFORE saving the question, since the quiz in the existing question may be changed.
        $affected_quizzes_ids_before = [];
        if (!empty($question_id)) {
            $affected_quizzes_ids_before = $this->getQuizzesIdsByQuestionsIds([$question_id]);
        }

		/** @var QuizModel $model_quiz */
		$model_quiz = Factory::getApplication()->bootComponent('com_quiztools')->getMVCFactory()
			->createModel('Quiz', 'Administrator', ['ignore_request' => true]);

		$data['catid'] = $model_quiz->createCategoryByCatId($data['catid'], 'com_quiztools.question');

		if (!parent::save($data)) {
			return false;
		}

		if (empty($question_id)) {  //question is new
			$db = $this->getDatabase();
			$query = $db->createQuery()
				->select('MAX(' . $db->qn('id') . ')')
				->from($db->qn('#__quiztools_questions'));
			$db->setQuery($query);
			$question_id = (int)$db->loadResult();
		}

		$affected_quizzes_ids_after = $this->getQuizzesIdsByQuestionsIds([$question_id]);
        $affected_quizzes_ids = array_values(array_unique(array_merge($affected_quizzes_ids_before, $affected_quizzes_ids_after)));

		if (!empty($affected_quizzes_ids)) {
			if ($this->recalculateQuizzesTotalScore($affected_quizzes_ids)) {
				return true;
			}

			return false;
		}

		return true;
	}

	/**
	 * Method to delete one or more records.
	 *
	 * @param   array  &$pks  An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since   1.6
	 */
	public function delete(&$pks)
	{
		// Here need to get affected quizzes Ids before deleting, otherwise there will be no data.
		// And the quiz total score will not be recalculated.
		$affected_quizzes_ids = $this->getQuizzesIdsByQuestionsIds($pks);

		if (!parent::delete($pks)) {
			return false;
		}

		if (!empty($affected_quizzes_ids)) {
			if ($this->recalculateQuizzesTotalScore($affected_quizzes_ids)) {
				return true;
			}

			return false;
		}

		return true;
	}

	/**
	 * Method to change the published state of one or more records.
	 *
	 * @param   array    &$pks   A list of the primary keys to change.
	 * @param   integer  $value  The value of the published state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	public function publish(&$pks, $value = 1)
	{
		if (!parent::publish($pks, $value)) {
			return false;
		}

		$affected_quizzes_ids = $this->getQuizzesIdsByQuestionsIds($pks);

		if (!empty($affected_quizzes_ids)) {
			if ($this->recalculateQuizzesTotalScore($affected_quizzes_ids)) {
				return true;
			}

			return false;
		}

		return true;
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
	 * Recalculate Quiz Total Score.
	 *
	 * @param array $quizzes_ids
	 *
	 * @return bool
	 */
	public function recalculateQuizzesTotalScore($quizzes_ids)
	{
		$db = $this->getDatabase();
		$query = $db->createQuery();

		foreach ($quizzes_ids as $quiz_id) {
			$query->clear()
				->select($db->qn(['id', 'type', 'points']))
				->from($db->qn('#__quiztools_questions'))
				->where($db->qn('quiz_id') . ' = :quizId')
				// When calculating the quiz total score only get published questions:
				->where($db->qn('state') . '=' . $db->q('1'))
				->bind(':quizId', $quiz_id, ParameterType::INTEGER);
			$db->setQuery($query);
			$data_questions = $db->loadObjectList();

            $total_score = $this->getTotalScoreOfQuestionsSet($data_questions);

			$query->clear()
				->update($db->qn('#__quiztools_quizzes'))
				->set($db->qn('total_score') . '=' . (float) $total_score)
				->where($db->qn('id') . ' = :id')
				->bind(':id', $quiz_id, ParameterType::INTEGER);
			$db->setQuery($query)->execute();
		}

		return true;
	}

    /**
     * Get sum Total score of questions set.
     * @param array $questions
     * @return float|int
     */
    public function getTotalScoreOfQuestionsSet($questions)
    {
        $dispatcher = $this->getDispatcher();
        PluginHelper::importPlugin('quiztools', null, true, $dispatcher);

        $total_score = 0;

        if (!empty($questions)) {
            foreach ($questions as $question) {
                $question_options_score = $dispatcher->dispatch(
                    'onQuestionOptionsGetScore',
                    new Model\PrepareDataEvent('onQuestionOptionsGetScore', [
                        'context' => 'com_quiztools.question.options.score',
                        'data'    => $question,
                        'subject' => new \stdClass(),
                    ])
                )->getArgument('result', 0);

                $total_score += (float) $question->points;
                $total_score += (float) $question_options_score;
            }
        }

        return $total_score;
    }

	/**
	 * Get quizzes Ids by questions Ids
	 *
	 * @param $question_ids
	 *
	 * @return array
	 */
	private function getQuizzesIdsByQuestionsIds($question_ids=[])
	{
		if (!is_array($question_ids)) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select($db->qn('quiz_id'))
			->from($db->qn('#__quiztools_questions'))
			->where($db->qn('id') . " IN ('" . implode("','", $question_ids) . "')");
		$db->setQuery($query);
		$quizzes = $db->loadColumn();

		// array_filter : Remove zero values ($quiz_id == 0 => Question Pool)
		$quizzes = array_values(array_filter(array_unique($quizzes)));

		return $quizzes;
	}
}
