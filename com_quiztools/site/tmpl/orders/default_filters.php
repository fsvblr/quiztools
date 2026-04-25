<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/** @var \Qt\Component\Quiztools\Site\View\Orders\HtmlView $this */

?>
<div class="orders-filters">
    <form action="<?php echo htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm" class="orders-filters-form">
        <div>
            <label class="filter-search-lbl visually-hidden" for="filter_search">
		        <?php echo Text::_('COM_QUIZTOOLS_ORDERS_FILTER_SEARCH_LABEL'); ?>
            </label>
            <input type="text" name="filter_search" id="filter_search"
                   value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
                   class="inputbox"
                   placeholder="<?php echo Text::_('COM_QUIZTOOLS_ORDERS_FILTER_SEARCH_PLACEHOLDER'); ?>">

            <button type="submit" name="btn_submit" class="btn btn-primary">
                <?php echo Text::_('COM_QUIZTOOLS_ORDERS_FILTER_SEARCH_BTN'); ?>
            </button>
        </div>
    </form>
</div>
