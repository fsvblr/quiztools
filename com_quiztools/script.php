<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Filesystem\Exception\FilesystemException;

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
					// Because the component is part of a package:
					if ($type == 'discover_install') {
						return false;
					}

					return true;
				}

				public function postflight(string $type, InstallerAdapter $adapter): bool
				{
                    $this->assetsCreating($type);
                    $this->assetsDeleting($type);

                    return true;
				}

                private function assetsCreating($type)
                {
                    // Creating a folder for certificates and initial certificate templates.
                    $sourceDir = JPATH_ROOT . '/media/com_quiztools/images/certificates';
                    $destinationDir = JPATH_ROOT . '/images/quiztools/certificates';

                    if (!file_exists($destinationDir)) {
                        mkdir($destinationDir, 0755, true);
                    }

                    if ($type == 'install') {
                        $imageFiles = glob($sourceDir . '/*.{jpg,jpeg,png}', GLOB_BRACE);

                        foreach ($imageFiles as $file) {
                            $destinationFile = $destinationDir . '/' . basename($file);
                            if (!file_exists($destinationFile)) {
                                copy($file, $destinationFile);
                            }
                        }
                    }
                }

                private function assetsDeleting($type)
                {
                    if ($type == 'uninstall') {
                        $this->deleteDir(JPATH_ROOT . '/images/quiztools');
                    }
                }

                private function deleteDir($dir) {
                    if (!is_dir($dir)) {
                        return false;
                    }

                    foreach (scandir($dir) as $item) {
                        if ($item == '.' || $item == '..') {
                            continue;
                        }

                        $path = $dir . DIRECTORY_SEPARATOR . $item;

                        if (is_dir($path)) {
                            $this->deleteDir($path);
                        } else {
                            try {
                                unlink($path);
                            } catch (FilesystemException $e) {
                                echo Text::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $file) . '<br>';
                            }
                        }
                    }

                    return rmdir($dir);
                }
            }
		);
	}
};
