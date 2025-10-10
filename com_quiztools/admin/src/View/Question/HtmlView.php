<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\View\Question;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Qt\Component\Quiztools\Administrator\Model\QuestionModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View to edit a question.
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
	 * The Question Type Form object
	 * (from question type plugin)
	 *
	 * @var    Form
	 * @since  1.5
	 */
	protected $question_type_form = null;

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
	 * Is this a 'boilerplate' type question?
	 *
	 * @var bool
	 */
	protected $is_boilerplate = false;

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
		/** @var QuestionModel $model */
		$model       = $this->getModel();
		$this->form  = $model->getForm();
		$this->item  = $model->getItem();
		$this->state = $model->getState();

		if (!empty($this->item->type)) {
			// Loading a form from a question type plugin:
			$this->question_type_form  = $model->getFormQuestionType(
				'question_' . $this->item->type,
				JPATH_SITE . '/plugins/quiztools/' . $this->item->type . '/forms/question_' . $this->item->type . '.xml',
				array('control' => 'jform')
			);

			if ($this->item->type == 'boilerplate') {
				$this->is_boilerplate = true;
			}
		}

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

		$user = $this->getCurrentUser();
		$is_new = ($this->item->id == 0);
		$checked_out = !(\is_null($this->item->checked_out) || $this->item->checked_out == $user->id);

        $toolbar = $this->getDocument()->getToolbar();
		$canDo = ContentHelper::getActions('com_quiztools');

		ToolbarHelper::title(
			$is_new ? Text::_('COM_QUIZTOOLS_QUESTION_TITLE_NEW') : Text::_('COM_QUIZTOOLS_QUESTION_TITLE_EDIT'),
			'fas fa-circle-question quiztools-question'
		);

		// If not checked out, can save the item.
		if (!$checked_out && ($canDo->get('core.edit') || $canDo->get('core.create'))) {
			$toolbar->apply('question.apply');
			$toolbar->save('question.save');
		}

		if (empty($this->item->id)) {
			$toolbar->cancel('question.cancel', 'JTOOLBAR_CANCEL');
		} else {
			$toolbar->cancel('question.cancel');
		}

		//$toolbar->help('COM_QUIZTOOLS_HELP_VIEW_QUESTION', true);
	}
}
