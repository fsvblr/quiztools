<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Plugin\PluginHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Select a Question Type model.
 *
 * @since  1.6
 */
class SelectquestiontypeModel extends ListModel
{
    /**
     * Method to get a list of items.
     *
     * @return  mixed  An array of objects on success, false on failure.
     */
    public function getItems()
    {
        $items = [];

        $lang = Factory::getApplication()->getLanguage();

        $plugins = (array) PluginHelper::getPlugin('quiztools');

        if (!empty($plugins)) {
            foreach ($plugins as $plugin) {
                $lang->load('plg_quiztools_' . $plugin->name, JPATH_ADMINISTRATOR);

                $items[] = (object)[
                    'type' => $plugin->name,
                    'name' => Text::_('PLG_QUIZTOOLS_QUESTION_TYPE_' . strtoupper($plugin->name) . '_NAME'),
                    'desc' => Text::_('PLG_QUIZTOOLS_' . strtoupper($plugin->name) . '_SELECTQUESTIONTYPE_DESC'),
                ];
            }
        }

        return $items;
    }
}
