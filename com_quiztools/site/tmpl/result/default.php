<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

/** @var \Qt\Component\Quiztools\Site\View\Result\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useStyle('com_quiztools.result');

?>
<div class="quiztools result<?php echo !empty($this->pageclass_sfx) ? ' result-'.$this->pageclass_sfx : ''; ?>">
    <div class="page-header">
        <h1><?php echo Text::_('COM_QUIZTOOLS_RESULT_PAGE_TITLE'); ?></h1>
    </div>
    <div class="quiz-result-wrap">
        <?php echo LayoutHelper::render('result', ['result' => $this->item]); ?>
    </div>
</div>
