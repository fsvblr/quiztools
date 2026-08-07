<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.truefalse
 *
 * @copyright   (C) 2026 https://github.com/fsvblr/quiztools
 * @license     GNU General Public License version 2 or later
 */

namespace Qt\Plugin\Quiztools\Truefalse\PluginTraits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\Event\Event;

/**
 * Get question options html (site).
 *
 * @since  1.0.0
 */
trait QuestionOptionsGetHtml
{
    /**
     * Get question options html (site).
     *
     * @param   Event  $event
     * @return bool
     * @since  1.0.0
     */
    public function QuestionOptionsGetHtml($event): bool
    {
        if (!($this->getApplication() instanceof CMSApplication)) {
            return false;
        }

        if (!$this->getApplication()->isClient('site')) {
            return false;
        }

        /**
         * @var   string|null        $context  The context for the data
         * @var   array|object|null  $data     An object or array containing the data for the form.
         */
        [$context, $data] = array_values($event->getArguments());

        if (!\in_array($context, ['com_quiztools.question.options.html'])) {
            return false;
        }

	    if (\is_array($data)) {
		    $data = (object) $data;  // =>question
	    }

        // Check that the question is of the current plugin type.
        if ($data->type != $this->name) {
            return false;
        }

        if (empty($data->id)) {
            return false;
        }

	    $questionOptionsData = $this->QuestionOptionsGetData($data, 'site');

        $data->options = $this->truefalseGetHtmlOptions($data, $questionOptionsData);

        $event->setArgument('result', $data);

        return true;
    }

    /**
     * Html question options (site)
     *
     * @param $data
     * @param $questionOptionsData
     * @return string
     * @since  1.3.0
     */
    private function truefalseGetHtmlOptions($data, $questionOptionsData)
    {
        $data->id = (int) $data->id;
        $data->type = htmlspecialchars($data->type, ENT_QUOTES, 'UTF-8');

        $html = '';

        $checked = (isset($questionOptionsData['typeData']['user_answer']) && (int) $questionOptionsData['typeData']['user_answer'] === 1) ?
            " checked" : "";
        $html .= '<div class="question-option question-option-' . $data->type . '">
                    <input 
                        type="radio" 
                        id="' . $data->type . '_yes" 
                        name="quiz[question][' . $data->id . '][options]" 
                        value="1" 
                        required 
                        ' . $checked . '
                    />
                    <label for="' . $data->type . '_yes"><p>' . Text::_('PLG_QUIZTOOLS_TRUEFALSE_SITE_HTML_OPTION_YES') . '</p></label>
                  </div>';

        $checked = (isset($questionOptionsData['typeData']['user_answer']) && (int) $questionOptionsData['typeData']['user_answer'] === 0) ?
            " checked" : "";
        $html .= '<div class="question-option question-option-' . $data->type . '">
                    <input 
                        type="radio" 
                        id="' . $data->type . '_no" 
                        name="quiz[question][' . $data->id . '][options]" 
                        value="0" 
                        required 
                        ' . $checked . '
                    />
                    <label for="' . $data->type . '_no"><p>' . Text::_('PLG_QUIZTOOLS_TRUEFALSE_SITE_HTML_OPTION_NO') . '</p></label>
                  </div>';

        return $html;
    }
}
