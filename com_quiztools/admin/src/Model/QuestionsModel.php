<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Methods supporting a list of questions records.
 *
 * @since  1.6
 */
class QuestionsModel extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   1.6
	 */
	public function __construct($config = [])
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = [
				'id', 'a.id',
				'quiz_id', 'a.quiz_id', 'quiz_title',
				'catid', 'a.catid', 'category_title',
				'type', 'a.type',
				'state', 'a.state',
				'ordering', 'a.ordering',
				'category_id', 'question_type',
			];
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function populateState($ordering = 'a.id', $direction = 'desc')
	{
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$state = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '');
		$this->setState('filter.state', $state);

		$category_id = $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id');
		$this->setState('filter.category_id', $category_id);

		$quiz_id = $this->getUserStateFromRequest($this->context . '.filter.quiz_id', 'filter_quiz_id', '');
		$this->setState('filter.quiz_id', $quiz_id);

		$question_type = $this->getUserStateFromRequest($this->context . '.filter.question_type', 'filter_question_type');
		$this->setState('filter.question_type', $question_type);

		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string  A store id.
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');
		$id .= ':' . $this->getState('filter.category_id');
		$id .= ':' . $this->getState('filter.quiz_id');
		$id .= ':' . $this->getState('filter.question_type');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  DatabaseQuery|string  A DatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.6
	 */
	protected function getListQuery()
	{
		$db    = $this->getDatabase();
		$query = $db->createQuery();

		$query->select(
			$this->getState(
				'list.select',
				[
					$db->qn('a.id'),
					$db->qn('a.quiz_id'),
					$db->qn('a.catid'),
					$db->qn('a.type'),
					$db->qn('a.text'),
					$db->qn('a.state'),
					$db->qn('a.ordering'),
					$db->qn('a.created'),
					$db->qn('a.checked_out'),
					$db->qn('a.checked_out_time'),
					$db->qn('q.title', 'quiz_title'),
					$db->qn('c.title', 'category_title'),
					$db->qn('uc.name', 'editor'),
				]
			)
		)
			->from($db->qn('#__quiztools_questions', 'a'))
			->join('LEFT', $db->qn('#__quiztools_quizzes', 'q'), $db->qn('q.id') . ' = ' . $db->qn('a.quiz_id'))
			->join('LEFT', $db->qn('#__categories', 'c'), $db->qn('c.id') . ' = ' . $db->qn('a.catid'))
			->join('LEFT', $db->qn('#__users', 'uc'), $db->qn('uc.id') . ' = ' . $db->qn('a.checked_out'))
		;

		// Filter by state
		$state = (string) $this->getState('filter.state');
		if (is_numeric($state)) {
			$state = (int) $state;
			$query->where($db->qn('a.state') . ' = :state')
				->bind(':state', $state, ParameterType::INTEGER);
		}

		// Filter by category
		$category_id = $this->getState('filter.category_id');
		if (is_numeric($category_id)) {
			$category_id = (int) $category_id;
			$query->where($db->qn('a.catid') . ' = :categoryId')
				->bind(':categoryId', $category_id, ParameterType::INTEGER);
		}

		// Filter by quiz
		$quiz_id = (string) $this->getState('filter.quiz_id');
		if (is_numeric($quiz_id)) {
			$quiz_id = (int) $quiz_id;
			$query->where($db->qn('a.quiz_id') . ' = :quizId')
				->bind(':quizId', $quiz_id, ParameterType::INTEGER);
		}

		// Filter by question type
		if ($question_type = $this->getState('filter.question_type')) {
			$query->where('(' . $db->qn('a.type') . ' LIKE :questionType)')
				->bind([':questionType'], $question_type);
		}

		// Filter by search in text
		if ($search = $this->getState('filter.search')) {
			if (stripos($search, 'id:') === 0) {
				$search = (int) substr($search, 3);
				$query->where($db->qn('a.id') . ' = :search')
					->bind(':search', $search, ParameterType::INTEGER);
			} else {
				$search = '%' . str_replace(' ', '%', trim($search)) . '%';
				$query->where('(' . $db->qn('a.text') . ' LIKE :search)')
					->bind([':search'], $search);
			}
		}

		// Add the list ordering clause.
		$order_col  = $this->state->get('list.ordering', 'a.id');
		$order_dirn = $this->state->get('list.direction', 'DESC');

		if ($order_col === 'a.ordering' || $order_col === 'category_title') {
			$ordering = [
				$db->qn('c.title') . ' ' . $db->escape($order_dirn),
				$db->qn('a.ordering') . ' ' . $db->escape($order_dirn),
			];
		} else {
			$ordering = $db->escape($order_col) . ' ' . $db->escape($order_dirn);
		}

		$query->order($ordering);

		return $query;
	}
}
