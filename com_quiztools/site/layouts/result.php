<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

extract($displayData);

if (!empty($result->results_pdf)
    || (!empty($result->passed) && !empty($result->results_certificate))
) {
    $token = HTMLHelper::getServiceRegistry()->getService('quiztoolsaccess')->getResultTokenForDisplay($result);
}

?>
<div class="quiz-result">
    <div class="quiz-result-block quiz-result-summary">
        <h4><?php echo Text::_('COM_QUIZTOOLS_LAYOUTS_RESULT_TITLE_RESULTS'); ?></h4>
        <div>
            <div class="quiz-result-row">
                <div><?php echo Text::_('COM_QUIZTOOLS_LAYOUTS_RESULT_TIME_SPENT'); ?>:</div>
                <div>
                    <?php echo $result->sum_time_spent; ?>
                </div>
            </div>
            <div class="quiz-result-row">
                <div><?php echo Text::_('COM_QUIZTOOLS_LAYOUTS_RESULT_USER_SCORE'); ?>:</div>
                <div>
                    <?php
                    echo number_format((float) $result->sum_points_received, 2, '.', '');
                    echo ' ' . Text::_('COM_QUIZTOOLS_LAYOUTS_RESULT_BY_CATEGORIES_OUT_OF') . ' ';
                    echo number_format((float) $result->total_score, 2, '.', '');
                    if ((float) $result->total_score != 0) {
                        $userPercent = number_format(((float) $result->sum_points_received / (float)
                                $result->total_score) * 100, 2, '.', '');
                        echo ' (' . $userPercent . '%)';
                    }
                    ?>
                </div>
            </div>
            <div class="quiz-result-row">
                <div><?php echo Text::_('COM_QUIZTOOLS_LAYOUTS_RESULT_PASSING_SCORE'); ?>:</div>
                <div>
                    <?php
                    $passingScore = round((float) $result->total_score * ((float) $result->passing_score / 100 ), 2);
                    $passingScore = number_format($passingScore, 2, '.', '') .
                        ' (' . number_format($result->passing_score, 2, '.', '') . '%)';
                    echo $passingScore;
                    ?>
                </div>
            </div>
            <?php if (!empty($result->results_by_categories) && !empty($result->byCategories)): ?>
                <div class="quiz-result-row quiz-result-categories">
                    <div><?php echo Text::_('COM_QUIZTOOLS_LAYOUTS_RESULT_BY_CATEGORIES'); ?>:</div>
                    <div>
                        <?php foreach ($result->byCategories as $categoryTitle => $category): ?>
                            <div class="quiz-result-category">
                                <?php echo $categoryTitle . ': ' . $category['userScore'] .
                                    ' ' . Text::_('COM_QUIZTOOLS_LAYOUTS_RESULT_BY_CATEGORIES_OUT_OF') .
                                    ' ' . $category['totalScore'] .
                                    ' (' . $category['userPercent'] . '%)'; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($result->finalMessage)): ?>
    <div class="quiz-result-block quiz-result-message">
        <h4><?php echo Text::_('COM_QUIZTOOLS_LAYOUTS_RESULT_TITLE_FINAL_MESSAGE'); ?></h4>
        <div><?php echo $result->finalMessage; ?></div>
    </div>
    <?php endif; ?>

    <div class="quiz-result-block quiz-result-actions">
        <?php if (!empty($result->results_pdf)): ?>
            <div class="quiz-result-action">
                <img src="/media/com_quiztools/images/icon-pdf.svg" class="quiz-result-icon"
                    alt="<?php echo Text::_('COM_QUIZTOOLS_LAYOUTS_RESULT_PDF_ALT'); ?>" />
                <a href="<?php echo Route::_('index.php?option=com_quiztools&task=result.getPdf&id=' . (int) $result->id .
                    '&token=' . $token . '&' . Session::getFormToken() . '=1'); ?>"
                >
                    <?php echo Text::_('COM_QUIZTOOLS_LAYOUTS_RESULT_PDF_DOWNLOAD'); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php if (!empty($result->passed) && !empty($result->results_certificate)): ?>
            <div class="quiz-result-action">
                <img src="/media/com_quiztools/images/icon-award.svg" class="quiz-result-icon"
                    alt="<?php echo Text::_('COM_QUIZTOOLS_LAYOUTS_RESULT_CERTIFICATE_ALT'); ?>" />
                <a href="<?php echo Route::_('index.php?option=com_quiztools&task=result.getCertificate&id=' . (int) $result->id .
                    '&token=' . $token . '&' . Session::getFormToken() . '=1'); ?>"
                >
                    <?php echo Text::_('COM_QUIZTOOLS_LAYOUTS_RESULT_CERTIFICATE_DOWNLOAD'); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php if (!$isLP):  // This is NOT a Learning Path ?>
            <div class="quiz-result-action">
                <img src="/media/com_quiztools/images/icon-reload.svg" class="quiz-result-icon"
                     alt="<?php echo Text::_('COM_QUIZTOOLS_LAYOUTS_RESULT_QUIZ_AGAIN_ALT'); ?>" />
                <a href="<?php echo Route::_('index.php?option=com_quiztools&view=quiz&id=' . (int) $result->quiz_id); ?>">
                    <?php echo Text::_('COM_QUIZTOOLS_LAYOUTS_RESULT_QUIZ_AGAIN'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($result->results_with_questions) && !empty($result->results_questions)): ?>
        <div class="quiz-result-block quiz-result-questions">
            <h4><?php echo Text::_('COM_QUIZTOOLS_LAYOUTS_RESULT_TITLE_QUESTIONS'); ?></h4>
            <?php $i = 1; ?>
            <?php foreach ($result->results_questions as $question): ?>
                <div class="quiz-result-question">
                    <h5><?php echo Text::_('COM_QUIZTOOLS_LAYOUTS_RESULT_QUESTION_TITLE') . ' ' . $i; ?></h5>
                    <div class="quiz-result-question__type">
                        <?php echo $question->typeName; ?>
                    </div>
                    <div class="quiz-result-question__text">
                        <?php echo $question->text; ?>
                    </div>
                    <?php if (!empty($question->resultHtml)): ?>
                        <div class="quiz-result-question__options">
                            <?php echo $question->resultHtml; ?>
                        </div>
                    <?php endif; ?>
                    <?php $i++; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="quiz-result-last">&nbsp;</div>
</div>
