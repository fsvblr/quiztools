<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\View\Questions;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Qt\Component\Quiztools\Administrator\Model\QuestionsModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View class for a list of questions.
 *
 * @since  1.6
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The search tools form
	 *
	 * @var    Form
	 * @since  1.6
	 */
	public $filterForm;

	/**
	 * The active search filters
	 *
	 * @var    array
	 * @since  1.6
	 */
	public $activeFilters = [];

	/**
	 * An array of items
	 *
	 * @var    array
	 * @since  1.6
	 */
	protected $items = [];

	/**
	 * The pagination object
	 *
	 * @var    Pagination
	 * @since  1.6
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var    \Joomla\Registry\Registry
	 * @since  1.6
	 */
	protected $state;

	/**
	 * Is this view an Empty State
	 *
	 * @var  boolean
	 * @since 4.0.0
	 */
	private $isEmptyState = false;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 *
	 * @throws  \Exception
	 */
	public function display($tpl = null): void
	{
		/** @var QuestionsModel $model */
		$model               = $this->getModel();
		$this->items         = $model->getItems();
		$this->pagination    = $model->getPagination();
		$this->state         = $model->getState();
		$this->filterForm    = $model->getFilterForm();
		$this->activeFilters = $model->getActiveFilters();

		if (!\count($this->items) && $this->isEmptyState = $model->getIsEmptyState()) {
			$this->setLayout('emptystate');
		}

		// Check for errors.
        if (\count($errors = $model->getErrors())) {
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		// Loading language files for question plugins.
		$types = [];
		if (!empty($this->items)) {
			$lang = $this->getLanguage();
			foreach ($this->items as $item) {
				if(!in_array($item->type, $types)) {
					$types[] = $item->type;
					$lang->load('plg_quiztools_' . $item->type, JPATH_ADMINISTRATOR);
				}
			}
		}
		unset($types);

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function addToolbar(): void
	{
		$canDo   = ContentHelper::getActions('com_quiztools');
		$user    = $this->getCurrentUser();
        $toolbar = $this->getDocument()->getToolbar();

		ToolbarHelper::title(Text::_('COM_QUIZTOOLS_QUESTIONS_TITLE'), 'fas fa-circle-question quiztools-questions');

		if ($canDo->get('core.create') || \count($user->getAuthorisedCategories('com_quiztools', 'core.create')) > 0) {
            $toolbar->standardButton('new', 'JTOOLBAR_NEW')
                ->onclick("location.href='index.php?option=com_quiztools&amp;view=selectquestiontype'");
		}

		if (!$this->isEmptyState && ($canDo->get('core.edit.state') || $canDo->get('core.delete'))) {
			$dropdown = $toolbar->dropdownButton('status-group', 'JTOOLBAR_CHANGE_STATUS')
				->toggleSplit(false)
				->icon('icon-ellipsis-h')
				->buttonClass('btn btn-action')
				->listCheck(true);

			$childBar = $dropdown->getChildToolbar();

			if ($canDo->get('core.edit.state')) {
				$childBar->publish('questions.publish')->listCheck(true);
				$childBar->unpublish('questions.unpublish')->listCheck(true);
				$childBar->checkin('questions.checkin')->listCheck(true);
			}
		}

		if (!$this->isEmptyState && $canDo->get('core.delete')) {
			$toolbar->delete('questions.delete', 'JTOOLBAR_DELETE')
				->message('JGLOBAL_CONFIRM_DELETE')
				->listCheck(true);
		}

		if ($canDo->get('core.admin') || $canDo->get('core.options')) {
			$toolbar->preferences('com_quiztools');
		}

		//$toolbar->help('COM_QUIZTOOLS_HELP_VIEW_QUESTIONS', true);

        $toolbar->help('COM_QUIZTOOLS_HELP_VIEW_CUSTOMERS', true)
            ->text('COM_QUIZTOOLS_HELP_VIEW_CUSTOMERS_BTN_TITLE')
            ->icon('fas fa-cart-shopping');
	}
}
