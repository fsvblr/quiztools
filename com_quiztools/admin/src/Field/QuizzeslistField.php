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
use Qt\Component\Quiztools\Administrator\Model\QuizzesModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class QuizzeslistField extends ListField
{
	protected $type = 'Quizzeslist';

    protected function getOptions()
    {
        $app = Factory::getApplication();

        /** @var QuizzesModel $model_quizzes */
        $model_quizzes = $app->bootComponent('com_quiztools')->getMVCFactory()
            ->createModel('Quizzes', 'Administrator', ['ignore_request' => true]);

        $model_quizzes->setState('filter.state', 1);

        $quizWithoutPool = !empty($this->element['quizWithoutPool']) ? $this->element['quizWithoutPool'] : false;
        if ($quizWithoutPool) {
            $model_quizzes->setState('filter.withoutPool', true);
        }

        $onlyFree = !empty($this->element['onlyFree']) ? $this->element['onlyFree'] : false;
        if ($onlyFree) {
            $model_quizzes->setState('filter.onlyFree', true);
        }

        $quizzes = $model_quizzes->getQuizzesList();

        return $quizzes;
    }

	protected function getInput()
	{
        $options = $this->getOptions();
        $options = array_merge(parent::getOptions(), $options);

        $options = array_map(function ($option) {
            if (isset($option->type_access) && (int) $option->type_access === 1) {  // paid
                $option->text = $option->text . ' [' . Text::_('COM_QUIZTOOLS_FIELD_QUIZZESLIST_TYPE_ACCESS_PAID') . ']';
            }
            return $option;
        }, $options);

        $idtag = $this->id ?: false;
        $class = $this->element['class'] ?: null;
        $attribs = $class ? 'class="'. $class .'"' : null;

		return HTMLHelper::_('select.genericlist',  $options,  $this->name, $attribs, 'value', 'text', $this->value, $idtag);
	}
}
