<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.hotspotsmultiple
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 * @license     GNU General Public License version 2 or later
 */

namespace Qt\Plugin\Quiztools\Hotspotsmultiple\PluginTraits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\Event;

/**
 * Loading styles and scripts.
 *
 * @since   1.0.0
 */
trait AddCssAndJs
{
    /**
     * Injects CSS and JavaScript (site)
     *
     * @param   Event  $event
     * @return  void
     * @since   1.0.0
     */
    public function addCSSAndJs($event): void
    {
        if (!($this->getApplication() instanceof CMSApplication)) {
            return;
        }

        if (!$this->getApplication()->isClient('site')) {
            return;
        }

	    $context = $event->getArgument('context');

	    if (!\in_array($context, ['com_quiztools.question.getAssets'])) {
		    return;
	    }

        try {
            $document = $this->getApplication()->getDocument();
        } catch (\Exception $e) {
            $document = null;
        }

        if (!($document instanceof HtmlDocument)) {
            return;
        }

        /** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
        $wa = $this->getApplication()->getDocument()->getWebAssetManager();
        $wa->getRegistry()->addRegistryFile('media/plg_quiztools_hotspotsmultiple/joomla.asset.json');

        if (!$wa->isAssetActive('style', 'plg_quiztools_hotspotsmultiple.hotspotsmultiple')) {
            $wa->useStyle('plg_quiztools_hotspotsmultiple.hotspotsmultiple');
        }

        if (!$wa->isAssetActive('script', 'plg_quiztools_hotspotsmultiple.hotspotsmultiple')) {
            $wa->useScript('plg_quiztools_hotspotsmultiple.hotspotsmultiple');
        }

        Text::script('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_SITE_WARNING_MORE_MARKERS');

	    $event->setArgument('result', true);
    }

    /**
     * Injects CSS and JavaScript (administrator)
     *
     * @param   Event  $event
     * @return  void
     * @since   1.4.0
     */
    public function addCSSAndJsInAdmin($event): void
    {
        if (!($this->getApplication() instanceof CMSApplication)) {
            return;
        }

        if (!$this->getApplication()->isClient('administrator')) {
            return;
        }

        $context = $event->getArgument('context');

        if (!\in_array($context, ['com_quiztools.question.getAssets'])) {
            return;
        }

        try {
            $document = $this->getApplication()->getDocument();
        } catch (\Exception $e) {
            $document = null;
        }

        if (!($document instanceof HtmlDocument)) {
            return;
        }

        /** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
        $wa = $this->getApplication()->getDocument()->getWebAssetManager();
        $wa->getRegistry()->addRegistryFile('media/plg_quiztools_hotspotsmultiple/joomla.asset.json');

        if (!$wa->isAssetActive('style', 'plg_quiztools_hotspotsmultiple.admin.hotspotsmultiple')) {
            $wa->useStyle('plg_quiztools_hotspotsmultiple.admin.hotspotsmultiple');
        }

        $wa->useScript('com_quiztools.fabric');

        if (!$wa->isAssetActive('script', 'plg_quiztools_hotspotsmultiple.admin.hotspotsmultiple')) {
            $wa->useScript('plg_quiztools_hotspotsmultiple.admin.hotspotsmultiple');
        }

        $document->addScriptOptions('com_quiztools.question.hotspotsmultiple', [
                'siteRoot' => URI::root(true) . '/',
            ]
        );

        Text::script('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_ADMIN_BTN_DRAW');
        Text::script('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_ADMIN_BTN_STOP');
        Text::script('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_ALERT_POLYGON_NOT_SIMPLE_HEADER');
        Text::script('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_ALERT_POLYGON_NOT_SIMPLE_BODY');

        $event->setArgument('result', true);
    }
}
