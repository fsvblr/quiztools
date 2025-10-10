<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Component Controller
 *
 * @since  1.5
 */
class DisplayController extends BaseController
{
    /**
     * The default view.
     *
     * @var    string
     * @since  1.6
     */
    protected $default_view = 'quizzes';

    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters and their variable types.
     * @see        \Joomla\CMS\Filter\InputFilter::clean() for valid values.
     *
     * @return  BaseController|boolean  This object to support chaining.
     *
     * @since   1.5
     */
    public function display($cachable = false, $urlparams = [])
    {
        $view   = $this->input->get('view', 'quizzes');
        $layout = $this->input->get('layout', 'default');
        $id     = $this->input->getInt('id');

        // Check for edit form.
        if (in_array($view, ['quiz', 'question', 'certificate'])
                && $layout == 'edit'
                    && !$this->checkEditId('com_quiztools.edit.'.$view, $id)
        ) {
            // Somehow the person just went to the form - we don't allow that.
            if (!\count($this->app->getMessageQueue())) {
                $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
            }

            $redirect_list_view = [
				'quiz'=>'quizzes',
				'question'=>'questions',
				'certificate'=>'certificates'
            ];

            $this->setRedirect(Route::_('index.php?option=com_quiztools&view=' . $redirect_list_view[$view], false));

            return false;
        }

        return parent::display();
    }
}
