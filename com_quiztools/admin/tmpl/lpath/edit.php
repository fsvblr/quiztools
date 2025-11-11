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

/** @var \Qt\Component\Quiztools\Administrator\View\Lpath\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');

?>
<form action="<?php echo Route::_('index.php?option=com_quiztools&view=lpath&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="lpath-form" class="form-validate"
      aria-label="<?php echo Text::_('COM_QUIZTOOLS_LPATH_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>">

	<?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'general', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('COM_QUIZTOOLS_LPATH_TAB_GENERAL')); ?>
        <div class="row">
            <div class="col-md-6">
                <fieldset id="fieldset-basicdata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_LPATH_FIELDSET_BASICDATA'); ?></legend>
                    <div>
                        <?php echo $this->form->renderField('state'); ?>
                        <?php echo $this->form->renderField('access'); ?>
                        <?php echo $this->form->renderField('catid'); ?>
                        <?php echo $this->form->renderField('type_access'); ?>
                        <?php echo $this->form->renderField('show_progressbar'); ?>
                    </div>
                </fieldset>
                <fieldset id="fieldset-descriptiondata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_LPATH_FIELDSET_DESCRIPTIONDATA'); ?></legend>
                    <div>
                        <div class="mb-xl-2">
                            <?php echo Text::_($this->form->getField('description')->getAttribute('description')); ?>
                        </div>
                        <?php echo $this->form->getInput('description'); ?>
                    </div>
                </fieldset>
            </div>
            <div class="col-md-6">
                <fieldset id="fieldset-content" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_LPATH_FIELDSET_CONTENT'); ?></legend>
                    <div>
                        <?php echo Text::_($this->form->getField('lpath_items')->getAttribute('description')); ?>
                        <?php echo $this->form->getInput('lpath_items'); ?>
                    </div>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

	    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('COM_QUIZTOOLS_LPATH_TAB_PUBLISHING')); ?>
        <div class="row">
            <div class="col-md-6">
                <fieldset id="fieldset-publishingdata" class="options-form">
                    <legend><?php echo Text::_('COM_QUIZTOOLS_LPATH_FIELDSET_PUBLISHINGDATA'); ?></legend>
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
                    <legend><?php echo Text::_('COM_QUIZTOOLS_LPATH_FIELDSET_METADATA'); ?></legend>
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
