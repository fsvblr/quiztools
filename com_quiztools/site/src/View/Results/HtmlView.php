<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Site\View\Results;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Qt\Component\Quiztools\Site\Model\ResultsModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * HTML View class for the QuizTools component
 *
 * @since  3.1
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The model state
	 *
	 * @var   \Joomla\Registry\Registry
	 *
	 * @since  3.1
	 */
	protected $state;

	/**
	 * The list of results
	 *
	 * @var    array|false
	 * @since  3.1
	 */
	protected $items;

	/**
	 * The pagination object
	 *
	 * @var    \Joomla\CMS\Pagination\Pagination
	 * @since  3.1
	 */
	protected $pagination;

	/**
	 * The page parameters
	 *
	 * @var    \Joomla\Registry\Registry|null
	 * @since  3.1
	 */
	protected $params = null;

	/**
	 * The page class suffix
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $pageclass_sfx = '';

	/**
	 * The logged in user
	 *
	 * @var    \Joomla\CMS\User\User|null
	 * @since  4.0.0
	 */
	protected $user = null;

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
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
        /** @var ResultsModel $model */
        $model            = $this->getModel();
		$this->state      = $model->getState();
		$this->items      = $model->getItems();
		$this->pagination = $model->getPagination();
		$this->params     = $this->state->get('params');
		$this->user       = $this->getCurrentUser();
        $this->filterForm    = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();

		if (\count($errors = $this->get('Errors'))) {
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		// Flag indicates to not add limitstart=0 to URL
		$this->pagination->hideEmptyLimitstart = true;

		$this->pageclass_sfx = htmlspecialchars(trim($this->params->get('pageclass_sfx', '')));

		$this->_prepareDocument();

		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return void
	 */
	protected function _prepareDocument()
	{
		$app = Factory::getApplication();
		$menu = $app->getMenu()->getActive();
		$Itemid = $app->getInput()->getInt('Itemid');
		$is_active_menu = $menu->id == $Itemid;

		if ($is_active_menu) {
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		} else {
			$this->params->def('page_heading', Text::_('COM_QUIZTOOLS_RESULTS_DEFAULT_PAGE_TITLE'));
		}

		if ($this->params->get('menu-meta_description')) {
			$this->getDocument()->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('robots')) {
			$this->getDocument()->setMetaData('robots', $this->params->get('robots'));
		}

		$this->setDocumentTitle($this->getDocument()->getTitle());
	}
}
