<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Site\Dispatcher;

use Joomla\CMS\Dispatcher\ComponentDispatcher;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * ComponentDispatcher class for com_quiztools
 *
 * @since  4.0.0
 */
class Dispatcher extends ComponentDispatcher
{
    /**
     * Dispatch a controller task. Redirecting the user if appropriate.
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function dispatch()
    {
		$view = $this->input->get('view');
	    $task = $this->input->get('task');

        $ajaxLpathAction = '';
        $isLpathPaid = false;

		if ($task == 'ajaxQuiz.getQuizData') {
			$view = 'quiz';
		} else if ($task == 'ajaxLpath.getLpathData') {
            $view = 'lpath';

            $lpathData = $this->input->get('lpath', [], 'ARRAY');
            $ajaxLpathAction = !empty($lpathData['action']) ? $lpathData['action'] : '';
            $isLpathPaid = !empty($lpathData['orderId']) ? true : false;
        }

	    $checkAccess = in_array($view, ['quiz', 'lpath', 'lpaths', 'result', 'results', 'orders']);
        $isAjax = in_array($task, ['ajaxQuiz.getQuizData', 'ajaxLpath.getLpathData']);

	    if ($checkAccess && !$this->app->getIdentity()->authorise('core.admin', 'com_quiztools')) {
            $accessService = HTMLHelper::getServiceRegistry()->getService('quiztoolsaccess');
		    $hasAccess = $accessService->isAccess($view);

		    if (!$hasAccess) {
			    if ($isAjax) {
                    if ($isLpathPaid && $ajaxLpathAction === 'steps') {
                        // Redirect to the subscriptions page without displaying an error message.
                        // Only if the Learning Path is paid and the "steps" are requested via ajax.
                        $response = $accessService->getResponseAccessRestrictPaidLpath();
                        echo new JsonResponse($response);
                        jexit();
                    } else {
                        echo new JsonResponse([], Text::_('JERROR_ALERTNOAUTHOR'), true);
                        jexit();
                    }
			    } else {
				    $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
			    }

			    return;
		    }
	    }

        parent::dispatch();
    }
}
