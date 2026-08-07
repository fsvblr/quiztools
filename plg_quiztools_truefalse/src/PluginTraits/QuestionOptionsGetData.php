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

use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\ParameterType;

/**
 * Get question options data.
 *
 * @since  1.0.0
 */
trait QuestionOptionsGetData
{
    /**
     * Get question options data.
     *
     * @param object $data
     * @param string $client
     * @return array|false
     * @since  1.0.0
     */
    public function QuestionOptionsGetData($data, $client = 'administrator')
    {
        $questionData = [];

        $db = $this->getDatabase();

        $query = $db->createQuery()
            ->select($db->qn(['correct_answer']))
            ->from($db->qn('#__quiztools_questions_' . $this->name))
            ->where($db->qn('question_id') . ' = :questionId')
            ->bind(':questionId', $data->id, ParameterType::INTEGER);

        try {
            $questionData['typeData'] = $db->setQuery($query)->loadAssoc();
        } catch (ExecutionFailureException $e) {
            return false;
        }

        // For true/false there are no separate options, we only supply the correct answer.
        $questionData['options'] = [];

        if ($client == 'site') {
            // If the current user already had an answer to this question within this quiz, we will get it:
            if (!empty($data->resultQuestionId)) {
                $query->clear();
                $query->select($db->qn('rqo.user_answer'))
                    ->from($db->qn('#__quiztools_results_questions_' . $this->name, 'rqo'))
                    ->where($db->qn('rqo.results_question_id') . ' = ' . $db->q((int) $data->resultQuestionId));

                try {
                    $user_answer = $db->setQuery($query)->loadResult();
                } catch (ExecutionFailureException $e) {
                    return false;
                }

                if (isset($user_answer)) {  // $user_answer may be zero
                    $questionData['typeData']['user_answer'] = $user_answer;
                }
            }
        }

        return $questionData;
    }
}
