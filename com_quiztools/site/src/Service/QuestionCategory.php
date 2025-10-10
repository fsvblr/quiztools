<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Site\Service;

use Joomla\CMS\Categories\Categories;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class QuestionCategory extends Categories
{
    public function __construct($options = array())
    {
        $options['table']     = '#__quiztools_questions';
        $options['extension'] = 'com_quiztools';

        parent::__construct($options);
    }
}
