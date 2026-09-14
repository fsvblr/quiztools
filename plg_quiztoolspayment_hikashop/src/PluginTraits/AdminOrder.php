<?php

/**
 * @package     QuizToolsPayment.Plugin
 * @subpackage  QuizToolsPayment.hikashop
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 * @license     GNU General Public License version 2 or later
 */

namespace Qt\Plugin\Quiztoolspayment\Hikashop\PluginTraits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Event\Application\BeforeRenderEvent;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;
use Joomla\Utilities\ArrayHelper;
use Qt\Component\Quiztools\Administrator\Model\OrderModel;

/**
 * Processing order in the admin panel.
 *
 * @since  1.2.0
 */
trait AdminOrder
{
    /**
     * Creating an order on the website.
     *
     * @param Event $event  The event we are handling
     * @param array $where
     * @since  1.5.0
     */
    public function adminOrderCreate(Event $event): void
    {
        $app = $this->getApplication();

        if (!($app instanceof CMSApplication)) {
            return;
        }

        if (!$app->isClient('site') && !$app->isClient('administrator')) {
            return;
        }

        $arguments = $event->getArguments();

        if (!empty($arguments[0]) && is_object($arguments[0])) {
            $order = $arguments[0];
            //$sendEmail = isset($arguments[1]) ? $arguments[1] : null;
        } else {
            return;
        }

        if (empty($order->cart->products)) {
            return;
        }

        // Products in the order (e-store):
        $products_ids = [];
        $products_qty = [];

        foreach ($order->cart->products as $product) {
            $products_ids[] = (int) $product->product_id;

            if (empty($products_qty[(int) $product->product_id])) {
                $products_qty[(int) $product->product_id] = (int) $product->order_product_quantity;
            } else {
                $products_qty[(int) $product->product_id] += (int) $product->order_product_quantity;
            }
        }

        $products_ids = array_values(array_unique($products_ids));  // This is unnecessary, the products in the cart are unique?

        // Checking if there is a subscription(s) in '#__quiztools_subscriptions' with the product from this order
        // 1 e-store product <=> 1 QT-subscription
        if (!empty($products_ids)) {
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
                //$app->enqueueMessage($e->getMessage(), 'error');
                $app->enqueueMessage('QuizTools Hikashop Payment: adminOrderCreate - Error 01', 'error');
                // ToDo: Logging?
            }
        }

        if (empty($subscriptions)) {
            return;
        }

        $order_id = !empty($order->order_id) ? (int) $order->order_id : 0;
        $order_user_id = !empty($order->order_user_id) ? (int) $order->order_user_id : 0;

        // Hikashop users and Joomla users are different entities with different IDs.
        if (!empty($order_user_id)) {
            $order_user_id = $this->getCmsUserId($order_user_id);
        }

        if (empty($order_id) || empty($order_user_id)) {
            return;
        }

        $orderStatus = !empty($order->order_status) ? $order->order_status : 'created';
        $access_statuses = $this->params->get('access_statuses', []);

        // An order in QuizTools has only 2 statuses: P (Pending) / C (Confirmed):
        if (in_array((string) $orderStatus, $access_statuses)) {
            $orderStatus = 'C';
        } else {
            $orderStatus = 'P';
        }

        /** @var OrderModel $QTorderModel */
        $QTorderModel = $app->bootComponent('com_quiztools')->getMVCFactory()
            ->createModel('Order', 'Administrator', ['ignore_request' => true]);

        // If a single e-store order contains two products, two "orders" are created in QuizTools.
        // It's easier to manage orders in QuizTools when changing or deleting an order line in the e-store.
        foreach ($subscriptions as $subscription) {
            $qtyProductInSubscription = !empty($products_qty[$subscription->product_id]) ? (int) $products_qty[$subscription->product_id] : 1;

            $QTorder = [
                'id' => '',
                'status' => htmlspecialchars($orderStatus, ENT_QUOTES, 'UTF-8'),
                'user_id' => (int) $order_user_id,
                'subscription_id' => (int) $subscription->id,
                'users_used' => 1,
                'attempts_max' => (int) $subscription->attempts * $qtyProductInSubscription,
                'store_type' => htmlspecialchars($this->storeName, ENT_QUOTES, 'UTF-8'),
                'store_order_id' => (int) $order_id,
                'store_product_id' => (int) $subscription->product_id,
                'created_by' => (int) $order_user_id,
                'modified_by' => (int) $order_user_id,
            ];

            $QTorderModel->save($QTorder);
        }
    }

    /**
     * Updating an order in the admin panel:
     * - Order list: change order status
     * - Editing an order:
     *   - "Main information" block: change the order status
     *   - "Customer" block: Select another user (!) => We don't handle this situation. See adminOrderAddAssets().
     *   - "Product list" block:
     *     - "Edit" an existing item (changing quantity)
     *     - +New OR "Select"
     *     - "Delete" an existing item
     *
     * Note: In Hikashop, different order update methods (see their variants above)
     * can fill $order with data of different structures.
     *
     * @param Event $event The event we are handling
     * @since  1.5.0
     */
    public function adminOrderUpdate(Event $event): void
    {
        $app = $this->getApplication();

        if (!($app instanceof CMSApplication)) {
            return;
        }

        if (!$app->isClient('site')  // Hikashop's payment plugins modify the order immediately after it is created.
            && !$app->isClient('administrator')) {
            return;
        }

        $arguments = $event->getArguments();

        if (!empty($arguments[0]) && is_object($arguments[0])) {
            $order = $arguments[0];
            //$sendEmail = isset($arguments[1]) ? $arguments[1] : null;
        } else {
            return;
        }

        if (empty($order->order_id)) {
            return;
        }

        $db = $this->getDatabase();
        $query = $db->createQuery();

        // Retrieving an updated order from the e-store:
        /*
        $query->select($db->qn(['order_id', 'order_user_id', 'order_status']))
            ->from($db->qn('#__hikashop_order'))
            ->where($db->qn('order_id') . ' = :orderId')
            ->bind(':orderId', $order->order_id, ParameterType::INTEGER);
        try {
            $storeOrder = $db->setQuery($query)->loadObject();
        } catch (\RuntimeException $e) {
            //$app->enqueueMessage($e->getMessage(), 'error');
            $app->enqueueMessage('QuizTools Hikashop Payment: adminOrderUpdate - Error 01', 'error');
            // ToDo: Logging?
        }
        */

        // Retrieving related orders from QuizTools
        $query->clear();
        $query->select($db->qn(['o.id', 'o.status', 'o.user_id', 'o.attempts_max', 'o.store_type', 'o.store_order_id', 'o.store_product_id']))
            ->select($db->qn('s.attempts', 'subscription_attempts'))
            ->from($db->qn('#__quiztools_orders', 'o'))
            ->join('INNER', $db->qn('#__quiztools_subscriptions', 's'),
                $db->qn('s.id') . ' = ' . $db->qn('o.subscription_id'))
            ->where($db->qn('o.store_type') . '=' . $db->q($this->storeName))
            ->where($db->qn('o.store_order_id') . ' = :storeOrderId')
            ->bind(':storeOrderId', $order->order_id, ParameterType::INTEGER)
        ;
        try {
            $QTorders = $db->setQuery($query)->loadObjectList();
        } catch (\RuntimeException $e) {
            //$app->enqueueMessage($e->getMessage(), 'error');
            $app->enqueueMessage('QuizTools Hikashop Payment: adminOrderUpdate - Error 02', 'error');
            // ToDo: Logging?
        }

        // The modified order in the e-store has no associated orders in QuizTools. Ignore it.
        if (empty($QTorders)) {
            return;
        }

        // Get all products from the e-store order:
        $query->clear();
        $query->select($db->qn(['product_id', 'order_product_quantity']))
            ->from($db->qn('#__hikashop_order_product'))
            ->where($db->qn('order_id') . ' = :orderId')
            ->bind(':orderId', $order->order_id, ParameterType::INTEGER);
        try {
            $storeOrderItems = $db->setQuery($query)->loadObjectList('product_id');
        } catch (\RuntimeException $e) {
            //$app->enqueueMessage($e->getMessage(), 'error');
            $app->enqueueMessage('QuizTools Hikashop Payment: adminOrderUpdate - Error 03', 'error');
            // ToDo: Logging?
        }

        // If there are no items in the e-store order, delete the associated orders in QuizTools:
        if (empty($storeOrderItems)) {
            $where = [
                $db->qn('store_order_id') . '=' . (int) $order->order_id,
                $db->qn('store_type') . '=' . $db->q($this->storeName),
            ];

            $this->adminOrderDelete(null, $where);

            return;
        }

        // If the status of the e-store order changes, update all related orders in QuizTools.
        // This is the most common situation handled by this method.
        if ((string) $order->old->order_status !== (string) $order->order_status) {
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
                $updStatus->status = htmlspecialchars($orderStatus, ENT_QUOTES, 'UTF-8');
                $db->updateObject('#__quiztools_orders', $updStatus, 'id');
            }
        }

        $delQtOrdersIdx = [];

        for ($i = 0; $i < count($QTorders); $i++) {
            // Checking for a change in quantity in the e-store order line (item):
            $productQty = 1;
            if (!empty($storeOrderItems[$QTorders[$i]->store_product_id])) {
                $productQty = (int) $storeOrderItems[$QTorders[$i]->store_product_id]->order_product_quantity;
            }

            $attemptsMax = (int) $QTorders[$i]->subscription_attempts * $productQty;

            if ((int) $QTorders[$i]->attempts_max !== $attemptsMax) {
                $updAttemptsMax = new \stdClass();
                $updAttemptsMax->id = (int) $QTorders[$i]->id;
                $updAttemptsMax->attempts_max = (int) $attemptsMax;
                $db->updateObject('#__quiztools_orders', $updAttemptsMax, 'id');
            }

            // Calculation of discrepancies e-store <-> QuizTools
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
        // Calculation of discrepancies e-store <-> QuizTools : END

        // In the e-store, the item was removed from the order, but it remains in QuizTools. We'll delete it as well.
        if (!empty($QTorders)) {
            $delIds = [];
            foreach ($QTorders as $QTorder) {
                $delIds[] = (int) $QTorder->id;
                if (!empty($delIds)) {
                    $where = [
                        $db->qn('id') . " IN ('" . implode("','", $delIds) . "')",
                    ];
                    $this->adminOrderDelete(null, $where);
                }
            }
        }

        // The item(s) was added to an order in the e-store. Let's create a related order in QuizTools.
        if (!empty($storeOrderItems)) {
            $newOrder = new \stdClass();
            $newOrder->cart = new \stdClass();
            $newOrder->cart->products = [];

            foreach ($storeOrderItems as $storeOrderItem) {
                $newProduct = new \stdClass();
                $newProduct->product_id = (int) $storeOrderItem->product_id;
                $newProduct->order_product_quantity = (int) $storeOrderItem->order_product_quantity;
                $newOrder->cart->products[] = $newProduct;
            }

            $newOrder->order_id = (int) $order->order_id;
            $newOrder->order_user_id = !empty($order->order_user_id)
                ? (int) $order->order_user_id
                : (!empty($order->old->order_user_id) ? (int) $order->old->order_user_id : 0);

            $newOrder->order_status = htmlspecialchars($order->order_status, ENT_QUOTES, 'UTF-8');

            $send_email = 0;
            $args = [$newOrder, $send_email];
            $eventName = 'onAfterOrderCreate';

            $dispatcher = $this->getDispatcher();
            $event = new Event($eventName, $args);
            $dispatcher->dispatch($eventName, $event);
        }
    }

    /**
     * Deleting an order in Hikashop deletes an order in QuizTools.
     *
     * @param   Event|null $event  The event we are handling
     * @param array $where
     * @since  1.5.0
     */
    public function adminOrderDelete(Event|null $event, array $where = []): void
    {
        $app = $this->getApplication();

        if (!($app instanceof CMSApplication)) {
            return;
        }

        if (!$app->isClient('administrator')) {
            return;
        }

        if (!$app->getIdentity()->authorise('core.delete', 'com_quiztools')) {
            throw new \Exception(Text::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
        }

        $db = $this->getDatabase();

        if (!empty($event) && empty($where)) {
            $arguments = $event->getArguments();

            if (!empty($arguments[0]) && is_array($arguments[0])) {
                $ids = $arguments[0];  // array od IDs of orders deleted in Hikashop
            } else {
                return;
            }

            if (!empty($ids)) {
                $ids = ArrayHelper::toInteger((array) $ids);
                $ids = array_filter($ids);
            }

            if (!empty($ids)) {
                $where = [
                    $db->qn('store_order_id') . " iN ('" . implode("','", $ids) . "')",
                    $db->qn('store_type') . '=' . $db->q($this->storeName),
                ];
            }
        }

        if (empty($where)) {
            return;
        }

        $query = $db->createQuery();
        $query->delete($db->qn('#__quiztools_orders'))
            ->where($where);
        $db->setQuery($query);
        try {
            $db->execute();
        } catch (\RuntimeException $e) {
            //$app->enqueueMessage($e->getMessage(), 'error');
            $app->enqueueMessage('QuizTools Hikashop Payment: adminOrderDelete - Error 01', 'error');
            // ToDo: Logging?
        }

        /** @var OrderModel $QTorderModel */
        $QTorderModel = $app->bootComponent('com_quiztools')->getMVCFactory()
            ->createModel('Order', 'Administrator', ['ignore_request' => true]);

        try {
            $QTorderModel->deleteRelatedData();
        } catch (\RuntimeException $e) {
            // Nothing. The model will display a message.
        }
    }

    /**
     * Getting Joomla userId by HikaShop userId.
     * Note: Hikashop users and Joomla users are different entities with different IDs.
     *
     * @param int $hikaShopUserId
     * @since 1.5.0
     */
    private function getCmsUserId($hikaShopUserId = 0): int
    {
        $db = $this->getDatabase();
        $query = $db->createQuery();

        $query->select($db->qn('user_cms_id'))
            ->from($db->qn('#__hikashop_user'))
            ->where($db->qn('user_id') . ' = :userId')
            ->bind(':userId', $hikaShopUserId, ParameterType::INTEGER);

        try {
            $user_cms_id = $db->setQuery($query)->loadResult();
        } catch (\RuntimeException $e) {
            //$this->getApplication()->enqueueMessage($e->getMessage(), 'error');
            $this->getApplication()->enqueueMessage('QuizTools Hikashop Payment: CMS user not found', 'error');
            // ToDo: Logging?
        }

        return (!empty($user_cms_id) ? (int) $user_cms_id : 0);
    }

    /**
     * Loading assets on the required pages.
     *
     * @param BeforeRenderEvent $event
     * @since 1.5.0
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
        $option = $input->get('option');
        $ctrl = $input->get('ctrl');
        $task = $input->get('task');
        $cid = $input->get('cid', [], 'array');

        $hikashopOrderId = !empty($cid) ? (int) $cid[0] : 0;

        // (!) In Hikashop, you can change the user when editing an order.
        // We're not handling this situation.
        // Because Hakashop, in conjunction with QuizTools, only performs the technical function of selling subscriptions.
        // All user-related issues should be resolved through QuizTools.
        // But we will leave a message for the admin...
        if ($option === 'com_hikashop' && $ctrl === 'order' && $task === 'show' && !empty($hikashopOrderId)) {
            // Let's make sure that the current Hikashop order is linked to order in QuizTools
            $db = $this->getDatabase();
            $query = $db->createQuery();
            $query->select($db->qn('id'))
                ->from($db->qn('#__quiztools_orders'))
                ->where($db->qn('store_type') . '=' . $db->q($this->storeName))
                ->where($db->qn('store_order_id') . ' = :storeOrderId')
                ->bind(':storeOrderId', $hikashopOrderId, ParameterType::INTEGER)
            ;
            try {
                $isExistQTorder = $db->setQuery($query)->loadResult();
            } catch (\RuntimeException $e) {
                //$app->enqueueMessage($e->getMessage(), 'error');
                $app->enqueueMessage('QuizTools Hikashop Payment: adminOrderAddAssets - Error 01', 'error');
                // ToDo: Logging?
            }

            if (empty($isExistQTorder)) {
                return;
            }

            /** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
            $wa = $document->getWebAssetManager();

            $wa->getRegistry()->addRegistryFile('media/plg_quiztoolspayment_hikashop/joomla.asset.json');

            if (!$wa->isAssetActive('script', 'plg_quiztoolspayment_hikashop.hikashopAdminOrder')) {
                $wa->useScript('plg_quiztoolspayment_hikashop.hikashopAdminOrder');
            }

            Text::script('PLG_QUIZTOOLSPAYMENT_HIKASHOP_CHANGING_USER_IN_ORDER_WARNING');
        }

        $event->setArgument('result', true);
    }
}
