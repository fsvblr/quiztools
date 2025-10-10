<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class QuestiontypeField extends FormField
{
	protected $type = 'Questiontype';

	protected function getInput()
	{
        $lang = Factory::getApplication()->getLanguage();
        $lang->load('plg_quiztools_' . $this->value, JPATH_ADMINISTRATOR);

        $html = '';

        $html .= '<input type="hidden" name="jform[' . $this->getAttribute('name') . ']" 
                    id="jform_' . $this->getAttribute('name') . '" value="' . $this->value . '" />';

        $html .= '<span style="display: inline-block; margin-top: 4px;">'
            . Text::_('PLG_QUIZTOOLS_QUESTION_TYPE_' . strtoupper($this->value) . '_NAME')
            . '</span>';

        return $html;
	}
}
