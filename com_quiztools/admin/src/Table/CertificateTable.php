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
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Certificate table
 *
 * @since  1.5
 */
class CertificateTable extends Table
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
		$this->typeAlias = 'com_quiztools.certificate';

		parent::__construct('#__quiztools_certificates', 'id', $db, $dispatcher);

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

		// Check for Certificate title
		if (trim($this->title) === '') {
			throw new \Exception(Text::_('COM_QUIZTOOLS_CERTIFICATE_WARNING_PROVIDE_TITLE'));
		}

		$this->title = htmlspecialchars_decode($this->title, ENT_QUOTES);

        // Check for Certificate image
        if (trim($this->file) === '') {
            throw new \Exception(Text::_('COM_QUIZTOOLS_CERTIFICATE_WARNING_PROVIDE_IMAGE'));
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
        if (isset($src['fields']) && \is_array($src['fields'])) {
            $registry = new Registry($src['fields']);
            $src['fields'] = (string) $registry;
        }

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

		return 'com_quiztools.certificate.' . (int) $this->$k;
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
