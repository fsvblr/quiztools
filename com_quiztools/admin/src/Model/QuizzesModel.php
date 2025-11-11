<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Methods supporting a list of quizzes records.
 *
 * @since  1.6
 */
class QuizzesModel extends ListModel
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
		        'title', 'a.title',
		        'catid', 'a.catid', 'category_title',
		        'state', 'a.state',
                'type_access', 'a.type_access',
		        'ordering', 'a.ordering',
		        'total_score', 'a.total_score',
		        'passing_score', 'a.passing_score',
		        'category_id',
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

        $type_access = $this->getUserStateFromRequest($this->context . '.filter.type_access', 'filter_type_access');
        $this->setState('filter.type_access', $type_access);

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
        $id .= ':' . $this->getState('filter.type_access');

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
					$db->qn('a.title'),
					$db->qn('a.alias'),
					$db->qn('a.catid'),
					$db->qn('a.state'),
					$db->qn('a.total_score'),
					$db->qn('a.passing_score'),
					$db->qn('a.ordering'),
					$db->qn('a.type_access'),
					$db->qn('a.checked_out'),
					$db->qn('a.checked_out_time'),
                    $db->qn('a.question_pool'),
					$db->qn('c.title', 'category_title'),
					$db->qn('uc.name', 'editor'),
				]
			)
		)
		->from($db->qn('#__quiztools_quizzes', 'a'))
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

        // Filter by type access (free / paid)
        $type_access = $this->getState('filter.type_access');
        if (is_numeric($type_access)) {
            $type_access = (int) $type_access;
            $query->where($db->qn('a.type_access') . ' = :typeAccess')
                ->bind(':typeAccess', $type_access, ParameterType::INTEGER);
        }

		// Filter by search in title
		if ($search = $this->getState('filter.search')) {
			if (stripos($search, 'id:') === 0) {
				$search = (int) substr($search, 3);
				$query->where($db->qn('a.id') . ' = :search')
					->bind(':search', $search, ParameterType::INTEGER);
			} else {
				$search = '%' . str_replace(' ', '%', trim($search)) . '%';
				$query->where('(' . $db->qn('a.title') . ' LIKE :search1 OR ' . $db->qn('a.alias') . ' LIKE :search2)')
					->bind([':search1', ':search2'], $search);
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

	/**
	 * Getting a list of quizzes to build a 'select' in forms.
	 *
	 * @return array|mixed
	 * @throws \Exception
	 */
	public function getQuizzesList()
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select($db->qn('id', 'value'))
			->select($db->qn('title', 'text'))
            ->select($db->qn('type_access'))
			->from($db->qn('#__quiztools_quizzes'))
            ->order($db->qn('title') . ' ASC');

        $state = (string) $this->getState('filter.state');
        if (is_numeric($state)) {
            $state = (int) $state;
            $query->where($db->qn('state') . ' = :state')
                ->bind(':state', $state, ParameterType::INTEGER);
        }

        // used in administrator/components/com_quiztools/forms/filter_questions.xml
        $withoutPool = $this->getState('filter.withoutPool');
        if ($withoutPool) {
            $query->where($db->qn('question_pool') . '=' . $db->q('no'));
        }

        // used in components/com_quiztools/tmpl/quiz/default.xml (menu type)
        $onlyFree = $this->getState('filter.onlyFree');
        if ($onlyFree) {
            $query->where($db->qn('type_access') . '=' . $db->q(0));
        }

		$db->setQuery($query);

		try {
			$quizzes = $db->loadObjectList();
		} catch (\Exception $e) {
			Factory::getApplication()->enqueueMessage(
				Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()),
				'warning'
			);

			return [];
		}

		return $quizzes;
	}
}
