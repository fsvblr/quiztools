<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.mchoice
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Plugin\Quiztools\Mchoice\PluginTraits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;

/**
 * Saving the answer to the question on the site.
 *
 * @since   4.0.0
 */
trait QuestionSaveAnswer
{
	/**
	 * Saving the answer to the question on the site.
	 *
	 * @param   Event  $event
	 *
	 * @return bool
	 */
    public function QuestionSaveAnswer($event): bool
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

	    if (!\in_array($context, ['com_quiztools.question.saveAnswer'])) {
		    return false;
	    }

	    if (\is_array($data)) {
		    $data = (object) $data;
	    }

	    // Check that the question is of the current plugin type.
	    if ($data->type != $this->name) {
		    return false;
	    }

	    if (empty($data->id)) {
		    return false;
	    }

	    $db = $this->getDatabase();
	    $query = $db->createQuery();

		// Get all data of the question's options:
	    $query->select('*')
		    ->from($db->qn('#__quiztools_questions_' . $this->name . '_options', 'qo'))
		    ->where($db->qn('qo.question_id') . ' = :questionId')
		    ->bind(':questionId', $data->id, ParameterType::INTEGER);
	    try {
		    $tbl_options = $db->setQuery($query)->loadObjectList();
	    } catch (ExecutionFailureException $e) {
		    return false;
	    }

		if (empty($tbl_options)) {
			return false;
		}

		$answerIsCorrect = 0;
        $totalPoints = (float) $data->points;
	    $answerPointsReceived = 0;
		$feedbackOfOptionAnswer = '';

        // Example answer: {"type":"mchoice","answer":"2"}
        // $data->answer: "2"

		// Checking the correctness of the answer:
		foreach ($tbl_options as $option) {
			if ((int) $option->id === (int) $data->answer) {
				if ($option->is_correct) {
					$answerIsCorrect = 1;
					$answerPointsReceivedFromQuestion = !empty($data->points) ? (float) $data->points : 0;
				} else {
                    $answerPointsReceivedFromQuestion = 0;
                }

                $answerPointsReceivedFromOption = !empty($option->points) ? (float) $option->points : 0;
                $answerPointsReceived += (float) $answerPointsReceivedFromQuestion + (float) $answerPointsReceivedFromOption;
				$feedbackOfOptionAnswer = !empty($option->feedback_msg) ? $option->feedback_msg : '';
			}

            if ((int) $option->is_correct === 1) {
                $totalPoints += (float) $option->points;
            }
		}

		// Filling the returned object with data:
		$data->savedAnswerResult = [
			'is_correct' => $answerIsCorrect,
            'feedbackOfOptionAnswer' => $feedbackOfOptionAnswer,
		];

		// Checking the allowed number of attempts to answer a question:
		$query->clear();
	    $query->select($db->qn(['id', 'attempts']))
		    ->from($db->qn('#__quiztools_results_questions'))
		    ->where($db->qn('result_quiz_id') . ' = :resultQuizId')
		    ->where($db->qn('question_id') . ' = :questionId')
		    ->bind(':resultQuizId', $data->resultQuizId, ParameterType::INTEGER)
		    ->bind(':questionId', $data->id, ParameterType::INTEGER);
	    $db->setQuery($query);
		$oldResult = $db->loadObject();

	    $attemptsMade = !empty($oldResult->attempts) ? (int) $oldResult->attempts : 0;

        // If there are restrictions on attempts and if there is more than one attempt,
        // we apply a penalty to the points received for the answer:
        if ((int) $data->attempts > 0 && $attemptsMade > 1 && $data->penalty) {  // penalty in %%
            $penaltyCoefficient = (100 - $data->penalty * ($attemptsMade - 1)) / 100;

            if ($penaltyCoefficient < 0) {
                $answerPointsReceived = 0;
            } else {
                $answerPointsReceived = round($answerPointsReceived * $penaltyCoefficient, 2);
            }
        }

        // Delete the old result if there is one:
        if (!empty($oldResult->id)) {
            $query->clear()
                ->delete($db->qn('#__quiztools_results_questions'))
                ->where($db->qn('id') . ' = :Id')
                ->bind(':Id', $oldResult->id, ParameterType::INTEGER);
            $db->setQuery($query)->execute();

            $query->clear()
                ->delete($db->qn('#__quiztools_results_questions_' . $this->name))
                ->where($db->qn('results_question_id') . ' = :resultsQuestionId')
                ->bind(':resultsQuestionId', $oldResult->id, ParameterType::INTEGER);
            $db->setQuery($query)->execute();
        }

        // And save the answer:
        $resultQuestion = new \stdClass();
        $resultQuestion->result_quiz_id = $data->resultQuizId;
        $resultQuestion->question_id = $data->id;
        $resultQuestion->total_points = $totalPoints;
        $resultQuestion->points_received = $answerPointsReceived;
        $resultQuestion->attempts = $attemptsMade + 1;
        $resultQuestion->is_correct = $data->savedAnswerResult['is_correct'];
        $resultQuestion->response_at = Factory::getDate()->toSql(); // in UTC
        $db->insertObject('#__quiztools_results_questions', $resultQuestion);
        $resultQuestion->id = $db->insertid();

        $resultTypeQuestion = new \stdClass();
        $resultTypeQuestion->results_question_id = $resultQuestion->id;
        $resultTypeQuestion->option_id = (int) $data->answer;
        $db->insertObject('#__quiztools_results_questions_' . $this->name, $resultTypeQuestion);

	    $event->setArgument('result', $data);

	    return true;
    }
}
