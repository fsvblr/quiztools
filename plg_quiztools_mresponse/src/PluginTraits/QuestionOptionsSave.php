<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.mresponse
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 * @license     GNU General Public License version 2 or later
 */

namespace Qt\Plugin\Quiztools\Mresponse\PluginTraits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\Table\AfterStoreEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\TableInterface;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\ParameterType;
use Qt\Component\Quiztools\Administrator\Helper\QuiztoolsHelper;
use Qt\Component\Quiztools\Administrator\Table\QuestionTable;
use Qt\Plugin\Quiztools\Mresponse\Table\QuestionMresponseOptionsTable;
use Qt\Plugin\Quiztools\Mresponse\Table\QuestionMresponseTable;

/**
 * Saving question options in the admin panel.
 *
 * @since  1.0.0
 */
trait QuestionOptionsSave
{
	/**
	 * Saving question options in the admin panel.
	 *
	 * @param   AfterStoreEvent  $event
	 * @return bool
     * @since  1.0.0
	 */
    public function QuestionOptionsSave($event): bool
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
	    $result = $event['result'];

	    if (!$result || !is_object($table)) {
		    return false;
	    }

	    if ($table instanceof QuestionTable) {
		    $typeAlias = $table->getTypeAlias();

		    if ($typeAlias != 'com_quiztools.question') {
			    return false;
		    }

		    $question_id = $table->getId();

			// Check that the question being saved is of the current plugin type.
		    if ($table->type != $this->name) {
			    return false;
		    }
	    } else {
		    return true;
	    }

        if (empty($question_id)) {
            return false;
        }

		$app = $this->getApplication();
		$input = $app->getInput();
		$formData = $input->get('jform', [], 'ARRAY');

	    $db = $this->getDatabase();
	    $query = $db->createQuery()
		    ->select($db->qn('id'))
		    ->from($db->qn('#__quiztools_questions_' . $this->name))
		    ->where($db->qn('question_id') . ' = :questionId')
		    ->bind(':questionId', $question_id, ParameterType::INTEGER);
	    try {
		    $question_type_id = $db->setQuery($query)->loadResult();
	    } catch (ExecutionFailureException $e) {
		    return false;
	    }

	    $questionTable = new QuestionMresponseTable(
			Factory::getContainer()->get('DatabaseDriver'),
			$this->getDispatcher()
	    );

	    $typeFields = [];
	    $typeFields['id'] = !empty($question_type_id) ? (int) $question_type_id : 0;
		$typeFields['question_id'] = (int) $question_id;
	    $typeFields['shuffle_answers'] = !empty($formData['shuffle_answers']) ? (int) $formData['shuffle_answers'] : 0;
	    $typeFields['partial_score'] = !empty($formData['partial_score']) ? (int) $formData['partial_score'] : 0;
	    $typeFields['feedback_partial_score'] = !empty($formData['feedback_partial_score'])
            ? QuiztoolsHelper::cleanHtml($formData['feedback_partial_score'])
            : '';

		if (!$questionTable->save($typeFields)) {
			return false;
		}

		$query->clear();
		$query->delete($db->qn('#__quiztools_questions_' . $this->name . '_options'))
			->where($db->qn('question_id') . ' = :questionId')
			->bind(':questionId', $question_id, ParameterType::INTEGER);
	    $db->setQuery($query);
	    try {
		    $db->execute();
	    } catch (\RuntimeException $e) {
		    $app->enqueueMessage($e->getMessage(), 'error');
	    }

	    $questionOptions = !empty($formData['question_options']) ? $formData['question_options'] : [];

		if (empty($questionOptions)) {
			return false;
		}

        // Check the number of set correct answers. There must be at least one.
        $correctCount = 0;
        foreach ($questionOptions as $questionOption) {
            if ((int) $questionOption['is_correct']) {
                $correctCount++;
            }
        }

        if ($correctCount < 1) {
            $app->enqueueMessage(Text::_('PLG_QUIZTOOLS_MRESPONSE_WARNING_NUMBER_CORRECT_ANSWERS'), 'error');
            //return false; // Continue to save options so that they can be more easily corrected.
        }
        // check - end

	    $questionOptionsTable = new QuestionMresponseOptionsTable(
			Factory::getContainer()->get('DatabaseDriver'),
			$this->getDispatcher()
	    );

		$i = 0;
		foreach ($questionOptions as $questionOption) {
			$questionOption['id'] = 0;
			$questionOption['question_id'] = (int) $question_id;
            $questionOption['option'] = QuiztoolsHelper::cleanHtml($questionOption['option']);
            $questionOption['is_correct'] = (int) $questionOption['is_correct'];
            $questionOption['points'] = (float) $questionOption['points'];
			$questionOption['ordering'] = $i;

			$questionOptionsTable->save($questionOption);
			$i++;
		}

	    return true;
    }
}
