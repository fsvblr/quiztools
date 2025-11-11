<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Qt\Component\Quiztools\Administrator\Helper\QuiztoolsHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * This models supports retrieving lists of quizzes.
 *
 * @since  1.6
 */
class QuizzesModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @since   1.6
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'title', 'a.title',
                'alias', 'a.alias',
                'catid', 'a.catid', 'category_title', 'category_id',
                'ordering', 'a.ordering',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * This method should only be called once per instantiation and is designed
     * to be called on the first call to the getState() method unless the model
     * configuration flag to ignore the request is set.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   3.0.1
     */
    protected function populateState($ordering = 'a.ordering', $direction = 'ASC')
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();

	    $params = $app->getParams();
	    $this->setState('params', $params);

	    $limit = $app->getUserStateFromRequest($this->context . '.limit', 'limit', $app->get('list_limit', 25), 'uint');
	    $this->setState('list.limit', $limit);

	    $this->setState('list.start', $input->get('limitstart', 0, 'uint'));

	    $order_col = $app->getUserStateFromRequest($this->context . '.filter_order', 'filter_order', '', 'string');
	    if (!\in_array($order_col, $this->filter_fields)) {
		    $order_col = 'a.ordering';
	    }
	    $this->setState('list.ordering', $order_col);

	    $list_order = $app->getUserStateFromRequest($this->context . '.filter_order_Dir', 'filter_order_Dir', '', 'cmd');
	    if (!\in_array(strtoupper($list_order), ['ASC', 'DESC', ''])) {
		    $list_order = 'ASC';
	    }
	    $this->setState('list.direction', $list_order);

	    $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
	    $this->setState('filter.search', $search);

	    // Page for a specific quiz category. There is no filter by category.
	    $category_id = $input->getInt('catid');
		if (!$category_id) {
			// Page for all quizzes of all categories. There is a filter by categories.
			$category_id = $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id');
		}
	    $this->setState('filter.category_id', $category_id);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string  A store id.
     *
     * @since   1.6
     */
    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.filter.search');
	    $id .= ':' . $this->getState('filter.category_id');

        return parent::getStoreId($id);
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
        $user = $this->getCurrentUser();

        $db = $this->getDatabase();
        $query = $db->createQuery();

        $query->select(
            $this->getState(
                'list.select',
                [
                    $db->qn('a.id'),
                    $db->qn('a.title'),
                    $db->qn('a.alias'),
	                $db->qn('a.catid'),
                    $db->qn('a.description'),
                    $db->qn('a.ordering'),
	                $db->qn('c.title', 'category_title'),
	                $db->qn('a.params'), // This field is for custom jobs
                ]
            )
        )
	        ->from($db->qn('#__quiztools_quizzes', 'a'))
            ->join('LEFT', $db->qn('#__categories', 'c'), $db->qn('c.id') . ' = ' . $db->qn('a.catid'))
            ->where($db->qn('type_access') . ' = ' . $db->q(0))
        ;

        // Filter by access level.
        $groups = $user->getAuthorisedViewLevels();
        $query->whereIn($db->qn('a.access'), $groups)
            ->whereIn($db->qn('c.access'), $groups);

        // Filter by state
        $query->where($db->qn('c.published') . ' = 1 AND ' . $db->qn('a.state') . ' = 1');

        // Filter by category
	    $category_id = $this->getState('filter.category_id');
	    if (is_numeric($category_id)) {
		    $category_id = (int) $category_id;
		    $query->where($db->qn('a.catid') . ' = :categoryId')
			    ->bind(':categoryId', $category_id, ParameterType::INTEGER);
	    }

	    // Filter by search by title, alias, id
	    if ($search = $this->getState('filter.search')) {
		    $search = StringHelper::strtolower($search);
		    if (stripos($search, 'id:') === 0) {
			    $search = (int) substr($search, 3);
			    $query->where($db->qn('a.id') . ' = :search')
				    ->bind(':search', $search, ParameterType::INTEGER);
		    } else {
			    $search = '%' . str_replace(' ', '%', trim($search)) . '%';
			    $query->where('(LOWER(' . $db->qn('a.title') . ') LIKE :search1 OR LOWER(' . $db->qn('a.alias') . ') LIKE :search2)')
				    ->bind([':search1', ':search2'], $search);
		    }
	    }

        // Add the list ordering clause.
        $query->order(
            $db->escape($this->getState('list.ordering', 'a.ordering')) . ' ' . $db->escape($this->getState('list.direction', 'ASC'))
        );

        return $query;
    }

    /**
     * Method to get a list of quizzes.
     *
     *
     * @return  mixed  An array of objects on success, false on failure.
     *
     * @since   1.6
     */
    public function getItems()
    {
        $items  = parent::getItems();

		if (!empty($items)) {
			foreach ($items as $item) {
                $registry = new Registry($item->params);
                $item->params = $registry->toArray();

				// If the quiz description contains the "readmore" insert, the first part of the description
				// will be shown in the category. Otherwise, there is no quiz description in the category.
                $item->description = QuiztoolsHelper::getDescriptionInCategory($item->description);
			}
		}

        return $items;
    }
}
