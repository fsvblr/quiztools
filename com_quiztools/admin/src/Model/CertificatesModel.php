<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Methods supporting a list of certificates records.
 *
 * @since  1.6
 */
class CertificatesModel extends ListModel
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
                'file', 'a.file',
                'state', 'a.state',
                'created', 'a.created',
            ];
        }

        parent::__construct($config);
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
    protected function populateState($ordering = 'a.id', $direction = 'desc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $state = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '');
        $this->setState('filter.state', $state);

        parent::populateState($ordering, $direction);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string  A store id.
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.state');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  DatabaseQuery|string  A DatabaseQuery object to retrieve the data set.
     *
     * @since   1.6
     */
    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery();

        $query->select(
            $this->getState(
                'list.select',
                [
                    $db->qn('a.id'),
                    $db->qn('a.title'),
                    $db->qn('a.file'),
                    $db->qn('a.state'),
                    $db->qn('a.created'),
                    $db->qn('a.checked_out'),
                    $db->qn('a.checked_out_time'),
                    $db->qn('uc.name', 'editor'),
                ]
            )
        )
            ->from($db->qn('#__quiztools_certificates', 'a'))
            ->join('LEFT', $db->qn('#__users', 'uc'), $db->qn('uc.id') . ' = ' . $db->qn('a.checked_out'))
        ;

        // Filter by state
        $state = (string) $this->getState('filter.state');
        if (is_numeric($state)) {
            $state = (int) $state;
            $query->where($db->qn('a.state') . ' = :state')
                ->bind(':state', $state, ParameterType::INTEGER);
        }

        // Filter by search in title
        if ($search = $this->getState('filter.search')) {
            if (stripos($search, 'id:') === 0) {
                $search = (int) substr($search, 3);
                $query->where($db->qn('a.id') . ' = :search')
                    ->bind(':search', $search, ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', trim($search)) . '%';
                $query->where('(' . $db->qn('a.title') . ' LIKE :search)')
                    ->bind([':search'], $search);
            }
        }

        // Add the list ordering clause.
        $order_col  = $this->state->get('list.ordering', 'a.id');
        $order_dirn = $this->state->get('list.direction', 'DESC');

        $ordering = $db->escape($order_col) . ' ' . $db->escape($order_dirn);

        $query->order($ordering);

        return $query;
    }

	/**
	 * Getting a list of certificates to build a select.
	 *
	 * @return array|mixed
	 * @throws \Exception
	 */
	public function getCertificatesList()
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select($db->qn('id', 'value'))
			->select($db->qn('title', 'text'))
			->from($db->qn('#__quiztools_certificates'))
            ->where($db->qn('state') . '=' . $db->q(1));
		$db->setQuery($query);

		try {
			$certificates = $db->loadObjectList();
		} catch (\Exception $e) {
			Factory::getApplication()->enqueueMessage(
				Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()),
				'warning'
			);

			return [];
		}

		return $certificates;
	}
}

