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

/** @var \Qt\Component\Quiztools\Administrator\View\Orders\HtmlView $this */

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useStyle('com_quiztools.admin.orders')
    ->useScript('table.columns')
    ->useScript('multiselect');

$user      = $this->getCurrentUser();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

?>
<form action="<?php echo Route::_('index.php?option=com_quiztools&view=orders'); ?>" method="post" name="adminForm" id="adminForm">
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
                    <table class="table" id="ordersList">
                        <caption class="visually-hidden">
							<?php echo Text::_('COM_QUIZTOOLS_ORDERS_TABLE_CAPTION'); ?>,
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                            <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                        </caption>
                        <thead>
                        <tr>
                            <td class="w-1 text-center">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
                            </td>
                            <th scope="col" class="d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_ORDERS_HEADING_SUBSCRIPTION_TITLE', 'subscription_title', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-20 text-center d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_ORDERS_HEADING_USER', 'user_name', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-20 text-center d-none d-md-table-cell">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_ORDERS_HEADING_STATUS', 'a.status', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-20 text-center d-none d-md-table-cell">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_QUIZTOOLS_ORDERS_HEADING_STORE_TYPE', 'a.store_type', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-5 text-center d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
						<?php foreach ($this->items as $i => $item) :
							$canEdit    = $user->authorise('core.edit', 'com_quiztools');
							$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->id || is_null($item->checked_out);
							$canChange  = $user->authorise('core.edit.state', 'com_quiztools') && $canCheckin;
							?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td class="text-center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->subscription_title); ?>
                                </td>
                                <th scope="row">
                                    <div class="break-word">
										<?php if ($item->checked_out) : ?>
											<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'orders.', $canCheckin); ?>
										<?php endif; ?>
										<?php if ($canEdit) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_quiztools&task=order.edit&id=' . (int) $item->id); ?>"
                                               title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->subscription_title); ?>">
												<?php echo $this->escape($item->subscription_title); ?></a>
										<?php else : ?>
											<?php echo $this->escape($item->subscription_title); ?>
										<?php endif; ?>
                                    </div>
                                </th>
                                <td class="text-center d-md-table-cell">
                                    <?php echo $this->escape($item->user_name); ?>
                                </td>
                                <td class="text-center d-none d-md-table-cell
                                        order-status-<?php echo !empty($item->status) ? mb_strtolower($this->escape($item->status), 'UTF-8') : ''; ?>">
                                    <?php echo !empty($item->status) ?  Text::_('COM_QUIZTOOLS_ORDERS_STATUS_' . $this->escape($item->status)) : ''; ?>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
                                    <?php echo $this->escape($item->store_type); ?>
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
