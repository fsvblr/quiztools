<?php

/**
 * @package     QuizToolsPayment.Plugin
 * @subpackage  QuizToolsPayment.virtuemart
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Plugin\Quiztoolspayment\Virtuemart\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * List of order statuses.
 */
class OrderstatuseslistField extends ListField
{
	protected $type = 'Orderstatuseslist';
    protected $storeName = 'virtuemart';

	protected function getOptions()
	{
        $options = [];

        $app = Factory::getApplication();
        $db = $this->getDatabase();

        $storeManifest = JPATH_ROOT . '/administrator/components/com_' . $this->storeName . '/' . $this->storeName . '.xml';

        if (file_exists($storeManifest)) {
            $query = $db->createQuery()
                ->select('DISTINCT ' . $db->qn('order_status_code', 'value'))
                ->select($db->qn('order_status_name', 'text'))
                ->from($db->qn('#__virtuemart_orderstates'));
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

        if (!empty($options)) {
            $lang = Factory::getApplication()->getLanguage();
            $langTag = $lang->getTag();
            if (!file_exists(JPATH_SITE . '/components/com_virtuemart/language/' . $langTag . '/' . $langTag . '.com_virtuemart_orders.ini')) {
                $langTag = 'en-GB';
            }
            $lang->load($langTag . '.com_virtuemart_orders', JPATH_SITE . '/components/com_virtuemart');

            $options = array_map(function ($option) {
                $option->text = Text::_($option->text);
                return $option;
            }, $options);
        }

        $options = array_merge(parent::getOptions(), $options);

        return $options;
	}
}
