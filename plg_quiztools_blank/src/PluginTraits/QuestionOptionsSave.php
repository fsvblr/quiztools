<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.blank
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 * @license     GNU General Public License version 2 or later
 */

namespace Qt\Plugin\Quiztools\Blank\PluginTraits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\Table\AfterStoreEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\TableInterface;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\ParameterType;
use Qt\Component\Quiztools\Administrator\Table\QuestionTable;
use Qt\Plugin\Quiztools\Blank\Table\QuestionBlankOptionsTable;
use Qt\Plugin\Quiztools\Blank\Table\QuestionBlankTable;

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

	    $questionTable = new QuestionBlankTable(
			Factory::getContainer()->get('DatabaseDriver'),
			$this->getDispatcher()
	    );

        $typeFields = [];
	    $typeFields['id'] = !empty($question_type_id) ? (int) $question_type_id : 0;
		$typeFields['question_id'] = (int) $question_id;
        $typeFields['shuffle_answers'] = !empty($formData['shuffle_answers']) ? (int) $formData['shuffle_answers'] : 0;

        $typeFields['distractors'] = [];
        if (!empty($formData['distractors'])) {  // => '[{"value":"tag1"},{"value":"tag2"},{"value":"tag3"}]'
            $distractors = json_decode($formData['distractors'], true);
            $typeFields['distractors'] = array_column($distractors, 'value');

            $typeFields['distractors'] = array_map(function($distractor) {
                $distractor = preg_replace('/[^\p{L}\p{N}_\. -]/u', '', $distractor);
                $distractor = preg_replace('/\s+/u', ' ', $distractor);
                $distractor = ltrim(trim($distractor), '.');
                return $distractor;
            }, $typeFields['distractors']);
            $typeFields['distractors'] = array_filter($typeFields['distractors']);
        }

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

	    $questionOptionsTable = new QuestionBlankOptionsTable(
			Factory::getContainer()->get('DatabaseDriver'),
			$this->getDispatcher()
	    );

		foreach ($questionOptions as $questionOption) {
			$questionOption['id'] = 0;
			$questionOption['question_id'] = (int) $question_id;

            if (!empty($questionOption['answers'][0])) {  // => '[{"value":"tag1"},{"value":"tag2"},{"value":"tag3"}]'
                $answers = json_decode($questionOption['answers'][0], true);
                $questionOption['answers'] = array_column($answers, 'value');

                $questionOption['answers'] = array_map(function($answer) {
                    $answer = preg_replace('/[^\p{L}\p{N}_\. -]/u', '', $answer);
                    $answer = preg_replace('/\s+/u', ' ', $answer);
                    $answer = ltrim(trim($answer), '.');
                    return $answer;
                }, $questionOption['answers']);
                $questionOption['answers'] = array_filter($questionOption['answers']);
            } else {
                $questionOption['answers'] = [];
            }

			$questionOptionsTable->save($questionOption);
		}

	    return true;
    }
}
