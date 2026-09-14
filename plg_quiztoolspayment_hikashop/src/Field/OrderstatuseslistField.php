<?php

/**
 * @package     QuizToolsPayment.Plugin
 * @subpackage  QuizToolsPayment.hikashop
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 * @license     GNU General Public License version 2 or later
 */

namespace Qt\Plugin\Quiztoolspayment\Hikashop\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * List of order statuses.
 *
 * @since  1.2.0
 */
class OrderstatuseslistField extends ListField
{
	protected $type = 'Orderstatuseslist';
    protected $storeName = 'hikashop';

	protected function getOptions()
	{
        $options = [];

        $app = Factory::getApplication();
        $db = $this->getDatabase();

        $storeManifest = JPATH_ROOT . '/administrator/components/com_' . $this->storeName . '/' . $this->storeName . '.xml';

        if (file_exists($storeManifest)) {
            $query = $db->createQuery()
                ->select('DISTINCT ' . $db->qn('orderstatus_namekey', 'value'))
                ->select($db->qn('orderstatus_name', 'text'))
                ->from($db->qn('#__hikashop_orderstatus'));
            $db->setQuery($query);

            try {
                $options = $db->loadObjectList();
            } catch (\Exception $e) {
                $app->enqueueMessage(
                    Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()),
                    'warning'
                );

                $options = [];
            }
        }

        $options = array_merge(parent::getOptions(), $options);

        return $options;
	}
}
