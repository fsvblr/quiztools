<?php

/**
 * @package     QuizToolsPayment.Plugin
 * @subpackage  QuizToolsPayment.virtuemart
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Plugin\Quiztoolspayment\Virtuemart\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Event\Application\AfterRouteEvent;
use Joomla\CMS\Event\Application\BeforeRenderEvent;
use Joomla\CMS\Event\Model;
use Joomla\CMS\Event\Plugin\AjaxEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Qt\Plugin\Quiztoolspayment\Virtuemart\PluginTraits\AdminOrder;
use Qt\Plugin\Quiztoolspayment\Virtuemart\PluginTraits\AdminOrders;
use Qt\Plugin\Quiztoolspayment\Virtuemart\PluginTraits\AdminSubscription;
use Qt\Plugin\Quiztoolspayment\Virtuemart\PluginTraits\AdminSubscriptions;

final class Virtuemart extends CMSPlugin implements SubscriberInterface, DispatcherAwareInterface
{
    use DispatcherAwareTrait;
    use DatabaseAwareTrait;
    use UserFactoryAwareTrait;

    use AdminOrder;
    use AdminOrders;
    use AdminSubscription;
    use AdminSubscriptions;

	/**
	 * The name of the store.
	 *
	 * @var string
     * @since  1.2.0
	 */
	public $storeName = 'virtuemart';

    /**
     * Store's component manifest file.
     *
     * @var string|null
     * @since  1.2.0
     */
    public $storeManifest = null;

    /**
     * Autoload the language files
     *
     * @var    boolean
     * @since  4.2.0
     */
    protected $autoloadLanguage = true;

    /**
     * Constructor
     *
     * @param DispatcherInterface $dispatcher
     * @param array $config      An optional associative array of configuration settings.
     *                           Recognized key values include 'name', 'group', 'params', 'language'
     *                           (this list is not meant to be comprehensive).
     * @since   1.5
     */
    public function __construct(DispatcherInterface $dispatcher, array $config) {
        parent::__construct($config);
        $this->setDispatcher($dispatcher);
        $this->storeManifest = JPATH_ROOT . '/administrator/components/com_' . $this->storeName . '/' . $this->storeName . '.xml';

        if (file_exists($this->storeManifest)) {
            // 'registerLegacyListener' will be removed in Joomla 7.0

            // Creating an order:
            $this->registerLegacyListener('plgVmConfirmedOrder');
            // - Changing the order status
            // - Changing the product quantity in an order line in the admin panel
            // - Adding a product line to an order line in the admin panel
            // - Removing a product line to an order line in the admin panel
            $this->registerLegacyListener('plgVmCouponUpdateOrderStatus');
        }
    }

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   4.2.0
     */
    public static function getSubscribedEvents(): array
    {
        try {
            $app = Factory::getApplication();
        } catch (\Exception $e) {
            return [];
        }

        if (!$app->isClient('site') && !$app->isClient('administrator')) {
            return [];
        }

        // no store component
        if (!file_exists(JPATH_ROOT . '/administrator/components/com_virtuemart/virtuemart.xml')) {
            return [];
        }

        return [
            'onAdminSubscriptionGetData' => 'onAdminSubscriptionGetData',
            'onAdminSubscriptionsGetData' => 'onAdminSubscriptionsGetData',
            'onAfterRoute' => 'onAfterRoute',
            'onAjaxVirtuemart' => 'onAjaxVirtuemart',
            'onBeforeRender' => 'onBeforeRender',
	        'onContentPrepareForm' => 'onContentPrepareForm',
        ];
    }

    /**
     * Handling the 'onAdminSubscriptionGetData' event.
     *
     * @param   Event  $event  The event we are handling
     * @return  void
     * @since   3.9.0
     */
    public function onAdminSubscriptionGetData(Event $event): void
    {
        $this->adminSubscriptionGetData($event);
    }

    /**
     * Handling the 'onAdminSubscriptionsGetData' event.
     *
     * @param   Event  $event  The event we are handling
     * @return  void
     * @since   3.9.0
     */
    public function onAdminSubscriptionsGetData(Event $event): void
    {
        $this->adminSubscriptionsGetData($event);
    }

    /**
     * After route listener.
     *
     * @param   AfterRouteEvent $event  The event instance.
     *
     * @return  void
     * @since   5.3.0
     */
    public function onAfterRoute(AfterRouteEvent $event): void
    {
        $this->adminOrderTaskListening($event);
    }

    /**
     * This method is called from Joomla's com_ajax.
     *
     * @param   AjaxEvent  $event  The event we are handling
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   4.0.0
     */
    public function onAjaxVirtuemart(AjaxEvent $event): void
    {
        if (!Session::checkToken('get')) {
            return;
        }

        $this->adminOrderVirtueMartRemoveOrder($event);
    }

    /**
     * onBeforeRender event
     *
     * @param   BeforeRenderEvent  $event
     *
     * @return void
     */
    public function onBeforeRender(BeforeRenderEvent $event)
    {
        $this->adminOrderAddAssets($event);
    }

    /**
     * Handling the 'onContentPrepareForm' event.
     *
     * @param   Model\PrepareFormEvent $event  The event instance.
     * @return  void
     * @since   3.9.0
     * @throws  \Exception
     */
    public function onContentPrepareForm(Model\PrepareFormEvent $event): void
    {
        $this->adminOrdersPrepareForm($event);
        $this->adminSubscriptionPrepareForm($event);
        $this->adminSubscriptionsPrepareForm($event);
	}

    /**
     * Creating an order
     *
     * @param $cart
     * @param $order
     * @return void
     */
    public function plgVmConfirmedOrder($cart, $order)
    {
        $this->adminOrderCreate($cart, $order);
    }

    /**
     * Changing the order status
     *
     * @param $order
     * @param $old_order_status
     * @return void
     */
    public function plgVmCouponUpdateOrderStatus($order, $old_order_status)
    {
        $this->adminOrderUpdate($order, $old_order_status);
    }

    /**
     * Getting the version of a store component.
     *
     * @return string|null
     */
    private function getStoreVersion()
    {
        $storeVersion = null;

        if (file_exists($this->storeManifest)) {
            $xml = simplexml_load_file($this->storeManifest);

            if (!empty($xml->version)) {
                $storeVersion = (string) $xml->version;
            }
        }

        return $storeVersion;
    }
}
