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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Qt\Component\Quiztools\Site\Helper\RouteHelper;

/** @var \Qt\Component\Quiztools\Site\View\Quizzes\HtmlView $this */

// schema.org : start
// https://validator.schema.org/
// https://search.google.com/test/rich-results
$listElements = [];
if (!empty($this->items)) {
    $siteUrl = Uri::getInstance()->toString(array('scheme', 'host', 'port'));
    $siteName = Factory::getApplication()->get('sitename');
    foreach ($this->items as $index => $item) {
        $quizUrl = $item->isAccessQuiz ? Route::_(RouteHelper::getQuizRoute($item->id, $item->catid)) : '';

        $listElements[] = [
            "@type" => "ListItem",
            "position" => $index + 1,
            "item" => [
                "@type" => "Course",
                "name" => $this->escape($item->title),
                "description" => !empty(trim(strip_tags($item->description))) ? $this->escape(trim(strip_tags($item->description))) : $this->escape($item->title),
                "url" => $siteUrl . $quizUrl,
                "provider" => [
                    "@type" => "Organization",
                    "name" => $siteName,
                    "url" => Uri::base()
                ],
            ]
        ];
    }
}

$schema = [
    "@context" => "https://schema.org",
    "@type" => "ItemList",
    "name" => $this->escape($this->params->get('page_heading')),
    "itemListElement" => $listElements
];
$schema = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
// schema.org : end

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->addInlineScript($schema, [], ['type' => 'application/ld+json']);
$wa->useStyle('com_quiztools.quizzes');

?>
<div class="quiztools quizzes-list<?php echo !empty($this->pageclass_sfx) ? ' quizzes-list-'.$this->pageclass_sfx : ''; ?>">

	<?php if ($this->params->get('show_page_heading')) : ?>
        <h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
	<?php endif; ?>

    <?php if ($this->params->get('show_quizzes_filters')) : ?>
        <?php echo $this->loadTemplate('filters'); ?>
    <?php endif; ?>

    <?php if (empty($this->items)) : ?>
        <div class="alert alert-info">
            <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
            <?php echo Text::_('COM_QUIZTOOLS_QUIZZES_NO_QUIZZES'); ?>
        </div>
    <?php else : ?>
        <div class="quizzes-list__list">
            <?php foreach ($this->items as $item) : ?>
                <div class="quizzes-list__item">
                    <div class="quizzes-list__item-title">
                        <?php if ($item->isAccessQuiz): ?>
                            <a href="<?php echo Route::_(RouteHelper::getQuizRoute($item->id, $item->catid)); ?>">
                                <?php echo $this->escape($item->title); ?>
                            </a>
                        <?php else: ?>
                            <?php echo $this->escape($item->title); ?>&nbsp;
                            <small>[<?php echo Text::_('COM_QUIZTOOLS_QUIZZES_ATTEMPTS_ARE_OVER'); ?>]</small>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($item->description)): ?>
                        <div class="quizzes-list__item-desc">
                            <?php echo $item->description; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($this->items)) : ?>
        <?php if ($this->pagination->pagesTotal > 1) : ?>
            <div class="quizzes-list__navigation w-100">
                <p class="quizzes-list__counter counter float-end pt-3 pe-2">
                    <?php echo $this->pagination->getPagesCounter(); ?>
                </p>
                <div class="quizzes-list__pagination">
                    <?php echo $this->pagination->getPagesLinks(); ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
