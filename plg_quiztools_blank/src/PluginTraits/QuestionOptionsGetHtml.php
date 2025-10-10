<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.blank
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Plugin\Quiztools\Blank\PluginTraits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplication;
use Joomla\Event\Event;

/**
 * Get question options html (site).
 *
 * @since   4.0.0
 */
trait QuestionOptionsGetHtml
{
	/**
	 * Get question options html (site).
	 *
	 * @param   Event  $event
	 *
	 * @return bool
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

        $data->text = $this->blankPrepareQuestionText($data, $questionOptionsData);

		$data->options = $this->blankGetHtmlOptions($data, $questionOptionsData);

	    $event->setArgument('result', $data);

	    return true;
    }

    /**
     * Replacing placeholders in the question text with answer insertion points.
     *
     * @param object $data
     * @param array $questionOptionsData
     * @return string
     */
    private function blankPrepareQuestionText($data, $questionOptionsData)
    {
        $text = $data->text;

        if (empty($text)) {
            return $text;
        }

        $optionMap = [];
        foreach ($questionOptionsData['options'] as $opt) {
            $opt['questionId'] = $data->id;
            $optionMap[$opt['ordering']] = $opt;
        }

        $text = preg_replace_callback('/\{blank(\d+)\}/', function($matches) use ($optionMap, $questionOptionsData) {
            $index = (int) $matches[1];
            if (isset($optionMap[$index])) {
                $opt = $optionMap[$index];
                $class = htmlspecialchars($opt['css_class']);
                $id = (int) $opt['id'];
                $questionId = (int) $opt['questionId'];

                $html = "<span class=\"quiztools-blank drop-target {$class}\" data-id=\"{$id}\"  data-questionId=\"{$questionId}\">";

                if (!empty($opt['user_answer'])) {
                    $i = array_search($opt['user_answer'], $questionOptionsData['typeData']['fillers']);
                    $id = 'blank_' . $opt['questionId'] . '_' . $i;
                    $html .= '<span class="quiztools-blank filler" id="' . $id . '" draggable="true" 
                        data-parent-id="draggable-zone_' . $opt['questionId'] . '">'
                        . htmlspecialchars($opt['user_answer'], ENT_QUOTES, 'UTF-8') . '</span>';
                }

                $html .= "</span>";

                return $html;
            }
            return $matches[0]; // if there is no match, leave as is
        }, $data->text);

        return $text;
    }

	/**
	 * Html question options (site)
	 *
	 * @param object $data
	 * @param array $questionOptionsData
	 *
	 * @return string
	 */
	private function blankGetHtmlOptions($data, $questionOptionsData)
	{
		$html = '';

		if (empty($questionOptionsData['typeData']['fillers'])) {
			return $html;
		}

        $user_answers = [];
        foreach ($questionOptionsData['options'] as $opt) {
            if (!empty($opt['user_answer'])) {
                $user_answers[] = $opt['user_answer'];
            }
        }

        $html .= '<div class="quiztools-blank draggable-zone" id="draggable-zone_' . $data->id . '">';

		foreach ($questionOptionsData['typeData']['fillers'] as $i => $filler) {
            if (in_array($filler, $user_answers)) {
                continue;
            }

            $id = 'blank_' . $data->id . '_' . $i;
            $html .= '<span class="quiztools-blank filler" id="' . $id . '" draggable="true" 
                        data-parent-id="draggable-zone_' . $data->id . '">'
                . htmlspecialchars($filler, ENT_QUOTES, 'UTF-8') . '</span>';
		}

        $html .= '</div>';

		return $html;
	}
}
