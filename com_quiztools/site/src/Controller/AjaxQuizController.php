<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 * @license     GNU General Public License version 2 or later
 */

namespace Qt\Component\Quiztools\Site\Controller;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Input\Input;
use Qt\Component\Quiztools\Administrator\Helper\QuiztoolsHelper;

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
     * Constructor.
     *
     * @param   array                     $config   An optional associative array of configuration settings.
     *                                              Recognized key values include 'name', 'default_task',
     *                                              'model_path', and 'view_path' (this list is not meant to be
     *                                              comprehensive).
     * @param   ?MVCFactoryInterface      $factory  The factory.
     * @param   ?CMSApplicationInterface  $app      The Application for the dispatcher
     * @param   ?Input                    $input    Input
     *
     * @since   3.0
     */
    public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSApplicationInterface $app = null, ?Input $input = null)
    {
        parent::__construct($config, $factory, $app, $input);

        $allowed_tasks = ['getQuizData'];
        $task = $this->input->get('task');

        if (!$task || !in_array($task, $allowed_tasks)) {
            throw new \Exception(Text::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
        }
    }

	/**
	 * Returns the quiz data of the ajax request.
	 *
	 * @return void
	 */
	public function getQuizData()
	{
        if (!QuiztoolsHelper::validateRealSession()) {
            echo new JsonResponse([], 'getQuizData: Error 01', true);
            // ToDo: Logging?
        }

		$quiz = $this->input->post->get('quiz', [], 'array');
		$method = !empty($quiz['action'])
            ? 'quiz' . ucfirst(strtolower(htmlspecialchars($quiz['action'], ENT_QUOTES, 'UTF-8')))
            : 'fallback';
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
			//echo new JsonResponse($e);
            echo new JsonResponse([], 'getQuizData: Error 02', true);
            // ToDo: Logging?
		}

		jexit();
	}
}
