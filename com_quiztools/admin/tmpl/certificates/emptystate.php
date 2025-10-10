<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

/** @var \Qt\Component\Quiztools\Administrator\View\Certificates\HtmlView $this */

$displayData = [
    'textPrefix' => 'COM_QUIZTOOLS_CERTIFICATES',
    'formURL'    => 'index.php?option=com_quiztools&view=certificates',
    //'helpURL'    => 'components/com_quiztools/help/en-GB/certificates.html',
    'icon'       => 'fas fa-certificate certificates',
];

$user = $this->getCurrentUser();

if ($user->authorise('core.create', 'com_quiztools') || count($user->getAuthorisedCategories('com_quiztools', 'core.create')) > 0) {
    $displayData['createURL'] = 'index.php?option=com_quiztools&task=certificate.add';
}

echo LayoutHelper::render('joomla.content.emptystate', $displayData);
