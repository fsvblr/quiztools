<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Site\Model;

use Joomla\CMS\Factory;
use Qt\Component\Quiztools\Administrator\Model\ResultModel as AdminResultModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Result model.
 *
 * @since  1.6
 */
class ResultModel extends AdminResultModel
{
    /**
     * Model context string.
     *
     * @var        string
     */
    protected $_context = 'com_quiztools.result.site';

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @since   1.6
     *
     * @return void
     */
    protected function populateState()
    {
        $app = Factory::getApplication();

        $pk = $app->getInput()->getInt('id');
        $this->setState('result.id', $pk);

        $params = $app->getParams();
        $this->setState('params', $params);

        $this->setState('result.layout', 'result');
    }
}
