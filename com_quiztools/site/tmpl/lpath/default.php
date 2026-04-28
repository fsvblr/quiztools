<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/** @var \Qt\Component\Quiztools\Site\View\Lpath\HtmlView $this */

Text::script('COM_QUIZTOOLS_LPATH_TITLE_COURSE_STRUCTURE');
Text::script('COM_QUIZTOOLS_LPATH_BUTTON_ACTION_STEPS');
Text::script('COM_QUIZTOOLS_LPATH_BUTTON_ACTION_STEP');
Text::script('COM_QUIZTOOLS_LPATH_BUTTON_ACTION_NEXT');

// schema.org : start
// https://validator.schema.org/
// https://search.google.com/test/rich-results
$steps = [];
if (!empty($this->item->steps)) {
    foreach ($this->item->steps as $index => $step) {
        $type = ($step->type == 'q') ? 'Quiz' : 'CreativeWork';
        $resourceType = ($step->type == 'q') ? 'Quiz' : 'Article';

        $steps[] = [
            "@type" => $type,
            "name" => $this->escape($step->title),
            "description" => !empty(trim(strip_tags($step->desc))) ? $this->escape(trim(strip_tags($step->desc))) : $this->escape($step->title),
            "learningResourceType" => $resourceType,
            "position" => $index + 1
        ];
    }
}
$schema = [
    "@context" => "https://schema.org",
    "@type" => "Course",
    "name" => $this->escape($this->item->title),
    "description" => !empty(trim(strip_tags($this->item->description))) ? $this->escape(trim(strip_tags($this->item->description))) : $this->escape($this->item->title),
    "provider" => [
        "@type" => "Organization",
        "name" => Factory::getApplication()->get('sitename'),
        "url" => Uri::base()
    ],
    "hasPart" => $steps
];
$schema = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
// schema.org : end

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate')
    ->addInlineScript($schema, [], ['type' => 'application/ld+json']);

$wa->useStyle('com_quiztools.lpath')
    ->useScript('com_quiztools.lpath');

$this->getDocument()->addScriptOptions('com_quiztools.lpath', (array) $this->lpath);
$this->getDocument()->addScriptOptions('com_quiztools.token', array('value' => Session::getFormToken()));

$order_id = Factory::getApplication()->getInput()->getInt('order_id', 0);
$this->getDocument()->addScriptOptions('com_quiztools.orderId', $order_id);

?>
<div class="quiztools lpath<?php echo !empty($this->pageclass_sfx) ? ' lpath-'.$this->pageclass_sfx : ''; ?>">
    <div class="page-header">
        <h1><?php echo $this->escape($this->item->title); ?></h1>
    </div>
    <div id="lpath-wrap" class="lpath-wrap"></div>
</div>
