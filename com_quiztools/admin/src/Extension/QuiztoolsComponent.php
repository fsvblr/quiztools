<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Extension;

use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Psr\Container\ContainerInterface;
use Qt\Component\Quiztools\Administrator\Plugin\QuiztoolsPlugin;
use Qt\Component\Quiztools\Administrator\Service\AccessService;
use Qt\Component\Quiztools\Administrator\Service\HTML\FieldsService;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Component class for com_quiztools
 *
 * @since  4.0.0
 */
class QuiztoolsComponent extends MVCComponent implements
    BootableExtensionInterface,
    CategoryServiceInterface,
    RouterServiceInterface
{
    use HTMLRegistryAwareTrait;
    use RouterServiceTrait;
    use CategoryServiceTrait;
    use DatabaseAwareTrait;

    public static $categories;

	public const CONDITION_TYPE_ACCESS_FREE = 0;
	public const CONDITION_TYPE_ACCESS_PAID = 1;

    /**
     * Booting the extension. This is the function to set up the environment of the extension like
     * registering new class loaders, etc.
     *
     * If required, some initial set up can be done from services of the container, eg.
     * registering HTML services.
     *
     * @param   ContainerInterface  $container  The container
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function boot(ContainerInterface $container)
    {
        self::$categories = $this->categoryFactory->createCategory();

	    $this->getRegistry()->register('quiztoolsfields', new FieldsService());
	    $this->getRegistry()->register('quiztoolsaccess', new AccessService());

		// Registering an observer - event handler inside a component:
		$dispatcher = Factory::getApplication()->getDispatcher();
		$quiztoolsPlugin = new QuiztoolsPlugin($dispatcher, []);
	    $quiztoolsPlugin->registerListeners();
    }

    /**
     * Returns the table for the count items functions for the given section.
     *
     * @param   ?string  $section  The section
     *
     * @return  string|null
     *
     * @since   4.0.0
     */
    protected function getTableNameForSection(string $section = null)
    {
        $sections = [
            'quiz' => 'quiztools_quizzes',
            'question' => 'quiztools_questions',
        ];

        $table_name = $sections[$section] ?? 'quiztools_quizzes';

        return $table_name;
    }

    /**
     * Returns the state column for the count items functions for the given section.
     *
     * @param   string  $section  The section
     *
     * @return  string|null
     *
     * @since   4.0.0
     */
    protected function getStateColumnForSection(string $section = null)
    {
        return 'state';
    }

    /**
     * Method to load the countItems method from the extensions
     *
     * @param   \stdClass[]  &$items      The category items
     * @param   string        $extension  The category extension
     *
     * @return  void
     *
     * @since   3.5
     */
    public function countItems(array $items, string $section)
    {
        $config = (object) [
            'related_tbl'   => $this->getTableNameForSection($section),
            'state_col'     => $this->getStateColumnForSection($section),
            'group_col'     => 'catid',
            'relation_type' => 'category_or_group',
            'counter_names' => [
                '0'  => 'count_unpublished',
                '1'  => 'count_published',
            ],
        ];

        ContentHelper::countRelations($items, $config);
    }
}
