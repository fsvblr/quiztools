<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

/** @var \Qt\Component\Quiztools\Administrator\View\Subscriptions\HtmlView $this */

$displayData = [
    'textPrefix' => 'COM_QUIZTOOLS_SUBSCRIPTIONS',
    'formURL'    => 'index.php?option=com_quiztools&view=subscriptions',
    //'helpURL'    => 'components/com_quiztools/help/en-GB/subscriptions.html',
    'icon'       => 'fas fa-table-cells-column-lock subscriptions',
];

$user = $this->getCurrentUser();

if ($user->authorise('core.create', 'com_quiztools') || count($user->getAuthorisedCategories('com_quiztools', 'core.create')) > 0) {
    $displayData['createURL'] = 'index.php?option=com_quiztools&task=subscription.add';
}

echo LayoutHelper::render('joomla.content.emptystate', $displayData);
