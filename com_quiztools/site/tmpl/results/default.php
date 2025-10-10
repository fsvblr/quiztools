<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Qt\Component\Quiztools\Administrator\Helper\QuiztoolsHelper;
use Qt\Component\Quiztools\Site\Helper\RouteHelper;

/** @var \Qt\Component\Quiztools\Site\View\Results\HtmlView $this */

Text::script('COM_QUIZTOOLS_RESULTS_FILTER_PLACEHOLDER_FROM');
Text::script('COM_QUIZTOOLS_RESULTS_FILTER_PLACEHOLDER_TO');

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useStyle('com_quiztools.results')
    ->useScript('com_quiztools.results');

$listOrder = $this->escape($this->state->get('list.ordering', 'a.start_datetime'));
$listDirn  = $this->escape($this->state->get('list.direction', 'DESC'));

$searchToolsOptions = [
    'activeOrder' => $listOrder,
    'activeDirection' => $listDirn,
];

?>
<div class="quiztools results-list<?php echo !empty($this->pageclass_sfx) ? ' results-list-'.$this->pageclass_sfx : ''; ?>">

	<?php if ($this->params->get('show_page_heading')) : ?>
        <h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
	<?php endif; ?>

    <form action="<?php echo Route::_(RouteHelper::getResultsRoute()); ?>" method="post" name="adminForm" id="adminForm">

        <?php if ($this->params->get('show_results_filters')) : ?>
            <div class="results-list__filters">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this, 'options' => $searchToolsOptions]); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($this->items)) : ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('COM_QUIZTOOLS_RESULTS_NO_RESULTS'); ?>
            </div>
        <?php else : ?>
            <div class="results-list__table-wrapper">
                <div class="results-list__table">
                    <div class="results-list__header">
                        <div class="cell w-3">#</div>
                        <div class="cell w-15">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_RESULTS_HEADING_DATETIME', 'a.start_datetime', $listDirn, $listOrder); ?>
                        </div>
                        <?php if ($this->state->get('admin.mode')) : ?>
                            <div class="cell w-15"><?php echo Text::_('COM_QUIZTOOLS_RESULTS_HEADING_USER'); ?></div>
                        <?php endif; ?>
                        <div class="cell w-<?php echo $this->state->get('admin.mode') ? '22' : '37'; ?>">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_RESULTS_HEADING_QUIZ', 'q.title', $listDirn, $listOrder); ?>
                        </div>
                        <div class="cell w-15 text-center">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_RESULTS_HEADING_USER_SCORE', 'a.sum_points_received', $listDirn, $listOrder); ?>
                        </div>
                        <div class="cell w-10 text-center">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_RESULTS_HEADING_PASSED', 'a.passed', $listDirn, $listOrder); ?>
                        </div>
                        <div class="cell w-10 text-center">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_RESULTS_HEADING_SPENT_TIME', 'a.sum_time_spent', $listDirn, $listOrder); ?>
                        </div>
                        <div class="cell w-10 text-center"><?php echo Text::_('COM_QUIZTOOLS_RESULTS_HEADING_CERTIFICATE'); ?></div>
                    </div>
                    <?php foreach ($this->items as $i => $item) : ?>
                        <div class="results-list__row">
                            <div class="cell w-3"><?php echo ($this->pagination->limitstart + 1 + $i); ?></div>
                            <div class="cell w-15">
                                <a href="<?php echo Route::_(RouteHelper::getResultRoute((int) $item->id)); ?>">
                                    <?php echo $item->start_datetime_for_display; ?>
                                </a>
                            </div>
                            <?php if ($this->state->get('admin.mode')) : ?>
                                <div class="cell w-15"><?php echo $this->escape($item->user_name); ?></div>
                            <?php endif; ?>
                            <div class="cell w-<?php echo $this->state->get('admin.mode') ? '22' : '37'; ?>">
                                <?php echo $this->escape($item->title); ?>
                            </div>
                            <div class="cell w-15 text-center">
                                <?php echo number_format($item->sum_points_received, 2, '.', ''); ?>
                            </div>
                            <div class="cell w-10 text-center">
                                <img src="/media/com_quiztools/images/icon-<?php echo $item->passed ? 'check' : 'close'; ?>.svg"
                                     class="results-list__icon-passed"
                                     alt="<?php echo $item->passed
                                         ? Text::_('COM_QUIZTOOLS_RESULTS_PASSED_ALT')
                                         : Text::_('COM_QUIZTOOLS_RESULTS_UNPASSED_ALT'); ?>" />
                            </div>
                            <div class="cell w-10 text-center">
                                <?php echo !empty($item->sum_time_spent) ? QuiztoolsHelper::secondsToTimeString($item->sum_time_spent) : ''; ?>
                            </div>
                            <div class="cell w-10 text-center">
                                <?php if (!empty($item->passed) && !empty($item->results_certificate)): ?>
                                    <?php $token = HTMLHelper::getServiceRegistry()->getService('quiztoolsaccess')->getResultTokenForDisplay($item); ?>
                                    <a href="<?php echo Route::_('index.php?option=com_quiztools&task=result.getCertificate&id=' . (int) $item->id .
                                        '&token=' . $token . '&' . Session::getFormToken() . '=1'); ?>"
                                    >
                                        <img src="/media/com_quiztools/images/icon-award.svg" class="results-list__icon-award"
                                             alt="<?php echo Text::_('COM_QUIZTOOLS_RESULTS_CERTIFICATE_ALT'); ?>" />
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php echo $this->pagination->getPagesLinks(); ?>

        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
