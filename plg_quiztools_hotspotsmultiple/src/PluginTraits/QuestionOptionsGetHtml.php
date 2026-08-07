<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.hotspotsmultiple
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 * @license     GNU General Public License version 2 or later
 */

namespace Qt\Plugin\Quiztools\Hotspotsmultiple\PluginTraits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\Event;

/**
 * Get question options html (site).
 *
 * @since   1.0.0
 */
trait QuestionOptionsGetHtml
{
	/**
	 * Get question options html (site).
	 *
	 * @param   Event  $event
	 * @return bool
     * @since   1.0.0
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

        $data->options = $this->hotspotsmultipleGetHtmlOptions($data, $questionOptionsData);

	    $event->setArgument('result', $data);

	    return true;
    }

	/**
	 * Html question options (site)
	 *
	 * @param $data
	 * @param $questionOptionsData
	 * @return string
     * @since   1.4.0
	 */
	private function hotspotsmultipleGetHtmlOptions($data, $questionOptionsData)
	{
		$html = '';

		if (empty($questionOptionsData['options'])) {
			return $html;
		}

        $data->id = (int) $data->id;
        $data->type = htmlspecialchars($data->type, ENT_QUOTES, 'UTF-8');

        // The type of quotes in the attribute with 'json_encode' matters!
        $html .=
            '<div class="question-option question-option-' . $data->type . '" id="' . $data->type . $data->id . '-area"
                data-id="' . $data->id . '" 
                data-checkOrder="' . (int) $questionOptionsData['typeData']['check_order'] . '" 
			    data-countMarkers="' . count($questionOptionsData['options']) . '" 
			    data-prevAnswer=\'' . json_encode($questionOptionsData['user_answers']) . '\' 
            >
			    <div id="' . $data->type . $data->id . '-wrapper" class="hotspot-wrapper">';

                    if (!empty($questionOptionsData['typeData']['image'])) {
                        $html .= '<span class="hotspot-image-wrapper">';
                            $html .= '<img src="' . URI::root(true) . '/' . $questionOptionsData['typeData']['image'] . '" 
                                        alt="' . Text::_('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_SITE_QUESTION_IMAGE_ALT') . '" />';
                        $html .= '</span>';
                    } else {
                        $html .= '<p>' . Text::_('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_SITE_WARNING_NO_IMAGE') . '</p>';
                    }

        $html .= '</div>';

        if (!empty($questionOptionsData['typeData']['image'])) {
            $html .= '<div>
                    <button class="hotspot-reset-btn" id="' . $data->type . $data->id . '-reset-btn" >
                        ' . Text::_('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_SITE_QUESTION_RESET_BTN') . '
                    </button>
                </div>';
        }

        $html .= '</div>';

		return $html;
	}
}
