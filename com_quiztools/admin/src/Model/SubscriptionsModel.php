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
 * Methods supporting a list of Subscriptions records.
 *
 * @since  1.6
 */
class SubscriptionsModel extends ListModel
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
                'payment_method', 'a.payment_method',
                'state', 'a.state',
                'ordering', 'a.ordering',
                'quiz_id', 'a.quiz_id',
                'lpath_id', 'a.lpath_id',
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

        $quiz_id = $this->getUserStateFromRequest($this->context . '.filter.quiz_id', 'filter_quiz_id');
        $this->setState('filter.quiz_id', $quiz_id);

        $lpath_id = $this->getUserStateFromRequest($this->context . '.filter.lpath_id', 'filter_lpath_id');
        $this->setState('filter.lpath_id', $lpath_id);

        $payment_method = $this->getUserStateFromRequest($this->context . '.filter.payment_method', 'filter_payment_method');
        $this->setState('filter.payment_method', $payment_method);

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
        $id .= ':' . $this->getState('filter.quiz_id');
        $id .= ':' . $this->getState('filter.lpath_id');
        $id .= ':' . $this->getState('filter.payment_method');

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
                    $db->qn('a.payment_method'),
					$db->qn('a.product_id'),
                    $db->qn('a.users_max'),
					$db->qn('a.state'),
					$db->qn('a.ordering'),
					$db->qn('a.checked_out'),
					$db->qn('a.checked_out_time'),
					$db->qn('uc.name', 'editor'),
				]
			)
		)
		->from($db->qn('#__quiztools_subscriptions', 'a'))
		->join('LEFT', $db->qn('#__users', 'uc'), $db->qn('uc.id') . ' = ' . $db->qn('a.checked_out'))
		;

		// Filter by state
		$state = (string) $this->getState('filter.state');
		if (is_numeric($state)) {
			$state = (int) $state;
			$query->where($db->qn('a.state') . ' = :state')
				->bind(':state', $state, ParameterType::INTEGER);
		}

        // Filter by quiz / lpath (in subscription_items)
        $quiz_id = $this->getState('filter.quiz_id');
        if (is_numeric($quiz_id)) {
            $query->where($db->qn('a.quiz_id') . ' = :quizId')
                ->bind(':quizId', $quiz_id, ParameterType::INTEGER);
        }
        $lpath_id = $this->getState('filter.lpath_id');
        if (is_numeric($lpath_id)) {
            $query->where($db->qn('a.lpath_id') . ' = :lpathId')
                ->bind(':lpathId', $lpath_id, ParameterType::INTEGER);
        }

		// Filter by search in title
		if ($search = $this->getState('filter.search')) {
			if (stripos($search, 'id:') === 0) {
				$search = (int) substr($search, 3);
				$query->where($db->qn('a.id') . ' = :search')
					->bind(':search', $search, ParameterType::INTEGER);
			} else {
				$search = '%' . str_replace(' ', '%', trim($search)) . '%';
				$query->where('(' . $db->qn('a.title') . ' LIKE :search1)')
					->bind([':search1'], $search);
			}
		}

        // Filter by Payment method
        if ($payment_method = $this->getState('filter.payment_method')) {
            $query->where($db->qn('a.payment_method') . '=' . $db->q($payment_method));
        }

		// Add the list ordering clause.
		$order_col  = $this->state->get('list.ordering', 'a.id');
		$order_dirn = $this->state->get('list.direction', 'DESC');

		$query->order($db->escape($order_col) . ' ' . $db->escape($order_dirn));

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
            $dispatcher = $this->getDispatcher();
            PluginHelper::importPlugin('quiztoolspayment', null, true, $dispatcher);

            // Getting data from payment plugins:
            $items = $dispatcher->dispatch(
                'onAdminSubscriptionsGetData',
                new Model\PrepareDataEvent('onAdminSubscriptionsGetData', [
                    'context' => 'com_quiztools.admin.subscriptions.data',
                    'data'    => $items,
                    'subject' => new \stdClass(),
                ])
            )->getArgument('result', $items);
        }

        return $items;
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

	/**
	 * Getting a list of Subscriptions to build a 'select' in forms.
	 *
	 * @return array|mixed
	 * @throws \Exception
	 */
	public function getSubscriptionsList()
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select($db->qn('id', 'value'))
			->select($db->qn('title', 'text'))
			->from($db->qn('#__quiztools_subscriptions'))
            ->order($db->qn('title') . ' ASC');

        // Filter by state
        $state = (string) $this->getState('filter.state');
        if (is_numeric($state)) {
            $state = (int) $state;
            $query->where($db->qn('state') . ' = :state')
                ->bind(':state', $state, ParameterType::INTEGER);
        }

        // Filter by Payment method
        if ($payment_method = $this->getState('filter.payment_method')) {
            $query->where($db->qn('payment_method') . '=' . $db->q($payment_method));
        }

		$db->setQuery($query);

		try {
			$subscriptions = $db->loadObjectList();
		} catch (\Exception $e) {
			Factory::getApplication()->enqueueMessage(
				Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()),
				'warning'
			);

			return [];
		}

		return $subscriptions;
	}
}
