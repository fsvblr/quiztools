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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Event\Event;

/**
 * Get question options HTML for the final page
 * of the quiz with the results of its completion.
 *
 * @since   4.0.0
 */
trait QuestionOptionsGetFinalPageHtml
{
	/**
	 * Get question options HTML for the final page
     * of the quiz with the results of its completion.
	 *
	 * @param   Event  $event
	 *
	 * @return bool
	 */
    public function QuestionOptionsGetFinalPageHtml($event): bool
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

	    if (!\in_array($context, ['com_quiztools.question.options.finalPageHtml'])) {
		    return false;
	    }

	    if (\is_array($data)) {
		    $data = (object) $data;  // =>question
	    }

	    // Check that the question is of the current plugin type.
	    if ($data->type != $this->name) {
		    return false;
	    }

        $html = '';
        $html .= '<div class="result-options ' . $this->name . '">
                <div class="result-options-header">
                    <div class="w-5 text-center">#</div>
                    <div class="w-65">' . Text::_('PLG_QUIZTOOLS_MRESPONSE_FINAL_RESULT_HEADING_OPTION_TEXT') . '</div>
                    <div class="w-15 text-center">' . Text::_('PLG_QUIZTOOLS_MRESPONSE_FINAL_RESULT_HEADING_OPTION_RIGHT_ANSWER') . '</div>
                    <div class="w-15 text-center">' . Text::_('PLG_QUIZTOOLS_MRESPONSE_FINAL_RESULT_HEADING_OPTION_USER_ANSWER') . '</div>
                </div>';

        $answersCorrect = [];
        $j = 'A';
        foreach ($data->results as $i => $option) {
            if ($option->is_correct) {
                $answersCorrect[] = $j;
            }

            $html .= '<div class="result-options-row row' . $i % 2 . '">
                    <div class="w-5 text-center">' . $j . '</div>
                    <div class="w-65">'. $option->option . '</div>
                    <div class="w-15 text-center">';
                        if ($option->is_correct) {
                            $html .= '<img src="/media/com_quiztools/images/icon-check.svg" class="quiz-result-choice-icon" 
                            alt="' . Text::_('PLG_QUIZTOOLS_MRESPONSE_FINAL_RESULT_OPTION_ALT_RIGHT_ANSWER') . '" />';
                        }
                    $html .= '</div>
                    <div class="w-15 text-center">';
                        if ($option->user_answer) {
                            $html .= '<img src="/media/com_quiztools/images/icon-' . ($option->is_correct ? 'check' : 'close') . '.svg" 
                            class="quiz-result-choice-icon" 
                            alt="' . Text::_('PLG_QUIZTOOLS_MRESPONSE_FINAL_RESULT_OPTION_ALT_USER_ANSWER') . '" />';
                        }
                    $html .= '</div>
                </div>';

            $j++;
        }
        $html .= '</div>';

        $html .= '<div class="result-options-score">';
        $html .= Text::_('PLG_QUIZTOOLS_MRESPONSE_FINAL_RESULT_USER_SCORE') . ': ';
        $html .= $data->points_received . '/' . $data->total_points;
        $html .= '</div>';

        if ($data->withFeedback) {
            $feedbackOfOptionAnswer = '';
            $qtyCorrectByUser = 0;
            $qtyInCorrectByUser = 0;

            foreach ($data->results as $option) {
                if ($option->user_answer) {
                    if (!empty($option->feedback_msg)) {
                        $feedbackOfOptionAnswer = $option->feedback_msg;
                    }
                    if ($option->is_correct) {
                        $qtyCorrectByUser++;
                    } else {
                        $qtyInCorrectByUser++;
                    }
                }
            }

            $is_partially_correct = 0;
            if (count($answersCorrect) > 0 && $qtyCorrectByUser > 0
                && count($answersCorrect) !== $qtyCorrectByUser
                  && $qtyInCorrectByUser === 0
            ) {
                $is_partially_correct = 1;
            }

            /** @var \Qt\Component\Quiztools\Site\Model\AjaxQuizModel $modelAjax */
            $modelAjax = Factory::getApplication()->bootComponent('com_quiztools')
                ->getMVCFactory()->createModel('AjaxQuiz', 'Site', ['ignore_request' => true]);

            $savedQuestion = new \stdClass();
            $savedQuestion->id = $data->question_id;
            $savedQuestion->type = $data->type;
            $savedQuestion->feedback_msg_right = $data->feedback_msg_right;
            $savedQuestion->feedback_msg_wrong = $data->feedback_msg_wrong;
            if (isset($data->partial_score)) {
                $savedQuestion->partial_score = $data->partial_score;
                $savedQuestion->feedback_partial_score = $data->feedback_partial_score;
            }
            $savedQuestion->savedAnswerResult = [
                'is_correct' => $data->is_correct,
                'is_partially_correct' => $is_partially_correct,
                'feedbackOfOptionAnswer' => $feedbackOfOptionAnswer,
            ];

            $feedback = $modelAjax->getQuestionFeedback($data->quizDataForFeedback, $savedQuestion);

            if (!empty($feedback['text'])) {
                $html .= '<div class="result-options-feedback"><span>' .
                    Text::_('PLG_QUIZTOOLS_MRESPONSE_FINAL_RESULT_FEEDBACK') . ': </span>' . $feedback['text'] . '</div>';
            }
        }

        $data->resultHtml = $html;

	    $event->setArgument('result', $data);

	    return true;
    }
}
