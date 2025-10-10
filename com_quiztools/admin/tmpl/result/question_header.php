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
        </div>
        <div>
            <div><?php echo !empty($this->item->user_name) ? $this->item->user_name : '-'; ?></div>
            <div><?php echo !empty($this->item->user_email) ? $this->item->user_email : '-'; ?></div>
        </div>
    </div>
</div>
