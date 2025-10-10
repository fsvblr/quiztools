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
use Joomla\CMS\Event\Content;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;

/**
 * Get the results of the answer to the question.
 *
 * @since   4.0.0
 */
trait QuestionOptionsGetResults
{
	/**
	 * Get the results of the answer to the question.
	 *
	 * @param   Event  $event
	 *
	 * @return bool
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
        $query = $db->createQuery();

        $query->select('*')
            ->from($db->qn('#__quiztools_questions_' . $this->name . '_options', 'qo'))
            ->where($db->qn('qo.question_id') . ' = :questionId')
            ->bind(':questionId', $data->question_id, ParameterType::INTEGER);

        try {
            $options = $db->setQuery($query)->loadObjectList();
        } catch (ExecutionFailureException $e) {
            return false;
        }

        if (empty($options)) {
            return false;
        }

        $query->clear();
        $query->select($db->qn('option_id'))
            ->from($db->qn('#__quiztools_results_questions_' . $this->name, 'ro'))
            ->where($db->qn('ro.results_question_id') . ' = :resultsQuestionId')
            ->bind(':resultsQuestionId', $data->id, ParameterType::INTEGER);

        try {
            $results = $db->setQuery($query)->loadColumn();
        } catch (ExecutionFailureException $e) {
            return false;
        }

        $data->results = [];

        foreach ($options as $option) {
            if (!empty($results) && in_array($option->id, $results)) {
                $option->user_answer = true;
            } else {
                $option->user_answer = false;
            }

            $data->results[] = $option;
        }

        // Processing question options and option's feedback in the result
        if (!empty($data->results)) {
            $dispatcher = $this->getDispatcher();
            PluginHelper::importPlugin('content', null, true, $dispatcher);

            foreach ($data->results as $i => $result) {
                $article = new \stdClass();
                $article->text = $result->option;
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
                    $data->results[$i]->option = $article->text;
                }
                unset($article);
            }
        }

	    $event->setArgument('result', $data);

	    return true;
    }
}
