<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Qt\Component\Quiztools\Site\Helper\RouteHelper;

/** @var \Qt\Component\Quiztools\Site\View\Lpaths\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useStyle('com_quiztools.lpaths');

?>
<div class="quiztools lpaths-list<?php echo !empty($this->pageclass_sfx) ? ' lpaths-list-'.$this->pageclass_sfx : ''; ?>">

	<?php if ($this->params->get('show_page_heading')) : ?>
        <h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
	<?php endif; ?>

    <?php if ($this->params->get('show_lpaths_filters')) : ?>
        <?php echo $this->loadTemplate('filters'); ?>
    <?php endif; ?>

    <?php if (empty($this->items)) : ?>
        <div class="alert alert-info">
            <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
            <?php echo Text::_('COM_QUIZTOOLS_LPATHS_NO_LPATHS'); ?>
        </div>
    <?php else : ?>
        <div class="lpaths-list__list">
            <?php foreach ($this->items as $item) : ?>
                <div class="lpaths-list__item">
                    <div class="lpaths-list__item-title">
                        <a href="<?php echo Route::_(RouteHelper::getLpathRoute($item->id, $item->catid)); ?>">
                            <?php echo $this->escape($item->title); ?>
                        </a>
                    </div>
                    <?php if (!empty($item->description)): ?>
                        <div class="lpaths-list__item-desc">
                            <?php echo $item->description; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($this->items)) : ?>
        <?php if ($this->pagination->pagesTotal > 1) : ?>
            <div class="lpaths-list__navigation w-100">
                <p class="lpaths-list__counter counter float-end pt-3 pe-2">
                    <?php echo $this->pagination->getPagesCounter(); ?>
                </p>
                <div class="lpaths-list__pagination">
                    <?php echo $this->pagination->getPagesLinks(); ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
