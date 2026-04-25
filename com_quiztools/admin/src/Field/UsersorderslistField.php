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
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * List of registered users who have paid for a subscription.
 */
class UsersorderslistField extends ListField
{
	protected $type = 'Usersorderslist';

	protected function getOptions()
	{
        $app = Factory::getApplication();

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select('DISTINCT ' . $db->qn('qo.user_id', 'value'))
            ->select("IF (
                     " . $db->qn('u.id') . " IS NOT NULL, 
                     CONCAT(" . $db->qn('u.name') . ", ' [', " . $db->qn('u.email') . ", ']'),
                     '" . Text::_('COM_QUIZTOOLS_FIELD_USERSORDERSLIST_TEXT_DELETED_USER') . "'
                  ) as 'text'")
            ->from($db->qn('#__quiztools_orders', 'qo'))
            ->join('LEFT', $db->qn('#__users', 'u'), $db->qn('u.id') . ' = ' . $db->qn('qo.user_id'))
            ->order($db->qn('text') . ' ASC')
        ;

        $db->setQuery($query);

        try {
            $options = $db->loadObjectList();
        } catch (\Exception $e) {
            $app->enqueueMessage(
                Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()),
                'warning'
            );

            $options = [];
        }

        $options = array_merge(parent::getOptions(), $options);

        return $options;
	}
}
