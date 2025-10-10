<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\View\Result;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Qt\Component\Quiztools\Administrator\Model\ResultModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View to display a result.
 *
 * @since  1.5
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The item
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
		/** @var ResultModel $model */
		$model       = $this->getModel();
		$this->item  = $model->getItem();
		$this->state = $model->getState();

        // Loading language files from question plugins.
        $types = [];
        if (!empty($this->item->results_questions)) {
            $lang = $this->getLanguage();
            foreach ($this->item->results_questions as $question) {
                if(!in_array($question->type, $types)) {
                    $types[] = $question->type;
                    $lang->load('plg_quiztools_' . $question->type, JPATH_ADMINISTRATOR);
                }
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

        $toolbar = $this->getDocument()->getToolbar();
        $layout = $this->getLayout();

        if ($layout === 'question') {
            ToolbarHelper::title(
                Text::_('COM_QUIZTOOLS_RESULT_QUESTION_TITLE'),
                'fas fa-square-poll-vertical quiztools-results'
            );

            $toolbar->cancel('result.cancelQuestion', 'JTOOLBAR_CANCEL');
        } else {
            ToolbarHelper::title(
                Text::_('COM_QUIZTOOLS_RESULT_QUIZ_TITLE'),
                'fas fa-square-poll-vertical quiztools-results'
            );

            $toolbar->cancel('result.cancelQuiz', 'JTOOLBAR_CANCEL');
        }

        //$toolbar->help('COM_QUIZTOOLS_HELP_VIEW_RESULT', true);
	}
}
