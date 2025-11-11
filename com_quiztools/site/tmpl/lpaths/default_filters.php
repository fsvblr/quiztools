<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/** @var \Qt\Component\Quiztools\Site\View\Lpaths\HtmlView $this */

?>
<div class="lpaths-filters">
    <form action="<?php echo htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm" class="lpaths-filters-form">
        <div>
            <label class="filter-search-lbl visually-hidden" for="filter_search">
		        <?php echo Text::_('COM_QUIZTOOLS_LPATHS_FILTER_SEARCH_LABEL'); ?>
            </label>
            <input type="text" name="filter_search" id="filter_search"
                   value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
                   class="inputbox"
                   placeholder="<?php echo Text::_('COM_QUIZTOOLS_LPATHS_FILTER_SEARCH_PLACEHOLDER'); ?>">

            <button type="submit" name="btn_submit" class="btn btn-primary">
                <?php echo Text::_('COM_QUIZTOOLS_LPATHS_FILTER_SEARCH_BTN'); ?>
            </button>
        </div>

	    <?php //The category filter is displayed only on the page of the "ALL categories" menu item. ?>
	    <?php if (!Factory::getApplication()->getInput()->getInt('catid')): ?>
            <div>
                <label class="filter-category-lbl visually-hidden" for="filter_category_id">
		            <?php echo Text::_('COM_QUIZTOOLS_LPATHS_FILTER_CATEGORY_LABEL'); ?>
                </label>
			    <?php
			    $filter_categoryId = $this->escape($this->state->get('filter.category_id', ''));
			    echo HTMLHelper::getServiceRegistry()->getService('quiztoolsfields')->categorieslist('com_quiztools.lpath', $filter_categoryId);
			    ?>
            </div>
	    <?php endif; ?>
    </form>
</div>
