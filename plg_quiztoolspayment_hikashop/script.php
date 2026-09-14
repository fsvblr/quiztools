<?php

/**
 * @package     QuizToolsPayment.Plugin
 * @subpackage  QuizToolsPayment.hikashop
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 * @license     GNU General Public License version 2 or later
 */

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

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
                private $storeName = 'hikashop';
                protected $minimumStoreVersion = '6.5.2';

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
                    $storeManifest = JPATH_ROOT . '/administrator/components/com_hikashop/hikashop.xml';

                    if (file_exists($storeManifest)) {
                        // Check for the minimum e-store version before continuing
                        $xml = simplexml_load_file($storeManifest);
                        $storeVersion = (string) $xml->version;

                        if (!empty($this->minimumStoreVersion) && version_compare($storeVersion, $this->minimumStoreVersion, '<')) {
                            Log::add(Text::sprintf('PLG_QUIZTOOLSPAYMENT_HIKASHOP_INSTALLER_MINIMUM_STORE_VERSION', $this->minimumStoreVersion), Log::WARNING, 'jerror');
                            //return false;  // Let them try anyway :)
                        }
                    } else {
                        Log::add(Text::_('PLG_QUIZTOOLSPAYMENT_HIKASHOP_INSTALLER_NO_STORE'), Log::WARNING, 'jerror');
                        //return false;  // Install the plugin anyway. It's not activated during the initial installation.
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

                    $query->update($db->qn('#__extensions'))
                        // Although the plugin is installed, Hikashop may not be installed.
                        // Payment plugins must be activated by the admin, and only the required ones.
                        ->set($db->qn('enabled') . ' = ' . $db->q('0'))
                        ->where($db->qn('type') . ' = ' . $db->q('plugin'))
                        ->where($db->qn('folder') . ' = ' . $db->q('quiztoolspayment'))
                        ->where($db->qn('element') . ' = ' . $db->q('hikashop'));

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
