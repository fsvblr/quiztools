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
use Joomla\CMS\Event\Table\AfterDeleteEvent;
use Joomla\CMS\Table\TableInterface;
use Joomla\Database\ParameterType;
use Qt\Component\Quiztools\Administrator\Table\QuestionTable;

/**
 * Deleting question type data (options) when deleting a question.
 *
 * @since   4.0.0
 */
trait QuestionOptionsDelete
{
	/**
	 * Deleting question type data (options) when deleting a question.
	 *
	 * @param   AfterDeleteEvent  $event
	 *
	 * @return bool
	 */
    public function QuestionOptionsDelete($event): bool
    {
	    if (!($this->getApplication() instanceof CMSApplication)) {
		    return false;
	    }

	    if (!$this->getApplication()->isClient('administrator')) {
		    return false;
	    }

	    // Extract arguments
	    /** @var TableInterface $table */
	    $table  = $event['subject'];
	    $pk = $event['pk'];

	    if (!$pk || !is_object($table)) {
		    return false;
	    }

	    if ($table instanceof QuestionTable) {
		    $typeAlias = $table->getTypeAlias();

		    if ($typeAlias != 'com_quiztools.question') {
			    return false;
		    }

			// Check that the question being saved is of the current plugin type.
		    if ($table->type != $this->name) {
			    return false;
		    }
	    } else {
		    return true;
	    }

        if (empty($pk)) {
            return false;
        }

		$app = $this->getApplication();

	    $db = $this->getDatabase();
	    $query = $db->createQuery()
		    ->delete($db->qn('#__quiztools_questions_' . $this->name))
			->where($db->qn('question_id') . ' = :questionId')
			->bind(':questionId', $pk, ParameterType::INTEGER);
	    $db->setQuery($query);
	    try {
		    $db->execute();
	    } catch (\RuntimeException $e) {
		    $app->enqueueMessage($e->getMessage(), 'error');
	    }

	    $query->clear()
		    ->delete($db->qn('#__quiztools_questions_' . $this->name . '_options'))
		    ->where($db->qn('question_id') . ' = :questionId')
		    ->bind(':questionId', $pk, ParameterType::INTEGER);
	    $db->setQuery($query);
	    try {
		    $db->execute();
	    } catch (\RuntimeException $e) {
		    $app->enqueueMessage($e->getMessage(), 'error');
	    }

	    return true;
    }
}
