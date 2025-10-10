<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Qt\Component\Quiztools\Administrator\View\Certificate\HtmlView $this */

$document = $this->getDocument();
$app = Factory::getApplication();
$input = $app->getInput();

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useStyle('com_quiztools.admin.certificate')
    ->useScript('keepalive')
    ->useScript('form.validate')
    ->useScript('com_quiztools.fabric')
    ->useScript('joomla.dialog')
    ->useScript('com_quiztools.admin.certificate')
    ->useScript('com_quiztools.admin.certificate.dialog');

Text::script('COM_QUIZTOOLS_CERTIFICATE_CONFIRM_PREVIEW_TITLE');
Text::script('COM_QUIZTOOLS_CERTIFICATE_CONFIRM_PREVIEW_BODY');

$document->addScriptOptions('com_quiztools.certificate', ['id' => $input->getInt('id', 0), 'title' => $this->item->title]);

?>
<form action="<?php echo Route::_('index.php?option=com_quiztools&view=certificate&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="certificate-form" class="form-validate" enctype="multipart/form-data"
      aria-label="<?php echo Text::_('COM_QUIZTOOLS_CERTIFICATE_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>">

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'general', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('COM_QUIZTOOLS_CERTIFICATE_TAB_GENERAL')); ?>
        <div class="row">
            <div class="col-md-6">
                <?php echo $this->form->renderField('note'); ?>
            </div>
            <div class="col-md-6">
                <?php echo $this->form->renderField('title'); ?>
                <?php echo $this->form->renderField('state'); ?>
                <?php echo $this->form->renderField('file'); ?>
                <?php echo $this->form->renderField('upload_image'); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <?php echo '<div class="subform-fields">' . $this->form->renderField('fields') . '</div>'; ?>
            </div>
        </div>
        <div class="mt-4">
            <div class="certificate-canvas-wrap" id="certificate-canvas-wrap"></div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('COM_QUIZTOOLS_CERTIFICATE_TAB_PUBLISHING')); ?>
        <div class="row">
            <div class="col-md-6">
                <?php echo $this->form->renderField('id'); ?>
                <?php echo $this->form->renderField('created'); ?>
                <?php echo $this->form->renderField('created_by'); ?>
                <?php echo $this->form->renderField('modified'); ?>
                <?php echo $this->form->renderField('modified_by'); ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="task" value="" id="certificate-form-task">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
