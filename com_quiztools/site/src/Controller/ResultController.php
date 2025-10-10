<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

/**
 * Results list controller class.
 *
 * @since  1.6
 */
class ResultController extends BaseController
{
    /**
     * Get a PDF-file with the result.
     *
     * @return bool
     * @throws \Exception
     */
    public function getPdf()
    {
        $this->checkToken('get');

        $input = $this->input;
        $resultId = $input->getInt('id', 0);
        $token = $input->getString('token');

        $isAccess = HTMLHelper::getServiceRegistry()->getService('quiztoolsaccess')->checkResultToken($token);

        if (empty($isAccess)) {
            $this->setRedirect(
                Route::_('index.php', false),
                Text::_('COM_QUIZTOOLS_RESULT_ERROR_ACCESS_TO_RESULT_BY_TOKEN'),
                'warning'
            );

            return false;
        }

        $lang = $this->app->getLanguage();
        $lang->load('com_quiztools', JPATH_ADMINISTRATOR);

        $adminResultController = $this->factory->createController('Result', 'Administrator', [], $this->app, $this->input);
        $adminResultController->getPdf($resultId);

        return true;
    }

    /**
     * Generating a certificate with the user's result.
     *
     * @return bool
     * @throws \Exception
     */
    public function getCertificate()
    {
        $this->checkToken('get');

        $input = $this->input;
        $resultId = $input->getInt('id', 0);
        $token = $input->getString('token');

        $isAccess = HTMLHelper::getServiceRegistry()->getService('quiztoolsaccess')->checkResultToken($token);

        if (empty($isAccess)) {
            $this->setRedirect(
                Route::_('index.php', false),
                Text::_('COM_QUIZTOOLS_RESULT_ERROR_ACCESS_TO_RESULT_BY_TOKEN'),
                'warning'
            );

            return false;
        }

        $lang = $this->app->getLanguage();
        $lang->load('com_quiztools', JPATH_ADMINISTRATOR);

        $adminResultController = $this->factory->createController('Result', 'Administrator', [], $this->app, $this->input);
        $adminResultController->getCertificate($resultId);

        return true;
    }
}
