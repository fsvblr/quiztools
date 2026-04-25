<?php

/**
 * @package     QuizToolsPayment.Plugin
 * @subpackage  QuizToolsPayment.virtuemart
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

return new class () implements ServiceProviderInterface {
	public function register(Container $container)
	{
		$container->set(
			InstallerScriptInterface::class,
			new class (
				$container->get(AdministratorApplication::class),
				$container->get(DatabaseInterface::class)
			) implements InstallerScriptInterface {
				private AdministratorApplication $app;
				private DatabaseInterface $db;
                private $storeName = 'virtuemart';
                protected $minimumStoreVersion = '4.4.10';

				public function __construct(AdministratorApplication $app, DatabaseInterface $db)
				{
					$this->app = $app;
					$this->db  = $db;
				}

				public function install(InstallerAdapter $adapter): bool
				{
					//$this->app->enqueueMessage('Successful installed.');

					return true;
				}

				public function update(InstallerAdapter $adapter): bool
				{
					//$this->app->enqueueMessage('Successful updated.');

					return true;
				}

				public function uninstall(InstallerAdapter $adapter): bool
				{
					//$this->app->enqueueMessage('Successful uninstalled.');

					return true;
				}

                public function preflight(string $type, InstallerAdapter $adapter): bool
                {
                    $storeManifest = JPATH_ROOT . '/administrator/components/com_virtuemart/virtuemart.xml';

                    if (file_exists($storeManifest)) {
                        // Check for the minimum e-store version before continuing
                        $xml = simplexml_load_file($storeManifest);
                        $storeVersion = (string) $xml->version;

                        if (!empty($this->minimumStoreVersion) && version_compare($storeVersion, $this->minimumStoreVersion, '<')) {
                            Log::add(Text::sprintf('PLG_QUIZTOOLSPAYMENT_VIRTUEMART_INSTALLER_MINIMUM_STORE_VERSION', $this->minimumStoreVersion), Log::WARNING, 'jerror');

                            //return false;
                            return true;  // Let them try anyway :) .
                        }
                    } else {
                        Log::add(Text::_('PLG_QUIZTOOLSPAYMENT_VIRTUEMART_INSTALLER_NO_STORE'), Log::WARNING, 'jerror');

                        return false;
                    }

                    return true;
                }

				public function postflight(string $type, InstallerAdapter $adapter): bool
				{
					if ($type == 'install') {
                        // Setting up the initial plugin parameters
                        $this->installPreSettings();
					}

					return true;
				}

                private function installPreSettings()
                {
                    $db = $this->db;
                    $query = $db->createQuery();
                    $query->select($db->qn('params'))
                        ->from($db->qn('#__extensions'))
                        ->where($db->qn('type') . ' = ' . $db->q('plugin'))
                        ->where($db->qn('folder') . ' = ' . $db->q('quiztoolspayment'))
                        ->where($db->qn('element') . ' = ' . $db->q('virtuemart'));
                    $db->setQuery($query);

                    try {
                        $params = $db->loadResult();
                    } catch (\RuntimeException $e) {
                        $this->app->enqueueMessage($e->getMessage(), 'error');
                        $params = '{}';
                    }

                    $registry = new Registry($params);
                    $params = $registry->toArray();
                    $params['access_statuses'] = ["C"];  // Confirmed
                    $registry = new Registry($params);
                    $params = $registry->toString();

                    $query->clear();
                    $query->update($db->qn('#__extensions'))
                        ->set($db->qn('enabled') . ' = ' . $db->q('1'))
                        ->set($db->qn('params') . ' = ' . $db->q($params))
                        ->where($db->qn('type') . ' = ' . $db->q('plugin'))
                        ->where($db->qn('folder') . ' = ' . $db->q('quiztoolspayment'))
                        ->where($db->qn('element') . ' = ' . $db->q('virtuemart'));

					try {
                        $db->execute();
                    } catch (\RuntimeException $e) {
                        $this->app->enqueueMessage($e->getMessage(), 'error');
                    }
                }
			}
		);
	}
};
