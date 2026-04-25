<?php

/**
 * @package     QuizToolsPayment.Plugin
 * @subpackage  QuizToolsPayment.virtuemart
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Plugin\Quiztoolspayment\Virtuemart\PluginTraits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Event\Application\AfterRouteEvent;
use Joomla\CMS\Event\Application\BeforeRenderEvent;
use Joomla\CMS\Event\Plugin\AjaxEvent;
use Joomla\Database\ParameterType;
use Qt\Component\Quiztools\Administrator\Model\OrderModel;

/**
 * Processing order in the admin panel.
 *
 * VirtueMart doesn't have events or triggers at the points we need.
 * It also doesn't use class Table to create and delete records.
 * Therefore, the code below is somewhat confusing...
 *
 * @since   3.9.0
 */
trait AdminOrder
{
    /**
     * Creating an order
     *
     * @param $cart
     * @param $order
     * @return void
     */
    public function adminOrderCreate($cart, $order, $orderStatus = 'P')
    {
        if (!($this->getApplication() instanceof CMSApplication)) {
            return;
        }

        if (!$this->getApplication()->isClient('administrator') && !$this->getApplication()->isClient('site')) {
            return;
        }

        if (empty($order['items'])) {
            return;
        }

        // Products in the order (e-store):
        $products_ids = [];
        $products_qty = [];
        foreach ($order['items'] as $item) {
            $products_ids[] = (int) $item->virtuemart_product_id;

            if (empty($products_qty[(int) $item->virtuemart_product_id])) {
                $products_qty[(int) $item->virtuemart_product_id] = (int) $item->product_quantity;
            } else {
                $products_qty[(int) $item->virtuemart_product_id] += (int) $item->product_quantity;
            }
        }
        $products_ids = array_values(array_unique($products_ids));  // This is unnecessary, the products in the VirtueMart cart are unique.

        // Checking if there is a subscription(s) in '#__quiztools_subscriptions' with the product from this order
        // 1 VM-product <=> 1 QT-subscription
        $db = $this->getDatabase();
        $query = $db->createQuery();
        $query->select($db->qn(['id', 'product_id', 'attempts']))
            ->from($db->qn('#__quiztools_subscriptions'))
            ->where($db->qn('state') . ' = 1')
            ->where($db->qn('payment_method') . ' = :paymentMethod')
            ->where($db->qn('product_id') . " IN ('" . implode("','", $products_ids) . "')")
            ->bind(':paymentMethod', $this->storeName, ParameterType::STRING);

        try {
            $subscriptions = $db->setQuery($query)->loadObjectList();
        } catch (\RuntimeException $e) {
            $this->getApplication()->enqueueMessage($e->getMessage(), 'error');
        }

        if (empty($subscriptions)) {
            return;
        }

        $order_id = !empty($order['details']['BT']->virtuemart_order_id) ? (int) $order['details']['BT']->virtuemart_order_id : 0;
        $order_user_id = !empty($order['details']['BT']->virtuemart_user_id) ? (int) $order['details']['BT']->virtuemart_user_id : 0;

        if (empty($order_id) || empty($order_user_id)) {
            return;
        }

        /** @var OrderModel $QTorderModel */
        $QTorderModel = $this->getApplication()->bootComponent('com_quiztools')->getMVCFactory()
            ->createModel('Order', 'Administrator', ['ignore_request' => true]);

        // If a single VM-order contains two products, two "orders" are created in QuizTools.
        // It's easier to manage orders in QuizTools when changing or deleting an order line in VirtueMart.
        foreach ($subscriptions as $subscription) {
            $qtyProductInSubscription = !empty($products_qty[$subscription->product_id]) ? (int) $products_qty[$subscription->product_id] : 1;

            $QTorder = [
                'id' => '',
                'status' => $orderStatus,
                'user_id' => $order_user_id,
                'subscription_id' => (int) $subscription->id,
                'users_used' => 1,
                'attempts_max' => (int) $subscription->attempts * $qtyProductInSubscription,
                'store_type' => $this->storeName,
                'store_order_id' => $order_id,
                'store_product_id' => (int) $subscription->product_id,
                'created_by' => $order_user_id,
                'modified_by' => $order_user_id,
            ];

            $QTorderModel->save($QTorder);
        }
    }

    /**
     * VM trigger 'plgVmCouponUpdateOrderStatus':
     * - Changing the order status
     * - Changing the product quantity in an order line in the admin panel
     * - Adding a product line to an order line in the admin panel
     * - Removing a product line to an order line in the admin panel
     *
     * Deleting an entire VM-order doesn't support any events or triggers.
     * Therefore, it's implemented using a separate method via ajax.
     *
     * @return void
     */
    public function adminOrderUpdate($order, $old_order_status)
    {
        if (!($this->getApplication() instanceof CMSApplication)) {
            return;
        }

        if (!$this->getApplication()->isClient('administrator') && !$this->getApplication()->isClient('site')) {
            return;
        }

        if (empty($order->virtuemart_order_id)) {
            return;
        }

        // Retrieving an updated order from the e-store:
        $db = $this->getDatabase();
        $query = $db->createQuery();
        $query->select($db->qn(['virtuemart_order_id', 'virtuemart_user_id', 'order_status']))
            ->from($db->qn('#__virtuemart_orders'))
            ->where($db->qn('virtuemart_order_id') . ' = :vmOrderId')
            ->bind(':vmOrderId', $order->virtuemart_order_id, ParameterType::INTEGER);
        try {
            $storeOrder = $db->setQuery($query)->loadObject();
        } catch (\RuntimeException $e) {
            $this->getApplication()->enqueueMessage($e->getMessage(), 'error');
        }

        // Retrieving related orders from QuizTools
        $query->clear();
        $query->select($db->qn(['o.id', 'o.status', 'o.user_id', 'o.attempts_max', 'o.store_type', 'o.store_order_id', 'o.store_product_id']))
            ->select($db->qn('s.attempts', 'subscription_attempts'))
            ->from($db->qn('#__quiztools_orders', 'o'))
            ->join('INNER', $db->qn('#__quiztools_subscriptions', 's'),
                $db->qn('s.id') . ' = ' . $db->qn('o.subscription_id'))
            ->where($db->qn('o.store_type') . '=' . $db->q($this->storeName))
            ->where($db->qn('o.store_order_id') . ' = :storeOrderId')
            ->bind(':storeOrderId', $order->virtuemart_order_id, ParameterType::INTEGER)
        ;
        try {
            $QTorders = $db->setQuery($query)->loadObjectList();
        } catch (\RuntimeException $e) {
            $this->getApplication()->enqueueMessage($e->getMessage(), 'error');
        }

        // The modified order in the e-store has no associated orders in QuizTools. Ignore it.
        if (empty($QTorders)) {
            return;
        }

        // Get all products from the e-store order:
        $query->clear();
        $query->select($db->qn(['virtuemart_product_id', 'product_quantity']))
            ->from($db->qn('#__virtuemart_order_items'))
            ->where($db->qn('virtuemart_order_id') . ' = :vmOrderId')
            ->bind(':vmOrderId', $order->virtuemart_order_id, ParameterType::INTEGER);
        try {
            $storeOrderItems = $db->setQuery($query)->loadObjectList('virtuemart_product_id');
        } catch (\RuntimeException $e) {
            $this->getApplication()->enqueueMessage($e->getMessage(), 'error');
        }

        // If there are no items in the VM-order, delete the associated orders in QuizTools:
        if (empty($storeOrderItems)) {
            $where = [
                $db->qn('store_order_id') . '=' . (int) $order->virtuemart_order_id,
                $db->qn('store_type') . '=' . $db->q($this->storeName),
            ];
            $this->adminOrderDelete($where);
            return;
        }

        // If the status of a VM-order changes, update all related orders in QuizTools.
        // This is the most common situation handled by this method.
        if ((string) $old_order_status !== (string) $order->order_status) {
            $access_statuses = $this->params->get('access_statuses', []);

            // An order in QuizTools has only 2 statuses: P (Pending) / C (Confirmed):
            if (in_array((string) $order->order_status, $access_statuses)) {
                $orderStatus = 'C';
            } else {
                $orderStatus = 'P';
            }

            foreach ($QTorders as $QTorder) {
                $updStatus = new \stdClass();
                $updStatus->id = (int) $QTorder->id;
                $updStatus->status = $orderStatus;
                $db->updateObject('#__quiztools_orders', $updStatus, 'id');
            }
        }

        $delQtOrdersIdx = [];

        for ($i = 0; $i < count($QTorders); $i++) {
            // Checking for a change in quantity in a VM-order line (item):
            $productQty = 1;
            if (!empty($storeOrderItems[$QTorders[$i]->store_product_id])) {
                $productQty = (int) $storeOrderItems[$QTorders[$i]->store_product_id]->product_quantity;
            }
            $attemptsMax = (int) $QTorders[$i]->subscription_attempts * $productQty;
            if ((int) $QTorders[$i]->attempts_max !== $attemptsMax) {
                $updAttemptsMax = new \stdClass();
                $updAttemptsMax->id = (int) $QTorders[$i]->id;
                $updAttemptsMax->attempts_max = $attemptsMax;
                $db->updateObject('#__quiztools_orders', $updAttemptsMax, 'id');
            }

            // Calculation of discrepancies VM <-> QuizTools
            if (!empty($storeOrderItems[$QTorders[$i]->store_product_id])) {
                unset($storeOrderItems[$QTorders[$i]->store_product_id]);
                $delQtOrdersIdx[] = $i;
            }
        }

        if (!empty($delQtOrdersIdx)) {
            foreach ($delQtOrdersIdx as $index) {
                unset($QTorders[$index]);
            }
        }

        if (!empty($QTorders)) {
            $QTorders = array_values($QTorders);
        }
        // Calculation of discrepancies VM <-> QuizTools : END

        // In VM, the item was removed from the order, but it remains in QuizTools. We'll delete it as well.
        if (!empty($QTorders)) {
            $delIds = [];
            foreach ($QTorders as $QTorder) {
                $delIds[] = (int) $QTorder->id;
                $where = [
                    $db->qn('id') . " IN ('" . implode("','", $delIds) . "')",
                ];
                $this->adminOrderDelete($where);
            }
        }

        // An item was added to an order in VM. Let's create a related order in QuizTools.
        if (!empty($storeOrderItems)) {
            $newOrder = [
                'items' => [],
                'details' => [
                    'BT' => new \stdClass(),
                ]
            ];

            $newOrder['details']['BT']->virtuemart_order_id = (int) $order->virtuemart_order_id;
            $newOrder['details']['BT']->virtuemart_user_id = (int) $storeOrder->virtuemart_user_id;

            foreach ($storeOrderItems as $itemId => $item) {
                $newOrder['items'][] = $item;
            }

            $orderStatus = (string) $order->order_status;
            $cart = new \stdClass();

            $this->adminOrderCreate($cart, $newOrder, $orderStatus);
        }
    }

    /**
     * Deleting an order in QuizTools.
     *
     * @param array $where
     * @return void
     */
    public function adminOrderDelete($where = [])
    {
        if (empty($where)) {
            return;
        }

        $db = $this->getDatabase();
        $query = $db->createQuery();
        $query->delete($db->qn('#__quiztools_orders'))
            ->where($where);
        $db->setQuery($query);
        try {
            $db->execute();
        } catch (\RuntimeException $e) {
            $this->getApplication()->enqueueMessage($e->getMessage(), 'error');
        }

        /** @var OrderModel $QTorderModel */
        $QTorderModel = $this->getApplication()->bootComponent('com_quiztools')->getMVCFactory()
            ->createModel('Order', 'Administrator', ['ignore_request' => true]);

        try {
            $QTorderModel->deleteRelatedData();
        } catch (\RuntimeException $e) {
            // Nothing. The model will display a message.
        }
    }

    /**
     * Deleting an entire VM-order doesn't support any events or triggers.
     * So let's do it via ajax.
     *
     * @param AfterRouteEvent $event
     * @return void
     */
    public function adminOrderTaskListening(AfterRouteEvent $event): void
    {
        $app = $this->getApplication();

        if (!($app instanceof CMSApplication)) {
            return;
        }

        if (!$app->isClient('administrator')) {
            return;
        }

        $input = $app->getInput();
        $option = $input->get('option');
        $task = $input->get('task');

        if ($option === 'com_virtuemart' && $task === 'remove') {
            $cid = (array) $input->post->get('cid', [], 'int');
            // Remove zero values resulting from input filter
            $cid = array_filter($cid);

            if (!empty($cid)) {
                $app->getSession()->set('quiztoolspayment.virtuemart.cid.remove', json_encode($cid));
            }
        }

        $event->setArgument('result', true);
    }

    /**
     * Deleting an entire VM-order doesn't support any events or triggers.
     *  So let's do it via ajax.
     *
     * @param BeforeRenderEvent $event
     * @return void
     */
    public function adminOrderAddAssets(BeforeRenderEvent $event): void
    {
        $app = $this->getApplication();

        if (!($app instanceof CMSApplication)) {
            return;
        }

        if (!$app->isClient('administrator')) {
            return;
        }

        try {
            $document = $app->getDocument();
        } catch (\Exception $e) {
            $document = null;
        }

        if (!($document instanceof HtmlDocument)) {
            return;
        }

        $input = $app->getInput();
        $session = $app->getSession();

        $option = $input->get('option');
        $view = $input->get('view');
        $virtuemart_order_id = $input->getInt('virtuemart_order_id');

        if ($option === 'com_virtuemart' && $view === 'orders' && !$virtuemart_order_id) {
            $cidRemove = $session->get('quiztoolspayment.virtuemart.cid.remove');

            if (!empty($cidRemove)) {
                /** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
                $wa = $document->getWebAssetManager();

                $wa->getRegistry()->addRegistryFile('media/plg_quiztoolspayment_virtuemart/joomla.asset.json');

                $document->addScriptOptions('quiztoolspayment.virtuemart.action', 'remove');
                $document->addScriptOptions('quiztoolspayment.virtuemart.cid', $cidRemove);

                if (!$wa->isAssetActive('script', 'plg_quiztoolspayment_virtuemart.vmAdminOrders')) {
                    $wa->useScript('plg_quiztoolspayment_virtuemart.vmAdminOrders');
                }
            }
        }

        $event->setArgument('result', true);
    }

    /**
     * Removing orders in QuizTools
     * that are associated with a removed VirtueMart order.
     *
     * @param AjaxEvent $event
     * @return void
     */
    public function adminOrderVirtueMartRemoveOrder(AjaxEvent $event): void
    {
        $app = $this->getApplication();
        $input = $app->getInput();
        $action = $input->get('action');

        if ($action !== 'remove') {
            return;
        }

        $cid = $input->getString('cid', '[]');
        $cid = json_decode($cid, true);

        if (empty($cid)) {
            return;
        }

        $db = $this->getDatabase();
        $where = [
            $db->qn('store_type') . '=' . $db->q($this->storeName),
            $db->qn('store_order_id') . " IN ('" . implode("','", $cid) . "')",
        ];

        try {
            $this->adminOrderDelete($where);

            $session = $app->getSession();
            $session->remove('quiztoolspayment.virtuemart.cid.remove');

        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }

        $event->setArgument('result', true);
    }
}
