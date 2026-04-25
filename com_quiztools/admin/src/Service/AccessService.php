<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Service;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Crypt\Crypt;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Filter\InputFilter;
use Qt\Component\Quiztools\Administrator\Extension\QuiztoolsComponent;
use Qt\Component\Quiztools\Site\Helper\RouteHelper;
use Qt\Component\Quiztools\Site\Model\OrdersModel;

/**
 * QuizTools "Access" helper
 *
 * @since  1.1.0
 */
class AccessService
{
    /**
     * Current ajax Quiz action
     * @var null|string
     * @since 1.2.0
     */
    public $ajaxQuizAction = null;

	/**
	 *  Check access
	 *
	 * @param string $view View/Entity
	 * @param int $pk Primary key
	 * @return bool
     * @since 1.0.0
	 */
    public function isAccess($view = null, $pk = null)
    {
		if (empty($view)) {
			return false;
		}

		$method = 'isAccess' . ucfirst(strtolower($view));

		if (method_exists($this, $method)) {
			return $this->$method($pk);
		} else {
			return false;
		}
    }

	/**
	 * Checking access to the quiz
	 *
	 * @param int  $pk  Primary key
     * @param int  $order_id  Order Id
	 * @return bool
     * @since 1.0.0
	 */
	public function isAccessQuiz($pk, $order_id = 0)
	{
		$pk = (int) $pk;
		$user = Factory::getApplication()->getIdentity();
		$input = Factory::getApplication()->getInput();

        if (is_null($order_id)) {  // from components/com_quiztools/layouts/result.php
            $order_id = 0;
        }
        if (empty($order_id)) {
            $order_id = $input->getInt('order_id', 0);
        }

		if (empty($pk)) {
			$pk = $input->getInt('id');

			if (empty($pk) && ($input->get('task') == 'ajaxQuiz.getQuizData')) {
				$ajaxData = $input->get('quiz', [], 'ARRAY');
				$pk = !empty((int) $ajaxData['id']) ? (int) $ajaxData['id'] : 0;
                $order_id = isset($ajaxData['orderId']) ? (int) $ajaxData['orderId'] : 0;
                $lp = !empty($ajaxData['lp']) ? json_decode($ajaxData['lp'], true) : [];

                $this->ajaxQuizAction = !empty($ajaxData['action']) ? $ajaxData['action'] : null;
			}
		}

		if (empty($pk)) {
			return false;
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->createQuery()
			->select($db->qn(['a.id', 'a.type_access', 'a.access', 'a.allow_continue']))
			->select($db->qn(['a.limit_attempts', 'a.attempts_reset_period', 'a.attempts_reset_next_day']))
			->from($db->qn('#__quiztools_quizzes', 'a'))
			->join(
				'INNER',
				$db->qn('#__categories', 'c'),
				$db->qn('c.id') . ' = ' . $db->qn('a.catid')
			)
			->where(
				[
					$db->qn('a.id') . ' = :pk',
					$db->qn('a.state') . ' = 1',
					$db->qn('c.published') . ' = 1',
				]
			)
			->bind(':pk', $pk, ParameterType::INTEGER)
		;

		$quiz = $db->setQuery($query)->loadObject() ?: null;

		if ($quiz === null) {
			return false;
		}

		// Check access level
		$groups = $user->getAuthorisedViewLevels();
		if (!in_array($quiz->access, $groups)) {
			return false;
		}

		// Checking attempts for free access
        if ($quiz->type_access == QuiztoolsComponent::CONDITION_TYPE_ACCESS_FREE) {
            if ($quiz->limit_attempts) {
                if (empty($this->checkQuizAttempts($quiz))) {
                    return false;
                }
            }
        }
		// Check paid access
		else if ($quiz->type_access == QuiztoolsComponent::CONDITION_TYPE_ACCESS_PAID) {
            $quiz->order_id = $order_id;

            // A quiz included in the Learning Path loaded in an iframe
            if (!empty($lp['id'])) {  // Ajax request
                $lpath_id = (int) $lp['id'];
            } else {                  // Loading the first screen of the quiz - description
                $lp = $input->get('lp', [], 'ARRAY');
                $lpath_id = !empty($lp['id']) ? (int) $lp['id'] : null;
            }

			if (empty($this->checkPaidQuizAccess($quiz, $lpath_id))) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if the number of attempts for this FREE quiz has expired.
	 *
	 * @param object $quiz
	 * @return bool
	 * @throws \Exception
     * @since 1.0.0
	 */
	private function checkQuizAttempts($quiz)
	{
		$user = Factory::getApplication()->getIdentity();
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->createQuery();

		$cookie_quizupi = Factory::getApplication()->getInput()->cookie->get('quizupi');
        $where = [];
        $where[] = $db->q(1) . '=' . $db->q(1);
		if ($user->guest && !empty($cookie_quizupi[$quiz->id])) {
			$unique_id = $cookie_quizupi[$quiz->id];
			$where[] = $db->qn('unique_id') . '=' . $db->q($unique_id);
		} else if ($user->id) {
			$where[] = $db->qn('user_id') . '=' . $db->q($user->id);
		}

		$number_times_passed = $this->getQuizAttempts($quiz, $where);

		if ((int) $number_times_passed < (int) $quiz->limit_attempts) {
			return true;
		} else if ($quiz->attempts_reset_period) {
			$query->clear()
				->select($db->qn('start_datetime'))
				->from($db->qn('#__quiztools_results_quizzes'))
				->where($db->qn('quiz_id') . ' = :quizId')
				->bind(':quizId', $quiz->id, ParameterType::INTEGER);
			if ($quiz->allow_continue) {
				$query->where($db->qn('finished') . '=' . $db->q(1));
			}
			$query->where($where)
				->order('`id` DESC')
				->setLimit($quiz->limit_attempts);
			$db->setQuery($query);
			$user_tries = $db->loadColumn();

            if (empty($user_tries)) {
                return true;
            }

            $last_user_tries = $user_tries[count($user_tries)-1];  // in UTC

            $userTimezone = $user->getParam('timezone', Factory::getApplication()->getConfig()->get('offset', 'UTC'));
            $userTimezone = new \DateTimeZone($userTimezone);

            $dt = new \DateTime($last_user_tries, new \DateTimeZone('UTC'));
            $dt->setTimezone($userTimezone);
            $curDay = $dt->format('Y-m-d');
            $dt->modify($quiz->attempts_reset_period * 60);

            $now = new \DateTime('now', $userTimezone);

            if ($dt > $now) {
				if ($quiz->attempts_reset_next_day && $curDay !== $now->format('Y-m-d')) {
					return true;
				}
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}

    /**
     * Retrieving quiz attempt count.
     *
     * @param object $quiz
     * @param array $where
     * @return int
     * @throws \Exception
     * @since 1.0.0
     */
    private function getQuizAttempts($quiz, $where = [])
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()
            ->select('COUNT(*)')
            ->from($db->qn('#__quiztools_results_quizzes'))
            ->where($db->qn('quiz_id') . ' = :quizId')
            ->bind(':quizId', $quiz->id, ParameterType::INTEGER);

        if ($quiz->allow_continue) {
            $query->where($db->qn('finished') . '=' . $db->q(1));
        }

        if (is_array($where) && !empty($where)) {
            $query->where($where);
        }

        $number_times_passed = $db->setQuery($query)->loadResult() ?: 0;

        // After completing the quiz, the results page loads.
        // At this point, the current attempt is already recorded in the database.
        // To pass the access check for the quiz results page, we'll reduce the total number of attempts by one.
        if (!empty((int) $number_times_passed) && $this->ajaxQuizAction === 'result') {
            $number_times_passed = (int) $number_times_passed - 1;
            // After using the results page request flag, we'll remove it.
            // This is because when generating the results page, there's a check to see if this quiz can be taken again.
            // The number of attempts must be up to date.
            $this->ajaxQuizAction = null;
        }

        return $number_times_passed;
    }

    /**
     * Checking access to the results.
     * Results are available only to authorized users.
     *
     * @return bool
     * @since 1.0.0
     */
    private function isAccessResults()
    {
        $user = Factory::getApplication()->getIdentity();

        if ($user->guest) {
            return false;
        }

        return true;
    }

    /**
     * Checking access to the result
     *
     * The result is available:
     * - only to authorized users;
     * - either to the user whose result it is, or to the admin.
     * ToDo: "teacher" sees "students"
     *
     * @param   int  $pk  Primary key
     * @return bool
     * @since 1.0.0
     */
    private function isAccessResult($pk)
    {
        $pk = (int) $pk;
        $input = Factory::getApplication()->getInput();

        if (empty($pk)) {
            $pk = $input->getInt('id');
        }

        if (empty($pk)) {
            return false;
        }

        $user = Factory::getApplication()->getIdentity();

        if ($user->guest) {
            return false;
        }

        if (!$user->authorise('core.admin', 'com_quiztools')) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->createQuery()
                ->select('1')
                ->from($db->qn('#__quiztools_results_quizzes'))
                ->where($db->qn('id') . ' = ' . $db->q($pk))
                ->where($db->qn('user_id') . ' = ' . $db->q((int) $user->id))
                ->where($db->qn('user_id') . ' > ' . $db->q(0));
            $allowed = $db->setQuery($query)->loadResult() ?: null;

            if (!$allowed) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get a token (for display) to access the result.
     *
     * @param object $result
     * @return string
     * @throws \Exception
     * @since 1.0.0
     */
    public function getResultTokenForDisplay($result)
    {
        $algorithm = 'sha256';
        $siteSecret = Factory::getApplication()->get('secret');

        $rawToken  = $result->unique_id . $result->start_datetime;
        $tokenHash = hash_hmac($algorithm, $rawToken, $siteSecret);
        $resultId  = $result->id;
        $message   = base64_encode("$algorithm:$resultId:$tokenHash");

        return $message;
    }

    /**
     * Checking the token to access the result.
     *
     * @param string $tokenString
     * @return bool
     * @since 1.0.0
     */
    public function checkResultToken($tokenString)
    {
        $filter = new InputFilter();
        $tokenString = $filter->clean($tokenString, 'BASE64');

        if (empty($tokenString)) {
            return false;
        }

        $authString = @base64_decode($tokenString);

        if (empty($authString) || (!str_contains($authString, ':'))) {
            return false;
        }

        $parts = explode(':', $authString, 3);

        if (\count($parts) != 3) {
            return false;
        }

        [$algo, $resultId, $tokenHMAC] = $parts;

        try {
            $siteSecret = Factory::getApplication()->get('secret');
        } catch (\Exception) {
            return false;
        }

        if (empty($siteSecret)) {
            return false;
        }

        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->createQuery();
            $query->select($db->qn(['start_datetime', 'unique_id']))
                ->from($db->qn('#__quiztools_results_quizzes'))
                ->where($db->qn('id') . ' = :id')
                ->bind(':id', $resultId, ParameterType::INTEGER);
            $db->setQuery($query);
            $result = $db->loadObject();
        } catch (\Exception) {
            return false;
        }

        if (empty($result->unique_id) || empty($result->start_datetime)) {
            return false;
        }

        $referenceTokenData = $result->unique_id . $result->start_datetime;
        $referenceHMAC = hash_hmac($algo, $referenceTokenData, $siteSecret);

        // Do the tokens match? Use a timing safe string comparison to prevent timing attacks.
        $hashesMatch = Crypt::timingSafeCompare($referenceHMAC, $tokenHMAC);

        return $hashesMatch;
    }

    /**
     * Checking access to Learning Paths
     *
     * @return bool
     * @since 1.1.0
     */
    private function isAccessLpaths()
    {
        $user = Factory::getApplication()->getIdentity();

        // Learning Paths are available only to authorized users.
        if ($user->guest) {
            return false;
        }

        return true;
    }

    /**
     * Checking access to the Learning Path.
     *
     * @param int  $pk  Primary key
     * @param int $order_id  Order ID
     * @return bool
     * @since 1.1.0
     */
    public function isAccessLpath($pk, $order_id = 0)
    {
        $pk = (int) $pk;
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $input = $app->getInput();

        if (empty($pk)) {
            $pk = $input->getInt('id');

            if (empty($pk) && ($input->get('task') == 'ajaxLpath.getLpathData')) {
                $ajaxData = $input->get('lpath', [], 'ARRAY');
                $pk = (int) $ajaxData['id'] ?: 0;
                $order_id = isset($ajaxData['orderId']) ? (int) $ajaxData['orderId'] : 0;
            }
        }

        if (empty($pk)) {
            return false;
        }

        // Learning Path is available only to authorized users.
        if ($user->guest) {
            return false;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()
            ->select($db->qn(['a.id', 'a.type_access', 'a.access']))
            ->from($db->qn('#__quiztools_lpaths', 'a'))
            ->join(
                'INNER',
                $db->qn('#__categories', 'c'),
                $db->qn('c.id') . ' = ' . $db->qn('a.catid')
            )
            ->where(
                [
                    $db->qn('a.id') . ' = :pk',
                    $db->qn('a.state') . ' = 1',
                    $db->qn('c.published') . ' = 1',
                ]
            )
            ->bind(':pk', $pk, ParameterType::INTEGER)
        ;

        $lpath = $db->setQuery($query)->loadObject() ?: null;

        if ($lpath === null) {
            return false;
        }

        // Check access level
        $groups = $user->getAuthorisedViewLevels();
        if (!in_array($lpath->access, $groups)) {
            return false;
        }

        // Check paid access
        if ($lpath->type_access == QuiztoolsComponent::CONDITION_TYPE_ACCESS_PAID) {
            $lpath->order_id = !empty($order_id) ? (int) $order_id : $input->getInt('order_id', 0);

            if (empty($this->checkPaidLpathAccess($lpath))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checking access to the orders
     *
     * @return bool
     * @since 1.2.0
     */
    private function isAccessOrders()
    {
        $user = Factory::getApplication()->getIdentity();

        // Orders are available only to authorized users
        if ((int) $user->id === 0) {
            return false;
        }

        return true;
    }

    /**
     * Get access order data.
     *
     * @param object $order
     * @return object
     * @since 1.2.0
     */
    public function getAccessOrderData(object $order)
    {
        $data = new \stdClass();
        $data->access = false;
        $data->start = $order->start_datetime;
        $data->end = $order->end_datetime;
        $data->attempts_max = $order->attempts_max;
        $data->attempts_used = 0;
        $data->attempts_used_byQuizzes = [];

        $user = Factory::getApplication()->getIdentity();

        if (empty($order->id)) {
            return $data;
        }

        if ((int) $order->user_id !== (int) $user->id) {
            if (empty($this->isOrderChildUser($order))) {
                return $data;
            }
        }

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $validStart = false;
        $start = new \DateTime($order->start_datetime, new \DateTimeZone('UTC'));
        if ($start <= $now) {
            $validStart = true;
        }

        $validEnd = false;
        if (is_null($order->end_datetime)) {  // "lifetime" access, unlimited days
            $validEnd = true;
        } else {
            $end = new \DateTime($order->end_datetime, new \DateTimeZone('UTC'));
            if ($now < $end) {
                $validEnd = true;
            }
        }

        $validAttempts = false;
        if ((int) $order->attempts_max === 0) {  // unlimited
            $validAttempts = true;
        } else {
            $orderUsedAttempts = $this->getOrderUsedAttempts($order);
            $usedAttempts = !empty($orderUsedAttempts['order']) ? (int) $orderUsedAttempts['order'] : 0;
            $usedAttemptsByQuizzes = !empty($orderUsedAttempts['quizzes']) ? $orderUsedAttempts['quizzes'] : [];
            if ((int) $usedAttempts < (int) $order->attempts_max) {
                $validAttempts = true;
            }
        }

        if ($validStart && $validEnd && $order->status === 'C') {
            if ($validAttempts) {
                $data->access = true;
            } else {
                if (!empty($order->type && $order->type === 'lpath')) {
                    // Check that all articles of the paid Learning Path have been completed
                    $lpath = new \stdClass();
                    $lpath->id = (int) $order->lpath_id;
                    $lpath->order_id = (int) $order->id;
                    if (!$this->allArticlesLpathCompleted($lpath)) {
                        $data->access = true;
                    }
                }
            }
        }

        $data->attempts_used = !empty($usedAttempts) ? (int) $usedAttempts : 0;
        $data->attempts_used_byQuizzes = !empty($usedAttemptsByQuizzes) ? $usedAttemptsByQuizzes : [];

        return $data;
    }

    /**
     * Checking whether the current user is a child user in the order
     * to which the subscription was shared.
     *
     * @param object $order
     * @return false|int
     * @throws \Exception
     * @since 1.2.0
     */
    private function isOrderChildUser($order)
    {
        $user = Factory::getApplication()->getIdentity();

        if ($user->guest) {
            return false;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()
            ->select($db->q(1))
            ->from($db->qn('#__quiztools_order_users'))
            ->where(
                [
                    $db->qn('order_id') . '=' . $db->q($order->id),
                    $db->qn('parent_user_id') . '=' . $db->q($order->user_id),
                    $db->qn('parent_user_id') . '!=' . $db->q($user->id),
                    $db->qn('user_id') . '=' . $db->q($user->id),
                ]
            );

        return $db->setQuery($query)->loadResult() ?: false;
    }

    /**
     * Receiving attempts made by the user to pass the quiz(zes) in the order.
     *
     * @param object $order
     * @return array
     * @throws \Exception
     * @since 1.2.0
     */
    public function getOrderUsedAttempts($order)
    {
        $usedAttempts = [
            'order' => (int) $order->attempts_max,   // Overall for the order
            'quizzes' => [],                         // By each quiz in the order
        ];

        $app = Factory::getApplication();

        if ($app->isClient('administrator') && !empty($order->user_id)) {
            $user_id = (int) $order->user_id;
        } else {
            $user = $app->getIdentity();
            $user_id = $user->id;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();

        $where = [];
        $where[] = $db->qn('user_id') . '=' . $db->q($user_id);
        $where[] = $db->qn('order_id') . '=' . $db->q($order->id);
        if (!is_null($order->reActivated)) {
            $where[] = $db->qn('start_datetime') . ' > ' . $db->q($order->reActivated);
        }

        if ($order->type === 'quiz') {
            $quizzesIds = [(int) $order->quiz_id];
            $quizzesData = $this->getQuizzesInOrderData($quizzesIds);

            $quiz = new \stdClass();
            $quiz->id = (int) $order->quiz_id;
            $quiz->allow_continue = !empty($quizzesData[$order->quiz_id]->allow_continue)
                ? (int) $quizzesData[$order->quiz_id]->allow_continue
                : 1;

            $quizUsedAttempts = $this->getQuizAttempts($quiz, $where);

            $usedAttempts['order'] = $quizUsedAttempts;
            $usedAttempts['quizzes'][$quiz->id] = $quizUsedAttempts;
        }
        else if ($order->type === 'lpath') {
            $query->clear()
                ->select($db->qn('lpath_items'))
                ->from($db->qn('#__quiztools_lpaths'))
                ->where($db->qn('id') . ' = :id')
                ->bind(':id', $order->lpath_id, ParameterType::INTEGER);
            $db->setQuery($query);
            $items = $db->loadResult();

            if (!empty($items)) {
                $items = json_decode($items, true);
            } else {
                return $usedAttempts;
            }

            $quizzesIds = [];
            foreach ($items as $item) {
                if ($item['type'] === 'q') {
                    $quizzes_ids[] = (int) $item['quiz_id'];
                }
            }

            if (empty($quizzes_ids)) {
                return $usedAttempts;
            }

            $quizzesData = $this->getQuizzesInOrderData($quizzesIds);

            foreach ($quizzes_ids as $quiz_id) {
                $quiz = new \stdClass();
                $quiz->id = (int) $quiz_id;
                $quiz->allow_continue = !empty($quizzesData[$quiz_id]->allow_continue) ? (int) $quizzesData[$quiz_id]->allow_continue : 1;

                $quizInOrderUsedAttempts = $this->getQuizAttempts($quiz, $where);
                $usedAttempts['quizzes'][$quiz->id] = $quizInOrderUsedAttempts;

                // Each quiz from LP can be completed as many times as indicated in the order.
                // The number of attempts made => the minimum number of attempts made for each quiz included in the Learning Path.
                if ((int) $quizInOrderUsedAttempts < (int) $usedAttempts['order']) {
                    $usedAttempts['order'] = $quizInOrderUsedAttempts;
                }
                // ... But if somehow (how?) a student passed one of the quizzes in the Learning Path more
                // than the maximum number of attempts set in the order, then we will highlight this number.
                if ((int) $quizInOrderUsedAttempts > (int) $order->attempts_max) {
                    $usedAttempts['order'] = $quizInOrderUsedAttempts;
                }
            }
        }

        return $usedAttempts;
    }

    /**
     * Receiving some quiz(zes) fields included in the order.
     *
     * @param array $quizzesIds
     * @return array
     * @since 1.2.0
     */
    private function getQuizzesInOrderData($quizzesIds = [])
    {
        if (empty($quizzesIds)) {
            return [];
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();
        $query->select($db->qn(['id', 'allow_continue']))
            ->from($db->qn('#__quiztools_quizzes'))
            ->where($db->qn('id') . " IN ('" . implode("','", $quizzesIds) . "')");
        $db->setQuery($query);
        $quizzesData = $db->loadObjectList('id');

        return $quizzesData;
    }

    /**
     * Check for paid access to the quiz or the Learning Path.
     *
     * @param object $item
     * @param array $where
     * @return bool
     * @throws \Exception
     */
    private function checkPaidAccess($item, $where = [])
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $orderId = !empty($item->order_id) ? $item->order_id : 0;

        if (empty($item->id) || empty($user->id) || empty($orderId)) {
            return false;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();
        $query->select('1')
            ->from($db->qn('#__quiztools_orders', 'o'))
            ->join(
                'INNER',
                $db->qn('#__quiztools_subscriptions', 's'),
                $db->qn('s.id') . ' = ' . $db->qn('o.subscription_id')
            )
            ->join(
                'INNER',
                $db->qn('#__quiztools_order_users', 'ou'),
                $db->qn('ou.order_id') . ' = ' . $db->qn('o.id')
            )
            ->where($db->qn('o.id') . '=' . $db->q($orderId))
            ->where($db->qn('ou.user_id') . '=' . $db->q($user->id))
            ->where($db->qn('o.status') . '=' . $db->q('C'));

        if (!empty($where)) {
            $query->andWhere($where, 'AND');
        }

        $db->setQuery($query);
        $orderExist = $db->loadResult();

        if (empty($orderExist)) {
            return false;
        }

        /** @var OrdersModel $modelOrders */
        $modelOrders = Factory::getApplication()->bootComponent('com_quiztools')->getMVCFactory()
            ->createModel('Orders', 'Site', ['ignore_request' => true]);

        // Forces the populateState() method to be called (and filling the '__state_set' property).
        // If it is called later, it will override the model's State.
        $modelOrders->getState();

        $modelOrders->setState('filter.ordersIds', [$orderId]);
        $orders = $modelOrders->getItems();

        if (empty($orders[0])) {
            return false;
        }

        $isAccess = false;

        if (!empty($orders[0]->accessData->access)) {
            $isAccess = true;
        }

        // Check that all articles of the paid Learning Path have been completed:
        if ($item->typeItem === 'lpath' && !$isAccess) {
            if (!$this->allArticlesLpathCompleted($item)) {
                $isAccess = true;
            }
        }

        return $isAccess;
    }

    /**
     * Checking access to a paid quiz included in one of the user's orders.
     *
     * @param object $quiz
     * @param int|null $lpath_id
     * @return bool
     * @throws \Exception
     * @since 1.2.0
     */
    private function checkPaidQuizAccess($quiz, $lpath_id = null)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        if (!empty($lpath_id)) {
            // A quiz included in the Learning Path, loaded in an iframe
            $where = [
                $db->qn('s.type') . '=' . $db->q('lpath'),
                $db->qn('s.lpath_id') . '=' . $db->q((int) $lpath_id),
            ];
        } else {
            $where = [
                $db->qn('s.type') . '=' . $db->q('quiz'),
                $db->qn('s.quiz_id') . '=' . $db->q((int) $quiz->id),
            ];
        }

        $quiz->typeItem = 'quiz';

        return $this->checkPaidAccess($quiz, $where);
    }

    /**
     * Checking access to a paid Learning Path included in one of the user's orders.
     *
     * @param object $lpath
     * @return bool
     * @throws \Exception
     * @since 1.2.0
     */
    private function checkPaidLpathAccess($lpath)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $where = [
            $db->qn('s.type') . '=' . $db->q('lpath'),
            $db->qn('s.lpath_id') . '=' . $db->q((int) $lpath->id),
        ];

        $lpath->typeItem = 'lpath';

        return $this->checkPaidAccess($lpath, $where);
    }

    /**
     * Check that all articles of the paid Learning Path have been completed.
     *
     * @param object $lpath
     * @return bool
     * @throws \Exception
     * @since 1.2.0
     */
    private function allArticlesLpathCompleted($lpath)
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $orderId = !empty($lpath->order_id) ? $lpath->order_id : 0;

        if (empty($lpath->id) || empty($user->id) || empty($orderId)) {
            return true;  // $isAccess will not change and will remain false
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();

        $query->select($db->qn('lpath_items'))
            ->from($db->qn('#__quiztools_lpaths'))
            ->where($db->qn('id') . '=' . $db->q((int) $lpath->id));
        $db->setQuery($query);
        $items = $db->loadResult();

        if (!empty($items)) {
            $items = json_decode($items, true);
        } else {
            return true;  // $isAccess will not change and will remain false
        }

        $idsArticlesAll = [];
        foreach ($items as $item) {
            if ($item['type'] === 'a') {
                $idsArticlesAll[] = (int) $item['article_id'];
            }
        }

        if (empty($idsArticlesAll)) {
            return true;   // $isAccess will not change and will remain false
        } else {
            sort($idsArticlesAll);
        }

        $query->clear()
            ->select($db->qn('type_id'))  //  ids completed articles
            ->from($db->qn('#__quiztools_lpaths_users'))
            ->where($db->qn('user_id') . '=' . $db->q((int) $user->id))
            ->where($db->qn('lpath_id') . '=' . $db->q((int) $lpath->id))
            ->where($db->qn('type') . '=' . $db->q('a'))
            ->where($db->qn('order_id') . '=' . $db->q((int) $orderId))
            ->where($db->qn('passed') . '=' . $db->q(1));
        $db->setQuery($query);
        $idsArticlesCompleted = $db->loadColumn();
        if (!empty($idsArticlesCompleted)) {
            sort($idsArticlesCompleted);
        }

        $idsArticlesNotCompleted = array_diff($idsArticlesAll, $idsArticlesCompleted);

        if (!empty($idsArticlesNotCompleted)) {
            return false;  // The value of $isAccess will change to true since not all articles have been completed yet.
        }

        return true;  // $isAccess will not change and will remain false
    }

    /**
     * Data for returning to the frontend line if the access check for the paid Learning Path is not passed.
     * And if "action" === "steps."
     *
     * @return array
     * @since 1.2.0
     */
    public function getResponseAccessRestrictPaidLpath()
    {
        $data = [
            'steps' => [],
            'countStepsTotal' => 0,
            'countStepsPassed' => 0,
            'setRedirect' => Route::_(RouteHelper::getOrdersRoute(), false),
        ];

        return $data;
    }
}
