<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class QuestiontypeslistField extends ListField
{
	protected $type = 'Questiontypeslist';

	protected function getInput()
	{
		$app = Factory::getApplication();
		$lang = $app->getLanguage();
		$input = $app->getInput();

        $filter = $input->get('filter', [], 'ARRAY');
        $selected = !empty($filter[$this->getAttribute('name')]) ? (string) $filter[$this->getAttribute('name')] : '';

        $attribs = 'class="form-select js-select-submit-on-change"';
        $id = 'filter_' . $this->getAttribute('name');

		$types = [];
		$plugins = (array) PluginHelper::getPlugin('quiztools');

		if (!empty($plugins)) {
			foreach ($plugins as $plugin) {
				$lang->load('plg_quiztools_' . $plugin->name, JPATH_ADMINISTRATOR);

				$types[] = (object)[
					'value' => $plugin->name,
					'text' => Text::_('PLG_QUIZTOOLS_QUESTION_TYPE_' . strtoupper($plugin->name) . '_NAME'),
				];
			}
		}

		$types = array_merge(parent::getOptions(), $types);

		return HTMLHelper::_('select.genericlist',  $types,  $this->name, $attribs, 'value', 'text', $selected, $id);
	}
}
