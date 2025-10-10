<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Site\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * QuizTools Component AjaxQuiz Controller
 *
 * https://manual.joomla.org/docs/general-concepts/javascript/ajax/
 *
 * @since  1.5
 */
class AjaxQuizController extends BaseController
{
	/**
	 * Returns the quiz data of the ajax request.
	 *
	 * @return void
	 */
	public function getQuizData()
	{
		try {
			Session::checkToken();
		} catch (\Exception $e) {
			echo new JsonResponse($e);
		}

		$quiz = $this->input->post->get('quiz', [], 'array');
		$method = !empty($quiz['action']) ? 'quiz' . ucfirst(strtolower($quiz['action'])) : 'fallback';
		$model = $this->getModel('ajaxQuiz');

		try {
			if (method_exists($model, $method)) {
				$result = $model->$method();
			} else {
				$result = [];
			}

			if (!empty($result)) {
				echo new JsonResponse($result);
			} else {
				echo new JsonResponse($result, Text::_('COM_QUIZTOOLS_QUIZ_ERROR_QUIZ_QUESTIONS_NOT_FOUND'), true);
			}
		}
		catch (\Exception $e) {
			echo new JsonResponse($e);
		}

		jexit();
	}
}
