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
 * QuizTools Component AjaxLpath Controller
 *
 * https://manual.joomla.org/docs/general-concepts/javascript/ajax/
 *
 * @since  1.5
 */
class AjaxLpathController extends BaseController
{
	/**
	 * Returns the Lpath data of the ajax request.
	 *
	 * @return void
	 */
	public function getLpathData()
	{
		try {
			Session::checkToken();
		} catch (\Exception $e) {
			echo new JsonResponse($e);
		}

		$lpath = $this->input->post->get('lpath', [], 'array');
		$method = !empty($lpath['action']) ? 'lpath' . ucfirst(strtolower($lpath['action'])) : 'fallback';
		$model = $this->getModel('ajaxLpath');

		try {
			if (method_exists($model, $method)) {
				$result = $model->$method();
			} else {
				$result = [];
			}

			if (!empty($result)) {
				echo new JsonResponse($result);
			} else {
				echo new JsonResponse($result, Text::_('COM_QUIZTOOLS_LPATH_ERROR_LPATH_NOT_FOUND'), true);
			}
		}
		catch (\Exception $e) {
			echo new JsonResponse($e);
		}

		jexit();
	}
}
