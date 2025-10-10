<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.boilerplate
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Plugin\Quiztools\Boilerplate\PluginTraits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Language\Text;
use Joomla\Event\Event;

/**
 * Loading styles and scripts.
 *
 * @since   4.0.0
 */
trait AddCssAndJs
{
    /**
     * Injects CSS and Javascript
     *
     * @param   Event  $event
     *
     * @return  void
     *
     * @since   4.0.0
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
        $wa->getRegistry()->addRegistryFile('media/plg_quiztools_boilerplate/joomla.asset.json');

        //if (!$wa->isAssetActive('style', 'plg_quiztools_boilerplate.boilerplate')) {
        //    $wa->useStyle('plg_quiztools_boilerplate.boilerplate');
        //}

        if (!$wa->isAssetActive('script', 'plg_quiztools_boilerplate.boilerplate')) {
            $wa->useScript('plg_quiztools_boilerplate.boilerplate');
        }

        // Load language strings here:
        //Text::script('PLG_QUIZTOOLS_BOILERPLATE_BLABLA');

	    $event->setArgument('result', true);
    }
}
