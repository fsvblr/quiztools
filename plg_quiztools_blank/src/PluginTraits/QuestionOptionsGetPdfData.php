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

        $answersUser = [];

        if (!empty($data->results)) {
            foreach ($data->results as $i => $result) {
                $placeholder = '{blank' . ($i + 1) . '}';
                $replacement = '{' . implode(', ', $result->answers) . '}';
                $data->text = str_replace($placeholder, $replacement, $data->text);

                $color = $result->is_correct ? '#457d54' : '#EB5757';
                $answersUser[] = '<span style="color:' . $color . ';">' . $result->user_answer . '</span>';
            }
        }

        $data->pdfOptions = '';

        $htmlResume = '<br><br><table>';
        $htmlResume .= '<tr><td>' . Text::_('PLG_QUIZTOOLS_BLANK_PDF_RESULT_USER_ANSWERS') . ': ' . implode(', ', $answersUser) . '</td></tr>';
        $htmlResume .= '</table>';
        $data->pdfResume = $htmlResume;

        if ($data->withFeedback) {
            $htmlFeedback = '';

            /** @var \Qt\Component\Quiztools\Site\Model\AjaxQuizModel $modelAjax */
            $modelAjax = Factory::getApplication()->bootComponent('com_quiztools')
                ->getMVCFactory()->createModel('AjaxQuiz', 'Site', ['ignore_request' => true]);

            $savedQuestion = new \stdClass();
            $savedQuestion->id = $data->question_id;
            $savedQuestion->type = $data->type;
            $savedQuestion->feedback_msg_right = $data->feedback_msg_right;
            $savedQuestion->feedback_msg_wrong = $data->feedback_msg_wrong;
            $savedQuestion->savedAnswerResult = [
                'is_correct' => $data->is_correct,
            ];

            $feedback = $modelAjax->getQuestionFeedback($data->quizDataForFeedback, $savedQuestion);

            if (!empty($feedback['text'])) {
                $htmlFeedback .= '<p>' . Text::_('PLG_QUIZTOOLS_BLANK_PDF_RESULT_FEEDBACK') . ': ' . $feedback['text'] . '</p>';
            }

            $data->pdfFeedback = $htmlFeedback;
        }

	    $event->setArgument('result', $data);

	    return true;
    }
}
