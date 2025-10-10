<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Site\Model;

use Joomla\CMS\Event\Content;
use Joomla\CMS\Event\Model;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Qt\Component\Quiztools\Administrator\Model\ResultModel;
use Qt\Component\Quiztools\Administrator\Model\QuestionModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * QuizTools Component AjaxQuiz Model
 *
 * @since  1.5
 */
class AjaxQuizModel extends BaseDatabaseModel
{
    /**
     * Quiz 'Start'
     *
     * @return array
     * @throws \Exception
     */
	public function quizStart()
	{
		$app = Factory::getApplication();
		$input = $app->getInput();

		$data = $input->get('quiz', [], 'ARRAY');
		$quiz_id = (int) $data['id'] ?: 0;

		/** @var QuizModel $model_quiz */
		$model_quiz = $app->bootComponent('com_quiztools')->getMVCFactory()
			->createModel('Quiz', 'Site', ['ignore_request' => true]);
		$quiz = $model_quiz->getItem($quiz_id);

		if (empty($quiz->id)) {
			throw new \Exception(Text::_('COM_QUIZTOOLS_QUIZ_ERROR_QUIZ_NOT_FOUND'));
		}

		$user = $this->getCurrentUser();
		$db = $this->getDatabase();
		$query = $db->createQuery();

		// set unique_id:
		$unique_id = '';

        if ($quiz->allow_continue) {
            $cookie_quizupi = $input->cookie->get('quizupi');

            if (!empty($cookie_quizupi[$quiz->id])) {
                $unique_id = $cookie_quizupi[$quiz->id];
            }

            $query->clear();
            $query->select($db->qn(['id', 'unique_id', 'start_datetime']))  // `start_datetime` in UTC
                ->from($db->qn('#__quiztools_results_quizzes'))
                ->where($db->qn('quiz_id') . ' = :quiz_id')
                ->where($db->qn('user_id') . ' = :user_id')
                ->where($db->qn('finished') . ' = ' . $db->q(0))
                ->bind(':quiz_id', $quiz->id, ParameterType::INTEGER)
                ->bind(':user_id', $user->id, ParameterType::INTEGER)
                ->order($db->qn('id') . ' DESC');

            if (!empty($unique_id)) {
                $query->where($db->qn('unique_id') . '=' . $db->q($unique_id));
            }

            $db->setQuery($query);
            $resultQuiz = $db->loadObject();

            if (!empty($resultQuiz)) {
                $resultQuiz->current_time = Factory::getDate()->toSql();  // in UTC
            }

            if (!empty($resultQuiz->unique_id)) {
                $unique_id = $resultQuiz->unique_id;
            }
        }

		if (empty($unique_id) || empty($resultQuiz->id)) {
			$unique_id = md5(uniqid(rand(), true));
		}

		$input->cookie->set("quizupi[$quiz->id]", $unique_id, [
            'expires'  => time() + 365 * 86400,  // one year
            'path'     => '/',
            'domain'   => '',
            'secure'   => $app->isHttpsForced(),
            'httponly' => true,
        ]);
        // end set unique_id

		$resultQuizId = !empty($resultQuiz->id) ? $resultQuiz->id : null;
		$new_quiz = empty($resultQuizId) ? true : false;
		$result = [];

		if ($new_quiz) {
			// user's data collection:
			$userData = new \stdClass();
            if ($user->id) {
                $user_name_arr = explode(' ', $user->name);
                $userData->surname = array_pop($user_name_arr);
                $userData->name = implode(' ', $user_name_arr);
                $userData->email = $user->email;
            } else {
                $userData->name = !empty($data['user']['name']) ? htmlspecialchars($data['user']['name'], ENT_QUOTES, 'UTF-8') : '';
                $userData->surname = !empty($data['user']['surname']) ? htmlspecialchars($data['user']['surname'], ENT_QUOTES, 'UTF-8') : '';
                $userData->email = !empty($data['user']['email']) ? htmlspecialchars($data['user']['email'], ENT_QUOTES, 'UTF-8') : '';
            }

			$dispatcher = $this->getDispatcher();
			PluginHelper::importPlugin('content', null, true, $dispatcher);
			PluginHelper::importPlugin('quiztools', null, true, $dispatcher);

			// The point of possible receipt/change of user data.
            // The added user data will be saved in json in the 'user_data' field.
			$userData = $dispatcher->dispatch(
				'onContentPrepare',
				new Content\ContentPrepareEvent('onContentPrepare', [
					'context' => 'com_quiztools.quiz.start.prepareUserData',
					'subject' => $userData,
					'params'  => new \stdClass(),
					'page'    => 0,
				])
			)->getArgument('result', $userData);
			// after 'onContentPrepare' => plugins/system/fields :
			if (isset($userData->jcfields)) {
                unset($userData->jcfields);
            }

			// create a new "result":
			$resultQuiz = new \stdClass();
			$resultQuiz->quiz_id = $quiz->id;
			$resultQuiz->user_id = $user->id;
			$resultQuiz->total_score = $quiz->total_score;
			$resultQuiz->passing_score = $quiz->passing_score;
			$resultQuiz->sum_points_received = 0;
			$resultQuiz->passed = 0;
			$resultQuiz->finished = 0;
			$resultQuiz->start_datetime = Factory::getDate()->toSql();  // in UTC
			$resultQuiz->sum_time_spent = 0;
			$resultQuiz->unique_id = $unique_id;
			$resultQuiz->params = '{}';  //data for custom jobs?
			$db->insertObject('#__quiztools_results_quizzes', $resultQuiz);
			$resultQuiz->id = $db->insertid();
			$resultQuizId = $resultQuiz->id;

			// save user data for new "result":
			$userData_tbl = new \stdClass();
			$userData_tbl->result_quiz_id = $resultQuizId;
			$userData_tbl->user_id = $user->id;
			$userData_tbl->user_name = $userData->name;
			$userData_tbl->user_surname = $userData->surname;
			$userData_tbl->user_email = $userData->email;
			if (isset($userData->name)) {
                unset($userData->name);
            }
			if (isset($userData->surname)) {
                unset($userData->surname);
            }
			if (isset($userData->email)) {
                unset($userData->email);
            }
			$userData_tbl->user_data = [];   //data for custom jobs?
			$userData = (array) $userData;
			if (!empty($userData)) {
				foreach ($userData as $key => $value) {
					$userData_tbl->user_data[$key] = $value;
				}
			}
			$userData_tbl->user_data = new Registry($userData_tbl->user_data);
			$userData_tbl->user_data = (string) $userData_tbl->user_data;
			$db->insertObject('#__quiztools_results_users', $userData_tbl);

			// get questions ids for new quiz:
			if ($quiz->question_pool == 'random') {
				$questionsIdsObjs = $this->getQuizQuestions(0, null, 'rand()', $quiz->question_pool_randon_qty, false);
			} elseif ($quiz->question_pool == 'by_categories') {
				$questionsIdsObjs = [];
				foreach ($quiz->question_pool_categories as $pool_category) {
					$questions_by_category = $this->getQuizQuestions(0, $pool_category->category_id, 'rand()', $pool_category->questions_qty, false);
					$questionsIdsObjs = array_merge($questionsIdsObjs, $questions_by_category);
				}
			} else { // =='no': pool is not used
				$questionsIdsObjs = $this->getQuizQuestions($quiz->id);
			}

			if (count($questionsIdsObjs) < 1) {
				return [];
			}

			if ($quiz->shuffle_questions) {
				shuffle($questionsIdsObjs);
			}

			// Creating and recording a chain of quiz questions:
			$chain = '';
			foreach ($questionsIdsObjs as $question) {
				$chain .= $question->id . '*';
			}
			$chain = rtrim($chain,'*');

			$chain_tbl = new \stdClass();
			$chain_tbl->quiz_id = $quiz->id;
			$chain_tbl->user_id = $user->id;
			$chain_tbl->chain = $chain;
			$chain_tbl->unique_id = $unique_id;
            $chain_tbl->result_quiz_id = $resultQuizId;
			$db->insertObject('#__quiztools_results_chains', $chain_tbl);

			if ($quiz->questions_on_page == 0) { //One question per page (default)
				$questionsIdsSet = [$questionsIdsObjs[0]->id];
			} elseif ($quiz->questions_on_page == 1) {  //All questions on one page
				$questionsIdsSet = [];
				foreach ($questionsIdsObjs as $question) {
					$questionsIdsSet[] = $question->id;
				}
			}

			$result['questionsCountTotal'] = count($questionsIdsObjs);
			$result['sumTimeSpent'] = 0;
			$result['numberCurrentQuestion'] = 1;
			$result['currentQuestionId'] = $questionsIdsObjs[0]->id;
			$result['firstQuestionId'] = $questionsIdsObjs[0]->id;
			$result['lastQuestionId'] = $questionsIdsObjs[count($questionsIdsObjs) - 1]->id;
            $result['unansweredQuestionsIds'] = explode('*', $chain);

            // If a question pool is used, the "total score" must be calculated and updated in the results table
            if ($quiz->question_pool !== 'no') {
                /** @var QuestionModel $modelQuestion */
                $modelQuestion = $app->bootComponent('com_quiztools')->getMVCFactory()
                    ->createModel('Question', 'Administrator', ['ignore_request' => true]);

                $total_score = $modelQuestion->getTotalScoreOfQuestionsSet($questionsIdsObjs);

                if (!empty($total_score)) {
                    // update a new "result":
                    $resultQuizUpd = new \stdClass();
                    $resultQuizUpd->id = $resultQuizId;
                    $resultQuizUpd->total_score = $total_score;
                    $db->updateObject('#__quiztools_results_quizzes', $resultQuizUpd, 'id');
                }
            }

            unset($questionsIdsObjs);
		}
        else {  // Continue old quiz

			// Get a previously saved chain of questions for this quiz:
			$query->clear();
			$query->select($db->qn('ch.chain'))
				->from($db->qn('#__quiztools_results_chains', 'ch'))
				/*
				->join(
					'INNER',
					$db->qn('#__quiztools_results_quizzes', 'r'),
					$db->qn('r.unique_id') . ' = ' . $db->qn('ch.unique_id')
				)
				->where($db->qn('r.id') . ' = :id')
				->bind(':id', $resultQuizId, ParameterType::INTEGER)
				*/
                ->where($db->qn('ch.result_quiz_id') . ' = :resultQuizId')
                ->bind(':resultQuizId', $resultQuizId, ParameterType::INTEGER)
            ;
			$db->setQuery($query);
			$chain = $db->loadResult();

			$chain_ids = [];
			if (!empty($chain)) {
				$chain_ids = explode('*', $chain);
			}

            // Get IDs of answered questions:
            $query->clear();
            $query->select($db->qn('question_id'))
                ->from($db->qn('#__quiztools_results_questions'))
                ->where($db->qn('result_quiz_id') . ' = :resultQuizId')
                ->bind(':resultQuizId', $resultQuizId, ParameterType::INTEGER)
                ->order($db->qn('id') . ' ASC');
            $db->setQuery($query);
            $answeredQuestionsIDs = $db->loadColumn();

            if (!empty($answeredQuestionsIDs)) {
                $last_answered_question_id = $answeredQuestionsIDs[count($answeredQuestionsIDs) - 1];
                $result['unansweredQuestionsIds'] = array_values(array_diff($chain_ids, $answeredQuestionsIDs));
            } else {
                $last_answered_question_id = null;
                $result['unansweredQuestionsIds'] = $chain_ids;
            }

			$next_question_id = !empty($chain_ids[0]) ? $chain_ids[0] : 0;
			$number_next_question = 1;
			if (!empty($chain_ids) && !empty($last_answered_question_id)) {
				foreach ($chain_ids as $i => $chain_id) {
					if ($chain_id == $last_answered_question_id) {
						$next_question_id = isset($chain_ids[$i+1]) ? $chain_ids[$i+1] : $chain_ids[$i];
						$number_next_question = isset($chain_ids[$i+1]) ? $i+2 : $i+1;
						break;
					}
				}
			}

			if ($quiz->questions_on_page == 0) {        //One question per page (default)
				$questionsIdsSet = [$next_question_id];
			} elseif ($quiz->questions_on_page == 1) {  //All questions on one page
				$questionsIdsSet = $chain_ids;
				for ($i = 0; $i < count($questionsIdsSet); $i++) {
					if ($questionsIdsSet[$i] != $next_question_id) {
						unset($questionsIdsSet[$i]);
					} else {
						break;
					}
				}
				$questionsIdsSet = array_values($questionsIdsSet);
			}

			$result['questionsCountTotal'] = !empty($chain_ids) ? count($chain_ids) : 0;
			$result['sumTimeSpent'] = strtotime($resultQuiz->current_time) - strtotime($resultQuiz->start_datetime);
			$result['numberCurrentQuestion'] = $number_next_question;
			$result['currentQuestionId'] = $next_question_id;
			$result['firstQuestionId'] = $next_question_id;
			$result['lastQuestionId'] = $questionsIdsSet[count($questionsIdsSet) - 1];
		}

		$result['resultQuizId'] = $resultQuizId;
		$result['uniqueId'] = $unique_id;
		$result['task'] = 'start';
		$result['questions'] = $this->prepareQuestionsData($resultQuizId, $questionsIdsSet);

		return $result;
	}

    /**
     * Quiz 'Next' question
     *
     * @param string $action 'action' with a quiz
     * @return array
     * @throws \Exception
     */
	public function quizNext($action = 'next')
	{
		$app = Factory::getApplication();
		$input = $app->getInput();
		$user = $this->getCurrentUser();

		$data = $input->get('quiz', [], 'ARRAY');
        // extra data created in some question types by form markup:
        if (isset($data['question']['options'])) {
            unset($data['question']['options']);
        }

		$quiz_id = (int) $data['id'] ?: 0;
		$resultQuizId = (int) $data['resultQuizId'] ?: 0;
		$unique_id = $data['uniqueId'] ? htmlspecialchars($data['uniqueId'], ENT_QUOTES, 'UTF-8') : null;
		$questions = $data['question'] ?: [];

		if (empty($quiz_id) || empty($resultQuizId) || empty($unique_id) || empty($questions)) {
			throw new \Exception(Text::_('COM_QUIZTOOLS_QUIZ_ERROR_QUIZ_NOT_FOUND'));
		}

		/** @var QuizModel $model_quiz */
		$model_quiz = $app->bootComponent('com_quiztools')->getMVCFactory()
			->createModel('Quiz', 'Site', ['ignore_request' => true]);
		$quiz = $model_quiz->getItem($quiz_id);

		if (empty($quiz->id)) {
			throw new \Exception(Text::_('COM_QUIZTOOLS_QUIZ_ERROR_QUIZ_NOT_FOUND'));
		}

		$db = $this->getDatabase();
		$query = $db->createQuery();

		$query->select('*')
			->from($db->qn('#__quiztools_results_quizzes'))
			->where($db->qn('id') . ' = :id')
			->bind(':id', $resultQuizId, ParameterType::INTEGER);
		$db->setQuery($query);
		$quizResult = $db->loadObject();

		if (empty($quizResult)
			|| ($quizResult->quiz_id != $quiz_id)
				|| ($quizResult->unique_id != $unique_id)
					|| ($quizResult->user_id != $user->id)
		) {
			throw new \Exception(Text::_('COM_QUIZTOOLS_QUIZ_ERROR_QUIZ_NOT_FOUND'));
		}

		$result = [];
		$result['resultQuizId'] = $resultQuizId;
		$result['uniqueId'] = $unique_id;

        if ($action !== 'skip') {
            foreach ($questions as $question) {
                $savedQuestion = $this->saveAnswer($resultQuizId, $question);
                if (empty($savedQuestion->id)) {
                    continue;
                }

                // Get question's feedback:
                if ($action !== 'prev') {
                    if ($quiz->feedback_question && $savedQuestion->feedback) {
                        if (!isset($result['questionsFeedback'])) {
                            $result['questionsFeedback'] = [];
                        }

                        $result['questionsFeedback'][$savedQuestion->id] = $this->getQuestionFeedback($quiz, $savedQuestion);
                    }
                }
            }
        }

        $utcNow = new \DateTime('now', new \DateTimeZone('UTC'));
        $startTime = new \DateTime($quizResult->start_datetime, new \DateTimeZone('UTC'));  // `start_datetime` in UTC
        $userTimeSpent = $utcNow->getTimestamp() - $startTime->getTimestamp();

        $result['sumTimeSpent'] = $userTimeSpent;

		//Time limit check (only for the option "Allow users to continue unfinished quiz - No")
		if (!$quiz->allow_continue && $quiz->timer_show && $quiz->limit_time) {
			if ($userTimeSpent > ($quiz->limit_time * 60)) {
				if ($this->setQuizFinished($quizResult, $quiz, true)) {
					$result['task'] = 'timeIsUp';
					return $result;
				}
			}
		}

		// Get a previously saved chain of questions for this quiz:
		$query->clear();
		$query->select($db->qn('chain'))
			->from($db->qn('#__quiztools_results_chains'))
			->where($db->qn('unique_id') . '=' . $db->q($unique_id))
            ->where($db->qn('result_quiz_id') . '=' . $db->q($resultQuizId))
        ;
		$db->setQuery($query);
		$chain = $db->loadResult();

		if (empty($chain)) {
			throw new \Exception(Text::_('COM_QUIZTOOLS_QUIZ_ERROR_QUIZ_QUESTIONS_NOT_FOUND'));
		}

		// Get IDs of answered questions:
		$query->clear();
		$query->select($db->qn('question_id'))
			->from($db->qn('#__quiztools_results_questions'))
			->where($db->qn('result_quiz_id') . ' = :resultQuizId')
			->bind(':resultQuizId', $resultQuizId, ParameterType::INTEGER)
			->order($db->qn('id') . ' ASC');
		$db->setQuery($query);
		$answeredQuestionsIDs = $db->loadColumn();

		if (empty($answeredQuestionsIDs)) {
			$answeredQuestionsIDs = [];
		}

		$chainIds = explode('*', $chain);      //chain IDs
		$notAnsweredQuestionsIDs = array_values(array_diff($chainIds, $answeredQuestionsIDs));

        $result['unansweredQuestionsIds'] = $notAnsweredQuestionsIDs;

        if ($action === 'finish') {
            if (empty($notAnsweredQuestionsIDs) || $quiz->skip_questions === 2) {  // Enable skip questions: Yes, and allow submit quiz with not answered questions
                $this->setQuizFinished($quizResult, $quiz);
                $result['task'] = 'result';
                return $result;
            } else {
                $action = 'next';
            }
        }

        // 'Next' && 'Prev' can only be on a page with one question.

        foreach ($questions as $id => $question) {
            $last_answered_question_id = $id;
            break;
        }
        $key_last_answered_in_chain = array_search($last_answered_question_id, $chainIds);

        if (\in_array($action, ['next', 'skip'])) {
            $next_question_id = !empty($chainIds[$key_last_answered_in_chain + 1])
                ? $chainIds[$key_last_answered_in_chain + 1]
                // loop back to the beginning of the chain:
                : (!empty($notAnsweredQuestionsIDs[0]) ? $notAnsweredQuestionsIDs[0] : $chainIds[0]);
        }

        if ($action === 'prev') {
            // We go only to the first question. There is no loop to the end of unanswered questions after 'prev' in first question.
            $next_question_id = !empty($chainIds[$key_last_answered_in_chain - 1]) ? $chainIds[$key_last_answered_in_chain - 1] : $chainIds[0];
        }

        $result['numberCurrentQuestion'] = array_search($next_question_id, $chainIds) + 1;
        $result['currentQuestionId'] = $next_question_id;
        $result['task'] = $action;
        $result['questions'] = $this->prepareQuestionsData($resultQuizId, [$next_question_id]);

		return $result;
	}

    /**
     * Quiz 'Prev' question
     *
     * @return array
     * @throws \Exception
     */
    public function quizPrev()
    {
        return $this->quizNext('prev');
    }

    /**
     * Quiz 'Skip' question
     *
     * @return array
     * @throws \Exception
     */
    public function quizSkip()
    {
        // Like 'Next', but without saving the question.
        return $this->quizNext('skip');
    }

    /**
     * Quiz 'Finish'
     *
     * @return array
     * @throws \Exception
     */
    public function quizFinish()
    {
        return $this->quizNext('finish');
    }

    /**
     * Get quiz questions.
     *
     * @param integer $quiz_id Quiz ID
     * @param integer|null $catid Quiz category ID
     * @param string|array $order Sorting order in query
     * @param integer|null $limit Limit in query
     * @param boolean $only_ids Flag for receiving only Ids
     * @return array|mixed
     * @throws \Exception
     */
	private function getQuizQuestions($quiz_id = 0, $catid = null, $order = ['q.ordering', 'q.id'], $limit = null, $only_ids = true)
	{
		$db = $this->getDatabase();
		$query = $db->createQuery();

		if ($only_ids) {
			$query->select($db->qn('id'));
		} else {
			$query->select('*');
		}

		$query->from($db->qn('#__quiztools_questions', 'q'))
			->join(
				'INNER',
				$db->qn('#__extensions', 'e'),
				$db->qn('e.element') . ' = ' . $db->qn('q.type')
			)
			->where($db->qn('q.quiz_id') . ' = :quizId')
			->where($db->qn('q.state') . ' = ' . $db->q(1))
			->where($db->qn('e.type') . ' = ' . $db->q('plugin'))
			->where($db->qn('e.enabled') . ' = ' . $db->q(1))
			->where($db->qn('e.folder') . ' = ' . $db->q('quiztools'))
			->bind(':quizId', $quiz_id, ParameterType::INTEGER)
			->order($order);

		if (!empty($catid)) {
			$query->where($db->qn('q.catid') . ' = :catId')
				->bind(':catId', $catid, ParameterType::INTEGER);
		}

		if (!empty($limit)) {
			$query->setLimit((int) $limit);
		}

		$db->setQuery($query);

		try {
			$questions = $db->loadObjectList();
		} catch (\Exception $e) {
			Factory::getApplication()->enqueueMessage(
				Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()),
				'warning'
			);

			return [];
		}

		return $questions;
	}

    /**
     * Preparation of processed question data.
     *
     * @param integer $resultQuizId The result of passing the quiz.
     * @param array $questions_ids Ids of questions to prepare
     * @return array|mixed
     */
	private function prepareQuestionsData($resultQuizId = 0, $questions_ids = [])
	{
		if (empty($resultQuizId) || empty($questions_ids)) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->createQuery();

		$query->select($db->qn('id'))
			->select($db->qn('quiz_id', 'quizId'))
			->select($db->qn(['type', 'text', 'attempts']))
			->select($db->qn('points', 'pointsQuestion'))
			->from($db->qn('#__quiztools_questions', 'q'))
			->where($db->qn('q.id') . " IN ('".implode("','", $questions_ids)."')");
		$db->setQuery($query);
		$questions = $db->loadObjectList();

		if (empty($questions)) {
			return [];
		}

		$dispatcher = $this->getDispatcher();
		PluginHelper::importPlugin('content', null, true, $dispatcher);
		PluginHelper::importPlugin('quiztools', null, true, $dispatcher);

		foreach ($questions as $question) {
			$query->clear();
			$query->select($db->qn(['id', 'attempts']))
				->from($db->qn('#__quiztools_results_questions'))
				->where($db->qn('result_quiz_id') . ' = :resultQuizId')
				->where($db->qn('question_id') . ' = :questionId')
				->bind(':resultQuizId', $resultQuizId, ParameterType::INTEGER)
				->bind(':questionId', $question->id, ParameterType::INTEGER);
			$db->setQuery($query);
			$result_question = $db->loadObject();

			$question->resultQuestionId = !empty($result_question->id) ? $result_question->id : null;

            // Does this question have any attempts left?
            $question->noAttemptsLeft = 0;  // Yes
            if (!empty($question->attempts)) {
                $attemptsMade = !empty($result_question->attempts) ?: 0;
                $question->noAttemptsLeft = ((int) $attemptsMade >= (int) $question->attempts) ? 1 : 0;
            }

            // The point at which content plugins process the question text.
			$question->text = $dispatcher->dispatch(
				'onContentPrepare',
				new Content\ContentPrepareEvent('onContentPrepare', [
					'context' => 'com_quiztools.question.prepareData',
					'subject' => $question,
					'params'  => new \stdClass(),
					'page'    => 0,
				])
			)->getArgument('result', $question)->text;
			// after 'onContentPrepare' => plugins/system/fields :
			if (isset($question->jcfields)) {
                unset($question->jcfields);
            }

			// Get points (score) from the question options:
			$question->pointsOptions = $dispatcher->dispatch(
				'onQuestionOptionsGetScore',
				new Model\PrepareDataEvent('onQuestionOptionsGetScore', [
					'context' => 'com_quiztools.question.options.score',
					'data'    => $question,
					'subject' => new \stdClass(),
				])
			)->getArgument('result', 0);

			$question->points = (float) $question->pointsQuestion + (float) $question->pointsOptions;
			unset($question->pointsQuestion);
			unset($question->pointsOptions);

			// Get question's options HTML (+$question->options):
			$question = $dispatcher->dispatch(
				'onQuestionOptionsGetHtml',
				new Model\PrepareDataEvent('onQuestionOptionsGetHtml', [
					'context' => 'com_quiztools.question.options.html',
					'data'    => $question,
					'subject' => new \stdClass(),
				])
			)->getArgument('result', $question);

			// The point at which the question changes before it is rendered.
			$question = $dispatcher->dispatch(
				'onQuestionAfterLoad',
				new Model\PrepareDataEvent('onQuestionAfterLoad', [
					'context' => 'com_quiztools.question.afterLoad',
					'data'    => $question,
					'subject' => new \stdClass(),
				])
			)->getArgument('result', $question);
		}

		return $questions;
	}

    /**
     * Saving the answer to the question.
     *
     * @param integer $resultQuizId The result of passing the quiz.
     * @param array $answerToQuestion Answer to question
     * @return mixed|\stdClass
     */
	private function saveAnswer($resultQuizId, $answerToQuestion)
	{
		$question_id = (int) $answerToQuestion['id'] ?: 0;
		$questionData = !empty($answerToQuestion['answer'])
			? json_decode($answerToQuestion['answer'], false)  //example: {"type":"mchoice","answer":"2"}
			: [];

		if (empty($resultQuizId) || empty($question_id) || empty($questionData)) {
			return new \stdClass();
		}

		$db = $this->getDatabase();
		$query = $db->createQuery();
		$query->select('q.*')
			->from($db->qn('#__quiztools_questions', 'q'))
			->where($db->qn('q.id') . ' = :questionId')
			->where($db->qn('q.state') . ' = 1')
			->bind(':questionId', $question_id, ParameterType::INTEGER);

        if ($questionData->type !== 'boilerplate') {
            $query->select('qt.*');
            $query->join(
                'INNER',
                $db->qn('#__quiztools_questions_' . htmlspecialchars($questionData->type), 'qt'),
                $db->qn('qt.question_id') . '=' . $db->qn('q.id')
            );
        }

		$db->setQuery($query);
		$question = $db->loadObject();

		if (empty($question)) {
			return new \stdClass();
		}

        // The 'id' field is present in both tables in the query above and will be overridden.
        if ($questionData->type !== 'boilerplate') {
            $question->id = !empty((int) $question->question_id) ? (int) $question->question_id : $question->id;
        }

		$question->resultQuizId = $resultQuizId;
		$question->answer = !empty($questionData->answer) ? $questionData->answer : null;

		$dispatcher = $this->getDispatcher();
		PluginHelper::importPlugin('quiztools', null, true, $dispatcher);

		$savedQuestion = $dispatcher->dispatch(
			'onQuestionSaveAnswer',
			new Model\PrepareDataEvent('onQuestionSaveAnswer', [
				'context' => 'com_quiztools.question.saveAnswer',
				'data'    => $question,
				'subject' => new \stdClass(),
			])
		)->getArgument('result', $question);

		return $savedQuestion;
	}

    /**
     * Updating the quiz results upon completion.
     *
     * @param object $quizResult The result of passing the quiz.
     * @param object $quiz  A quiz that was passed.
     * @param boolean $timeIsUp  "Time is up" flag, if applicable.
     * @return boolean
     */
	private function setQuizFinished($quizResult, $quiz, $timeIsUp = false)
	{
		if (empty($quizResult) || empty($quiz)) {
			return false;
		}

        $db = $this->getDatabase();
        $query = $db->createQuery();
        $query->select('SUM(`points_received`)')
            ->from($db->qn('#__quiztools_results_questions'))
            ->where($db->qn('result_quiz_id') . ' = :resultQuizId')
            ->bind(':resultQuizId', $quizResult->id, ParameterType::INTEGER);
        $db->setQuery($query);
        $receivedPointsForAnsweredQuestions = $db->loadResult();

        $utc = new \DateTime('now', new \DateTimeZone('UTC'));
        $startDatetime = new \DateTime($quizResult->start_datetime, new \DateTimeZone('UTC'));  // `start_datetime` in UTC
        $userTimeSpent = $utc->getTimestamp() - $startDatetime->getTimestamp();

        $passing_score = round($quizResult->total_score * ($quizResult->passing_score / 100), 2);
        $is_passed = (float) $receivedPointsForAnsweredQuestions >= $passing_score ? 1 : 0;
        if ($timeIsUp) {
            $is_passed = 0;
        }

        $query->clear()
            ->update($db->qn('#__quiztools_results_quizzes'))
            ->set($db->qn('sum_points_received') . '=' . (float) $receivedPointsForAnsweredQuestions)
            ->set($db->qn('sum_time_spent') . '=' . $userTimeSpent)
            ->set($db->qn('passed') . '=' . $db->q($is_passed))
            ->set($db->qn('finished') . '=' . $db->q(1))
            ->where($db->qn('id') . ' = :id')
            ->bind(':id', $quizResult->id, ParameterType::INTEGER);
        $db->setQuery($query)->execute();

		return true;
	}

    /**
     *  Getting feedback on a question.
     *
     *  Feedback return logic:
     *  1) feedback text - in language constants
     *  2) in the quiz - "Enable Question feedback": "Yes" to display feedback after each question in this quiz.
     *     Feedback text from the quiz overrides language constants.
     *  3) in the question - "Enable Question feedback": Will override 'Enable Question feedback' from the quiz settings for the current question.
     *     Feedback text from the question overrides feedback text from the quiz.
     *  4) The selected answer option may have its own additional feedback (for example, the Multiple Choice question type).
     *     This additional feedback is added after the main feedback.
     *
     * @param object $quiz  The quiz the question relates to.
     * @param object $savedQuestion  The question we are receiving feedback on.
     * @return array
     */
	public function getQuestionFeedback($quiz, $savedQuestion)
	{
		$return = [
			'class' => '',
			'text' => '',
		];

		if (empty($quiz->id) || !$savedQuestion->id || $savedQuestion->type === 'boilerplate') {
			return $return;
		}

		$feedback = [
            'correct' => [
                'class' => 'feedback-correct',
                'text' => Text::_('COM_QUIZTOOLS_QUIZ_FEEDBACK_CORRECT'),
            ],
            'incorrect' => [
                'class' => 'feedback-incorrect',
                'text' => Text::_('COM_QUIZTOOLS_QUIZ_FEEDBACK_INCORRECT'),
            ],
            'partially_correct' => [
                'class' => '',
                'text' => '',
            ],
        ];

		// Feedback from the quiz settings overrides language constants:
        if (!empty($quiz->feedback_msg_right)) {
            $feedback['correct']['text'] = $quiz->feedback_msg_right;
        }
        if (!empty($quiz->feedback_msg_wrong)) {
            $feedback['incorrect']['text'] = $quiz->feedback_msg_wrong;
        }

		// Feedback from the question settings overrides feedback from the quiz settings:
        if (!empty($savedQuestion->feedback_msg_right)) {
            $feedback['correct']['text'] = $savedQuestion->feedback_msg_right;
        }
        if (!empty($savedQuestion->feedback_msg_wrong)) {
            $feedback['incorrect']['text'] = $savedQuestion->feedback_msg_wrong;
        }

		if (isset($savedQuestion->partial_score)) {
            if ($savedQuestion->partial_score) {
                $feedback['partially_correct']['text'] = Text::_('COM_QUIZTOOLS_QUIZ_FEEDBACK_PARTIALLY_CORRECT');
                if (isset($savedQuestion->feedback_partial_score) && trim($savedQuestion->feedback_partial_score)) {
                    $feedback['partially_correct']['text'] = $savedQuestion->feedback_partial_score;
                }
                $feedback['partially_correct']['class'] = 'feedback-partially-correct';
            } else {
                $feedback['partially_correct'] = $feedback['incorrect'];
            }
		} else {
            $feedback['partially_correct'] = $feedback['incorrect'];
        }

        if ($savedQuestion->savedAnswerResult['is_correct']) {
            $return = $feedback['correct'];
        } else {
            $return = $feedback['incorrect'];
        }

        if (!empty($savedQuestion->savedAnswerResult['is_partially_correct'])) {
            $return = $feedback['partially_correct'];
        }

        if (!empty($savedQuestion->savedAnswerResult['feedbackOfOptionAnswer'])) {
            $return['text'] .= '<br>' . $savedQuestion->savedAnswerResult['feedbackOfOptionAnswer'];
        }

        // Processing question's feedback with content plugins
        if (!empty($return['text'])) {
            $dispatcher = $this->getDispatcher();
            PluginHelper::importPlugin('content', null, true, $dispatcher);

            $article = new \stdClass();
            $article->text = $return['text'];
            $article = $dispatcher->dispatch(
                'onContentPrepare',
                new Content\ContentPrepareEvent('onContentPrepare', [
                    'context' => 'com_quiztools.question.feedback.prepareData',
                    'subject' => $article,
                    'params'  => new \stdClass(),
                    'page'    => 0,
                ])
            )->getArgument('result', $article)->text;
            if (!empty($article->text)) {
                $return['text'] = $article->text;
            }
            unset($article);
        }

		return $return;
	}

    /**
     * Quiz Result
     *
     * @return array
     * @throws \Exception
     */
    public function quizResult()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();

        $data = $input->get('quiz', [], 'ARRAY');
        $resultQuizId = (int) $data['resultQuizId'] ?: 0;

        /** @var ResultModel $modelResult */
        $modelResult = $app->bootComponent('com_quiztools')->getMVCFactory()
            ->createModel('Result', 'Administrator', ['ignore_request' => true]);

        // Forces the populateState() method to be called (and filling the '__state_set' property).
        // If it is called later, it will override the model's State.
        $modelResult->getState();

        $modelResult->setState('result.id', $resultQuizId);
        $modelResult->setState('result.layout', 'result');
        $result = $modelResult->getItem();

        $return = [
            'html' => LayoutHelper::render('result', ['result' => $result]),
            'redirect' => [
                'redirectAfterFinish' => $result->redirect_after_finish,
                'redirectAfterFinishLink' => $result->redirect_after_finish_link,
                'redirectAfterFinishDelay' => $result->redirect_after_finish_delay,
            ],
        ];

        return $return;
    }
}
