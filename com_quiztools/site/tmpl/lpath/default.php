<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

/** @var \Qt\Component\Quiztools\Site\View\Lpath\HtmlView $this */

Text::script('COM_QUIZTOOLS_LPATH_TITLE_COURSE_STRUCTURE');
Text::script('COM_QUIZTOOLS_LPATH_BUTTON_ACTION_STEPS');
Text::script('COM_QUIZTOOLS_LPATH_BUTTON_ACTION_STEP');
Text::script('COM_QUIZTOOLS_LPATH_BUTTON_ACTION_NEXT');

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate');
$wa->useStyle('com_quiztools.lpath')
    ->useScript('com_quiztools.lpath');

$this->getDocument()->addScriptOptions('com_quiztools.lpath', (array) $this->lpath);
$this->getDocument()->addScriptOptions('com_quiztools.token', array('value' => Session::getFormToken()));

?>
<div class="quiztools lpath<?php echo !empty($this->pageclass_sfx) ? ' lpath-'.$this->pageclass_sfx : ''; ?>">
    <div class="page-header">
        <h1><?php echo $this->escape($this->item->title); ?></h1>
    </div>
    <div id="lpath-wrap" class="lpath-wrap"></div>
</div>
