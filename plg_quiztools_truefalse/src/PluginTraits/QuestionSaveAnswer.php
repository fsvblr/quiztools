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

        // Get the correct answer stored for this question
        $query = $db->createQuery()
            ->select($db->qn('correct_answer'))
            ->from($db->qn('#__quiztools_questions_' . $this->name))
            ->where($db->qn('question_id') . ' = :questionId')
            ->bind(':questionId', $data->id, ParameterType::INTEGER);

        try {
            $correctAnswer = (int) $db->setQuery($query)->loadResult();
        } catch (ExecutionFailureException $e) {
            return false;
        }

        // Example answer: {"type":"truefalse","answer":"1"}
        // $data->answer: "1"

        // The answer from the user (0 or 1)
        $userAnswer = (int) $data->answer;

        $answerIsCorrect = ($userAnswer === $correctAnswer) ? 1 : 0;
        $totalPoints = (float) $data->points;
        $answerPointsReceived = $answerIsCorrect ? $totalPoints : 0.0;

        // Filling the returned object with data:
        $data->savedAnswerResult = [
            'is_correct' => $answerIsCorrect,
        ];

        // Check the allowed number of attempts to answer a question:
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

        // Delete old result if any
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

        // Insert new result
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

        // Insert answer in type‑specific table
        $resultTypeQuestion = new \stdClass();
        $resultTypeQuestion->results_question_id = $resultQuestion->id;
        $resultTypeQuestion->user_answer = $userAnswer;
        $db->insertObject('#__quiztools_results_questions_' . $this->name, $resultTypeQuestion);

        $event->setArgument('result', $data);

        return true;
    }
}
