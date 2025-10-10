<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.boilerplate
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Plugin\Quiztools\Boilerplate\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Qt\Plugin\Quiztools\Boilerplate\PluginTraits\AddCssAndJs;
use Qt\Plugin\Quiztools\Boilerplate\PluginTraits\QuestionSaveAnswer;

final class Boilerplate extends CMSPlugin implements SubscriberInterface, DispatcherAwareInterface
{
    use DatabaseAwareTrait;
    use DispatcherAwareTrait;

	use AddCssAndJs;
	use QuestionSaveAnswer;

	/**
	 * The name (type) of the plugin.
	 * Used in dynamic language variables.
	 *
	 * @var string
	 */
	public $name = 'boilerplate';

    /**
     * Autoload the language files
     *
     * @var    boolean
     * @since  4.2.0
     */
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   4.2.0
     */
    public static function getSubscribedEvents(): array
    {
        try {
            $app = Factory::getApplication();
        } catch (\Exception $e) {
            return [];
        }

        if (!$app->isClient('site') && !$app->isClient('administrator')) {
            return [];
        }

        return [
	        'onQuestionGetAssets' => 'onQuestionGetAssets',
	        'onQuestionSaveAnswer' => 'onQuestionSaveAnswer',
        ];
    }

	/**
	 * Loading question's assets before rendering the Quiz on the site.
	 *
	 * @param   Event  $event  The event we are handling
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0.0
	 */
	public function onQuestionGetAssets(Event $event)
	{
		$this->addCSSAndJs($event);
	}

	/**
	 * Saving the answer to the question on the site.
	 *
	 * @param   Event  $event  The event we are handling
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0.0
	 */
	public function onQuestionSaveAnswer(Event $event)
	{
		$this->QuestionSaveAnswer($event);
	}
}
