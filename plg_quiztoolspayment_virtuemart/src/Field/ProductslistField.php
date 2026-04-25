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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\Exception\ExecutionFailureException;
use Qt\Component\Quiztools\Administrator\Model\SubscriptionModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * List of products.
 */
class ProductslistField extends ListField
{
	protected $type = 'Productslist';

	protected function getOptions()
	{
        $app = Factory::getApplication();
        $input = $app->getInput();

        $option = $input->get('option');
        $view = $input->get('view');
        $layout = $input->get('layout');
        $id = $input->getInt('id');

        $db = $this->getDatabase();
        $query = $db->createQuery();

        // Products previously associated with other subscriptions.
        // We'll exclude them from the list. One product => one subscription.
        $query->select('DISTINCT ' . $db->qn('product_id'))
            ->from($db->qn('#__quiztools_subscriptions'))
            ->where($db->qn('payment_method') . '=' . $db->q('virtuemart'));

        // ... but at the same time, we will leave the required option in the settings of the previously created subscription.
        if ($app->isClient('administrator')
            && $option === 'com_quiztools'
                && $view === 'subscription'
                    && $layout === 'edit'
                        && !empty($id)
        ) {
            $query->where($db->qn('id') . ' != ' . $db->q($id));
        }

        try {
            $used_product_ids = $db->setQuery($query)->loadColumn();
        } catch (ExecutionFailureException $e) {
            $used_product_ids = [];
        }

        $langTag = Factory::getApplication()->getLanguage()->getTag();
        if (!file_exists(JPATH_SITE . '/components/com_virtuemart/language/' . $langTag . '/' . $langTag . '.com_virtuemart.ini')) {
            $langTag = 'en-GB';
        }
        $langSuffix = strtolower(str_replace('-', '_', $langTag));

        $query->clear()
            ->select('DISTINCT ' . $db->qn('p.virtuemart_product_id', 'value'))
            ->select($db->qn('lang.product_name', 'text'))
            ->from($db->qn('#__virtuemart_products', 'p'))
            ->join('LEFT', $db->qn('#__virtuemart_products_' . $langSuffix, 'lang'),
                $db->qn('lang.virtuemart_product_id') . ' = ' . $db->qn('p.virtuemart_product_id'))
            ->where($db->qn('p.published') . ' = ' . $db->q(1));
        if (!empty($used_product_ids)) {
            $query->where($db->qn('p.virtuemart_product_id') . " NOT IN ('" . implode("','", $used_product_ids) . "')");
        }
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

        return $options;
	}

    protected function getInput()
    {
        $options = $this->getOptions();
        $options = array_merge(parent::getOptions(), $options);

        $app = Factory::getApplication();
        $subscription_id = $app->getInput()->getInt('id');
        $formData = $app->getUserState('com_quiztools.edit.subscription.data', []);

        if (empty($formData) && !empty($subscription_id)) {
            /** @var SubscriptionModel $modelSubscription */
            $modelSubscription = $app->bootComponent('com_quiztools')->getMVCFactory()
                ->createModel('Subscription', 'Administrator', ['ignore_request' => true]);

            $formData = $modelSubscription->getItem($subscription_id);
        }

        $formData = (object) $formData;
        $selected = '';

        if (!empty($formData->payment_method) && $formData->payment_method === 'virtuemart') {
            $selected = $formData->product_id;
        }

        $idtag = $this->id ?: false;

        $attribs = 'class="form-select"';
        if (!empty($this->element['disabled']) && $this->element['disabled'] == 'true') {
            $attribs .= ' disabled="disabled"';
        }

        return HTMLHelper::_('select.genericlist',  $options,  $this->name, $attribs,
            'value', 'text', $selected, $idtag);
    }
}
