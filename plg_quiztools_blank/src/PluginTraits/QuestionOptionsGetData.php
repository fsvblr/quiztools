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

use Joomla\CMS\Event\Content;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

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
		    ->select($db->qn(['shuffle_answers', 'distractors']))
		    ->from($db->qn('#__quiztools_questions_' . $this->name))
		    ->where($db->qn('question_id') . ' = :questionId')
		    ->bind(':questionId', $data->id, ParameterType::INTEGER);

	    try {
		    $questionData['typeData'] = $db->setQuery($query)->loadAssoc();
	    } catch (ExecutionFailureException $e) {
		    return false;
	    }

        if (!empty($questionData['typeData']['distractors'])) {
            $registry = new Registry($questionData['typeData']['distractors']);
            $questionData['typeData']['distractors'] = $registry->toArray();
        }

	    $query->clear();
		if ($client == 'administrator') {
			$query->select($db->qn(['qo.answers', 'qo.points', 'qo.ordering', 'qo.css_class']));
		} elseif ($client == 'site') {
			$query->select($db->qn(['qo.id', 'qo.ordering', 'qo.answers', 'qo.css_class']));
		}
		$query->from($db->qn('#__quiztools_questions_' . $this->name . '_options', 'qo'))
		    ->where($db->qn('qo.question_id') . ' = :questionId')
		    ->bind(':questionId', $data->id, ParameterType::INTEGER);

	    if ($client == 'administrator') {
		    $query->order($db->qn('qo.ordering') . ' ASC');
	    } elseif ($client == 'site') {

			// If the current user already had an answer to this question within this quiz, we will get it:
		    if (!empty($data->resultQuestionId)) {
                $query->select($db->qn('rqo.blank_id', 'user_blank_id'));
                $query->select($db->qn('rqo.answer', 'user_answer'));

                $query->join(
                    'LEFT',
                    $db->qn('#__quiztools_results_questions_' . $this->name, 'rqo'),
                    ('(' .
                        $db->qn('rqo.blank_id') . ' = ' . $db->qn('qo.id') .
                        ' AND ' .
                        $db->qn('rqo.results_question_id') . ' = ' . $db->q($data->resultQuestionId) .
                    ')')
                );
		    }

            $query->order($db->qn('qo.ordering') . ' ASC');
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

	    if ($client == 'site' && !empty($questionData['options'])) {
            $fillers = [];

            foreach ($questionData['options'] as $i => $option) {
                if (!empty($option['answers'])) {
                    $registry = new Registry($option['answers']);
                    $answers = $registry->toArray();
                    $fillers = array_merge($fillers, $answers);
                    unset($questionData['options'][$i]['answers']);
                }
            }

            if (!empty($questionData['typeData']['distractors'])) {
                $fillers = array_merge($fillers, $questionData['typeData']['distractors']);
            }

            if (!empty($fillers)) {
                if ($questionData['typeData']['shuffle_answers']) {
                    shuffle($fillers);
                }

                // Processing fillers by content plugins:
                $dispatcher = $this->getDispatcher();
                PluginHelper::importPlugin('content', null, true, $dispatcher);
                foreach ($fillers as $i => $filler) {
                    $article = new \stdClass();
                    $article->text = $filler;
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
                        $fillers[$i] = $article->text;
                    }
                    unset($article);
                }
            }

            $questionData['typeData']['fillers'] = $fillers;
            unset($questionData['typeData']['distractors']);
	    }

	    return $questionData;
    }
}
