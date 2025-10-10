<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.mresponse
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Plugin\Quiztools\Mresponse\PluginTraits;

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

        $data->options = $this->mresponseGetHtmlOptions($data, $questionOptionsData);

	    $event->setArgument('result', $data);

	    return true;
    }

	/**
	 * Html question options (site)
	 *
	 * @param $data
	 * @param $questionOptionsData
	 *
	 * @return string
	 */
	private function mresponseGetHtmlOptions($data, $questionOptionsData)
	{
		$html = '';

		if (empty($questionOptionsData['options'])) {
			return $html;
		}

		foreach ($questionOptionsData['options'] as $option) {
			$checked = !empty($option['user_answer']) ? " checked" : "";
			$html .=
			'<div class="question-option question-option-'.$data->type.'">
			    <input 
				    type="checkbox" 
				    id="'.$data->type.'_'.$option['id'].'" 
				    name="quiz[question]['.$data->id.'][options]['.$option['id'].']" 
				    value="'.$option['id'].'" 
				    '.$checked.'
				    />
                <label for="'.$data->type.'_'.$option['id'].'">'.$option['option'].'</label>
            </div>';
		}

		return $html;
	}
}
