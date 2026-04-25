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
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Order table
 *
 * @since  1.6
 */
class OrderTable extends Table
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
     * @param   DatabaseInterface        $db          Database connector object
     * @param   ?DispatcherInterface  $dispatcher  Event dispatcher for this table
     *
     * @since   1.5
     */
    public function __construct(DatabaseInterface $db, DispatcherInterface $dispatcher = null)
    {
        $this->typeAlias = 'com_quiztools.order';

        parent::__construct('#__quiztools_orders', 'id', $db, $dispatcher);
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
        $date = Factory::getDate()->toSql();
	    $user = Factory::getApplication()->getIdentity();

        try {
            parent::check();
        } catch (\Exception $e) {
	        throw new \Exception($e->getMessage(), 500, $e);
        }

        // Set created date if not set.
        if (!(int) $this->created) {
            $this->created = $date;
        }

        if ($this->id) {
            // Existing item
            $this->modified_by = $user->id;
            $this->modified    = $date;
            if (empty($this->created_by)) {
                $this->created_by = 0;
            }
        } else {
            // Field created_by can be set by the user, so we don't touch it if it's set.
            if (empty($this->created_by)) {
                $this->created_by = $user->id;
            }

            // Set modified to created date if not set
            if (!(int) $this->modified) {
                $this->modified = $this->created;
            }

            // Set modified_by to created_by user if not set
            if (empty($this->modified_by)) {
                $this->modified_by = $this->created_by;
            }
        }

        return true;
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

		return 'com_quiztools.order.' . (int) $this->$k;
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
