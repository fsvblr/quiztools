<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var \Qt\Component\Quiztools\Administrator\View\Result\HtmlView $this */

?>
<div class="col-md-12 p-4 ps-3">
    <h3><?php echo $this->item->quiz_title; ?></h3>
    <div class="d-flex mt-4">
        <div>
            <div class="me-3"><?php echo Text::_('COM_QUIZTOOLS_RESULT_QUIZ_HEADER_USER_NAME'); ?>:</div>
            <div class="me-3"><?php echo Text::_('COM_QUIZTOOLS_RESULT_QUIZ_HEADER_USER_EMAIL'); ?>:</div>
            <div class="me-3"><?php echo Text::_('COM_QUIZTOOLS_RESULT_QUIZ_HEADER_DATETIME'); ?>:</div>
            <div class="me-3"><?php echo Text::_('COM_QUIZTOOLS_RESULT_QUIZ_HEADER_TIME_SPENT'); ?>:</div>
            <div class="me-3"><?php echo Text::_('COM_QUIZTOOLS_RESULT_QUIZ_HEADER_TOTAL_SCORE'); ?>:</div>
            <div class="me-3"><?php echo Text::_('COM_QUIZTOOLS_RESULT_QUIZ_HEADER_PASSING_SCORE'); ?>:</div>
            <div class="me-3"><?php echo Text::_('COM_QUIZTOOLS_RESULT_QUIZ_HEADER_USER_SCORE'); ?>:</div>
            <div class="me-3"><?php echo Text::_('COM_QUIZTOOLS_RESULT_QUIZ_HEADER_USER_PASSED'); ?>:</div>
        </div>
        <div>
            <div><?php echo !empty($this->item->user_name) ? $this->item->user_name : '-'; ?></div>
            <div><?php echo !empty($this->item->user_email) ? $this->item->user_email : '-'; ?></div>
            <div><?php echo !empty($this->item->start_datetime_for_display) ? $this->item->start_datetime_for_display : '-'; ?></div>
            <div><?php echo !empty($this->item->sum_time_spent) ? $this->item->sum_time_spent : '-'; ?></div>
            <div><?php echo !empty($this->item->total_score) ? number_format((float) $this->item->total_score, 2, '.', '') : '-'; ?></div>
            <div>
                <?php
                $passingScore = (float) $this->item->total_score * ((float) $this->item->passing_score / 100 );
                $passingScore = round($passingScore, 2);
                echo number_format($passingScore, 2, '.', '') . ' (' . $this->item->passing_score . '%)';
                ?>
            </div>
            <div>
                <?php
                $userPercent = 0;
                if (!empty($this->item->total_score)) {
                    $userPercent = round(((float) $this->item->sum_points_received / (float) $this->item->total_score) * 100, 2);
                }
                echo !empty($this->item->sum_points_received)
                    ? number_format((float) $this->item->sum_points_received, 2, '.', '') . ' (' . $userPercent . '%)'
                    : '-';
                ?>
            </div>
            <div>
                <span class="icon-<?php echo (int) $this->item->passed ? 'check passed' :
                    'times failed'; ?> icon-results-passed" aria-hidden="true"></span>
            </div>
        </div>
    </div>
</div>
