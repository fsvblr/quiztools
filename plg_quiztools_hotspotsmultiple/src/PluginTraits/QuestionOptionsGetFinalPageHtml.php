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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Event\Event;
use Qt\Component\Quiztools\Administrator\Helper\QuiztoolsHelper;

/**
 * Get question options HTML for the final page
 * of the quiz with the results of its completion.
 *
 * @since   1.0.0
 */
trait QuestionOptionsGetFinalPageHtml
{
	/**
	 * Get question options HTML for the final page
     * of the quiz with the results of its completion.
	 *
	 * @param   Event  $event
	 * @return bool
     * @since   1.0.0
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
        $html .= '<div class="result-options ' . $this->name . '">';
            $html .= '<div class="result-options-row">
                        <div style="width:' . (int) $data->results['questionData']->imageSize['width'] . 'px;max-width:100%;">
                        ' . $data->results['resultImage'] . ' 
                        </div>
                    </div>';
        $html .= '</div>';

        $html .= '<div class="result-options-score">';
            $html .= Text::_('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_FINAL_RESULT_USER_SCORE') . ': ';
            $html .= (float) $data->points_received . '/' . (float) $data->total_points;
        $html .= '</div>';

        if ($data->withFeedback) {
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
                $html .= '<div class="result-options-feedback"><span>' .
                    Text::_('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_FINAL_RESULT_FEEDBACK') . ': </span>' . QuiztoolsHelper::cleanHtml($feedback['text']) . '</div>';
            }
        }

        $data->resultHtml = $html;

	    $event->setArgument('result', $data);

	    return true;
    }
}
