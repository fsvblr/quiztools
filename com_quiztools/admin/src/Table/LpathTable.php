<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Table;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Learning Path table
 *
 * @since  1.6
 */
class LpathTable extends Table
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
        $this->typeAlias = 'com_quiztools.lpath';

        parent::__construct('#__quiztools_lpaths', 'id', $db, $dispatcher);

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
        $db = $this->getDatabase();

        try {
            parent::check();
        } catch (\Exception $e) {
	        throw new \Exception($e->getMessage(), 500, $e);
        }

	    // Check for valid title
	    if (trim($this->title) === '') {
		    throw new \Exception(Text::_('COM_QUIZTOOLS_LPATH_WARNING_PROVIDE_VALID_TITLE'));
	    }

	    // Set title
	    $this->title = htmlspecialchars_decode($this->title, ENT_QUOTES);

	    // Set alias
	    if (trim($this->alias) == '') {
		    $this->alias = $this->title;
	    }

	    $this->alias = ApplicationHelper::stringURLSafe($this->alias, $this->language);

	    if (trim(str_replace('-', '', $this->alias)) == '') {
		    $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
	    }

        // Check for a valid category.
	    if (!$this->catid = (int) $this->catid) {
	        throw new \Exception(Text::_('JLIB_DATABASE_ERROR_CATEGORY_REQUIRED'));
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

	    // Set ordering
	    if (empty($this->ordering)) {
		    // Set ordering to last if ordering was 0
		    $this->ordering = self::getNextOrder($db->qn('catid') . ' = ' . ((int) $this->catid)
			    . ' AND ' . $db->qn('state') . ' >= 0');
	    }

		// Set language
	    if (empty($this->language)) {
		    $this->language = '*';
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
        if (isset($src['lpath_items']) && \is_array($src['lpath_items'])) {
            $src['lpath_items'] = array_map(function ($item) {
                if ($item['type'] !== 'a') {
                    $item['article_id'] = "0";
                } else {
                    $item['quiz_id'] = "0";
                }

                if (empty($item['unique_id'])
                    || ($item['type'] === 'a' && mb_substr($item['unique_id'], 0, 1) !== 'a')
                        || ($item['type'] === 'q' && mb_substr($item['unique_id'], 0, 1) !== 'q')
                ) {
                    $prefix = $item['type'] . (($item['type'] === 'a') ? $item['article_id'] : $item['quiz_id']) . '_';
                    // Let's generate a short (8 characters) key.
                    // Remove characters that might be problematic in a URL or database ('+', '/', '='):
                    $item['unique_id'] = $prefix . substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(6))), 0, 8);
                }

                if ($item['type'] == 'a') {
                    $form = new Form('com_quiztools.lpath_items', ['control' => 'jform']);
                    $loaded = $form->loadFile(JPATH_ADMINISTRATOR . '/components/com_quiztools/forms/lpath_items.xml');
                    if ($loaded) {
                        $field = $form->getField('min_time_article');
                        if ($field) {
                            $min = !empty($field->getAttribute('min')) ? (int) $field->getAttribute('min') : 1;
                            if ((int) $item['min_time_article'] < $min) {
                                $item['min_time_article'] = $min;
                            }

                            $max = !empty($field->getAttribute('max')) ? (int) $field->getAttribute('max') : 86400;
                            if ((int) $item['min_time_article'] > $max) {
                                $item['min_time_article'] = $max;
                            }
                        }
                    }
                }

                return $item;
            }, $src['lpath_items']);

            $registry = new Registry($src['lpath_items']);
            $src['lpath_items'] = (string) $registry;
        }

	    // The `params` field is reserved for custom jobs. Used in custom plugins(?).
	    if (!empty($src['id'])) {
            $db = $this->getDatabase();
		    $query = $db->createQuery()
			    ->select($db->qn(('params')))
			    ->from($db->qn($this->_tbl))
			    ->where($db->qn('id') . ' = ' . $db->q((int) $src['id']));
            $db->setQuery($query);
		    $params = $db->loadResult();
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
	 * Overrides Table::store
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	public function store($updateNulls = true)
	{
		// Verify that the alias is unique
		$table = new self($this->getDatabase(), $this->getDispatcher());

		if ($table->load(['alias' => $this->alias, 'catid' => $this->catid]) && ($table->id != $this->id || $this->id == 0)) {
			throw new \Exception(Text::_('COM_QUIZTOOLS_LPATH_ERROR_UNIQUE_ALIAS'));
		}

		return parent::store($updateNulls);
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

		return 'com_quiztools.lpath.' . (int) $this->$k;
	}
}
