<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.blank
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Plugin\Quiztools\Blank\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Question Table
 *
 * @since  1.6
 */
class QuestionBlankTable extends Table
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
        parent::__construct('#__quiztools_questions_blank', 'id', $db, $dispatcher);
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
        $src['distractors'] = (isset($src['distractors']) && \is_array($src['distractors']))
            ? $src['distractors']
            : [];

        $registry = new Registry($src['distractors']);
        $src['distractors'] = (string) $registry;

        return parent::bind($src, $ignore);
    }
}
