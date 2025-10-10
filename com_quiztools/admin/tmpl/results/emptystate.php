<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

/** @var \Qt\Component\Quiztools\Administrator\View\Results\HtmlView $this */

$displayData = [
    'textPrefix' => 'COM_QUIZTOOLS_RESULTS',
    'formURL'    => 'index.php?option=com_quiztools&view=results',
    //'helpURL'    => 'components/com_quiztools/help/en-GB/results.html',
    'icon'       => 'fas fa-square-poll-vertical quiztools-results',
];

echo LayoutHelper::render('joomla.content.emptystate', $displayData);
