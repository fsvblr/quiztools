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

use Joomla\CMS\Event\Content;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\ParameterType;

/**
 * Get question options data.
 *
 * @since   4.0.0
 */
trait QuestionOptionsGetData
{
	/**
	 * Get question options data.
	 *
	 * @param object $data
	 * @param string $client
	 *
	 * @return array|false
	 */
    public function QuestionOptionsGetData($data, $client = 'administrator')
    {
	    $questionData = [];

	    $db = $this->getDatabase();

	    $query = $db->createQuery()
		    ->select($db->qn(['shuffle_answers']))
		    ->from($db->qn('#__quiztools_questions_' . $this->name))
		    ->where($db->qn('question_id') . ' = :questionId')
		    ->bind(':questionId', $data->id, ParameterType::INTEGER);

	    try {
		    $questionData['typeData'] = $db->setQuery($query)->loadAssoc();
	    } catch (ExecutionFailureException $e) {
		    return false;
	    }

	    $query->clear();
		if ($client == 'administrator') {
			$query->select($db->qn(['qo.option', 'qo.is_correct', 'qo.points', 'qo.ordering', 'qo.feedback_msg']));
		} elseif ($client == 'site') {
			$query->select($db->qn(['qo.id', 'qo.option']));
		}
		$query->from($db->qn('#__quiztools_questions_' . $this->name . '_options', 'qo'))
		    ->where($db->qn('qo.question_id') . ' = :questionId')
		    ->bind(':questionId', $data->id, ParameterType::INTEGER);

	    if ($client == 'administrator') {
		    $query->order($db->qn('qo.ordering') . ' ASC');
	    } elseif ($client == 'site') {

			// If the current user already had an answer to this question within this quiz, we will get it:
		    if (!empty($data->resultQuestionId)) {
                $query->select($db->qn('rqo.option_id', 'user_answer'));

                $query->join(
                    'LEFT',
                    $db->qn('#__quiztools_results_questions_' . $this->name, 'rqo'),
                    ('(' .
                        $db->qn('rqo.option_id') . ' = ' . $db->qn('qo.id') .
                        ' AND ' .
                        $db->qn('rqo.results_question_id') . ' = ' . $db->q($data->resultQuestionId) .
                    ')')
                );
		    }

			if ($questionData['typeData']['shuffle_answers']) {
				$query->order('rand()');
			} else {
				$query->order($db->qn('qo.ordering') . ' ASC');
			}
	    }

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

	    // Processing option text by content plugins:
	    if ($client == 'site' && !empty($questionData['options'])) {
            $dispatcher = $this->getDispatcher();
            PluginHelper::importPlugin('content', null, true, $dispatcher);
			foreach ($questionData['options'] as $i => $option) {
				$article = new \stdClass();
				$article->text = $option['option'];
                $article = $dispatcher->dispatch(
					'onContentPrepare',
					new Content\ContentPrepareEvent('onContentPrepare', [
						'context' => 'com_quiztools.question.option.prepareData',
						'subject' => $article,
						'params'  => new \stdClass(),
						'page'    => 0,
					])
				)->getArgument('result', $article);
				if (!empty($article->text)) {
                    $questionData['options'][$i]['option'] = $article->text;
				}
				unset($article);
			}
	    }

	    return $questionData;
    }
}
