<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Qt\Component\Quiztools\Administrator\View\Selectquestiontype\HtmlView $this */

?>
<div class="new-modules">
    <?php if (empty($this->items)) : ?>
        <div class="alert alert-info">
            <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
            <?php echo Text::_('COM_QUIZTOOLS_SELECTQUESTIONTYPE_NO_MATCHING_RESULTS'); ?>
        </div>
    <?php else : ?>
        <h2 class="pb-3 ms-3">
            <?php echo Text::_('COM_QUIZTOOLS_SELECTQUESTIONTYPE_TYPE_CHOOSE'); ?>
        </h2>
        <div class="main-card card-columns p-4">
            <?php foreach ($this->items as $item) : ?>
                <?php $link = 'index.php?option=com_quiztools&task=question.add&type=' . $item->type; ?>
                <?php $name = $this->escape($item->name); ?>
                <?php $desc = HTMLHelper::_('string.truncate', $this->escape(strip_tags($item->desc)), 200); ?>
                <a href="<?php echo Route::_($link); ?>" class="new-module mb-3"
                   aria-label="<?php echo Text::sprintf('COM_QUIZTOOLS_SELECTQUESTIONTYPE_SELECT_TYPE', $name); ?>">
                    <div class="new-module-details">
                        <h3 class="new-module-title"><?php echo $name; ?></h3>
                        <p class="new-module-caption p-0">
                            <?php echo $desc; ?>
                        </p>
                    </div>
                    <span class="new-module-link">
                        <span class="icon-plus" aria-hidden="true"></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
