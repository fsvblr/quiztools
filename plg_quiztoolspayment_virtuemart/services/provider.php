<?php

/**
 * @package     QuizToolsPayment.Plugin
 * @subpackage  QuizToolsPayment.virtuemart
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\DI\Container;
use Joomla\CMS\Factory;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Qt\Plugin\Quiztoolspayment\Virtuemart\Extension\Virtuemart;

return new class implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   4.2.0
     */
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new Virtuemart(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('quiztoolspayment', 'virtuemart')
                );
                $plugin->setApplication(Factory::getApplication());
                $plugin->setDatabase($container->get(DatabaseInterface::class));
                $plugin->setUserFactory($container->get(UserFactoryInterface::class));

                return $plugin;
            }
        );
    }
};
