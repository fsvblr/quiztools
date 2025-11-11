<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Service\HTML;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * QuizTools HTML helper
 *
 * @since  3.0
 */
class FieldsService
{
    /**
     * Render the list of categories
     *
     * @param   integer  $catid  The category item id
     *
     * @return  string  The HTML
     *
     * @throws  \Exception
     */
    public function categorieslist($extension = 'com_quiztools', $catid = 0)
    {
	    $user = Factory::getApplication()->getIdentity();

	    $db = Factory::getContainer()->get(DatabaseInterface::class);
	    $query = $db->createQuery()
		    ->select($db->qn('id', 'value'))
		    ->select($db->qn('title', 'text'))
		    ->from($db->qn('#__categories'))
		    ->where($db->qn('extension') . ' = :extension')
		    ->where($db->qn('published') . '=' . $db->q(1))
		    ->bind(':extension', $extension)
		    ->order('lft')
	    ;

	    if (!$user->authorise('core.admin')) {
		    $groups = $user->getAuthorisedViewLevels();
		    $query->whereIn($db->qn('access'), $groups);
	    }

	    $db->setQuery($query);

	    try {
		    $categories = $db->loadObjectList();
	    } catch (\Exception $e) {
		    Factory::getApplication()->enqueueMessage(
			    Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()),
			    'warning'
		    );

		    return '';
	    }

	    $first = new \stdClass();
	    $first->value = '';
	    $first->text = Text::_('COM_QUIZTOOLS_SERVICE_HTML_FIELDS_CATEGORIES_OPTION_SELECT');
	    array_unshift($categories, $first);

	    $attribs = [
		    'class' => 'form-select',
		    'onchange' => 'this.form.submit();',
	    ];

	    return HTMLHelper::_('select.genericlist',  $categories,  'filter_category_id', $attribs,
		    'value', 'text', $catid);
    }
}
