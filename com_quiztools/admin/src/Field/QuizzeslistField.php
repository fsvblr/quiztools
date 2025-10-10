<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;
use Qt\Component\Quiztools\Administrator\Model\QuizzesModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class QuizzeslistField extends ListField
{
	protected $type = 'Quizzeslist';

	protected function getInput()
	{
		$app = Factory::getApplication();

		/** @var QuizzesModel $model_quizzes */
		$model_quizzes = $app->bootComponent('com_quiztools')->getMVCFactory()
			->createModel('Quizzes', 'Administrator', ['ignore_request' => true]);

		$quizzes = $model_quizzes->getQuizzesList();

		$input = $app->getInput();
		$component = $input->get('option');
		$view = $input->get('view');
		$layout = $input->get('layout');
		$id = $input->getInt('id', 0);
		$question_id = ($component == 'com_quiztools' && $view == 'question' && $layout == 'edit') ? $id : null;
		$menu_type_quiz = ($component == 'com_menus' && $view == 'item' && $layout == 'edit') ? true : false;
        $resultsAdminPage = ($app->isClient('administrator') && $component == 'com_quiztools' && $view == 'results') ? true : false;
        $resultsSitePage = ($app->isClient('site') && $component == 'com_quiztools' && $view == 'results') ? true : false;

		if ($menu_type_quiz) {  //Creating "Quiz" Menu Type
			$first = new \stdClass();
			$first->value = '';
			$first->text = Text::_('COM_QUIZTOOLS_QUESTION_FIELD_QUIZ_ID_OPTION_SELECT');
			array_unshift($quizzes, $first);
		} else {
            if (!$resultsAdminPage && !$resultsSitePage) {
                $pool = new \stdClass();
                $pool->value = 0;
                $pool->text = Text::_('COM_QUIZTOOLS_QUESTION_FIELD_QUIZ_ID_OPTION_POOL');
                array_unshift($quizzes, $pool);
            }
		}

		$quizzes = array_merge(parent::getOptions(), $quizzes);

		$db = $this->getDatabase();
		$query = $db->createQuery();

		if (is_null($question_id)) {
			if ($menu_type_quiz) {
				//Creating "Quiz" Menu Type
				$selected_quiz_id = '';
				if (!empty($id)) {
					$query->clear()
						->select($db->qn('link'))
						->from($db->qn('#__menu'))
						->where($db->qn('id') . ' = :id')
						->bind(':id', $id, ParameterType::INTEGER);
					$db->setQuery($query);
					$menuItem_link = $db->loadResult();

					if (!empty($menuItem_link)) {
						parse_str($menuItem_link, $menuItem_link_arr);
						if (!empty($menuItem_link_arr['id'])) {
							$selected_quiz_id = (int)$menuItem_link_arr['id'];
						}
					}
				}

				$attribs = 'class="form-select"';
				$select_id = 'quiz_id';
			} else {
				// Question List page OR Results page
				$filter = $input->get('filter', [], 'ARRAY');
				// For the filter empty value must be a string
				$selected_quiz_id = (isset($filter['quiz_id']) && is_numeric($filter['quiz_id'])) ? (int)$filter['quiz_id'] : '';
				$attribs = 'class="form-select js-select-submit-on-change"';
				$select_id = 'filter_quiz_id';
			}
		} else {
			// Question edit page
			$form_data = $app->getUserState('com_quiztools.edit.question.data');
			$selected_quiz_id = !empty($form_data['quiz_id']) ? (int)$form_data['quiz_id'] : 0;
			$attribs = 'class="form-select"';
			$select_id = 'quiz_id';
		}

		if (empty($form_data['quiz_id']) && !empty($question_id)) {
			$query->clear()
				->select($db->qn('quiz_id'))
				->from($db->qn('#__quiztools_questions'))
				->where($db->qn('id') . ' = :id')
				->bind(':id', $question_id, ParameterType::INTEGER);
			$db->setQuery($query);

			try {
				$selected_quiz_id = $db->loadResult();
			} catch (\Exception $e) {
				$app->enqueueMessage(
					Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()),
					'warning'
				);

				$selected_quiz_id = 0;
			}
		}

		return HTMLHelper::_('select.genericlist',  $quizzes,  $this->name, $attribs, 'value', 'text',
			$selected_quiz_id, $select_id);
	}
}
