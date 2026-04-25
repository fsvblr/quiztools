<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\CalendarField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Qt\Component\Quiztools\Administrator\Helper\QuiztoolsHelper;

/** @var \Qt\Component\Quiztools\Administrator\View\Order\HtmlView $this */

$document = $this->getDocument();

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useStyle('com_quiztools.admin.order')
    ->useScript('keepalive')
    ->useScript('form.validate')
    ->useScript('joomla.dialog')
    ->useScript('com_quiztools.admin.order.dialog.reactivate');

Text::script('COM_QUIZTOOLS_ORDER_DIALOG_REACTIVATE_HEADER');
Text::script('COM_QUIZTOOLS_ORDER_DIALOG_REACTIVATE_BTN_CANCEL');
Text::script('COM_QUIZTOOLS_ORDER_DIALOG_REACTIVATE_BTN_REACTIVATE');

?>
<form action="<?php echo Route::_('index.php?option=com_quiztools&view=order&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="order-form" class="form-validate"
      aria-label="<?php echo Text::_('COM_QUIZTOOLS_ORDER_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>">

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'general', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('COM_QUIZTOOLS_ORDER_TAB_GENERAL')); ?>
        <div class="row">
            <div class="col-md-6">
                <fieldset id="fieldset-basicdata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_ORDER_FIELDSET_BASICDATA'); ?></legend>
                    <div>
                        <?php if (empty($this->item->id)) : ?>
                            <?php echo $this->form->renderField('subscription_id'); ?>
                            <?php echo $this->form->renderField('user_id'); ?>
                        <?php else: ?>
                            <div class="control-group">
                                <div class="control-label">
                                    <?php echo Text::_('COM_QUIZTOOLS_ORDER_TEXTLABEL_SUBSCRIPTION_ID'); ?>
                                </div>
                                <div class="controls hstack">
                                    <?php echo $this->escape($this->item->subscription->title); ?>
                                </div>
                            </div>
                            <input type="hidden" name="jform[subscription_id]" id="jform_subscription_id"
                                   value="<?php echo (int) $this->item->subscription_id; ?>">

                            <div class="control-group">
                                <div class="control-label">
                                    <?php echo Text::_('COM_QUIZTOOLS_ORDER_TEXTLABEL_USER_ID'); ?>
                                </div>
                                <div class="controls hstack">
                                    <?php echo $this->escape($this->item->user_name) . ' [' . $this->escape($this->item->user_email) . ']'; ?>
                                </div>
                            </div>
                            <input type="hidden" name="jform[user_id]" id="jform_user_id"
                                   value="<?php echo (int) $this->item->user_id; ?>">
                        <?php endif; ?>

                        <?php if (empty($this->item->id) || $this->item->store_type === 'manual'): ?>
                            <?php echo $this->form->renderField('status'); ?>
                        <?php else: ?>
                            <div class="control-group">
                                <div class="control-label">
                                    <?php echo Text::_('COM_QUIZTOOLS_ORDER_TEXTLABEL_STATUS'); ?>
                                </div>
                                <div class="controls hstack">
                                    <?php echo Text::_('COM_QUIZTOOLS_ORDER_STATUS_' . $this->escape($this->item->status)); ?>
                                </div>
                            </div>
                            <input type="hidden" name="jform[status]" id="jform_status"
                                   value="<?php echo $this->escape($this->item->status); ?>">
                        <?php endif; ?>
                    </div>
                </fieldset>
            </div>
            <div class="col-md-6">
                <fieldset id="fieldset-detailsdata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_ORDER_FIELDSET_DETAILSDATA'); ?></legend>
                    <div>
                        <div class="control-group">
                            <div class="control-label">
                                <?php echo Text::_('COM_QUIZTOOLS_ORDER_TEXTLABEL_STORE_TYPE'); ?>:
                            </div>
                            <div class="controls hstack">
                                <?php if (empty($this->item->id)) : ?>
                                    <?php echo Text::_('COM_QUIZTOOLS_ORDER_STORE_TYPE_MANUAL'); ?>
                                <?php else: ?>
                                    <?php if (!empty($this->item->store_type)) : ?>
                                        <?php echo $this->escape($this->item->store_type); ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($this->item->store_order_id)) : ?>
                            <div class="control-group">
                                <div class="control-label">
                                    <?php echo Text::_('COM_QUIZTOOLS_ORDER_TEXTLABEL_STORE_ORDER_ID'); ?>:
                                </div>
                                <div class="controls hstack">
                                    <?php echo (int) $this->item->store_order_id; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($this->item->store_product_id)) : ?>
                            <div class="control-group">
                                <div class="control-label">
                                    <?php echo Text::_('COM_QUIZTOOLS_ORDER_TEXTLABEL_STORE_PRODUCT_ID'); ?>:
                                </div>
                                <div class="controls hstack">
                                    <?php echo (int) $this->item->store_product_id; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($this->item->id)) : ?>
                            <div class="control-group">
                                <div class="control-label">
                                    <?php echo Text::_('COM_QUIZTOOLS_ORDER_TEXTLABEL_START_DATETIME'); ?>:
                                </div>
                                <div class="controls hstack">
                                    <?php echo QuiztoolsHelper::fromUtcToUsersTimeZone($this->item->start_datetime); ?>
                                </div>
                            </div>
                            <div class="control-group">
                                <div class="control-label">
                                    <?php echo Text::_('COM_QUIZTOOLS_ORDER_TEXTLABEL_END_DATETIME'); ?>:
                                </div>
                                <div class="controls hstack">
                                    <?php if (is_null($this->item->end_datetime)):  // "lifetime" access, unlimited days ?>
                                        <?php if ((int) $this->item->attempts_max === 0): // unlimited ?>
                                            <?php echo Text::_('COM_QUIZTOOLS_ORDER_TEXT_LIFETIME_ACCESS') ?>
                                        <?php else: ?>
                                            <?php echo Text::_('COM_QUIZTOOLS_ORDER_TEXT_DEPENDS_ATTEMPTS') ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php echo QuiztoolsHelper::fromUtcToUsersTimeZone($this->item->end_datetime); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="control-group">
                                <div class="control-label">
                                    <?php echo Text::_('COM_QUIZTOOLS_ORDER_TEXTLABEL_ATTEMPTS_MAX'); ?>:
                                </div>
                                <div class="controls hstack">
                                    <?php if (isset($this->item->attempts_max) && (int) $this->item->attempts_max === 0):  // unlimited ?>
                                        <?php echo Text::_('COM_QUIZTOOLS_ORDER_TEXT_UNLIMITED_ATTEMPTS'); ?>
                                    <?php else: ?>
                                        <?php echo (int) $this->item->attempts_max; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (isset($this->item->users_used)) : ?>
                                <div class="control-group">
                                    <div class="control-label">
                                        <?php echo Text::_('COM_QUIZTOOLS_ORDER_TEXTLABEL_ATTEMPTS_USED'); ?>:
                                    </div>
                                    <div class="controls hstack">
                                        <?php echo (int) $this->item->attempts_used; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php // ToDo: For further development: sharing purchased subscription with subordinate users: ?>
                        <?php //if (!empty($this->item->users_used)) : ?>
                            <!--<div class="control-group">
                                <div class="control-label">
                                    <?php //echo Text::_('COM_QUIZTOOLS_ORDER_TEXTLABEL_USERS_MAX'); ?>:
                                </div>
                                <div class="controls hstack">
                                    <?php //echo (int) $this->item->subscription->users_max; ?>
                                </div>
                            </div>
                            <div class="control-group">
                                <div class="control-label">
                                    <?php //echo Text::_('COM_QUIZTOOLS_ORDER_TEXTLABEL_USERS_USED'); ?>:
                                </div>
                                <div class="controls hstack">
                                    <?php //echo (int) $this->item->users_used; ?>
                                </div>
                            </div>-->
                        <?php //endif; ?>
                    </div>
                </fieldset>

                <?php echo $this->form->renderField('users_used'); ?>
                <?php echo $this->form->renderField('start_datetime'); ?>
                <?php echo $this->form->renderField('end_datetime'); ?>
                <?php echo $this->form->renderField('attempts_max'); ?>
                <?php echo $this->form->renderField('store_type'); ?>
                <?php echo $this->form->renderField('store_order_id'); ?>
                <?php echo $this->form->renderField('store_product_id'); ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

	    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('COM_QUIZTOOLS_ORDER_TAB_PUBLISHING')); ?>
        <div class="row">
            <div class="col-md-6">
                <fieldset id="fieldset-publishingdata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_ORDER_FIELDSET_PUBLISHINGDATA'); ?></legend>
                    <div>
	                    <?php echo $this->form->renderField('id'); ?>
					    <?php echo $this->form->renderField('created'); ?>
					    <?php echo $this->form->renderField('created_by'); ?>
					    <?php echo $this->form->renderField('modified'); ?>
					    <?php echo $this->form->renderField('modified_by'); ?>
                    </div>
                </fieldset>
            </div>
        </div>
	    <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<!-- Order Reactivate -->
<?php if (!empty($this->item->id)) : ?>
<template id="order-reactivate" >
    <form action="#" method="post" name="reactivate-form" id="reactivate-form" class="reactivate-form form-validate">
        <div class="reactivate-note">
            <?php echo Text::_('COM_QUIZTOOLS_ORDER_DIALOG_REACTIVATE_NOTE'); ?>
        </div>
        <div class="control-group">
            <div class="control-label">
                <label id="reactivate-access-to-lbl" for="reactivate-access-to">
                    <?php echo Text::_('COM_QUIZTOOLS_ORDER_DIALOG_REACTIVATE_ACCESS_TO_LABEL'); ?>
                </label>
            </div>
            <div class="controls">
                <?php
                $end_datetime = $this->form->getValue('end_datetime');
                $end_datetime = !empty($end_datetime) ? (new \DateTime($end_datetime))->format('Y-m-d') : '';

                $calendar = new CalendarField;
                $calendar->setDatabase(Factory::getContainer()->get(DatabaseInterface::class));
                $calendarElement = new SimpleXMLElement(
                        '<field
                            name="reactivate[access_to]"
                            id="reactivate-access-to"
                            filter="none"
                            singleheader="false"
                            weeknumbers="false"
                        />'
                );
                $calendar->setup($calendarElement, $end_datetime);
                echo $calendar->input;
                ?>
                <div class="reactivate-access-to-desc">
                    <small class="form-text">
                        <?php echo Text::_('COM_QUIZTOOLS_ORDER_DIALOG_REACTIVATE_ACCESS_TO_NOTE'); ?>
                    </small>
                </div>
            </div>
        </div>
        <div class="control-group">
            <div class="control-label">
                <label id="reactivate-attempts-lbl" for="reactivate-attempts">
                    <?php echo Text::_('COM_QUIZTOOLS_ORDER_DIALOG_REACTIVATE_ATTEMPTS_LABEL'); ?>
                </label>
            </div>
            <div class="controls">
                <?php
                $attempts = (int) $this->form->getValue('attempts_max');
                $attempts = !empty($attempts) ? $attempts : 0;
                ?>
                <input type="number" inputmode="numeric" name="reactivate[attempts]" id="reactivate-attempts"
                       value="<?php echo $attempts; ?>" class="input-small" max="10000" step="1" min="0">
                <div class="reactivate-attempts-desc">
                    <small class="form-text">
                        <?php echo Text::_('COM_QUIZTOOLS_ORDER_DIALOG_REACTIVATE_ATTEMPTS_NOTE'); ?>
                    </small>
                </div>
            </div>
        </div>
        <input type="hidden" name="reactivate[order_id]" value="<?php echo (int) $this->item->id; ?>">
        <input type="hidden" name="task" value="order.reactivate">
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</template>
<?php endif; ?>
