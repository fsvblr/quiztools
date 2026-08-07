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
use Joomla\CMS\Language\Text;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;

/**
 * Get the results of the answer to the question.
 *
 * @since  1.0.0
 */
trait QuestionOptionsGetResults
{
    /**
     * Get the results of the answer to the question.
     *
     * @param   Event  $event
     * @return bool
     * @since  1.0.0
     */
    public function QuestionOptionsGetResults($event): bool
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

        if (!\in_array($context, ['com_quiztools.question.results'])) {
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

        // Get the correct answer from the question type table
        $query = $db->createQuery()
            ->select($db->qn('correct_answer'))
            ->from($db->qn('#__quiztools_questions_' . $this->name))
            ->where($db->qn('question_id') . ' = :questionId')
            ->bind(':questionId', $data->question_id, ParameterType::INTEGER);

        try {
            $correctAnswer = (int) $db->setQuery($query)->loadResult();
        } catch (ExecutionFailureException $e) {
            return false;
        }

        // Get the user answer from the results type table
        $query->clear();
        $query->select($db->qn('user_answer'))
            ->from($db->qn('#__quiztools_results_questions_' . $this->name, 'ro'))
            ->where($db->qn('ro.results_question_id') . ' = :resultsQuestionId')
            ->bind(':resultsQuestionId', $data->id, ParameterType::INTEGER);

        try {
            $userAnswer = (int) $db->setQuery($query)->loadResult();
        } catch (ExecutionFailureException $e) {
            $userAnswer = null;
        }

        // Build the result structure (two "options": Yes and No)
        $data->results = [];

        $yesOption = new \stdClass();
        $yesOption->option = Text::_('PLG_QUIZTOOLS_TRUEFALSE_RESULTS_OPTION_YES');
        $yesOption->is_correct = ($correctAnswer === 1) ? 1 : 0;
        $yesOption->user_answer = ($userAnswer === 1) ? true : false;

        $noOption = new \stdClass();
        $noOption->option = Text::_('PLG_QUIZTOOLS_TRUEFALSE_RESULTS_OPTION_NO');
        $noOption->is_correct = ($correctAnswer === 0) ? 1 : 0;
        $noOption->user_answer = ($userAnswer === 0) ? true : false;

        $data->results[] = $yesOption;
        $data->results[] = $noOption;

        $event->setArgument('result', $data);

        return true;
    }
}
