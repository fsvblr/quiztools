<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.mresponse
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Plugin\Quiztools\Mresponse\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Event\Table\AfterDeleteEvent;
use Joomla\CMS\Event\Table\AfterStoreEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Qt\Plugin\Quiztools\Mresponse\PluginTraits\AddCssAndJs;
use Qt\Plugin\Quiztools\Mresponse\PluginTraits\QuestionOptionsDelete;
use Qt\Plugin\Quiztools\Mresponse\PluginTraits\QuestionOptionsDeleteResults;
use Qt\Plugin\Quiztools\Mresponse\PluginTraits\QuestionOptionsGetAdminData;
use Qt\Plugin\Quiztools\Mresponse\PluginTraits\QuestionOptionsGetData;
use Qt\Plugin\Quiztools\Mresponse\PluginTraits\QuestionOptionsGetFinalPageHtml;
use Qt\Plugin\Quiztools\Mresponse\PluginTraits\QuestionOptionsGetHtml;
use Qt\Plugin\Quiztools\Mresponse\PluginTraits\QuestionOptionsGetPdfData;
use Qt\Plugin\Quiztools\Mresponse\PluginTraits\QuestionOptionsGetResults;
use Qt\Plugin\Quiztools\Mresponse\PluginTraits\QuestionOptionsGetScore;
use Qt\Plugin\Quiztools\Mresponse\PluginTraits\QuestionOptionsSave;
use Qt\Plugin\Quiztools\Mresponse\PluginTraits\QuestionSaveAnswer;

final class Mresponse extends CMSPlugin implements SubscriberInterface, DispatcherAwareInterface
{
    use DatabaseAwareTrait;
    use DispatcherAwareTrait;

    use AddCssAndJs;
	use QuestionOptionsDelete;
    use QuestionOptionsDeleteResults;
	use QuestionOptionsGetAdminData;
	use QuestionOptionsGetData;
    use QuestionOptionsGetFinalPageHtml;
	use QuestionOptionsGetHtml;
    use QuestionOptionsGetPdfData;
    use QuestionOptionsGetResults;
	use QuestionOptionsGetScore;
    use QuestionOptionsSave;
	use QuestionSaveAnswer;

	/**
	 * The name (type) of the plugin.
	 * Used in dynamic language variables.
	 *
	 * @var string
	 */
	public $name = 'mresponse';

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
            'onTableAfterDelete' => 'onTableAfterDelete',
            'onTableAfterStore' => 'onTableAfterStore',
            'onQuestionOptionsDeleteResults' => 'onQuestionOptionsDeleteResults',
            'onQuestionOptionsGetAdminData' => 'onQuestionOptionsGetAdminData',
            'onQuestionGetAssets' => 'onQuestionGetAssets',
            'onQuestionOptionsGetFinalPageHtml' => 'onQuestionOptionsGetFinalPageHtml',
            'onQuestionOptionsGetHtml' => 'onQuestionOptionsGetHtml',
            'onQuestionOptionsGetPdfData' => 'onQuestionOptionsGetPdfData',
            'onQuestionOptionsGetResults' => 'onQuestionOptionsGetResults',
            'onQuestionOptionsGetScore' => 'onQuestionOptionsGetScore',
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
	 * Post-processor for $table->delete($pk)
	 *
	 * @param   AfterDeleteEvent  $event  The event to handle
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function onTableAfterDelete(AfterDeleteEvent $event)
	{
		$this->QuestionOptionsDelete($event);
	}

	/**
	 * Post-processor for $table->store($updateNulls)
	 *
	 * @param   AfterStoreEvent  $event  The event to handle
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function onTableAfterStore(AfterStoreEvent $event)
	{
		$this->QuestionOptionsSave($event);
    }

    /**
     * Removing results from the question table.
     *
     * @param   Event  $event  The event we are handling
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   4.0.0
     */
    public function onQuestionOptionsDeleteResults(Event $event)
    {
        $this->QuestionOptionsDeleteResults($event);
    }

	/**
	 * Get question data related to this type.
	 *
	 * @param   Event  $event  The event we are handling
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0.0
	 */
	public function onQuestionOptionsGetAdminData(Event $event)
	{
		$this->QuestionOptionsGetAdminData($event);
	}

    /**
     * Get question options HTML for the final page
     * of the quiz with the results of its completion.
     *
     * @param   Event  $event  The event we are handling
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   4.0.0
     */
    public function onQuestionOptionsGetFinalPageHtml(Event $event)
    {
        $this->QuestionOptionsGetFinalPageHtml($event);
    }

	/**
	 * Get question options HTML for site.
	 *
	 * @param   Event  $event  The event we are handling
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0.0
	 */
	public function onQuestionOptionsGetHtml(Event $event)
	{
		$this->QuestionOptionsGetHtml($event);
	}

    /**
     * Get question options PDF data.
     *
     * @param   Event  $event  The event we are handling
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   4.0.0
     */
    public function onQuestionOptionsGetPdfData(Event $event)
    {
        $this->QuestionOptionsGetPdfData($event);
    }

    /**
     * Get the results of the answer to the question.
     *
     * @param   Event  $event  The event we are handling
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   4.0.0
     */
    public function onQuestionOptionsGetResults(Event $event)
    {
        $this->QuestionOptionsGetResults($event);
    }

	/**
	 * Get a score of the question and its options.
	 *
	 * @param   Event  $event  The event we are handling
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0.0
	 */
	public function onQuestionOptionsGetScore(Event $event)
	{
		$this->QuestionOptionsGetScore($event);
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
