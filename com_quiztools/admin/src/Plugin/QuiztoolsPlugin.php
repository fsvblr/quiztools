<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Plugin;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Event\SubscriberInterface;

final class QuiztoolsPlugin extends CMSPlugin implements SubscriberInterface, DispatcherAwareInterface
{
    use DatabaseAwareTrait;
    use DispatcherAwareTrait;

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
			'onContentPrepare' => 'onContentPrepare',
		];
	}

	/**
	 * Processing 'onContentPrepare'
	 *
	 * @param   ContentPrepareEvent  $event  Event instance
	 *
	 * @return  void
	 */
	public function onContentPrepare(ContentPrepareEvent $event)
	{
		$context = $event->getContext();
        $item = $event->getItem();

		$allowed_context = [
			'com_quiztools.quiz.prepareDescription',
			'com_quiztools.question.prepareData',
			'com_quiztools.question.option.prepareData',
			// add here
		];

		if (!in_array($context, $allowed_context)) {
			return;
		}

		if ($context == 'com_quiztools.quiz.prepareDescription') {
            $item = $this->prepareQuizDescription($item);
		}

		if (in_array($context,
			[
				'com_quiztools.question.prepareData',
				'com_quiztools.question.option.prepareData',
			]
		)) {
            $item = $this->prepareQuestionData($item);
		}

        $event->setArgument('result', $item);
	}

	/**
	 * Prepare Quiz Description.
	 *
	 * @param   object $item
	 * @return object
	 */
	private function prepareQuizDescription($item)
	{
		if (!isset($item->text)) {
			return $item;
		}

        // 1:
        $text = $item->text;
        $text = $this->fixRelativeSrcPaths($text);
        if ($text) {
            $item->text = $text;
        }

        // 2:
		$description = $item->text;
		$user = Factory::getApplication()->getIdentity();

		$start_new_quiz = true;
		$cookie_quizupi = Factory::getApplication()->getInput()->cookie->get('quizupi');
		if (!empty($cookie_quizupi[$item->id]) && $item->allow_continue) {
			$start_new_quiz = false;
		}

        $replacement_name = '';
        $replacement_surname = '';
        $replacement_email = '';

		if ($user->guest && $start_new_quiz) {
			$form = Factory::getContainer()->get(FormFactoryInterface::class)->createForm('quiz_description', []);
			$form->loadFile(JPATH_SITE . '/components/com_quiztools/forms/quiz_description.xml');

			$replacement_name = $form->renderField('quiz[user][name]');
			$replacement_surname = $form->renderField('quiz[user][surname]');
			$replacement_email = $form->renderField('quiz[user][email]');
		}

        $description = preg_replace('/#name#/', $replacement_name, $description, 1);
        $description = preg_replace('/#surname#/', $replacement_surname, $description, 1);
        $description = preg_replace('/#email#/', $replacement_email, $description, 1);

		if ($description) {
			$item->text = $description;
		}

        return $item;
	}

    /**
     * Prepare Question Data.
     *
     * @param object $item
     * @return object
     */
	private function prepareQuestionData($item)
	{
		if (!isset($item->text)) {
			return $item;
		}

		$text = $item->text;
		$text = $this->fixRelativeSrcPaths($text);

		if ($text) {
			$item->text = $text;
		}

        return $item;
	}

	/**
	 * When saving in the admin panel, the WYSIWYG editor
	 * removes the first slash in the relative path to the file.
	 *
	 * @param $text
	 *
	 * @return string
	 */
    private function fixRelativeSrcPaths($text) {
        return preg_replace_callback(
            '/\bsrc\s*=\s*([\'"])(?!\/)(?!http?:\/\/)([^\'"]+)\1/i',
            function ($matches) {
                return 'src=' . $matches[1] . '/' . $matches[2] . $matches[1];
            },
            $text
        );
    }
}
