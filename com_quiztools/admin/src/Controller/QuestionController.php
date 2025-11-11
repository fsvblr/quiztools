<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Question controller class.
 *
 * @since  1.6
 */
class QuestionController extends FormController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  1.6
     */
    protected $text_prefix = 'COM_QUIZTOOLS_QUESTION';

	/**
	 * Method override to check if you can edit an existing record.
	 *
	 * @param   array   $data  An array of input data.
	 * @param   string  $key   The name of the key for the primary key.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	protected function allowEdit($data = [], $key = 'id')
	{
		$record_id = isset($data[$key]) ? (int)$data[$key] : 0;
		$category_id = 0;

		if ($record_id) {
            $category_id = (int) $this->getModel()->getItem($record_id)->catid;
		}

		if ($category_id) {
			// The category has been set. Check the category permissions.
			return $this->app->getIdentity()->authorise('core.edit', $this->option . '.category.' . $category_id);
		}

		// Since there is no asset tracking, revert to the component permissions.
		return parent::allowEdit($data, $key);
	}

    /**
     * Override parent add method.
     *
     * @return  \Exception|void  True if the record can be added, a \Exception object if not.
     *
     * @since   1.6
     */
    public function add()
    {
        $app = $this->app;

        $result = parent::add();

        if ($result instanceof \Exception) {
            return $result;
        }

        $type = $this->input->get('type');

        if (empty($type)) {
            $redirectUrl = 'index.php?option=' . $this->option . '&view=' . $this->view_list;
            $this->setRedirect(Route::_($redirectUrl, false));
            $app->enqueueMessage(Text::_('COM_QUIZTOOLS_QUESTION_ERROR_INVALID_TYPE'), 'warning');
        }

        $app->setUserState('com_quiztools.add.question.type', $type);
    }

    /**
     * Override parent cancel method to reset the add module state.
     *
     * @param   string  $key  The name of the primary key of the URL variable.
     *
     * @return  boolean  True if access level checks pass, false otherwise.
     *
     * @since   1.6
     */
    public function cancel($key = null)
    {
        $result = parent::cancel();

        $this->app->setUserState('com_quiztools.add.question.type', null);

        return $result;
    }
}
