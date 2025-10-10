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

/** @var \Qt\Component\Quiztools\Administrator\View\Result\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useStyle('com_quiztools.admin.results');

$user = $this->getCurrentUser();
$canEdit = $user->authorise('core.edit', 'com_quiztools');

?>
<form action="<?php echo Route::_('index.php?option=com_quiztools&view=result&layout=default&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="result-form" class="form-validate" enctype="multipart/form-data"
      aria-label="<?php echo Text::_('COM_QUIZTOOLS_RESULT_QUIZ_DISPLAY', true); ?>">

    <div class="main-card">
        <?php echo $this->loadTemplate('header'); ?>

        <?php if (empty($this->item->results_questions)): ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
            </div>
        <?php else: ?>
            <table class="table" id="resultQuizList">
                <thead>
                <tr>
                    <td class="w-1 text-center">
                        <?php echo '#'; ?>
                    </td>
                    <td class="w-50">
                        <?php echo Text::_('COM_QUIZTOOLS_RESULT_QUIZ_HEADING_QUESTION'); ?>
                    </td>
                    <td class="w-20 text-center">
                        <?php echo Text::_('COM_QUIZTOOLS_RESULT_QUIZ_HEADING_TYPE'); ?>
                    </td>
                    <td class="w-10 text-center">
                        <?php echo Text::_('COM_QUIZTOOLS_RESULT_QUIZ_HEADING_TOTAL_POINTS'); ?>
                    </td>
                    <td class="w-10 text-center">
                        <?php echo Text::_('COM_QUIZTOOLS_RESULT_QUIZ_HEADING_POINTS_RECEIVED'); ?>
                    </td>
                    <td class="w-10 text-center">
                        <?php echo Text::_('COM_QUIZTOOLS_RESULT_QUIZ_HEADING_CORRECT'); ?>
                    </td>
                </tr>
                </thead>
                <tbody>
                <?php
                $i = 0;
                foreach ($this->item->results_questions as $question): ?>
                    <tr class="row<?php echo $i % 2; ?>">
                        <td class="text-center">
                            <?php echo $i + 1; ?>
                        </td>
                        <th scope="row">
                            <div class="break-word">
                                <?php if ($canEdit) : ?>
                                    <a href="<?php echo Route::_('index.php?option=com_quiztools&view=result&layout=question&qid=' . (int) $question->id . '&id=' . (int) $this->item->id); ?>">
                                        <?php echo strip_tags($question->text); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo strip_tags($question->text); ?>
                                <?php endif; ?>
                            </div>
                        </th>
                        <td class="text-center">
                            <?php echo $question->typeName; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($question->type !== 'boilerplate'): ?>
                                <?php echo number_format($question->total_points, 2, '.', ''); ?>
                            <?php else: ?>
                                <?php echo '-'; ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($question->type !== 'boilerplate'): ?>
                                <?php echo number_format($question->points_received, 2, '.', ''); ?>
                            <?php else: ?>
                                <?php echo '-'; ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($question->type !== 'boilerplate'): ?>
                                <span class="icon-<?php echo (int) $question->is_correct ? 'check passed' :
                                    'times failed'; ?> icon-results-passed" aria-hidden="true"></span>
                            <?php else: ?>
                                <?php echo '-'; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php
                $i++;
                endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
