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

use Joomla\CMS\Filter\InputFilter;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\ParameterType;

/**
 * Get question options data.
 *
 * @since   1.0.0
 */
trait QuestionOptionsGetData
{
	/**
	 * Get question options data.
	 *
	 * @param object $data
	 * @param string $client
	 * @return array|false
     * @since   1.0.0
	 */
    public function QuestionOptionsGetData($data, $client = 'administrator')
    {
        $filter = InputFilter::getInstance();

	    $questionData = [];

	    $db = $this->getDatabase();

	    $query = $db->createQuery()
		    ->select($db->qn(['check_order', 'image']))
		    ->from($db->qn('#__quiztools_questions_' . $this->name))
		    ->where($db->qn('question_id') . ' = :questionId')
		    ->bind(':questionId', $data->id, ParameterType::INTEGER);

	    try {
		    $questionData['typeData'] = $db->setQuery($query)->loadAssoc();
	    } catch (ExecutionFailureException $e) {
		    return false;
	    }

        if (!empty($questionData['typeData']['image'])) {
            $questionData['typeData']['image'] = $filter->clean($questionData['typeData']['image'], 'path');
        }

	    $query->clear();
		if ($client == 'administrator') {
			$query->select($db->qn(['qo.coordinates', 'qo.color', 'qo.points', 'qo.ordering']));
		} elseif ($client == 'site') {
			$query->select($db->qn(['qo.id']));
		}

		$query->from($db->qn('#__quiztools_questions_' . $this->name . '_options', 'qo'))
		    ->where($db->qn('qo.question_id') . ' = :questionId')
		    ->bind(':questionId', $data->id, ParameterType::INTEGER)
            ->order($db->qn('qo.ordering') . ' ASC');

	    try {
		    $tbl_options = $db->setQuery($query)->loadAssocList();
	    } catch (ExecutionFailureException $e) {
		    return false;
	    }

	    $questionData['options'] = [];

	    if (!empty($tbl_options)) {
		    foreach ($tbl_options as $tbl_option) {
			    $questionData['options'][] = $tbl_option;
		    }
	    }

        if ($client == 'site') {
            // If the current user already had an answer to this question within this quiz, we will get it:
            $questionData['user_answers'] = [];

            if (!empty($data->resultQuestionId)) {
                $query->clear();
                $query->select($db->qn('rqo.answer_coordinates'))
                    ->from($db->qn('#__quiztools_results_questions_' . $this->name, 'rqo'))
                    ->where($db->qn('rqo.results_question_id') . ' = ' . $db->q((int) $data->resultQuestionId))
                    ->order($db->qn('rqo.answer_ordering') . ' ASC');

                try {
                    $user_answers = $db->setQuery($query)->loadColumn();
                } catch (ExecutionFailureException $e) {
                    return false;
                }

                if (!empty($user_answers)) {
                    $questionData['user_answers'] = array_map(fn($answer) => json_decode($answer, false), $user_answers);
                }
            }
        }

	    return $questionData;
    }
}
