<?php

/**
 * @package     QuizToolsPayment.Plugin
 * @subpackage  QuizToolsPayment.hikashop
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 * @license     GNU General Public License version 2 or later
 */

namespace Qt\Plugin\Quiztoolspayment\Hikashop\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Event\Application\BeforeRenderEvent;
use Joomla\CMS\Event\Model;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Qt\Plugin\Quiztoolspayment\Hikashop\PluginTraits\AdminOrder;
use Qt\Plugin\Quiztoolspayment\Hikashop\PluginTraits\AdminOrders;
use Qt\Plugin\Quiztoolspayment\Hikashop\PluginTraits\AdminSubscription;
use Qt\Plugin\Quiztoolspayment\Hikashop\PluginTraits\AdminSubscriptions;

final class Hikashop extends CMSPlugin implements SubscriberInterface, DispatcherAwareInterface
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
	public $storeName = 'hikashop';

    /**
     * Autoload the language files
     *
     * @var    boolean
     * @since  4.2.0
     */
    protected $autoloadLanguage = true;

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

        // no e-store component
        if (!file_exists(JPATH_ROOT . '/administrator/components/com_hikashop/hikashop.xml')) {
            return [];
        }

        return [
            'onBeforeRender' => 'onBeforeRender',
            'onAdminSubscriptionGetData' => 'onAdminSubscriptionGetData',
            'onAdminSubscriptionsGetData' => 'onAdminSubscriptionsGetData',
            'onContentPrepareForm' => 'onContentPrepareForm',
            // Hikashop Events:
            'onAfterOrderCreate' => 'onAfterOrderCreate',
            'onAfterOrderUpdate' => 'onAfterOrderUpdate',
            'onAfterOrderDelete' => 'onAfterOrderDelete',
        ];
    }

    /**
     * Handling the 'onAdminSubscriptionGetData' event.
     *
     * @param   Event  $event  The event we are handling
     * @return  void
     * @since  1.2.0
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
     * @since  1.2.0
     */
    public function onAdminSubscriptionsGetData(Event $event): void
    {
        $this->adminSubscriptionsGetData($event);
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
     * Creating an order.
     *
     * @param   Event  $event  The event we are handling
     * @return void
     * @since  1.5.0
     */
    public function onAfterOrderCreate(Event $event)
    {
        $this->adminOrderCreate($event);
    }

    /**
     * Changing the order.
     *
     * @param   Event  $event  The event we are handling
     * @return void
     * @since  1.5.0
     */
    public function onAfterOrderUpdate(Event $event)
    {
        $this->adminOrderUpdate($event);
    }

    /**
     * Deleting an order.
     *
     * @param   Event  $event  The event we are handling
     * @return void
     * @since  1.5.0
     */
    public function onAfterOrderDelete(Event $event)
    {
        $this->adminOrderDelete($event);
    }

    /**
     * onBeforeRender event
     *
     * @param   BeforeRenderEvent  $event
     *
     * @return void
     * @since  5.3.0
     */
    public function onBeforeRender(BeforeRenderEvent $event)
    {
        $this->adminOrderAddAssets($event);
    }
}
