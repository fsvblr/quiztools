<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.blank
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Plugin\Quiztools\Blank\Table;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Question Options Table
 *
 * @since  1.6
 */
class QuestionBlankOptionsTable extends Table
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
        parent::__construct('#__quiztools_questions_blank_options', 'id', $db, $dispatcher);
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

		// Check for Question's Acceptable answer(s)
		if (empty($this->answers) || (is_string($this->answers) && empty(json_decode($this->answers, true)))) {
			throw new \Exception(Text::_('PLG_QUIZTOOLS_BLANK_WARNING_PROVIDE_OPTION_ANSWER'));
		}

		return true;
	}

    /**
     * Overloaded bind function
     *
     * @param   mixed  $src   An associative array or object to bind to the \Joomla\CMS\Table\Table instance.
     * @param   mixed  $ignore  An optional array or space separated list of properties to ignore while binding.
     *
     * @return  boolean  True on success
     *
     * @since   1.5
     */
    public function bind($src, $ignore = [])
    {
        $src['answers'] = (isset($src['answers']) && \is_array($src['answers']))
            ? $src['answers']
            : [];

        $registry = new Registry($src['answers']);
        $src['answers'] = (string) $registry;

        return parent::bind($src, $ignore);
    }
}
