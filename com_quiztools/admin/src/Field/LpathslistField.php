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
use Qt\Component\Quiztools\Administrator\Model\LpathsModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class LpathslistField extends ListField
{
	protected $type = 'Lpathslist';

    protected function getOptions()
    {
        $app = Factory::getApplication();

        /** @var LpathsModel $model_lpaths */
        $model_lpaths = $app->bootComponent('com_quiztools')->getMVCFactory()
            ->createModel('Lpaths', 'Administrator', ['ignore_request' => true]);

        $model_lpaths->setState('filter.state', 1);

        $onlyFree = !empty($this->element['onlyFree']) ? $this->element['onlyFree'] : false;
        if ($onlyFree) {
            $model_lpaths->setState('filter.onlyFree', true);
        }

        $quizzes = $model_lpaths->getLearningPathsList();

        return $quizzes;
    }

	protected function getInput()
	{
        $options = $this->getOptions();
        $options = array_merge(parent::getOptions(), $options);

        $options = array_map(function ($option) {
            if (isset($option->type_access) && (int) $option->type_access === 1) {  // paid
                $option->text = $option->text . ' [' . Text::_('COM_QUIZTOOLS_FIELD_LPATHSLIST_TYPE_ACCESS_PAID') . ']';
            }
            return $option;
        }, $options);

        $idtag = $this->id ?: false;
        $class = $this->element['class'] ?: null;
        $attribs = $class ? 'class="'. $class .'"' : null;

		return HTMLHelper::_('select.genericlist',  $options,  $this->name, $attribs, 'value', 'text', $this->value, $idtag);
	}
}
