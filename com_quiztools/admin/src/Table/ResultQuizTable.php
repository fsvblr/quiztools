<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Table;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Result Quiz table
 *
 * @since  1.6
 */
class ResultQuizTable extends Table
{
    /**
     * Indicates that columns fully support the NULL value in the database
     *
     * @var    boolean
     * @since  4.0.0
     */
    protected $_supportNullValue = true;

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
        $this->typeAlias = 'com_quiztools.result.quiz';

        parent::__construct('#__quiztools_results_quizzes', 'id', $db, $dispatcher);
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
        $app = Factory::getApplication();
	    $user = $app->getIdentity();

        try {
            parent::check();
        } catch (\Exception $e) {
	        throw new \Exception($e->getMessage(), 500, $e);
        }

        if ($app->isClient('site')) {
            if (!(int) $this->user_id) {
                $this->user_id = $user->id;
            }

            if (!$this->start_datetime) {
                $this->start_datetime = Factory::getDate()->toSql();
            }
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
	    // The `params` field is reserved for custom jobs. Used in custom plugins(?).
	    if (!empty($src['id'])) {
		    $query = $this->_db->getQuery(true)
			    ->select($this->_db->qn(('params')))
			    ->from($this->_db->qn($this->_tbl))
			    ->where($this->_db->qn('id') . ' = ' . $this->_db->q((int) $src['id']));
		    $this->_db->setQuery($query);
		    $params = $this->_db->loadResult();
	    } else {
		    $params = '';
	    }

	    $params = new Registry($params);

	    if (!isset($src['params']) || !\is_array($src['params'])) {
		    $src['params'] = [];
	    }

	    $params->merge(new Registry($src['params']));
	    $src['params'] = (string) $params;

        return parent::bind($src, $ignore);
    }

	/**
	 * Returns the asset name of the entry as it appears in the {@see Asset} table.
	 *
	 * @return  string  The asset name.
	 *
	 * @since   4.1.0
	 */
	protected function _getAssetName(): string
	{
		$k = $this->_tbl_key;

		return 'com_quiztools.result.quiz.' . (int) $this->$k;
	}

    /**
     * Get the type alias
     *
     * @return  string  The alias as described above
     *
     * @since   4.0.0
     */
    public function getTypeAlias()
    {
        return $this->typeAlias;
    }
}
