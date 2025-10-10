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

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * List of registered users with quiz completion results.
 */
class UsersresultslistField extends ListField
{
	protected $type = 'Usersresultslist';

	protected function getOptions()
	{
        $app = Factory::getApplication();

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select('DISTINCT ' . $db->qn('qrq.user_id', 'value'))
            ->select($db->qn('u.name', 'text'))
            ->from($db->qn('#__quiztools_results_quizzes', 'qrq'))
            ->join('LEFT', $db->qn('#__users', 'u'), $db->qn('u.id') . ' = ' . $db->qn('qrq.user_id'))
            ->where($db->qn('qrq.user_id') . ' > 0')
        ;
        $db->setQuery($query);

        try {
            $users = $db->loadObjectList();
        } catch (\Exception $e) {
            $app->enqueueMessage(
                Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()),
                'warning'
            );

            $users = [];
        }

        return $users;
	}

    protected function getInput()
    {
        $options = $this->getOptions();
        $options = array_merge(parent::getOptions(), $options);

        $id = $this->element['id'] ?: 'filter_user_id';
        $attribs = 'class="form-select js-select-submit-on-change"';

        return HTMLHelper::_('select.genericlist',  $options,  $this->name, $attribs, 'value', 'text', $this->value, $id);
    }
}
