<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Order model.
 *
 * @since  1.6
 */
class OrderModel extends AdminModel
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
    public $typeAlias = 'com_quiztools.order';

    /**
     * Method to get a single record.
     *
     * @param integer $pk The id of the primary key.
     *
     * @return  mixed  Object on success, false on failure.
     * @throws \Exception
     */
    public function getItem($pk = null)
    {
        if ($item = parent::getItem($pk)) {
            $item->subscription = $this->getSubscriptionDataForOrder((int) $item->subscription_id);

            if (!empty($item->subscription->type)) {
                $item->type = $item->subscription->type;
                $item->quiz_id = !empty($item->subscription->quiz_id) ? (int) $item->subscription->quiz_id : 0;
                $item->lpath_id = !empty($item->subscription->lpath_id) ? (int) $item->subscription->lpath_id : 0;

                $orderUsedAttempts = HTMLHelper::getServiceRegistry()->getService('quiztoolsaccess')->getOrderUsedAttempts($item);
                $item->attempts_used = !empty($orderUsedAttempts['order']) ? (int) $orderUsedAttempts['order'] : 0;
                $item->attempts_used_byQuizzes = !empty($orderUsedAttempts['quizzes']) ? $orderUsedAttempts['quizzes'] : [];
            }

            $db = $this->getDatabase();
            $query = $db->createQuery();
            $query->select($db->qn(['name', 'email']))
                ->from($db->qn('#__users'))
                ->where($db->qn('id') . ' = ' . $db->q((int) $item->user_id));
            $db->setQuery($query);

            try {
                $userData = $db->loadObject();
            } catch (\Exception $e) {
                Factory::getApplication()->enqueueMessage(
                    Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()),
                    'warning'
                );
            }

            $item->user_name = !empty($userData->name) ? $userData->name : '';
            $item->user_email = !empty($userData->email) ? $userData->email : '';

            /*
            // ToDo: For further development: sharing purchased subscription with subordinate users:
            // ToDo: LEFT JOIN => if (empty(user_name)) when outputting, use "user was deleted"
            $query->clear();
            $query->select($db->qn('ou.user_id'))
                ->select($db->qn('u.name', 'user_name'))
                ->select($db->qn('u.email', 'user_email'))
                ->from($db->qn('#__quiztools_order_users', 'ou'))
                ->join('LEFT', $db->qn('#__users', 'u'), $db->qn('u.id') . ' = ' . $db->qn('ou.user_id'))
                ->where($db->qn('ou.order_id') . ' = ' . $db->q((int) $pk))
                ->where($db->qn('ou.parent_user_id') . ' = ' . $db->q((int) $item->user_id));
            $db->setQuery($query);

            try {
                $users = $db->loadObjectList();
            } catch (\Exception $e) {
                Factory::getApplication()->enqueueMessage(
                    Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()),
                    'warning'
                );
            }

            $item->students = !empty($users) ? $users : [];
            */
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
        $form = $this->loadForm('com_quiztools.order', 'order', ['control' => 'jform', 'load_data' => $loadData]);

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
        $data = Factory::getApplication()->getUserState('com_quiztools.edit.order.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

	    // Plugin's folder 'quiztoolspayment': event 'onContentPrepareData'
        $this->preprocessData('com_quiztools.order', $data, 'quiztoolspayment');

        return $data;
    }

    /**
     * Method to save the form data.
     *
     * @param   array  $data  The form data.
     * @return  boolean  True on success, False on error.
     * @since   1.6
     */
    public function save($data): bool
    {
        $isNew = empty($data['id']);
        $subscription = $this->getSubscriptionDataForOrder((int) $data['subscription_id']);

        if (empty($data['users_used'])) {
            $data['users_used'] = 1;
        }

        if (empty($data['start_datetime']) || empty($data['end_datetime'])) {
            $start = date('Y-m-d H:i:s');
            $end = date('Y-m-d H:i:s');

            if ($subscription->access_type === 'days') {
                $start = Factory::getDate()->toSql();  // in UTC
                $modify = '+' . $subscription->access_days . ' days';

                if ((int) $subscription->access_days === 0) {  // "lifetime" access, unlimited days
                    $end = null;
                } else {
                    $end = (new \DateTime($start, new \DateTimeZone('UTC')))
                        ->modify($modify)
                        ->format('Y-m-d H:i:s');
                }
            } else if ($subscription->access_type === 'period') {
                $start = $subscription->access_from;
                $end = strtotime($subscription->access_to) > strtotime($subscription->access_from)
                    ? $subscription->access_to
                    : $subscription->access_from;
            }

            if (empty($data['start_datetime'])) {
                $data['start_datetime'] = $start;
            }

            if (empty($data['end_datetime'])) {
                $data['end_datetime'] = $end;
            }

            if (!is_null($data['end_datetime']) && strtotime($data['start_datetime']) > strtotime($data['end_datetime'])) {
                $data['start_datetime'] = $data['end_datetime'];
            }
        }

        if (empty($data['attempts_max'])) {
            $data['attempts_max'] = (int) $subscription->attempts;
        }

        if (parent::save($data)) {
            if ($isNew) {
                // Mapping users to an order:
                $db = $this->getDatabase();
                $order_id = $this->getState($this->getName() . '.id');

                $orderUser = new \stdClass();
                $orderUser->id = '';
                $orderUser->order_id = (int) $order_id;
                $orderUser->parent_user_id = (int) $data['user_id'];
                $orderUser->user_id = (int) $data['user_id'];
                $db->insertObject('#__quiztools_order_users', $orderUser);
            }
        } else {
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
        if (!parent::delete($pks)) {
            return false;
        }

        if (!$this->deleteRelatedData()) {
            return false;
        }

        return true;
    }

    /**
     * Removing users matched to deleted orders.
     *
     * @return bool
     * @throws \Exception
     * @since   1.2.0
     */
    public function deleteRelatedData()
    {
        $db = $this->getDatabase();
        $query = $db->createQuery();

        $query->select($db->qn('id'))
            ->from($db->qn('#__quiztools_orders'));
        $db->setQuery($query);
        $ordersIds = $db->loadColumn();

        $query->clear();
        $query->delete($db->qn('#__quiztools_order_users'));
        if (!empty($ordersIds)) {
            $query->where($db->qn('order_id') . " NOT IN ('" . implode("','", $ordersIds) . "')");
        }
        $db->setQuery($query);
        try {
            $db->execute();
        } catch (\RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return false;
        }

        // Deleting an order will delete all results associated with that order:
        $query->clear();
        $query->select($db->qn('id'))
            ->from($db->qn('#__quiztools_results_quizzes'))
            ->where($db->qn('order_id') . ' <> 0')
            ->where($db->qn('order_id') . " NOT IN ('" . implode("','", $ordersIds) . "')")
        ;
        $db->setQuery($query);
        $deleteResultsIds = $db->loadColumn();

        if (!empty($deleteResultsIds)) {
            /** @var ResultModel $resultModel */
            $resultModel = Factory::getApplication()->bootComponent('com_quiztools')->getMVCFactory()
                ->createModel('Result', 'Administrator', ['ignore_request' => true]);

            try {
                $resultModel->delete($deleteResultsIds);
            } catch (\RuntimeException $e) {
                // Nothing. The model will display a message.
            }
        }

        $query->clear();
        $query->delete($db->qn('#__quiztools_lpaths_users'))
            ->where($db->qn('type') . ' = ' . $db->q('a'))  // type 'q' removed in $resultModel->delete($deleteResultsIds)
            ->where($db->qn('order_id') . ' <> 0')
            ->where($db->qn('order_id') . " NOT IN ('" . implode("','", $ordersIds) . "')");
        $db->setQuery($query);
        try {
            $db->execute();
        } catch (\RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
        // End

        return true;
    }

    /**
     * Get Subscription Data for Order.
     *
     * @param int $subscription_id
     * @return mixed|null
     * @throws \Exception
     * @since   1.2.0
     */
    public function getSubscriptionDataForOrder(int $subscription_id = 0): mixed
    {
        $db = $this->getDatabase();
        $query = $db->createQuery();
        $query->select($db->qn(['title', 'type', 'quiz_id', 'lpath_id']))
            ->select($db->qn(['users_max', 'access_type', 'access_days', 'access_from', 'access_to', 'attempts']))
            ->from($db->qn('#__quiztools_subscriptions'))
            ->where($db->qn('id') . ' = ' . $db->q((int) $subscription_id));
        $db->setQuery($query);

        try {
            $subscription = $db->loadObject();
        } catch (\Exception $e) {
            $subscription = null;

            Factory::getApplication()->enqueueMessage(
                Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()),
                'warning'
            );
        }

        return $subscription;
    }
}
