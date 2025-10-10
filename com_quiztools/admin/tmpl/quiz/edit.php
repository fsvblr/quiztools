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

/** @var \Qt\Component\Quiztools\Administrator\View\Quiz\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');

?>
<form action="<?php echo Route::_('index.php?option=com_quiztools&view=quiz&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="quiz-form" class="form-validate"
      aria-label="<?php echo Text::_('COM_QUIZTOOLS_QUIZ_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>">

	<?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'general', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('COM_QUIZTOOLS_QUIZ_TAB_GENERAL')); ?>
        <div class="row">
            <div class="col-md-6">
                <fieldset id="fieldset-basicdata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_QUIZ_FIELDSET_BASICDATA'); ?></legend>
                    <div>
                        <?php echo $this->form->renderField('state'); ?>
                        <?php echo $this->form->renderField('catid'); ?>
                        <?php echo $this->form->renderField('access'); ?>
                        <?php echo $this->form->renderField('certificate_id'); ?>
                    </div>
                </fieldset>
                <fieldset id="fieldset-limitsdata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_QUIZ_FIELDSET_LIMITSDATA'); ?></legend>
                    <?php echo $this->form->renderField('type_access'); ?>
                    <?php echo $this->form->renderField('quiz_autostart'); ?>
                    <?php echo $this->form->renderField('allow_continue'); ?>
                    <?php echo $this->form->renderField('passing_score'); ?>
                    <?php echo $this->form->renderField('timer_show'); ?>
                    <?php echo $this->form->renderField('timer_style'); ?>
                    <?php echo $this->form->renderField('limit_time'); ?>
                    <?php echo $this->form->renderField('limit_attempts'); ?>
                    <?php echo $this->form->renderField('attempts_reset_period'); ?>
                    <?php echo $this->form->renderField('attempts_reset_next_day'); ?>
                </fieldset>
            </div>
            <div class="col-md-6">
                <fieldset id="fieldset-descriptiondata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_QUIZ_FIELDSET_DESCRIPTIONDATA'); ?></legend>
                    <div>
                        <div class="mb-xl-2">
	                        <?php echo Text::_($this->form->getField('description')->getAttribute('description')); ?>
                        </div>
			            <?php echo $this->form->getInput('description'); ?>
                    </div>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

	    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'questions', Text::_('COM_QUIZTOOLS_QUIZ_TAB_QUESTIONS')); ?>
        <div class="row">
            <div class="col-md-4">
                <fieldset id="fieldset-questionsdata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_QUIZ_FIELDSET_QUESTIONSDATA'); ?></legend>
                    <div>
					    <?php echo $this->form->renderField('questions_on_page'); ?>
					    <?php echo $this->form->renderField('shuffle_questions'); ?>
					    <?php echo $this->form->renderField('skip_questions'); ?>
					    <?php echo $this->form->renderField('enable_prev_button'); ?>
					    <?php echo $this->form->renderField('question_number'); ?>
					    <?php echo $this->form->renderField('question_points'); ?>
                    </div>
                </fieldset>
            </div>
            <div class="col-md-8">
                <fieldset id="fieldset-pooldata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_QUIZ_FIELDSET_POOLDATA'); ?></legend>
                    <p><?php echo Text::_('COM_QUIZTOOLS_QUIZ_FIELDSET_POOLDATA_DESC'); ?></p>
                    <div>
	                    <?php echo $this->form->renderField('question_pool'); ?>
	                    <?php echo $this->form->renderField('question_pool_randon_qty'); ?>
	                    <?php echo $this->form->renderField('question_pool_categories'); ?>
                    </div>
                </fieldset>
            </div>
        </div>
	    <?php echo HTMLHelper::_('uitab.endTab'); ?>

	    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'results', Text::_('COM_QUIZTOOLS_QUIZ_TAB_RESULTS')); ?>
        <div class="row">
            <div class="col-md-6">
                <fieldset id="fieldset-finishdata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_QUIZ_FIELDSET_FINISHDATA'); ?></legend>
                    <div>
					    <?php echo $this->form->renderField('redirect_after_finish'); ?>
					    <?php echo $this->form->renderField('redirect_after_finish_link'); ?>
					    <?php echo $this->form->renderField('redirect_after_finish_delay'); ?>
                    </div>
                </fieldset>
            </div>
            <div class="col-md-6">
                <fieldset id="fieldset-finalpagedata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_QUIZ_FIELDSET_FINALPAGEDATA'); ?></legend>
                    <div>
	                    <?php echo $this->form->renderField('note_final_page'); ?>
	                    <?php echo $this->form->renderField('results_by_categories'); ?>
                        <?php echo $this->form->renderField('results_with_questions'); ?>
	                    <?php echo $this->form->renderField('results_pdf'); ?>
                        <?php echo $this->form->renderField('results_certificate'); ?>
                    </div>
                </fieldset>
            </div>
        </div>
	    <?php echo HTMLHelper::_('uitab.endTab'); ?>

	    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'feedback', Text::_('COM_QUIZTOOLS_QUIZ_TAB_FEEDBACK')); ?>
        <div class="row">
            <div class="col-md-6">
                <fieldset id="fieldset-feedbacksettingsdata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_QUIZ_FIELDSET_FEEDBACKSETTINGSDATA'); ?></legend>
                    <div>
					    <?php echo $this->form->renderField('feedback_question'); ?>
                        <?php echo $this->form->renderField('feedback_question_pdf'); ?>
                        <?php echo $this->form->renderField('feedback_question_final'); ?>
					    <?php echo $this->form->renderField('feedback_msg_right'); ?>
					    <?php echo $this->form->renderField('feedback_msg_wrong'); ?>

                    </div>
                </fieldset>
            </div>
            <div class="col-md-6">
                <fieldset id="fieldset-finalmsgdata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_QUIZ_FIELDSET_FINALMSGDATA'); ?></legend>
                    <div>
	                    <?php echo $this->form->renderField('feedback_final_msg_options'); ?>
                        <?php echo $this->form->renderField('feedback_final_msg_default_passed'); ?>
                        <?php echo $this->form->renderField('feedback_final_msg_default_unpassed'); ?>
	                    <?php echo $this->form->renderField('feedback_final_msg'); ?>
                    </div>
                </fieldset>
            </div>
        </div>
	    <?php echo HTMLHelper::_('uitab.endTab'); ?>

	    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('COM_QUIZTOOLS_QUIZ_TAB_PUBLISHING')); ?>
        <div class="row">
            <div class="col-md-6">
                <fieldset id="fieldset-publishingdata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_QUIZ_FIELDSET_PUBLISHINGDATA'); ?></legend>
                    <div>
	                    <?php echo $this->form->renderField('id'); ?>
					    <?php echo $this->form->renderField('created'); ?>
					    <?php echo $this->form->renderField('created_by'); ?>
					    <?php echo $this->form->renderField('modified'); ?>
					    <?php echo $this->form->renderField('modified_by'); ?>
                    </div>
                </fieldset>
            </div>
            <div class="col-md-6">
                <fieldset id="fieldset-metadata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_QUIZ_FIELDSET_METADATA'); ?></legend>
                    <div>
					    <?php echo $this->form->renderField('metatitle'); ?>
					    <?php echo $this->form->renderField('metadesc'); ?>
					    <?php echo $this->form->renderField('metakey'); ?>
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
