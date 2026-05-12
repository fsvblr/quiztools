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

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\Table\AfterStoreEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\TableInterface;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\ParameterType;
use Qt\Component\Quiztools\Administrator\Table\QuestionTable;
use Qt\Plugin\Quiztools\Truefalse\Table\QuestionTruefalseTable;

/**
 * Saving question options in the admin panel.
 *
 * @since   4.0.0
 */
trait QuestionOptionsSave
{
    /**
     * Saving question options in the admin panel.
     *
     * @param   AfterStoreEvent  $event
     *
     * @return bool
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

        $questionTable = new QuestionTruefalseTable(
            Factory::getContainer()->get('DatabaseDriver'),
            $this->getDispatcher()
        );

        $typeFields = [];
        $typeFields['id'] = !empty($question_type_id) ? $question_type_id : 0;
        $typeFields['question_id'] = $question_id;
        $typeFields['correct_answer'] = !empty($formData['correct_answer']) ? (int) $formData['correct_answer'] : 0;

        if (!$questionTable->save($typeFields)) {
            return false;
        }

        return true;
    }
}
