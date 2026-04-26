<?php

/**
 * @package     System.Plugin
 * @subpackage  System.quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Plugin\System\Quiztools\Extension;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\Application\AfterInitialiseEvent;
use Joomla\CMS\Event\Application\AfterRouteEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Event\Priority;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Service plugin of the QuizTools component.
 *
 * @since  3.9.0
 */
final class Quiztools extends CMSPlugin implements SubscriberInterface, DispatcherAwareInterface
{
    use DatabaseAwareTrait;
    use DispatcherAwareTrait;
    use UserFactoryAwareTrait;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return array
     * @since   5.3.0
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

        return [
            'onAfterInitialise' => ['onAfterInitialise', Priority::HIGH],
            'onAfterRoute' => ['onAfterRoute', Priority::HIGH],
        ];
    }

    /**
     * After initialise listener.
     *
     * @param   AfterInitialiseEvent  $event  The event instance.
     * @return  void
     * @since   1.5
     */
    public function onAfterInitialise(AfterInitialiseEvent $event): void
    {
        if (!($this->getApplication() instanceof CMSApplication)) {
            return;
        }

        if ($this->getApplication()->isClient('administrator')) {
            $this->importPaymentPlugin();
        }
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
        if (!($this->getApplication() instanceof CMSApplication)) {
            return;
        }

        if ($this->getApplication()->isClient('site')) {
            $this->importPaymentPlugin();
        }
    }

    /**
     * Import 'quiztoolspayment' plugin
     *
     * @return void
     * @since   1.2.0
     */
    private function importPaymentPlugin(): void
    {
        $payment = $this->getQuizToolsPaymentComponent();

        if (!empty($payment)) {
            PluginHelper::importPlugin('quiztoolspayment', (string) $payment, true, $this->getApplication()->getDispatcher());
        }
    }

    /**
     * Determining whether the current component is an e-store
     * used to sell subscriptions in the QuizTool component.
     *
     * @return false|mixed
     * @since   1.2.0
     */
    private function getQuizToolsPaymentComponent()
    {
        $option = $this->getApplication()->getInput()->get('option', '');
        $dir = JPATH_ROOT . '/plugins/quiztoolspayment';

        foreach (scandir($dir) as $pluginName) {
            if ($pluginName === '.' || $pluginName === '..') {
                continue;
            }

            if (is_dir($dir . '/' . $pluginName)) {
                if ('com_' . $pluginName === (string) $option) {
                    return $pluginName;
                }
            }
        }

        return false;
    }
}
