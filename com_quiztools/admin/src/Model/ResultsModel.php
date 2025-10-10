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
use Qt\Component\Quiztools\Administrator\Helper\QuiztoolsHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Methods supporting a list of results records.
 *
 * @since  1.6
 */
class ResultsModel extends ListModel
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
                'quiz_id', 'a.quiz_id',
                'user_id', 'a.user_id',
                'total_score', 'a.total_score',
                'passing_score', 'a.passing_score',
                'sum_points_received', 'a.sum_points_received',
                'passed', 'a.passed',
                'finished', 'a.finished',
                'start_datetime', 'a.start_datetime',
                'sum_time_spent', 'a.sum_time_spent',
                'title', 'q.title',
                'from', 'to',
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
    protected function populateState($ordering = 'a.start_datetime', $direction = 'desc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $passed = $this->getUserStateFromRequest($this->context . '.filter.passed', 'filter_passed', '');
        $this->setState('filter.passed', $passed);

        $quiz_id = $this->getUserStateFromRequest($this->context . '.filter.quiz_id', 'filter_quiz_id', '');
        $this->setState('filter.quiz_id', $quiz_id);

        $user_id = $this->getUserStateFromRequest($this->context . '.filter.user_id', 'filter_user_id', '');
        $this->setState('filter.user_id', $user_id);

        $from = $this->getUserStateFromRequest($this->context . '.filter.from', 'filter_from', '');
        $this->setState('filter.from', $from);

        $to = $this->getUserStateFromRequest($this->context . '.filter.to', 'filter_to', '');
        $this->setState('filter.to', $to);

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
        $id .= ':' . $this->getState('filter.passed');
        $id .= ':' . $this->getState('filter.quiz_id');
        $id .= ':' . $this->getState('filter.user_id');
        $id .= ':' . $this->getState('filter.from');
        $id .= ':' . $this->getState('filter.to');

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
                    $db->qn('a.user_id'),
                    $db->qn('a.total_score'),
                    $db->qn('a.passing_score'),
                    $db->qn('a.sum_points_received'),
                    $db->qn('a.passed'),
                    $db->qn('a.finished'),
                    $db->qn('a.start_datetime'),
                    $db->qn('a.sum_time_spent'),
                    $db->qn('q.title'),

                    // Used at the front end:
                    $db->qn('a.unique_id'),
                    $db->qn('q.results_certificate'),
                    $db->qn('q.results_pdf'),
                ]
            )
        )
            ->select("IF (
                    " . $db->qn('a.user_id') . " > 0, 
                    " . $db->qn('u.name') . ", 
                    CONCAT (
                        IF (  
                            (" . $db->qn('ru.user_name') . " = '' AND " . $db->qn('ru.user_surname') . " = ''),
                            '" . Text::_('COM_QUIZTOOLS_RESULTS_USER_ANONYMOUS') . "',
                            CONCAT(" . $db->qn('ru.user_name') . ", ' '," . $db->qn('ru.user_surname') . ")
                        ), 
                        ' ', 
                        ' (" . Text::_('COM_QUIZTOOLS_RESULTS_USER_GUEST') . ")'
                    )
                ) as 'user_name'")

            ->from($db->qn('#__quiztools_results_quizzes', 'a'))
            ->join('LEFT', $db->qn('#__users', 'u'), $db->qn('u.id') . ' = ' . $db->qn('a.user_id'))
            ->join('LEFT', $db->qn('#__quiztools_results_users', 'ru'), $db->qn('ru.result_quiz_id') . ' = ' . $db->qn('a.id'))
            ->join('LEFT', $db->qn('#__quiztools_quizzes', 'q'), $db->qn('q.id') . ' = ' . $db->qn('a.quiz_id'))
        ;

        // Filter by selected items for export to file
        $selectedItems = $this->getState('filter.selectedItems');
        $task = $this->getState('filter.task');

        if (!empty($selectedItems) && !empty($task) && \in_array($task, ['exportExcel'])) {
            $selectedItems = array_filter((array) $selectedItems);
            $query->where($db->qn('a.id') . " IN ('".implode("','", $selectedItems)."')");
        } else {

            // Filter by passed
            $passed = (string)$this->getState('filter.passed');
            if (is_numeric($passed)) {
                $passed = (int)$passed;
                $query->where($db->qn('a.passed') . ' = :passed')
                    ->bind(':passed', $passed, ParameterType::INTEGER);
            }

            // Filter by quiz_id
            $quiz_id = (string)$this->getState('filter.quiz_id');
            if (is_numeric($quiz_id)) {
                $quiz_id = (int)$quiz_id;
                $query->where($db->qn('a.quiz_id') . ' = :quizId')
                    ->bind(':quizId', $quiz_id, ParameterType::INTEGER);
            }

            // Filter by user_id
            $filterUserId = (string)$this->getState('filter.user_id');
            if (is_numeric($filterUserId)) {
                $filterUserId = (int) $filterUserId;
                $query->where($db->qn('a.user_id') . ' = :userId')
                    ->bind(':userId', $filterUserId, ParameterType::INTEGER);
            }

            // Filter by from / to
            // `start_datetime` in UTC, filter in local time
            $user = $this->getCurrentUser();
            $userTimezone = $user->getParam('timezone', Factory::getApplication()->getConfig()->get('offset', 'UTC'));
            $userTimezone = new \DateTimeZone($userTimezone);
            $from = $this->state->get('filter.from');
            if (!empty($from)) {
                $localDate = new \DateTime($from, $userTimezone);
                $localDate->setTimezone(new \DateTimeZone('UTC'));
                $fromUtc = $localDate->format('Y-m-d H:i:s');
                $query->where($db->qn('a.start_datetime') . ' >= ' . $db->q($db->escape($fromUtc)));
            }
            $to = $this->state->get('filter.to');
            if (!empty($to)) {
                $localDate = new \DateTime($to, $userTimezone);
                $localDate->setTimezone(new \DateTimeZone('UTC'));
                $toUtc = $localDate->format('Y-m-d H:i:s');
                $query->where($db->qn('a.start_datetime') . ' <= ' . $db->q($db->escape($toUtc)));
            }

            // Filter by search in the quiz title
            if ($search = $this->getState('filter.search')) {
                if (stripos($search, 'id:') === 0) {
                    $search = (int)substr($search, 3);
                    $query->where($db->qn('a.quiz_id') . ' = :search')
                        ->bind(':search', $search, ParameterType::INTEGER);
                } else {
                    $search = '%' . $db->escape(trim($search), true) . '%';
                    $query->where(
                        '(' .
                        $db->qn('q.title') . ' LIKE :search1 ' .
                        ' OR ' . $db->qn('ru.user_name') . ' LIKE :search2' .
                        ' OR ' . $db->qn('ru.user_surname') . ' LIKE :search3' .
                        ')'
                    )
                        ->bind([':search1', ':search2', ':search3'], $search);
                }
            }
        }

        // Add the list ordering clause.
        $order_col  = $this->state->get('list.ordering', 'a.start_datetime');
        $order_dirn = $this->state->get('list.direction', 'DESC');

        $ordering = $db->escape($order_col) . ' ' . $db->escape($order_dirn);

        $query->order($ordering);

        return $query;
    }

    /**
     * Method to get an array of data items.
     *
     * @return  mixed  An array of data items on success, false on failure.
     *
     * @since   1.6
     */
    public function getItems()
    {
        $items = parent::getItems();

        if (!empty($items)) {
            foreach ($items as $item) {
                // From UTC to user's time zone
                if (!empty($item->start_datetime)) {
                    $item->start_datetime_for_display = QuiztoolsHelper::fromUtcToUsersTimeZone($item->start_datetime);
                }
            }
        }

        return $items;
    }
}
