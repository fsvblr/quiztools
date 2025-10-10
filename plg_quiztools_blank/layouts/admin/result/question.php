<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.blank
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;

extract($displayData);

?>
<div class="col-md-12 p-4 ps-3">
    <div>
        <?php echo !empty($question->type) ? '[' . $question->typeName . ']:' : ''; ?>
    </div>
    <div class="mt-2">
        <?php echo !empty($question->text) ? $question->text : ''; ?>
    </div>
</div>

<?php if (empty($question->results)): ?>
    <div class="alert alert-info">
        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
    </div>
<?php else: ?>
    <table class="table">
        <thead>
        <tr>
            <td class="w-1 text-center">
                <?php echo '#'; ?>
            </td>
            <td class="w-70">
                <?php echo Text::_('PLG_QUIZTOOLS_BLANK_LAYOUT_ADMIN_RESULT_QUESTION_HEADING_OPTION_TEXT'); ?>
            </td>
            <td class="w-15 text-center">
                <?php echo Text::_('PLG_QUIZTOOLS_BLANK_LAYOUT_ADMIN_RESULT_QUESTION_HEADING_RIGHT_ANSWER'); ?>
            </td>
            <td class="w-15 text-center">
                <?php echo Text::_('PLG_QUIZTOOLS_BLANK_LAYOUT_ADMIN_RESULT_QUESTION_HEADING_USER_ANSWER'); ?>
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
                    <?php echo '{blank' . $i + 1 . '}'; ?>
                </td>
                <td class="text-center">
                    <?php echo implode('/ ', $result->answers); ?>
                </td>
                <td class="text-center">
                    <span style="color:<?php echo $result->is_correct ? '#457d54' : '#EB5757'; ?>">
                        <?php echo htmlspecialchars($result->user_answer, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
