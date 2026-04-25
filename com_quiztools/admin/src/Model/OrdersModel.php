<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Model;

use Joomla\CMS\Event\Model;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Methods supporting a list of Orders records.
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
                'status', 'a.status',
                'user_id', 'a.user_id',
                'subscription_id', 'a.subscription_id',
                'store_type', 'a.store_type',
                's.title', 'subscription_title',
                'user_name',
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

	    $subscription_id = $this->getUserStateFromRequest($this->context . '.filter.subscription_id', 'filter_subscription_id');
	    $this->setState('filter.subscription_id', $subscription_id);

        $user_id = $this->getUserStateFromRequest($this->context . '.filter.user_id', 'filter_user_id');
        $this->setState('filter.user_id', $user_id);

        $status = $this->getUserStateFromRequest($this->context . '.filter.status', 'filter_status');
        $this->setState('filter.status', $status);

        $store_type = $this->getUserStateFromRequest($this->context . '.filter.store_type', 'filter_store_type');
        $this->setState('filter.store_type', $store_type);

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
        $id .= ':' . $this->getState('filter.subscription_id');
        $id .= ':' . $this->getState('filter.user_id');
        $id .= ':' . $this->getState('filter.status');
        $id .= ':' . $this->getState('filter.store_type');

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
                    $db->qn('a.status'),
					$db->qn('a.user_id'),
                    $db->qn('a.subscription_id'),
					$db->qn('a.store_type'),
					$db->qn('a.checked_out'),
					$db->qn('a.checked_out_time'),
                    $db->qn('uc.name', 'editor'),
				]
			)
		)
        ->select("IF (
                 " . $db->qn('s.title') . " IS NOT NULL, 
                 " . $db->qn('s.title') . ",
                 '" . Text::_('COM_QUIZTOOLS_ORDERS_TEXT_DELETED_SUBSCRIPTION') . "'
              ) as 'subscription_title'")

        ->select("IF (
                     " . $db->qn('u.id') . " IS NOT NULL, 
                     CONCAT(" . $db->qn('u.name') . ", ' [', " . $db->qn('u.email') . ", ']'),
                     '" . Text::_('COM_QUIZTOOLS_ORDERS_TEXT_DELETED_USER') . "'
                  ) as 'user_name'")

		->from($db->qn('#__quiztools_orders', 'a'))
        ->join('LEFT', $db->qn('#__quiztools_subscriptions', 's'), $db->qn('s.id') . ' = ' . $db->qn('a.subscription_id'))
        ->join('LEFT', $db->qn('#__users', 'u'), $db->qn('u.id') . ' = ' . $db->qn('a.user_id'))
		->join('LEFT', $db->qn('#__users', 'uc'), $db->qn('uc.id') . ' = ' . $db->qn('a.checked_out'))
		;

		// Filter by Order status
		$orderStatus = (string) $this->getState('filter.status');
		if (!empty($orderStatus)) {
			$query->where($db->qn('a.status') . ' = :orderStatus')
				->bind(':orderStatus', $orderStatus, ParameterType::STRING);
		}

        // Filter by store_type (payment method)
        $store_type = (string) $this->getState('filter.store_type');
        if (!empty($store_type)) {
            $query->where($db->qn('a.store_type') . ' = :storeType')
                ->bind(':storeType', $store_type, ParameterType::STRING);
        }

        // Filter by user_id
        $user_id = $this->getState('filter.user_id');
        if (is_numeric($user_id)) {
            $query->where($db->qn('a.user_id') . ' = :userId')
                ->bind(':userId', $user_id, ParameterType::INTEGER);
        }

        // Filter by subscription_id
        $subscription_id = $this->getState('filter.subscription_id');
        if (is_numeric($subscription_id)) {
            $query->where($db->qn('a.subscription_id') . ' = :subscriptionId')
                ->bind(':subscriptionId', $subscription_id, ParameterType::INTEGER);
        }

		// Filter by search in subscription title
		if ($search = $this->getState('filter.search')) {
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

		// Add the list ordering clause.
		$order_col  = $this->state->get('list.ordering', 'a.id');
		$order_dirn = $this->state->get('list.direction', 'DESC');

		$query->order($db->escape($order_col) . ' ' . $db->escape($order_dirn));

		return $query;
	}

    /**
     * Method to allow derived classes to preprocess the form.
     *
     * @param   Form    $form   A Form object.
     * @param   mixed   $data   The data expected for the form.
     * @param   string  $group  The name of the plugin group to import (defaults to "content").
     *
     * @return  void
     *
     * @see     FormField
     * @since   4.0.0
     * @throws  \Exception if there is an error in the form event.
     */
    protected function preprocessForm(Form $form, $data, $group = 'quiztoolspayment')
    {
        if ($this instanceof DispatcherAwareInterface) {
            $dispatcher = $this->getDispatcher();
        } else {
            $dispatcher = Factory::getContainer()->get(DispatcherInterface::class);
        }

        // Import the appropriate plugin group.
        PluginHelper::importPlugin($group, null, true, $dispatcher);

        // Trigger the form preparation event.
        $dispatcher->dispatch(
            'onContentPrepareForm',
            new Model\PrepareFormEvent('onContentPrepareForm', ['subject' => $form, 'data' => $data])
        );
    }


}
