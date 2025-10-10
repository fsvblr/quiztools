<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.mresponse
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Plugin\Quiztools\Mresponse\PluginTraits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplication;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;

/**
 * Get sum scores of question's options.
 *
 * @since   4.0.0
 */
trait QuestionOptionsGetScore
{
	/**
	 * Get sum scores of question's options.
	 *
	 * @param   Event  $event
	 *
	 * @return bool
	 */
    public function QuestionOptionsGetScore($event): bool
    {
	    if (!($this->getApplication() instanceof CMSApplication)) {
		    return false;
	    }

	    if (!$this->getApplication()->isClient('site') && !$this->getApplication()->isClient('administrator')) {
		    return false;
	    }

	    /**
	     * @var   string|null        $context  The context for the data
	     * @var   array|object|null  $data     An object or array containing the data for the form.
	     */
	    [$context, $data] = array_values($event->getArguments());

	    if (!\in_array($context, ['com_quiztools.question.options.score'])) {
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

	    $query = $db->createQuery()
		    ->select('SUM('.$db->qn('points').')')
		    ->from($db->qn('#__quiztools_questions_' . $this->name . '_options'))
		    ->where($db->qn('question_id') . ' = :questionId')
		    ->bind(':questionId', $data->id, ParameterType::INTEGER);

	    try {
		    $options_score = $db->setQuery($query)->loadResult();
	    } catch (ExecutionFailureException $e) {
		    return false;
	    }

	    $event->setArgument('result', $options_score);

	    return true;
    }
}
