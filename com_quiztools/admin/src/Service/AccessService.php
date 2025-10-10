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
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Filter\InputFilter;
use Qt\Component\Quiztools\Administrator\Extension\QuiztoolsComponent;

/**
 * QuizTools helper
 *
 * @since  3.0
 */
class AccessService
{
	/**
	 *  Access check
	 *
	 * @param string $view View/Entity
	 * @param int $pk Primary key
	 *
	 * @return bool
	 */
    public function isAccess($view = null, $pk = null)
    {
		if (!$view) {
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
	 * @param   int  $pk  Primary key
	 *
	 * @return bool
	 */
	private function isAccessQuiz($pk)
	{
		$pk = (int) $pk;
		$user = Factory::getApplication()->getIdentity();
		$input = Factory::getApplication()->getInput();

		if (!$pk) {
			$pk = $input->getInt('id');

			if (!$pk && ($input->get('task') == 'ajax.getQuizData')) {
				$ajaxData = $input->get('quiz', [], 'ARRAY');
				$pk = (int) $ajaxData['id'] ?: 0;
			}
		}

		if (!$pk) {
			return false;
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->createQuery()
			->select($db->qn(['a.id', 'a.type_access', 'a.access']))
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

		// Check attempts
		if ($quiz->limit_attempts) {
			if (!$this->checkQuizAttempts($quiz)) {
				return false;
			}
		}

		// Check paid access
		if ($quiz->type_access == QuiztoolsComponent::CONDITION_TYPE_ACCESS_PAID) {
			if (!$this->checkQuizPaidAccess($quiz)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if the number of attempts for this quiz has expired.
	 *
	 * @param $quiz
	 *
	 * @return bool
	 * @throws \Exception
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

		$query->clear()
			->select('COUNT(*)')
			->from($db->qn('#__quiztools_results_quizzes'))
			->where($db->qn('quiz_id') . ' = :quizId')
			->bind(':quizId', $quiz->id, ParameterType::INTEGER);
		if ($quiz->allow_continue) {
			$query->where($db->qn('finished') . '=' . $db->q(1));
		}
		$query->where($where);
		$number_times_passed = $db->setQuery($query)->loadResult() ?: 0;

		if ($number_times_passed < $quiz->limit_attempts) {
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

	private function checkQuizPaidAccess($quiz)
	{
		// ToDo: Write a method when paid quizzes will be added.

		return true;
	}

    /**
     * Checking access to the results
     *
     * Results are available only to authorized users.
     *
     * @return bool
     */
    private function isAccessResults()
    {
        $user = Factory::getApplication()->getIdentity();

        if ((int) $user->id === 0) {
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
     *
     * @param   int  $pk  Primary key
     *
     * @return bool
     */
    private function isAccessResult($pk)
    {
        $pk = (int) $pk;
        $input = Factory::getApplication()->getInput();

        if (!$pk) {
            $pk = $input->getInt('id');
        }

        if (!$pk) {
            return false;
        }

        $user = Factory::getApplication()->getIdentity();

        if ((int) $user->id === 0) {
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
        $referenceHMAC      = hash_hmac($algo, $referenceTokenData, $siteSecret);

        // Do the tokens match? Use a timing safe string comparison to prevent timing attacks.
        $hashesMatch = Crypt::timingSafeCompare($referenceHMAC, $tokenHMAC);

        return $hashesMatch;
    }
}
