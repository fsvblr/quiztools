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
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Qt\Component\Quiztools\Administrator\View\Result\HtmlView $this */

$app = Factory::getApplication();
$input = $app->getInput();

$resultQuestionId = $input->getInt('qid', 0);
$question = !empty($this->item->results_questions[$resultQuestionId]) ?
    $this->item->results_questions[$resultQuestionId] : new \stdClass();

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useStyle('com_quiztools.admin.results');

?>
<form action="<?php echo Route::_('index.php?option=com_quiztools&view=result&layout=question&qid=' . $resultQuestionId . '&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="result-question-form" class="form-validate" enctype="multipart/form-data"
      aria-label="<?php echo Text::_('COM_QUIZTOOLS_RESULT_QUESTION_DISPLAY', true); ?>">

    <div class="main-card">
        <?php echo $this->loadTemplate('header'); ?>
        <hr class="mt-0 mb-0" />

        <?php //Overriding a non-standard layout in a plugin: ?>
        <?php $layoutPath = JPATH_SITE . '/plugins/quiztools/' . $question->type . '/layouts/admin/result/question.php'; ?>
        <?php if (is_file($layoutPath)): ?>
            <?php
            echo LayoutHelper::render(
                    'admin.result.question',
                    ['question' => $question],
                    JPATH_SITE . '/plugins/quiztools/' . $question->type . '/layouts'
            );
            ?>
        <?php else: ?>
            <div class="col-md-12 p-4 ps-3">
                <div>
                    <?php echo !empty($question->type) ? '[' . $question->typeName . ']:' : ''; ?>
                </div>
                <div class="mt-2">
                    <?php echo !empty($question->text) ? $question->text : ''; ?>
                </div>
            </div>

            <?php if (empty($question->results)): ?>
                <?php if ($question->type !== 'boilerplate'): ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <table class="table">
                    <thead>
                    <tr>
                        <td class="w-1 text-center">
                            <?php echo '#'; ?>
                        </td>
                        <td class="w-70">
                            <?php echo Text::_('COM_QUIZTOOLS_RESULT_QUESTION_HEADING_OPTION_TEXT'); ?>
                        </td>
                        <td class="w-15 text-center">
                            <?php echo Text::_('COM_QUIZTOOLS_RESULT_QUESTION_HEADING_RIGHT_ANSWER'); ?>
                        </td>
                        <td class="w-15 text-center">
                            <?php echo Text::_('COM_QUIZTOOLS_RESULT_QUESTION_HEADING_USER_ANSWER'); ?>
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($question->results as $i => $result): ?>
                        <tr class="row<?php echo $i % 2; ?>">
                            <td class="text-center">
                                <?php echo $i + 1; ?>
                            </td>
                            <td>
                                <?php echo $result->option; ?>
                            </td>
                            <td class="text-center">
                                <?php if ((int) $result->is_correct): ?>
                                    <span class="icon-check passed icon-results-passed" aria-hidden="true"></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($result->user_answer): ?>
                                    <span class="icon-<?php echo (int) $result->is_correct ? 'check passed' :
                                        'times failed'; ?> icon-results-passed" aria-hidden="true"></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
