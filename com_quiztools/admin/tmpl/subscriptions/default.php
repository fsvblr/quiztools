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

/** @var \Qt\Component\Quiztools\Administrator\View\Subscriptions\HtmlView $this */

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$user      = $this->getCurrentUser();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'a.ordering';

if ($saveOrder && !empty($this->items)) {
	$saveOrderingUrl = 'index.php?option=com_quiztools&task=subscriptions.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}

?>
<form action="<?php echo Route::_('index.php?option=com_quiztools&view=subscriptions'); ?>" method="post" name="adminForm" id="adminForm">
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
                    <table class="table" id="subscriptionsList">
                        <caption class="visually-hidden">
							<?php echo Text::_('COM_QUIZTOOLS_SUBSCRIPTIONS_TABLE_CAPTION'); ?>,
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
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_SUBSCRIPTIONS_HEADING_TITLE', 'a.title', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-15 text-center d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_SUBSCRIPTIONS_HEADING_PAYMENT_METHOD', 'a.payment_method', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-25 text-center d-none d-md-table-cell">
                                <?php echo Text::_('COM_QUIZTOOLS_SUBSCRIPTIONS_HEADING_PRODUCT_TITLE'); ?>
                            </th>
                            <!--<th scope="col" class="w-5 text-center d-none d-md-table-cell">
                                <?php //echo Text::_('COM_QUIZTOOLS_SUBSCRIPTIONS_HEADING_USERS'); ?>
                            </th>-->
                            <th scope="col" class="w-5 text-center d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                            </th>
                        </tr>
                        </thead>
                        <tbody <?php if ($saveOrder) :
							?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"<?php
						endif; ?>>
						<?php foreach ($this->items as $i => $item) :
							$canEdit    = $user->authorise('core.edit', 'com_quiztools');
							$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->id || is_null($item->checked_out);
							$canChange  = $user->authorise('core.edit.state', 'com_quiztools') && $canCheckin;
							?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td class="text-center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
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
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'subscriptions.', $canChange, 'cb'); ?>
                                </td>
                                <th scope="row">
                                    <div class="break-word">
										<?php if ($item->checked_out) : ?>
											<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'subscriptions.', $canCheckin); ?>
										<?php endif; ?>
										<?php if ($canEdit) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_quiztools&task=subscription.edit&id=' . (int) $item->id); ?>"
                                               title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->title); ?>">
												<?php echo $this->escape($item->title); ?></a>
										<?php else : ?>
											<?php echo $this->escape($item->title); ?>
										<?php endif; ?>
                                    </div>
                                </th>
                                <td class="text-center d-none d-md-table-cell">
                                    <?php echo $this->escape($item->payment_method); ?>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
                                    <?php if ((string) $item->payment_method === 'manual'): ?>
                                        <?php echo '*'; ?>
                                    <?php else: ?>
                                        <?php if ($canEdit && !empty($item->product_admin_link) && !empty($item->product_title)) : ?>
                                            <a href="<?php echo Route::_($item->product_admin_link); ?>"
                                               title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->product_title); ?>"
                                               target="_blank" rel="noopener noreferrer">
                                                <?php echo $this->escape($item->product_title); ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo !empty($item->product_title) ? $this->escape($item->product_title) : ''; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <!--<td class="text-center d-none d-md-table-cell">
                                    <?php //echo (int) $item->users_max; ?>
                                </td>-->
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
