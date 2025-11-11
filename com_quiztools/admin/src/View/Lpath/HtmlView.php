<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\View\Lpath;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Qt\Component\Quiztools\Administrator\Model\LpathModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View to edit a Learning Path.
 *
 * @since  1.5
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The Form object
     *
     * @var    Form
     * @since  1.5
     */
    protected $form;

    /**
     * The active item
     *
     * @var    \stdClass
     * @since  1.5
     */
    protected $item;

    /**
     * The model state
     *
     * @var    \Joomla\Registry\Registry
     * @since  1.5
     */
    protected $state;

    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     *
     * @since   1.5
     *
     * @throws  \Exception
     */
    public function display($tpl = null): void
    {
        /** @var LpathModel $model */
        $model       = $this->getModel();
        $this->form  = $model->getForm();
        $this->item  = $model->getItem();
        $this->state = $model->getState();

        // Check for errors.
        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 * @throws  \Exception
	 */
	protected function addToolbar(): void
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $user        = $this->getCurrentUser();
        $is_new      = ($this->item->id == 0);
        $checked_out = !(\is_null($this->item->checked_out) || $this->item->checked_out == $user->id);

        $toolbar = $this->getDocument()->getToolbar();
        $canDo = ContentHelper::getActions('com_quiztools');

        ToolbarHelper::title(
            $is_new ? Text::_('COM_QUIZTOOLS_LPATH_TITLE_NEW') : Text::_('COM_QUIZTOOLS_LPATH_TITLE_EDIT'),
            'fas fa-graduation-cap quiztools-quiz'
        );

        // If not checked out, can save the item.
        if (!$checked_out && ($canDo->get('core.edit') || $canDo->get('core.create'))) {
            $toolbar->apply('lpath.apply');
	        $toolbar->save('lpath.save');
        }

        if (empty($this->item->id)) {
            $toolbar->cancel('lpath.cancel', 'JTOOLBAR_CANCEL');
        } else {
            $toolbar->cancel('lpath.cancel');
        }

	    //$toolbar->help('COM_QUIZTOOLS_HELP_VIEW_LPATH', true);
    }
}
