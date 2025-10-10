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

		if ($task == 'ajax.getQuizData') {
			$view = 'quiz';
		}

	    $checkAccess = in_array($view, ['quiz', 'results', 'result']);

	    if ($checkAccess && !$this->app->getIdentity()->authorise('core.admin', 'com_quiztools')) {
		    $hasAccess = HTMLHelper::getServiceRegistry()->getService('quiztoolsaccess')->isAccess($view);

		    if (!$hasAccess) {
			    if ($task == 'ajax.getQuizData') {
				    echo new JsonResponse([], Text::_('JERROR_ALERTNOAUTHOR'), true);
					jexit();
			    } else {
				    $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
			    }
			    return;
		    }
	    }

        parent::dispatch();
    }
}
