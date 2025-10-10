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

use Joomla\CMS\Application\CMSApplication;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Event\Event;

/**
 * Removing results from the question table.
 *
 * @since   4.0.0
 */
trait QuestionOptionsDeleteResults
{
	/**
	 * Removing results from the question table.
	 *
	 * @param   Event  $event
	 *
	 * @return bool
	 */
    public function QuestionOptionsDeleteResults($event): bool
    {
	    if (!($this->getApplication() instanceof CMSApplication)) {
		    return false;
	    }

	    if (!$this->getApplication()->isClient('administrator')) {
		    return false;
	    }

	    /**
	     * @var   string|null        $context  The context for the data
	     * @var   array|object|null  $data     An object or array containing the data for the form.
	     */
	    [$context, $data] = array_values($event->getArguments());

	    if (!\in_array($context, ['com_quiztools.question.options.delete.results'])) {
		    return false;
	    }

	    if (!\is_array($data) || empty($data)) {
            return false;
	    }

	    $db = $this->getDatabase();
	    $query = $db->createQuery();
        $query->delete($db->qn('#__quiztools_results_questions_' . $this->name))
            ->where($db->qn('results_question_id') . " NOT IN ('" . implode("','", $data) . "')");
        $db->setQuery($query);

        try {
            $db->execute();
        } catch (ExecutionFailureException $e) {
            $event->setArgument('result', false);
        }

	    $event->setArgument('result', true);

	    return true;
    }
}
