<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.hotspotsmultiple
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 * @license     GNU General Public License version 2 or later
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;

extract($displayData);

?>
<div class="px-4 pt-4 pb-2">
    <div>
        <?php echo !empty($question->type) ? '[' . $question->typeName . ']:' : ''; ?>
    </div>
    <div class="mt-2">
        <?php echo !empty($question->text) ? $question->text : ''; ?>
    </div>
</div>

<div class="d-flex px-4">
    <div>
        <div class="me-3"><?php echo Text::_('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_LAYOUT_ADMIN_RESULT_QUESTION_FINAL_RESULT_LABEL'); ?>:</div>
        <div class="me-3"><?php echo Text::_('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_LAYOUT_ADMIN_RESULT_QUESTION_CHECK_ORDER_LABEL'); ?>:</div>
        <hr />
        <div class="me-3"><?php echo Text::_('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_LAYOUT_ADMIN_RESULT_QUESTION_BY_HOTSPOTS_LABEL'); ?>:</div>
        <?php foreach ($question->results['options'] as $i => $option): ?>
            <?php $color = !empty($option->color) ? $option->color : '#212529'; ?>
            <div class="me-3" style="color:<?php echo $color; ?>">
                <?php echo Text::_('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_LAYOUT_ADMIN_RESULT_QUESTION_TEXT_AREA') . '#' . ($i + 1); ?>:
            </div>
        <?php endforeach; ?>
    </div>
    <div>
        <div>
            <?php if ($question->is_correct): ?>
                <?php echo Text::_('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_LAYOUT_ADMIN_RESULT_QUESTION_FINAL_RESULT_CORRECT'); ?>
            <?php else: ?>
                <?php echo Text::_('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_LAYOUT_ADMIN_RESULT_QUESTION_FINAL_RESULT_INCORRECT'); ?>
            <?php endif; ?>
        </div>
        <div>
            <?php echo (int) $question->results['questionData']->check_order ? Text::_('JYES') : Text::_('JNO'); ?>
        </div>
        <hr />
        <div>&nbsp;</div>
        <?php foreach ($question->results['options'] as $i => $option): ?>
            <div>
                <span class="icon-<?php echo (int) $question->results['answersResults']['byHotspots'][$i] ?
                    'check passed' : 'times failed'; ?> icon-results-passed" aria-hidden="true"></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if (empty($question->results['resultImage'])): ?>
    <div class="pb-3 alert alert-info">
        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
    </div>
<?php else: ?>
    <div class="m-4 pb-3" style="max-width:30rem;">
        <?php echo $question->results['resultImage']; ?>
    </div>
<?php endif; ?>
