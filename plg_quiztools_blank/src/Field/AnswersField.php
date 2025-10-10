<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.blank
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Plugin\Quiztools\Blank\Field;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Qt\Component\Quiztools\Administrator\Field\TagifyField;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * A field with multiple string values,
 * with the ability to add new ones.
 *
 * @since  5.3
 */
class AnswersField extends TagifyField
{
    /**
     * List of values.
     *
     * @var    string
     * @since  3.1
     */
    public $type = 'Answers';

    /**
     * Method to get a list of values
     *
     * @return  array[]
     */
    protected function getOptions()
    {
        $app = Factory::getApplication();
		$question_id = $app->getInput()->getInt('id');

        $currentOrdering = preg_replace('/\D+/', '', $this->id);

        if (empty($question_id)) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->createQuery();

	    $query->select($db->qn('answers'))
		    ->from($db->qn('#__quiztools_questions_blank_options'))
		    ->where($db->qn('question_id') . ' = ' . $db->q($question_id))
            ->where($db->qn('ordering') . ' = ' . $db->q((int) $currentOrdering + 1));
	    $db->setQuery($query);

        try {
            $values = $db->loadResult();
        } catch (\RuntimeException $e) {
            return [];
        }

        $registry = new Registry($values);
        $values = $registry->toArray();

        return $values;
    }
}
