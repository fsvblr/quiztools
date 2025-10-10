<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Table;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Question table
 *
 * @since  1.5
 */
class QuestionTable extends Table
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
		$this->typeAlias = 'com_quiztools.question';

		parent::__construct('#__quiztools_questions', 'id', $db, $dispatcher);

		$this->setColumnAlias('published', 'state');
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

		// Check for Question text
		if (trim($this->text) === '') {
			throw new \Exception(Text::_('COM_QUIZTOOLS_QUESTION_WARNING_PROVIDE_TEXT'));
		}

		$this->text = htmlspecialchars_decode($this->text, ENT_QUOTES);

		// Check for a valid category.
		if (!$this->catid = (int) $this->catid) {
			throw new \Exception(Text::_('JLIB_DATABASE_ERROR_CATEGORY_REQUIRED'));
		}

		// If the type is "boilerplate" then this data is missing.
		// And a TEXT column in a database cannot have a default value.
		if (empty($this->feedback_msg_right)) {
			$this->feedback_msg_right = '';
		}
		if (empty($this->feedback_msg_wrong)) {
			$this->feedback_msg_wrong = '';
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

		// Set ordering
		if (empty($this->ordering)) {
			// Set ordering to last if ordering was 0
			$this->ordering = self::getNextOrder($this->_db->qn('catid') . ' = ' . ((int) $this->catid)
				. ' AND ' . $this->_db->qn('state') . ' >= 0');
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
	 * Method to store a row in the database from the Table instance properties.
	 *
	 * If a primary key value is set the row with that primary key value will be updated with the instance property values.
	 * If no primary key value is set a new row will be inserted into the database with the properties from the Table instance.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.7.0
	 */
	public function store($updateNulls = false)
	{
		PluginHelper::importPlugin('quiztools', null, true, $this->getDispatcher());

		return parent::store($updateNulls);
	}

	/**
	 * Method to delete a row from the database table by primary key value.
	 *
	 * @param   mixed  $pk  An optional primary key value to delete.  If not set the instance property value is used.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.7.0
	 * @throws  \UnexpectedValueException
	 */
	public function delete($pk = null)
	{
		PluginHelper::importPlugin('quiztools', null, true, $this->getDispatcher());

		return parent::delete($pk);
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

		return 'com_quiztools.question.' . (int) $this->$k;
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
