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

/** @var \Qt\Component\Quiztools\Administrator\View\Questions\HtmlView $this */

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
	->useScript('multiselect');

$user      = $this->getCurrentUser();
$user_id    = $user->id;
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'a.ordering';

if ($saveOrder && !empty($this->items)) {
	$saveOrderingUrl = 'index.php?option=com_quiztools&task=questions.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}
?>
<form action="<?php echo Route::_('index.php?option=com_quiztools&view=questions'); ?>" method="post" name="adminForm" id="adminForm">
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
                    <table class="table" id="questionsList">
                        <caption class="visually-hidden">
							<?php echo Text::_('COM_QUIZTOOLS_QUESTIONS_TABLE_CAPTION'); ?>,
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                            <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                        </caption>
                        <thead>
                        <tr>
                            <td class="w-1 text-center">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
                            </td>
                            <th scope="col" class="w-1 text-center d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'desc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
                            </th>
                            <th scope="col" class="w-1 text-center">
								<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col">
								<?php echo Text::_('COM_QUIZTOOLS_QUESTIONS_HEADING_TEXT'); ?>
                            </th>
                            <th scope="col" class="w-15 text-center d-none d-md-table-cell">
		                        <?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_QUESTIONS_HEADING_QUIZ', 'quiz_title', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-15 text-center d-none d-md-table-cell">
		                        <?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_QUESTIONS_HEADING_TYPE', 'a.type', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-15 text-center d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_QUESTIONS_HEADING_CATEGORY', 'category_title', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-5 text-center d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                            </th>
                        </tr>
                        </thead>
                        <tbody <?php if ($saveOrder) :
							?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"<?php
						endif; ?>>
						<?php foreach ($this->items as $i => $item) :
							$ordering  = ($listOrder == 'ordering');
							$item->cat_link = Route::_('index.php?option=com_categories&extension=com_quiztools.question&task=edit&type=other&cid[]=' . $item->catid);
							$canCreate  = $user->authorise('core.create', 'com_quiztools.category.' . $item->catid);
							$canEdit    = $user->authorise('core.edit', 'com_quiztools.category.' . $item->catid);
							$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user_id || is_null($item->checked_out);
							$canChange  = $user->authorise('core.edit.state', 'com_quiztools.category.' . $item->catid) && $canCheckin;

							$item->text = $this->escape(strip_tags($item->text));
                            if(mb_strlen($item->text, 'UTF-8') > 60) {
	                            $item->text = mb_substr($item->text, 0, 60, 'UTF-8') . '...';
                            }
							?>
                            <tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo $item->catid; ?>">
                                <td class="text-center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->text); ?>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
									<?php
									$iconClass = '';
									if (!$canChange) {
										$iconClass = ' inactive';
									} elseif (!$saveOrder) {
										$iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
									}
									?>
                                    <span class="sortable-handler <?php echo $iconClass ?>">
                                        <span class="icon-ellipsis-v" aria-hidden="true"></span>
                                    </span>
									<?php if ($canChange && $saveOrder) : ?>
                                        <input type="text" name="order[]" size="5"
                                               value="<?php echo $item->ordering; ?>" class="width-20 text-area-order hidden">
									<?php endif; ?>
                                </td>
                                <td class="text-center">
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'questions.', $canChange, 'cb'); ?>
                                </td>
                                <th scope="row">
                                    <div class="break-word">
										<?php if ($item->checked_out) : ?>
											<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'questions.', $canCheckin); ?>
										<?php endif; ?>
										<?php if ($canEdit) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_quiztools&task=question.edit&id=' . (int) $item->id); ?>"
                                               title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $item->text; ?>">
												<?php echo $item->text; ?></a>
										<?php else : ?>
											<?php echo $item->text; ?>
										<?php endif; ?>
                                    </div>
                                </th>
                                <td class="text-center d-none d-md-table-cell">
		                            <?php echo ((int)$item->quiz_id === 0)
                                        ? Text::_('COM_QUIZTOOLS_QUESTIONS_QUESTION_POOL')
                                        : $this->escape($item->quiz_title); ?>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
		                            <?php echo Text::_('PLG_QUIZTOOLS_QUESTION_TYPE_' . strtoupper($item->type) . '_NAME'); ?>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
									<?php echo $this->escape($item->category_title); ?>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
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
