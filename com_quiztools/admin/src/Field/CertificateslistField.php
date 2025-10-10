<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;
use Qt\Component\Quiztools\Administrator\Model\CertificatesModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class CertificateslistField extends ListField
{
	protected $type = 'Certificateslist';

	protected function getInput()
	{
		$app = Factory::getApplication();

		/** @var CertificatesModel $model_certificates */
		$model_certificates = $app->bootComponent('com_quiztools')->getMVCFactory()
			->createModel('Certificates', 'Administrator', ['ignore_request' => true]);

		$certificates = $model_certificates->getCertificatesList();
		$certificates = array_merge(parent::getOptions(), $certificates);

		$input = $app->getInput();
		$component = $input->get('option');
		$view = $input->get('view');
		$layout = $input->get('layout');
		$id = $input->getInt('id', 0);
		$quiz_id = ($component == 'com_quiztools' && $view == 'quiz' && $layout == 'edit') ? $id : null;

		if (!is_null($quiz_id)) {
			$form_data = $app->getUserState('com_quiztools.edit.quiz.data');
		}

		$selected_certificate_id = 0;

		if (!empty($form_data['certificate_id'])) {
			$selected_certificate_id = $form_data['certificate_id'];
		} else {
			if (!empty($quiz_id)) {
				$db = $this->getDatabase();
				$query = $db->createQuery()
					->select($db->qn('certificate_id'))
					->from($db->qn('#__quiztools_quizzes'))
					->where($db->qn('id') . ' = :id')
					->bind(':id', $quiz_id, ParameterType::INTEGER);
				$db->setQuery($query);

				try {
					$selected_certificate_id = $db->loadResult();
				} catch (\Exception $e) {
					$app->enqueueMessage(
						Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()),
						'warning'
					);

					$selected_certificate_id = 0;
				}
			}
		}

		return HTMLHelper::_('select.genericlist',  $certificates,  $this->name, 'class="form-select"',
			'value', 'text', $selected_certificate_id);
	}
}
