<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.mchoice
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Plugin\Quiztools\Mchoice\PluginTraits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplication;
use Joomla\Event\Event;

/**
 * Get question options for admin.
 *
 * @since   4.0.0
 */
trait QuestionOptionsGetAdminData
{
	/**
	 * Get question options for admin.
	 *
	 * @param   Event  $event
	 *
	 * @return bool
	 */
    public function QuestionOptionsGetAdminData($event): bool
    {
	    if (!($this->getApplication() instanceof CMSApplication)) {
		    return false;
	    }

	    if (!$this->getApplication()->isClient('administrator')) {
		    return false;
	    }

	    /**
	     * @var   string|null        $context  The context for the data
	     * @var   array|object|null  $data     An object or array containing the data for the form.
	     */
	    [$context, $data] = array_values($event->getArguments());

	    if (!\in_array($context, ['com_quiztools.admin.question.typeData'])) {
		    return false;
	    }

	    if (\is_array($data)) {
		    $data = (object) $data;
	    }

	    // Check that the question is of the current plugin type.
	    if ($data->type != $this->name) {
		    return false;
	    }

	    if (empty($data->id)) {
		    return false;
	    }

	    $questionData = $this->QuestionOptionsGetData($data, 'administrator');

		if (!empty($questionData['typeData'])) {
			foreach ($questionData['typeData'] as $key => $value) {
				$data->{$key} = $value;
			}
		}

	    $data->question_options = [];
	    if (!empty($questionData['options'])) {
		    foreach ($questionData['options'] as $option) {
			    $data->question_options[] = $option;
		    }
	    }

	    $event->setArgument('result', $data);

	    return true;
    }
}
