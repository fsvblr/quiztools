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
 * Get question options pdf data.
 *
 * @since   4.0.0
 */
trait QuestionOptionsGetPdfData
{
	/**
	 * Get question options pdf data.
	 *
	 * @param   Event  $event
	 *
	 * @return bool
	 */
    public function QuestionOptionsGetPdfData($event): bool
    {
	    if (!($this->getApplication() instanceof CMSApplication)) {
		    return false;
	    }

        if (!$this->getApplication()->isClient('administrator')
            && !$this->getApplication()->isClient('site')
        ) {
            return false;
        }

	    /**
	     * @var   string|null        $context  The context for the data
	     * @var   array|object|null  $data     An object or array containing the data for the form.
	     */
	    [$context, $data] = array_values($event->getArguments());

	    if (!\in_array($context, ['com_quiztools.question.options.pdfData'])) {
		    return false;
	    }

	    if (\is_array($data)) {
		    $data = (object) $data;  // =>question
	    }

	    // Check that the question is of the current plugin type.
	    if ($data->type != $this->name) {
		    return false;
	    }

        $j = 'A';
        $answersCorrect = [];
        $answersUser = [];

        $htmlOptions = '<table>';
        foreach ($data->results as $option) {
            $htmlOptions .= '<tr><td style="width: 20px;">' . $j . '.</td><td>' . $option->option . '</td></tr>';
            if ($option->is_correct) {
                $answersCorrect[] = $j;
            }
            if ($option->user_answer) {
                $answersUser[] = $j;
            }
            $j++;
        }
        $htmlOptions .= '</table>';
        $data->pdfOptions = $htmlOptions;

        $htmlResume = '<br><br><table>';
        $htmlResume .= '<tr><td>' . Text::_('PLG_QUIZTOOLS_MRESPONSE_PDF_RESULT_CORRECT_ANSWERS') . ': ' . implode(', ', $answersCorrect) . '</td></tr>';
        $htmlResume .= '<tr><td>' . Text::_('PLG_QUIZTOOLS_MRESPONSE_PDF_RESULT_USER_ANSWERS') . ': ' . implode(', ', $answersUser) . '</td></tr>';
        $htmlResume .= '</table>';
        $data->pdfResume = $htmlResume;

        if ($data->withFeedback) {
            $htmlFeedback = '';
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
                $htmlFeedback .= '<p>' . Text::_('PLG_QUIZTOOLS_MRESPONSE_PDF_RESULT_FEEDBACK') . ': ' . $feedback['text'] . '</p>';
            }

            $data->pdfFeedback = $htmlFeedback;
        }

	    $event->setArgument('result', $data);

	    return true;
    }
}
