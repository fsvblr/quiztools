<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.boilerplate
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Plugin\Quiztools\Boilerplate\PluginTraits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
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

        // Example answer: {"type":"boilerplate","answer":"ok"}

        // Filling the returned object with data:
        $data->savedAnswerResult = [
            'is_correct' => 1,
        ];

	    $db = $this->getDatabase();
	    $query = $db->createQuery();

        // Number of attempts to answer a question:
        $query->select($db->qn(['id', 'attempts']))
            ->from($db->qn('#__quiztools_results_questions'))
            ->where($db->qn('result_quiz_id') . ' = :resultQuizId')
            ->where($db->qn('question_id') . ' = :questionId')
            ->bind(':resultQuizId', $data->resultQuizId, ParameterType::INTEGER)
            ->bind(':questionId', $data->id, ParameterType::INTEGER);
        $db->setQuery($query);
        $oldResult = $db->loadObject();

        $attemptsMade = !empty($oldResult->attempts) ? (int) $oldResult->attempts : 0;

        // Delete the old result if there is one:
        if (!empty($oldResult->id)) {
            $query->clear()
                ->delete($db->qn('#__quiztools_results_questions'))
                ->where($db->qn('id') . ' = :Id')
                ->bind(':Id', $oldResult->id, ParameterType::INTEGER);
            $db->setQuery($query)->execute();
        }

        // And save the answer:
        $resultQuestion = new \stdClass();
        $resultQuestion->result_quiz_id = $data->resultQuizId;
        $resultQuestion->question_id = $data->id;
        $resultQuestion->total_points = 0;
        $resultQuestion->points_received = 0;
        $resultQuestion->attempts = $attemptsMade + 1;
        $resultQuestion->is_correct = 1;
        $resultQuestion->response_at = Factory::getDate()->toSql(); // in UTC
        $db->insertObject('#__quiztools_results_questions', $resultQuestion);

	    $event->setArgument('result', $data);

	    return true;
    }
}
