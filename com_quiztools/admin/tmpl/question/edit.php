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

/** @var \Qt\Component\Quiztools\Administrator\View\Question\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate');

?>
<form action="<?php echo Route::_('index.php?option=com_quiztools&view=question&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="question-form" class="form-validate"
      aria-label="<?php echo Text::_('COM_QUIZTOOLS_QUESTION_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>">

    <div class="main-card">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'general', 'recall' => true, 'breakpoint' => 768]); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('COM_QUIZTOOLS_QUESTION_TAB_GENERAL')); ?>
        <div class="row">
            <div class="col-md-6">
                <fieldset id="fieldset-basicdata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_QUESTION_FIELDSET_BASICDATA'); ?></legend>
                    <div>
	                    <?php echo $this->form->renderField('id'); ?>
						<?php echo $this->form->renderField('type'); ?>
						<?php echo $this->form->renderField('state'); ?>
						<?php echo $this->form->renderField('quiz_id'); ?>
						<?php echo $this->form->renderField('catid'); ?>
	                    <?php if(!$this->is_boilerplate): ?>
                            <?php echo $this->form->renderField('attempts'); ?>
                            <?php echo $this->form->renderField('points'); ?>
                            <?php echo $this->form->renderField('penalty'); ?>
	                    <?php endif; ?>
                    </div>
                </fieldset>
	            <?php if(!empty($this->question_type_form) && !empty($this->question_type_form->getFieldset('basic'))): ?>
                <fieldset id="fieldset-optionsbasicdata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_QUESTION_FIELDSET_OPTIONSBASICDATA'); ?></legend>
                    <div>
                        <?php echo $this->question_type_form->renderFieldset('basic'); ?>
                    </div>
                </fieldset>
	            <?php endif; ?>
            </div>
            <div class="col-md-6">
                <fieldset id="fieldset-descriptiondata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_QUESTION_FIELDSET_DESCRIPTIONDATA'); ?></legend>
		            <?php echo $this->form->getInput('text'); ?>
                </fieldset>
            </div>
        </div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

	    <?php if(!$this->is_boilerplate): ?>
            <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'feedback', Text::_('COM_QUIZTOOLS_QUESTION_TAB_FEEDBACK')); ?>
            <div class="row">
                <div class="col-md-12">
                    <fieldset id="fieldset-feedbackdata" class="options-form">
                        <legend><?php echo Text::_('COM_QUIZTOOLS_QUESTION_FIELDSET_FEEDBACKDATA'); ?></legend>
                        <?php echo $this->form->renderField('feedback'); ?>
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo $this->form->renderField('feedback_msg_right'); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo $this->form->renderField('feedback_msg_wrong'); ?>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>

            <?php if(!empty($this->question_type_form) && !empty($this->question_type_form->getFieldset('feedback'))): ?>
            <div class="row">
                <div class="col-md-8">
                    <fieldset id="fieldset-question-feedbackdata" class="options-form">
                        <legend><?php echo Text::_('COM_QUIZTOOLS_QUESTION_FIELDSET_QUESTION_FEEDBACKDATA'); ?></legend>
                        <div>
                            <?php echo $this->question_type_form->renderFieldset('feedback'); ?>
                        </div>
                    </fieldset>
                </div>
            </div>
            <?php endif; ?>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>
	    <?php endif; ?>

	    <?php if(!empty($this->question_type_form) && !empty($this->question_type_form->getFieldset('options'))): ?>
		    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'options', Text::_('COM_QUIZTOOLS_QUESTION_TAB_OPTIONS')); ?>
            <div class="row">
                <div class="col-md-12">
                    <fieldset id="fieldset-optionsdata" class="options-form">
                        <legend><?php echo Text::_('COM_QUIZTOOLS_QUESTION_FIELDSET_OPTIONSDATA'); ?></legend>
                        <div>
	                        <?php echo $this->question_type_form->renderFieldset('options'); ?>
                        </div>
                    </fieldset>
                </div>
            </div>
		    <?php echo HTMLHelper::_('uitab.endTab'); ?>
	    <?php endif; ?>

		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <?php echo $this->form->renderField('created'); ?>
    <?php echo $this->form->renderField('created_by'); ?>
    <?php echo $this->form->renderField('modified'); ?>
    <?php echo $this->form->renderField('modified_by'); ?>

    <input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
