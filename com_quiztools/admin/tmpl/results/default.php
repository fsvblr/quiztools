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
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Qt\Component\Quiztools\Administrator\Helper\QuiztoolsHelper;

/** @var \Qt\Component\Quiztools\Administrator\View\Results\HtmlView $this */

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useStyle('com_quiztools.admin.results')
    ->useScript('table.columns')
    ->useScript('multiselect')
    ->useScript('com_quiztools.admin.results')
;

$user      = $this->getCurrentUser();
$user_id   = $user->id;
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

?>
<form action="<?php echo Route::_('index.php?option=com_quiztools&view=results'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
				<?php
				echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]);
				?>
				<?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
						<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
				<?php else : ?>
                    <table class="table" id="resultsList">
                        <caption class="visually-hidden">
							<?php echo Text::_('COM_QUIZTOOLS_RESULTS_TABLE_CAPTION'); ?>,
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                            <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                        </caption>
                        <thead>
                        <tr>
                            <td class="w-1 text-center">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
                            </td>
                            <th scope="col" class="w-20">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_RESULTS_HEADING_DATETIME', 'a.start_datetime', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-15">
                                <?php echo Text::_('COM_QUIZTOOLS_RESULTS_HEADING_USER'); ?>
                            </th>
                            <th scope="col" class="w-15">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_RESULTS_HEADING_QUIZ', 'q.title', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-5 text-center">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_RESULTS_HEADING_TOTAL_SCORE', 'a.total_score', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-5 text-center">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_RESULTS_HEADING_PASSING_SCORE', 'a.passing_score', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-5 text-center">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_RESULTS_HEADING_USER_SCORE', 'a.sum_points_received', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-5 text-center">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_RESULTS_HEADING_PASSED', 'a.passed', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-5 text-center">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_RESULTS_HEADING_FINISHED', 'a.finished', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-9 text-center">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_RESULTS_HEADING_SPENT_TIME', 'a.sum_time_spent', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-5 text-center">
                                <?php echo Text::_('COM_QUIZTOOLS_RESULTS_HEADING_PDF'); ?>
                            </th>
                            <th scope="col" class="w-5 text-center">
                                <?php echo Text::_('COM_QUIZTOOLS_RESULTS_HEADING_CERTIFICATE'); ?>
                            </th>
                            <th scope="col" class="w-5 text-center">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_RESULTS_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
						<?php
						$canEdit = $user->authorise('core.edit', 'com_quiztools');
                        foreach ($this->items as $i => $item) :
							?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td class="text-center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
                                </td>
                                <th scope="row">
                                    <div class="break-word">
										<?php if ($canEdit) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_quiztools&view=result&id=' . (int) $item->id); ?>"
                                               title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $item->start_datetime_for_display; ?>">
												<?php echo $item->start_datetime_for_display; ?></a>
										<?php else : ?>
											<?php echo $item->start_datetime_for_display; ?>
										<?php endif; ?>
                                    </div>
                                </th>
                                <td>
                                    <?php echo $this->escape($item->user_name); ?>
                                </td>
                                <td>
                                    <?php echo $this->escape($item->title); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo number_format($item->total_score, 2, '.', ''); ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $passingScore = (float) $item->total_score * ((float) $item->passing_score / 100 );
                                    $passingScore = round($passingScore, 2);
                                    echo number_format($passingScore, 2, '.', '') . ' (' . $item->passing_score . '%)';
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php echo number_format($item->sum_points_received, 2, '.', ''); ?>
                                </td>
                                <td class="text-center">
                                    <span class="icon-<?php echo (int) $item->passed ? 'check passed' :
                                        'times failed'; ?> icon-results-passed" aria-hidden="true"></span>
                                </td>
                                <td class="text-center">
                                    <div class="tbody-icon">
                                        <span class="icon-<?php echo (int) $item->finished ? 'publish finished' :
                                            'unpublish not-finished'; ?> icon-results-finished" aria-hidden="true"></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php echo !empty($item->sum_time_spent) ? QuiztoolsHelper::secondsToTimeString($item->sum_time_spent) : ''; ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?php echo Route::_('index.php?option=com_quiztools&task=result.getPdf&id=' . (int) $item->id . '&' . Session::getFormToken() . '=1'); ?>"
                                        class="icon-results-pdf" title="<?php echo Text::_('COM_QUIZTOOLS_RESULTS_GENERATE_PDF'); ?>">
                                        <i class="fa-regular fa-file-pdf"></i>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <?php if ((int) $item->passed): ?>
                                        <a href="<?php echo Route::_('index.php?option=com_quiztools&task=result.getCertificate&id=' . (int) $item->id . '&' . Session::getFormToken() . '=1'); ?>"
                                           class="icon-results-certificate" title="<?php echo Text::_('COM_QUIZTOOLS_RESULTS_GENERATE_CERTIFICATE'); ?>">
                                            <i class="fa-regular fa-file-lines"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
									<?php echo $item->id; ?>
                                </td>
                            </tr>
						<?php endforeach; ?>
                        </tbody>
                    </table>

					<?php echo $this->pagination->getListFooter(); ?>
				<?php endif; ?>

                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
				<?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
