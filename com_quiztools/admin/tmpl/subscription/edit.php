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

/** @var \Qt\Component\Quiztools\Administrator\View\Subscription\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');
$wa->useScript('com_quiztools.admin.subscription');

?>
<form action="<?php echo Route::_('index.php?option=com_quiztools&view=subscription&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="subscription-form" class="form-validate"
      aria-label="<?php echo Text::_('COM_QUIZTOOLS_SUBSCRIPTION_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>">

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'general', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('COM_QUIZTOOLS_SUBSCRIPTION_TAB_GENERAL')); ?>
        <div class="row">
            <div class="col-md-6">
                <fieldset id="fieldset-basicdata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_SUBSCRIPTION_FIELDSET_BASICDATA'); ?></legend>
                    <div>
                        <?php echo $this->form->renderField('title'); ?>
                        <?php echo $this->form->renderField('state'); ?>
                        <?php echo $this->form->renderField('payment_method'); ?>

                        <div
                            class="control-group"
                            data-showon='[{"field":"jform[payment_method]","values":["manual"],"sign":"=","op":""}]'
                            style="margin-top: -1rem;"
                        >
                            <div class="control-label"></div>
                            <div class="controls">
                                <small class="form-text">
                                    <?php echo Text::_($this->form->getField('payment_manual_note')
                                        ->getAttribute('description')); ?>
                                </small>
                            </div>
                        </div>

                        <?php if (!empty($this->item->select_product_id) && \is_array($this->item->select_product_id)): ?>
                            <?php foreach ($this->item->select_product_id as $payment_method => $product_id): ?>
                                <?php echo $this->form->renderField('select_product_id][' . $payment_method); ?>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php echo $this->form->renderField('users_max'); ?>
                    </div>
                </fieldset>
            </div>
            <div class="col-md-6">
                <fieldset id="fieldset-content" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_SUBSCRIPTION_FIELDSET_CONTENT'); ?></legend>
                    <div>
                        <?php echo $this->form->renderField('type'); ?>
                        <?php echo $this->form->renderField('quiz_id'); ?>
                        <?php echo $this->form->renderField('lpath_id'); ?>
                        <?php echo $this->form->renderField('access_type'); ?>
                        <?php echo $this->form->renderField('access_days'); ?>
                        <?php echo $this->form->renderField('access_from'); ?>
                        <?php echo $this->form->renderField('access_to'); ?>
                        <?php echo $this->form->renderField('attempts'); ?>
                    </div>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

	    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('COM_QUIZTOOLS_SUBSCRIPTION_TAB_PUBLISHING')); ?>
        <div class="row">
            <div class="col-md-6">
                <fieldset id="fieldset-publishingdata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_SUBSCRIPTION_FIELDSET_PUBLISHINGDATA'); ?></legend>
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
