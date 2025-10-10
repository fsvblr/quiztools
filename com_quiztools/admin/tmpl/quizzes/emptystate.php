<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

/** @var \Qt\Component\Quiztools\Administrator\View\Quizzes\HtmlView $this */

$displayData = [
    'textPrefix' => 'COM_QUIZTOOLS_QUIZZES',
    'formURL'    => 'index.php?option=com_quiztools&view=quizzes',
    //'helpURL'    => 'components/com_quiztools/help/en-GB/quizzes.html',
    'icon'       => 'fas fa-user-graduate quizzes',
];

$user = $this->getCurrentUser();

if ($user->authorise('core.create', 'com_quiztools') || count($user->getAuthorisedCategories('com_quiztools', 'core.create')) > 0) {
    $displayData['createURL'] = 'index.php?option=com_quiztools&task=quiz.add';
}

echo LayoutHelper::render('joomla.content.emptystate', $displayData);
