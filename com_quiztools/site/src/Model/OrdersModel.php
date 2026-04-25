<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Router\Route;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Joomla\String\StringHelper;
use Qt\Component\Quiztools\Site\Helper\RouteHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * This models supports retrieving lists of Orders.
 *
 * @since  1.6
 */
class OrdersModel extends ListModel
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
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * This method should only be called once per instantiation and is designed
     * to be called on the first call to the getState() method unless the model
     * configuration flag to ignore the request is set.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   3.0.1
     */
    protected function populateState($ordering = 'a.id', $direction = 'DESC')
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();

	    $params = $app->getParams();
	    $this->setState('params', $params);

	    $limit = $app->getUserStateFromRequest($this->context . '.limit', 'limit', $app->get('list_limit', 25), 'uint');
	    $this->setState('list.limit', $limit);

	    $this->setState('list.start', $input->get('limitstart', 0, 'uint'));

	    $order_col = $app->getUserStateFromRequest($this->context . '.filter_order', 'filter_order', '', 'string');
	    if (!\in_array($order_col, $this->filter_fields)) {
		    $order_col = 'a.id';
	    }
	    $this->setState('list.ordering', $order_col);

	    $list_order = $app->getUserStateFromRequest($this->context . '.filter_order_Dir', 'filter_order_Dir', '', 'cmd');
	    if (!\in_array(strtoupper($list_order), ['ASC', 'DESC', ''])) {
		    $list_order = 'DESC';
	    }
	    $this->setState('list.direction', $list_order);

	    $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
	    $this->setState('filter.search', $search);

        $ordersIds = $this->getUserStateFromRequest($this->context . '.filter.ordersIds', 'filter_ordersIds', []);
        $this->setState('filter.ordersIds', $ordersIds);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * @param   string  $id  A prefix for the store id.
     * @return  string  A store id.
     * @since   1.6
     */
    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . json_encode($this->getState('filter.ordersIds'));

        return parent::getStoreId($id);
    }

    /**
     * Get the master query for retrieving a list of Learning Paths subject to the model state.
     *
     * @return  QueryInterface
     * @since   1.6
     */
    protected function getListQuery()
    {
        $user = $this->getCurrentUser();

        $db = $this->getDatabase();
        $query = $db->createQuery();

        $query->select(
            $this->getState(
                'list.select',
                [
                    $db->qn('a.id'),
                    $db->qn('a.status'),
                    $db->qn('a.user_id'),
                    $db->qn('a.subscription_id'),
                    $db->qn('a.store_type'),
                    $db->qn('a.start_datetime'),
                    $db->qn('a.end_datetime'),
                    $db->qn('a.attempts_max'),
                    $db->qn('a.users_used'),
                    $db->qn('s.users_max'),
                    $db->qn('s.type'),
                    $db->qn('s.quiz_id'),
                    $db->qn('s.lpath_id'),
                    $db->qn('a.reActivated'),
                ]
            )
        )
        ->select("IF (
             " . $db->qn('s.title') . " IS NOT NULL, 
             " . $db->qn('s.title') . ",
             '" . Text::_('COM_QUIZTOOLS_ORDERS_TEXT_DELETED_SUBSCRIPTION') . "'
          ) as 'subscription_title'")

        ->from($db->qn('#__quiztools_orders', 'a'))
        ->join(
            'LEFT',
            $db->qn('#__quiztools_subscriptions', 's'),
            $db->qn('s.id') . ' = ' . $db->qn('a.subscription_id')
        )
        ->join(
            'INNER',
            $db->qn('#__quiztools_order_users', 'ou'),
            $db->qn('ou.order_id') . ' = ' . $db->qn('a.id')
        )
        ->where($db->qn('ou.user_id') . ' = ' . $db->q((int) $user->id))
        ;

	    // Filter by search by subscription title, order id
	    if ($search = $this->getState('filter.search')) {
		    $search = StringHelper::strtolower($search);
		    if (stripos($search, 'id:') === 0) {
			    $search = (int) substr($search, 3);
			    $query->where($db->qn('a.id') . ' = :search')
				    ->bind(':search', $search, ParameterType::INTEGER);
		    } else {
                $search = '%' . str_replace(' ', '%', trim($search)) . '%';
                $query->where('(' . $db->qn('s.title') . ' LIKE :search1)')
                    ->bind([':search1'], $search);
		    }
	    }

        // Filter by orders Ids
        if ($ordersIds = $this->getState('filter.ordersIds')) {
            if (is_array($ordersIds) && !empty($ordersIds)) {
                $query->where($db->qn('a.id') . " IN ('" . implode("','", $ordersIds) . "')");
            }
        }

        // Add the list ordering clause.
        $query->order(
            $db->escape($this->getState('list.ordering', 'a.id')) . ' ' . $db->escape($this->getState('list.direction', 'DESC'))
        );

        return $query;
    }

    /**
     * Method to get a list of Orders.
     *
     * @return  mixed  An array of objects on success, false on failure.
     *
     * @since   1.6
     */
    public function getItems()
    {
        $items  = parent::getItems();

        if (!empty($items)) {
            foreach ($items as $item) {
                $item->accessData = HTMLHelper::getServiceRegistry()->getService('quiztoolsaccess')->getAccessOrderData($item);

                $item->link = '';
                if (!empty($item->accessData->access)) {
                    if ($item->type === 'quiz') {
                        $item->link = Route::_(RouteHelper::getQuizRoute((int) $item->quiz_id, null, $item->id), false);
                    } else if ($item->type === 'lpath') {
                        $item->link = Route::_(RouteHelper::getLpathRoute((int) $item->lpath_id, null, $item->id), false);
                    }
                }
                unset($item->type);
                unset($item->quiz_id);
                unset($item->lpath_id);
            }
        }

        return $items;
    }
}
