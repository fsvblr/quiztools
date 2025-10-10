<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.mchoice
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Plugin\Quiztools\Mchoice\Table;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Question Options Table
 *
 * @since  1.6
 */
class QuestionMchoiceOptionsTable extends Table
{
    /**
     * Constructor
     *
     * @param   DatabaseDriver        $db          Database connector object
     * @param   ?DispatcherInterface  $dispatcher  Event dispatcher for this table
     *
     * @since   1.5
     */
    public function __construct(DatabaseDriver $db, DispatcherInterface $dispatcher = null)
    {
        parent::__construct('#__quiztools_questions_mchoice_options', 'id', $db, $dispatcher);
    }

	/**
	 * Overloaded check function
	 *
	 * @return  boolean  True if the object is ok
	 *
	 * @see     Table::check()
	 * @since   4.0.0
	 */
	public function check()
	{
		try {
			parent::check();
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), 500, $e);
		}

		// Check for Question Option text
		if (trim($this->option) === '') {
			throw new \Exception(Text::_('PLG_QUIZTOOLS_MCHOICE_WARNING_PROVIDE_OPTION_TEXT'));
		}

		return true;
	}
}
