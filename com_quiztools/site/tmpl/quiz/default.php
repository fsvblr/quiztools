<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

/** @var \Qt\Component\Quiztools\Site\View\Quiz\HtmlView $this */

Text::script('COM_QUIZTOOLS_QUIZ_BUTTON_ACTION_START');
Text::script('COM_QUIZTOOLS_QUIZ_BUTTON_ACTION_NEXT');
Text::script('COM_QUIZTOOLS_QUIZ_BUTTON_ACTION_PREV');
Text::script('COM_QUIZTOOLS_QUIZ_BUTTON_ACTION_SKIP');
Text::script('COM_QUIZTOOLS_QUIZ_BUTTON_ACTION_CONTINUE');
Text::script('COM_QUIZTOOLS_QUIZ_BUTTON_ACTION_FINISH');
Text::script('COM_QUIZTOOLS_QUIZ_BUTTON_ACTION_QUIT');
Text::script('COM_QUIZTOOLS_QUIZ_COMPONENT_COUNT_QUESTIONS_TEXT_QUESTION');
Text::script('COM_QUIZTOOLS_QUIZ_COMPONENT_COUNT_QUESTIONS_TEXT_OF');
Text::script('COM_QUIZTOOLS_QUIZ_COMPONENT_TIMER_TEXT_OF');
Text::script('COM_QUIZTOOLS_QUIZ_COMPONENT_ANSWER_POINTS_TEXT_POINTS');
Text::script('COM_QUIZTOOLS_QUIZ_ERROR_VALIDATION');
Text::script('COM_QUIZTOOLS_QUIZ_ERROR_QUIZ_TIME_UP');
Text::script('COM_QUIZTOOLS_QUIZ_ERROR_QUESTION_NO_ATTEMPTS_LEFT');

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate');
$wa->useStyle('com_quiztools.quiz')
    ->useStyle('com_quiztools.result')
    ->useScript('com_quiztools.quiz');

$this->getDocument()->addScriptOptions('com_quiztools.quiz', (array) $this->quiz);
$this->getDocument()->addScriptOptions('com_quiztools.token', array('value' => Session::getFormToken()));

?>
<div class="quiztools quiz<?php echo !empty($this->pageclass_sfx) ? ' quiz-'.$this->pageclass_sfx : ''; ?>">
    <div class="page-header">
        <h1><?php echo $this->escape($this->item->title); ?></h1>
    </div>
    <div id="quiz-wrap" class="quiz-wrap"></div>
</div>
