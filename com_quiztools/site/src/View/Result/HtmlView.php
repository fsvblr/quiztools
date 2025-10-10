<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Site\View\Result;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Qt\Component\Quiztools\Site\Model\ResultModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * HTML Quiz View class for the QuizTools component
 *
 * @since  1.5
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The quiz object
     *
     * @var  \stdClass
     */
    protected $item;

    /**
     * The page parameters
     *
     * @var    \Joomla\Registry\Registry|null
     *
     * @since  4.0.0
     */
    protected $params = null;

    /**
     * The model state
     *
     * @var   \Joomla\Registry\Registry
     */
    protected $state;

    /**
     * The user object
     *
     * @var   \Joomla\CMS\User\User|null
     */
    protected $user = null;

    /**
     * The page class suffix
     *
     * @var    string
     *
     * @since  4.0.0
     */
    protected $pageclass_sfx = '';

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        /** @var ResultModel $model */
        $model = $this->getModel();
        $this->item = $model->getItem();
        $this->state = $model->getState();
	    $this->params = $this->state->get('params');
        $this->user = $this->getCurrentUser();
	    $this->pageclass_sfx = htmlspecialchars($this->params->get('pageclass_sfx', ''));

        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->_prepareDocument();

        parent::display($tpl);
    }

    /**
     * Prepares the document.
     *
     * @return  void
     */
    protected function _prepareDocument()
    {
        $this->setDocumentTitle(Text::_('COM_QUIZTOOLS_RESULT_DOCUMENT_TITLE'));

        if (!empty($this->params->get('menu-meta_description'))) {
            $this->getDocument()->setDescription($this->params->get('menu-meta_description'));
        }

        if (!empty($this->params->get('robots'))) {
            $this->getDocument()->setMetaData('robots', $this->params->get('robots'));
        }
    }
}
