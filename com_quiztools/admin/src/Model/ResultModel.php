<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Model;

use Joomla\CMS\Event\Content;
use Joomla\CMS\Event\Model;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;
use Qt\Component\Quiztools\Administrator\Helper\QuiztoolsHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Result model.
 *
 * @since  1.6
 */
class ResultModel extends BaseDatabaseModel
{
    /**
     * The URL option for the component.
     *
     * @var    string
     * @since  3.0
     */
    protected $option = 'com_quiztools';

    /**
     * An item.
     *
     * @var    array
     * @since  1.6
     */
    protected $_item = null;

    /**
     * Model context string.
     *
     * @var        string
     */
    protected $_context = 'com_quiztools.result';

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @since   1.6
     *
     * @return void
     */
    protected function populateState()
    {
        $app = Factory::getApplication();

        $pk = $app->getInput()->getInt('id');
        $this->setState('result.id', $pk);

        $layout = $app->getInput()->get('layout');
        $this->setState('result.layout', $layout);
    }

    /**
     * Method to get result data.
     *
     * @param   integer  $pk  The id of the result.
     *
     * @return  object|boolean  Item data object on success, boolean false
     */
    public function getItem($pk = null)
    {
        $app = Factory::getApplication();

        $pk = (int) ($pk ?: $this->getState('result.id'));
        $layout = $this->getState('result.layout');

        if ($this->_item === null) {
            $this->_item = [];
        }

        if (!isset($this->_item[$pk])) {
            try {
                $db = $this->getDatabase();
                $query = $db->createQuery();

                $query->select('a.*')
                    ->select($db->qn('q.title', 'quiz_title'))
                    ->select($db->qn(['q.certificate_id']))
                    ->select("IF (
                         " . $db->qn('a.user_id') . " > 0, 
                         " . $db->qn('u.name') . ", 
                         CONCAT (
                             IF (  
                                 (" . $db->qn('ru.user_name') . " = '' AND " . $db->qn('ru.user_surname') . " = ''),
                                 '" . Text::_('COM_QUIZTOOLS_RESULTS_USER_ANONYMOUS') . "',
                                 CONCAT(" . $db->qn('ru.user_name') . ", ' '," . $db->qn('ru.user_surname') . ")
                             ), 
                            ' ', 
                            ' (" . Text::_('COM_QUIZTOOLS_RESULTS_USER_GUEST') . ")'
                        )
                      ) as 'user_name'")

                    ->select("IF (
                             " . $db->qn('a.user_id') . " > 0, 
                             " . $db->qn('u.email') . ", 
                             IF (" . $db->qn('ru.user_email') . " = '', '-', " . $db->qn('ru.user_email') . ")
                         ) as 'user_email'");

                if ($layout === 'pdf') {
                    $query->select($db->qn([
                        'q.results_by_categories',
                        'q.feedback_question_pdf', 'q.feedback_msg_right', 'q.feedback_msg_wrong'
                    ]));
                }

                if ($layout === 'result') {
                    $query->select($db->qn([
                        'q.redirect_after_finish', 'q.redirect_after_finish_link', 'q.redirect_after_finish_delay',
                        'q.results_by_categories', 'q.results_pdf', 'q.results_certificate',
                        'q.results_with_questions',
                        'q.feedback_question_final', 'q.feedback_msg_right', 'q.feedback_msg_wrong',

                        'q.feedback_final_msg_options',
                        'q.feedback_final_msg_default_passed', 'q.feedback_final_msg_default_unpassed',
                        'q.feedback_final_msg'
                    ]));
                }

                $query->from($db->qn('#__quiztools_results_quizzes', 'a'))
                    ->join('LEFT', $db->qn('#__users', 'u'), $db->qn('u.id') . ' = ' . $db->qn('a.user_id'))
                    ->join('LEFT', $db->qn('#__quiztools_results_users', 'ru'), $db->qn('ru.result_quiz_id') . ' = ' . $db->qn('a.id'))
                    ->join('LEFT', $db->qn('#__quiztools_quizzes', 'q'), $db->qn('q.id') . ' = ' . $db->qn('a.quiz_id'))
                    ->where(
                        [
                            $db->qn('a.id') . ' = :pk',
                        ]
                    )
                    ->bind(':pk', $pk, ParameterType::INTEGER);

                $db->setQuery($query);
                $data = $db->loadObject();

                if (empty($data)) {
                    throw new \Exception(Text::_('COM_QUIZTOOLS_RESULT_ERROR_RESULT_NOT_FOUND'), 404);
                }

                if (!empty($data->sum_time_spent)) {
                    $data->sum_time_spent = QuiztoolsHelper::secondsToTimeString($data->sum_time_spent);
                }

                // From UTC to user's time zone
                if (!empty($data->start_datetime)) {
                    $data->start_datetime_for_display = QuiztoolsHelper::fromUtcToUsersTimeZone($data->start_datetime);
                }

                $data->results_questions = $this->getResultsQuestions($data->id);

                if (!empty($data->results_questions)) {
                    $dispatcher = $this->getDispatcher();
                    PluginHelper::importPlugin('content', null, true, $dispatcher);
                    PluginHelper::importPlugin('quiztools', null, true, $dispatcher);
                    $lang = $app->getLanguage();

                    foreach ($data->results_questions as $id => $question) {
                        $lang->load('plg_quiztools_' . $question->type, JPATH_ADMINISTRATOR);
                        $question->typeName = Text::_('PLG_QUIZTOOLS_QUESTION_TYPE_' . strtoupper($question->type) . '_NAME');

                        // Processing question text in the result
                        $data->results_questions[$id]->text = $dispatcher->dispatch(
                            'onContentPrepare',
                            new Content\ContentPrepareEvent('onContentPrepare', [
                                'context' => 'com_quiztools.question.prepareData',
                                'subject' => $question,
                                'params'  => new \stdClass(),
                                'page'    => 0,
                            ])
                        )->getArgument('result', $data->results_questions[$id])->text;
                        // after 'onContentPrepare' => plugins/system/fields :
                        if (isset($data->results_questions[$id]->jcfields)) {
                            unset($data->results_questions[$id]->jcfields);
                        }

                        if (\in_array($layout, ['question', 'pdf', 'result'])) {
                            $data->results_questions[$id] = $dispatcher->dispatch(
                                'onQuestionOptionsGetResults',
                                new Model\PrepareDataEvent('onQuestionOptionsGetResults', [
                                    'context' => 'com_quiztools.question.results',
                                    'data'    => $data->results_questions[$id],
                                    'subject' => new \stdClass(),
                                ])
                            )->getArgument('result', $data->results_questions[$id]);
                        }
                    }

                    if (!empty($data->results_by_categories)) {
                        $categories = [];
                        foreach ($data->results_questions as $question) {
                            if (!isset($categories[$question->question_category_title])) {
                                $categories[$question->question_category_title] = [
                                    'totalScore' => 0,
                                    'userScore' => 0,
                                    'userPercent' => 0.00,
                                ];
                            }

                            $categories[$question->question_category_title]['totalScore'] += (float) $question->total_points;
                            $categories[$question->question_category_title]['userScore'] += (float) $question->points_received;
                        }

                        if (!empty($categories)) {
                            foreach ($categories as $categoryTitle => $category) {
                                $categories[$categoryTitle]['totalScore'] = number_format(round($categories[$categoryTitle]['totalScore'], 2), 2, '.', '');
                                $categories[$categoryTitle]['userScore'] = number_format(round($categories[$categoryTitle]['userScore'], 2), 2, '.', '');

                                if ((float) $categories[$categoryTitle]['totalScore'] != 0) {
                                    $categories[$categoryTitle]['userPercent'] = ($categories[$categoryTitle]['userScore'] / $categories[$categoryTitle]['totalScore'])  * 100;
                                    $categories[$categoryTitle]['userPercent'] = number_format(round($categories[$categoryTitle]['userPercent'], 2), 2, '.', '');
                                }
                            }
                        }

                        $data->byCategories = $categories;
                    }
                }

                if ($layout === 'result') {
                    $data = $this->populateItemForResultPage($data);
                }

                $this->_item[$pk] = $data;

            } catch (\Exception $e) {
                $msg = Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage());
                $app->enqueueMessage($msg, 'warning');

                $this->_item[$pk] = false;
            }
        }

        return $this->_item[$pk];
    }

    /**
     * Receiving data on answered questions from a completed quiz.
     *
     * @param int $resultQuizId
     * @return array|mixed
     * @throws \Exception
     */
    private function getResultsQuestions($resultQuizId = 0)
    {
        try {
            $db = $this->getDatabase();
            $query = $db->createQuery();

            $query->select($db->qn(['a.id', 'a.question_id', 'a.total_points', 'a.points_received', 'a.is_correct']))
                ->select($db->qn(['q.text', 'q.type', 'q.feedback_msg_right', 'q.feedback_msg_wrong']))
                ->select($db->qn('q.catid', 'question_category_id'))
                ->select($db->qn('c.title', 'question_category_title'))
                ->from($db->qn('#__quiztools_results_questions', 'a'))
                ->join('INNER', $db->qn('#__quiztools_questions', 'q'), $db->qn('q.id') . ' = ' . $db->qn('a.question_id'))
                ->join('LEFT', $db->qn('#__categories', 'c'), $db->qn('c.id') . ' = ' . $db->qn('q.catid'))
                ->where($db->qn('a.result_quiz_id') . ' = :resultQuizId')
                ->bind(':resultQuizId', $resultQuizId)
                ->order($db->qn('a.id'));
            $db->setQuery($query);
            $data = $db->loadObjectList('id');
        } catch (\Exception $e) {
            $msg = Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage());
            Factory::getApplication()->enqueueMessage($msg, 'warning');
            $data = [];
        }

        return $data;
    }

    /**
     * Filling the result object with the data
     * needed for the result page.
     *
     * @param object $data
     * @return object
     */
    private function populateItemForResultPage($data)
    {
        // final message
        if ($data->feedback_final_msg_options !== 'hide') {
            $data->finalMessage = '';

            if ($data->feedback_final_msg_options === 'byPassed') {
                $data->finalMessage .= $data->passed
                    ? $data->feedback_final_msg_default_passed
                    : $data->feedback_final_msg_default_unpassed;
            }

            if (\in_array($data->feedback_final_msg_options, ['byPercent', 'byPoints'])) {
                if (!empty($data->feedback_final_msg)) {
                    $messages = json_decode($data->feedback_final_msg, true);
                    if (!empty($messages['feedback_final_msg0'])) {
                        $userPoints = (float) $data->sum_points_received;
                        $userPercent = 0;
                        if ((float) $data->total_score > 0) {
                            $userPercent = ((float) $data->sum_points_received /  (float) $data->total_score) * 100;
                        }

                        $comparisonValue = 0;
                        if ($data->feedback_final_msg_options === 'byPoints') {
                            $comparisonValue = $userPoints;
                        } else if ($data->feedback_final_msg_options === 'byPercent') {
                            $comparisonValue = $userPercent;
                        }

                        foreach ($messages as $message) {
                            if ($comparisonValue >= (float) $message['feedback_final_msg_from']
                                && $comparisonValue <= (float) $message['feedback_final_msg_to']
                            ) {
                                $data->finalMessage .= $message['feedback_final_msg_message'];
                                break;
                            }
                        }
                    }
                }
            }
        }
        // final message end

        // questions result page's html
        if (!empty($data->results_with_questions)) {
            if (!empty($data->feedback_question_final)) {
                $quizDataForFeedback = new \stdClass();
                $quizDataForFeedback->id = $data->quiz_id;
                $quizDataForFeedback->feedback_msg_right = $data->feedback_msg_right;
                $quizDataForFeedback->feedback_msg_wrong = $data->feedback_msg_wrong;
            }

            if (!empty($data->results_questions)) {
                $dispatcher = $this->getDispatcher();
                PluginHelper::importPlugin('quiztools', null, true, $dispatcher);

                foreach ($data->results_questions as $question) {
                    if (!empty($question->results)) {
                        $question->withFeedback = (bool) $data->feedback_question_final;
                        if ($question->withFeedback) {
                            $question->quizDataForFeedback = $quizDataForFeedback;
                        }

                        // Get question's options HTML:
                        $question = $dispatcher->dispatch(
                            'onQuestionOptionsGetFinalPageHtml',
                            new Model\PrepareDataEvent('onQuestionOptionsGetFinalPageHtml', [
                                'context' => 'com_quiztools.question.options.finalPageHtml',
                                'data'    => $question,
                                'subject' => new \stdClass(),
                            ])
                        )->getArgument('result', $question);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Method to delete one or more records.
     *
     * @param array  &$pks An array of record primary keys.
     *
     * @return  boolean  True if successful, false if an error occurs.
     *
     * @throws \Exception
     * @since   1.6
     */
    public function delete(&$pks)
    {
        if (!Factory::getApplication()->isClient('administrator')) {
            return false;
        }

        $pks = ArrayHelper::toInteger((array) $pks);
        $table = $this->getTable('ResultQuiz');
        $dispatcher = $this->getDispatcher();
        $user = $this->getCurrentUser();

        PluginHelper::importPlugin('content', null, true, $dispatcher);

        foreach ($pks as $i => $pk) {
            if ($table->load($pk)) {
                if ($user->authorise('core.delete', $this->option)) {
                    $context = $this->option . '.' . $this->name;

                    $beforeDeleteEvent = new Model\BeforeDeleteEvent('onContentBeforeDelete', [
                        'context' => $context,
                        'subject' => $table,
                    ]);
                    $result = $dispatcher->dispatch('onContentBeforeDelete', $beforeDeleteEvent)->getArgument('result', []);

                    if (\in_array(false, $result, true)) {
                        throw new \Exception(Text::_('COM_QUIZTOOLS_RESULT_ERROR_UNKNOWN_BEFORE_DELETING'));
                    }

                    if (!$table->delete($pk)) {
                        throw new \Exception(Text::_('COM_QUIZTOOLS_RESULT_ERROR_DELETING'));
                    }

                    $dispatcher->dispatch('onContentAfterDelete', new Model\AfterDeleteEvent('onContentAfterDelete', [
                        'context' => $context,
                        'subject' => $table,
                    ]));
                } else {
                    unset($pks[$i]);
                    Log::add(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), Log::WARNING, 'jerror');
                }
            } else {
                throw new \Exception(Text::sprintf('COM_QUIZTOOLS_RESULT_ERROR_DELETING_RESULT_NOT_FOUND', $pk));
            }
        }

        if (!$this->deleteRelatedData()) {
            throw new \Exception(Text::_('COM_QUIZTOOLS_RESULT_ERROR_DELETING_RELATED_DATA'));
        }

        $this->cleanCache();

        return true;
    }

    /**
     * Deleting data associated with deleted results in all results tables.
     *
     * @return  boolean  True on success, false on failure.
     */
    private function deleteRelatedData()
    {
        $app = Factory::getApplication();

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->qn('id'))
            ->from($db->qn('#__quiztools_results_quizzes'));
        $db->setQuery($query);
        $ids = $db->loadColumn();

        if (empty($ids)) {
            return true;
        }

        $query->clear();
        $query->delete($db->qn('#__quiztools_results_users'))
            ->where($db->qn('result_quiz_id') . " NOT IN ('" . implode("','", $ids) . "')");
        $db->setQuery($query);
        try {
            $db->execute();
        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }

        $query->clear();
        $query->delete($db->qn('#__quiztools_results_chains'))
            ->where($db->qn('result_quiz_id') . ' <> 0')
            ->where($db->qn('result_quiz_id') . " NOT IN ('" . implode("','", $ids) . "')");
        $db->setQuery($query);
        try {
            $db->execute();
        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }

        $query->clear();
        $query->delete($db->qn('#__quiztools_results_questions'))
            ->where($db->qn('result_quiz_id') . " NOT IN ('" . implode("','", $ids) . "')");
        $db->setQuery($query);
        try {
            $db->execute();
        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }

        $query->clear();
        $query->select($db->qn('id'))
            ->from($db->qn('#__quiztools_results_questions'));
        $db->setQuery($query);
        $resultsQuestionsIds = $db->loadColumn();

        if (!empty($resultsQuestionsIds)) {
            $dispatcher = $this->getDispatcher();
            PluginHelper::importPlugin('quiztools', null, true, $dispatcher);

            $result = $dispatcher->dispatch(
                'onQuestionOptionsDeleteResults',
                new Model\PrepareDataEvent('onQuestionOptionsDeleteResults', [
                    'context' => 'com_quiztools.question.options.delete.results',
                    'data'    => $resultsQuestionsIds,
                    'subject' => new \stdClass(),
                ])
            )->getArgument('result', true);

            if (!$result) {
                return false;
            }
        }

        return true;
    }
}
