<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Qt\Component\Quiztools\Administrator\Helper\QuiztoolsHelper;

/** @var \Qt\Component\Quiztools\Site\View\Orders\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useStyle('com_quiztools.orders');

?>
<div class="quiztools orders-list<?php echo !empty($this->pageclass_sfx) ? ' orders-list-'.$this->pageclass_sfx : ''; ?>">

	<?php if ($this->params->get('show_page_heading')) : ?>
        <h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
	<?php endif; ?>

    <?php if ($this->params->get('show_orders_filters')) : ?>
        <?php echo $this->loadTemplate('filters'); ?>
    <?php endif; ?>

    <?php if (empty($this->items)) : ?>
        <div class="alert alert-info">
            <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
            <?php echo Text::_('COM_QUIZTOOLS_ORDERS_NO_ORDERS'); ?>
        </div>
    <?php else : ?>
        <div class="orders-list__list">
            <?php foreach ($this->items as $item) : ?>
                <div class="orders-list__item">
                    <div class="orders-list__item-title">
                        <?php if (!empty($item->accessData->access)): ?>
                            <a href="<?php echo $item->link; ?>">
                                <?php echo $this->escape($item->subscription_title); ?>
                            </a>
                        <?php else: ?>
                            <?php echo $this->escape($item->subscription_title); ?>&nbsp;
                            <span class="orders-list__item-noaccess">
                                <?php if ($item->status === 'P'): ?>
                                    <?php echo Text::_('COM_QUIZTOOLS_ORDERS_TEXT_PENDING'); ?>
                                <?php else: ?>
                                    <?php echo Text::_('COM_QUIZTOOLS_ORDERS_TEXT_EXPIRED'); ?>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                        <div class="orders-list__item-data">
                            <span>
                                <?php echo Text::_('COM_QUIZTOOLS_ORDERS_TEXT_START') ?>:&nbsp;
                                <?php echo QuiztoolsHelper::fromUtcToUsersTimeZone($item->accessData->start); ?>
                            </span>
                            <span class="orders-list__data-separator"></span>
                            <span>
                                <?php echo Text::_('COM_QUIZTOOLS_ORDERS_TEXT_END') ?>:&nbsp;
                                <?php if (is_null($item->accessData->end)): ?>
                                    <?php if ((int) $item->accessData->attempts_max === 0): // unlimited ?>
                                        <?php echo Text::_('COM_QUIZTOOLS_ORDERS_TEXT_LIFETIME_ACCESS') ?>
                                    <?php else: ?>
                                        <?php echo Text::_('COM_QUIZTOOLS_ORDERS_TEXT_DEPENDS_ATTEMPTS') ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php echo QuiztoolsHelper::fromUtcToUsersTimeZone($item->accessData->end); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="orders-list__item-data">
                            <?php echo Text::_('COM_QUIZTOOLS_ORDERS_TEXT_COUNT_ATTEMPTS') ?>:&nbsp;
                            <?php if ((int) $item->accessData->attempts_max === 0): // unlimited ?>
                                <?php echo Text::_('COM_QUIZTOOLS_ORDERS_TEXT_ATTEMPTS_UNLIMITED'); ?>
                            <?php else: ?>
                                <?php echo (int) $item->accessData->attempts_used; ?>&nbsp;
                                <?php echo Text::_('COM_QUIZTOOLS_ORDERS_TEXT_ATTEMPTS_FROM') ?>&nbsp;
                                <?php echo (int) $item->accessData->attempts_max; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($this->items)) : ?>
        <?php if ($this->pagination->pagesTotal > 1) : ?>
            <div class="orders-list__navigation w-100">
                <p class="orders-list__counter counter float-end pt-3 pe-2">
                    <?php echo $this->pagination->getPagesCounter(); ?>
                </p>
                <div class="orders-list__pagination">
                    <?php echo $this->pagination->getPagesLinks(); ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
