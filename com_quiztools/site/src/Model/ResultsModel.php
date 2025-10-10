<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\QueryInterface;
use Qt\Component\Quiztools\Administrator\Model\ResultsModel as AdminResultsModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * This models supports retrieving lists of results.
 *
 * @since  1.6
 */
class ResultsModel extends AdminResultsModel
{
    /**
     * Context string for the model type.  This is used to handle uniqueness
     * when dealing with the getStoreId() method and caching data structures.
     *
     * @var    string
     * @since  1.6
     */
    protected $context = 'com_quiztools.results.site';

    /**
     * Constructor
     *
     * @param   array                 $config   An array of configuration options (name, state, dbo, table_path, ignore_request).
     * @param   ?MVCFactoryInterface  $factory  The factory.
     *
     * @since   1.6
     * @throws  \Exception
     */
    public function __construct($config = [], ?MVCFactoryInterface $factory = null)
    {
        parent::__construct($config, $factory);

        $user = $this->getCurrentUser();

        if ($user->authorise('core.admin', 'com_quiztools')) {
            $this->filterFormName = 'filter_results.admin';
        } else {
            $this->filterFormName = 'filter_results';
        }
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   1.6
     */
    protected function populateState($ordering = 'a.start_datetime', $direction = 'DESC')
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();

        parent::populateState($ordering, $direction);

        $params = Factory::getApplication()->getParams();
        $this->setState('params', $params);

        $limit = $app->getUserStateFromRequest($this->context . '.limit', 'limit', $app->get('list_limit', 25), 'uint');
        $this->setState('list.limit', $limit);

        $this->setState('list.start', $input->get('limitstart', 0, 'uint'));

        $user = $this->getCurrentUser();
        if ($user->authorise('core.admin', 'com_quiztools')) {
            $this->setState('admin.mode', 1);
        }
    }

    /**
     * Get the master query for retrieving a list of quizzes subject to the model state.
     *
     * @return  QueryInterface
     *
     * @since   1.6
     */
    protected function getListQuery()
    {
        $lang = Factory::getApplication()->getLanguage();
        $lang->load('com_quiztools', JPATH_ADMINISTRATOR);

        $query = parent::getListQuery();

        if (!$this->getState('admin.mode')) {
            $db   = $this->getDatabase();
            $user = $this->getCurrentUser();

            $query->where($db->qn('a.user_id') . ' = ' . $db->q((int) $user->id));

            // Results are available only to authorized users
            // Additional checks see in "AccessService".
            $query->where($db->qn('a.user_id') . ' > ' . $db->q(0));
        }

        $order_col  = $this->state->get('list.ordering', 'a.start_datetime');
        //$order_dirn = $this->state->get('list.direction', 'DESC');

        // In addition to the selected sorting by column, we will add a default sorting
        // so that the latest data is always at the top:
        if ($order_col !== 'a.start_datetime') {
            $query->order('a.start_datetime DESC');
        }

        return $query;
    }
}
