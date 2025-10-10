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
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Certificate model.
 *
 * @since  1.6
 */
class CertificateModel extends AdminModel
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $text_prefix = 'COM_QUIZTOOLS';

	/**
	 * The type alias for this content type.
	 *
	 * @var    string
	 * @since  3.2
	 */
	public $typeAlias = 'com_quiztools.certificate';

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk)) {
			$registry = new Registry($item->fields);
            $item->fields = $registry->toArray();
		}

		return $item;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \Joomla\CMS\Form\Form|boolean  A Form object on success, false on failure
	 *
	 * @since   1.6
	 */
	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm('com_quiztools.certificate', 'certificate', ['control' => 'jform', 'load_data' => $loadData]);

		if (empty($form)) {
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_quiztools.edit.certificate.data', []);

		if (empty($data)) {
			$data = $this->getItem();
		}

		$this->preprocessData('com_quiztools.certificate', $data, 'quiztools');

		return $data;
	}

	/**
	 * Method to delete one or more records.
	 *
	 * @param   array  &$pks  An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since   1.6
	 */
	public function delete(&$pks)
	{
		$affected_quizzes_ids = $this->getQuizzesIdsByCertificatesIds($pks);

		if (!parent::delete($pks)) {
			return false;
		}

		if (!empty($affected_quizzes_ids)) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('COM_QUIZTOOLS_CERTIFICATE_DELETE_WARNING_AFFECTED_QUIZZES', implode(', ', $affected_quizzes_ids)),
                'warning'
            );
		}

		return true;
	}

    /**
     * Method to change the published state of one or more records.
     *
     * @param   array    &$pks   A list of the primary keys to change.
     * @param   integer  $value  The value of the published state.
     *
     * @return  boolean  True on success.
     *
     * @since   1.6
     */
    public function publish(&$pks, $value = 1)
    {
        if (!parent::publish($pks, $value)) {
            return false;
        }

        if ($value === 0) {
            $affected_quizzes_ids = $this->getQuizzesIdsByCertificatesIds($pks);

            if (!empty($affected_quizzes_ids)) {
                Factory::getApplication()->enqueueMessage(
                    Text::sprintf('COM_QUIZTOOLS_CERTIFICATE_UNPUBLISH_WARNING_AFFECTED_QUIZZES', implode(', ', $affected_quizzes_ids)),
                    'warning'
                );
            }
        }

        return true;
    }

	/**
	 * Get quizzes Ids by certificates Ids
	 *
	 * @param array $certificates_ids
	 *
	 * @return array
	 */
	private function getQuizzesIdsByCertificatesIds($certificates_ids=[])
	{
		if (!is_array($certificates_ids)) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select($db->qn('id'))
			->from($db->qn('#__quiztools_quizzes'))
			->where($db->qn('certificate_id') . " IN ('" . implode("','", $certificates_ids) . "')");
		$db->setQuery($query);
		$quizzes = $db->loadColumn();

		// array_filter : Remove zero values ($certificate_id == 0 => 'No Certificate')
		$quizzes = array_values(array_filter(array_unique($quizzes)));

		return $quizzes;
	}
}
