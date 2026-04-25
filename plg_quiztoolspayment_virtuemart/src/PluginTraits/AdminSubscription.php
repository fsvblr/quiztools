<?php

/**
 * @package     QuizToolsPayment.Plugin
 * @subpackage  QuizToolsPayment.virtuemart
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Plugin\Quiztoolspayment\Virtuemart\PluginTraits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\Model;
use Joomla\Event\Event;

/**
 * Processing the subscription form in the admin panel.
 *
 * @since   3.9.0
 */
trait AdminSubscription
{
    /**
     * Runs on content preparation of the subscription form in the admin panel.
     *
     * @param   Event $event  The event instance.
     * @return  bool
     * @since   3.9.0
     */
    public function adminSubscriptionGetData(Event $event): bool
    {
        if (!($this->getApplication() instanceof CMSApplication)) {
            return false;
        }

        if (!$this->getApplication()->isClient('administrator')) {
            return false;
        }

        /**
         * @var   string|null        $context  The context for the data
         * @var   array|object|null  $data     An object or array containing the data for the form.
         */
        [$context, $data] = array_values($event->getArguments());

        if (!\in_array($context, ['com_quiztools.admin.subscription.data'])) {
            return false;
        }

        if (\is_array($data)) {
            $data = (object) $data;
        }

        // Adding data to $data
        if ((string) $data->payment_method === (string) $this->storeName) {
            $data->select_product_id[$this->storeName] = (int) $data->product_id;
        } else {
            $data->select_product_id[$this->storeName] = 0;
        }

        $event->setArgument('result', $data);

        return true;
    }

	/**
	 * Processing the subscription form in the admin panel.
	 *
	 * @param   Model\PrepareFormEvent $event
	 * @return void
     * @since   3.9.0
	 */
    public function adminSubscriptionPrepareForm(Model\PrepareFormEvent $event): void
    {
        if (!$this->getApplication()->isClient('administrator')) {
            return;
        }

        $form     = $event->getForm();
        $data     = $event->getData();
        $formName = $form->getName();

        $allowedFormNames = [
            'com_quiztools.subscription',
        ];

        if (!\in_array($formName, $allowedFormNames, true)) {
            return;
        }

        // Adding the current payment method to the form:
        $fieldXml = $form->getFieldXml('payment_method');
        $fieldXml->addChild('option', ucfirst($this->storeName))
            ->addAttribute('value', $this->storeName);

        // Adding a new field "select_product_id[$this->storeName]" to the subscription form
        $xml = '
            <field addfieldprefix="Qt\Plugin\Quiztoolspayment\\' . ucfirst($this->storeName) . '\Field" 
                name="select_product_id][' . $this->storeName . '"
                type="productslist"
                label="PLG_QUIZTOOLSPAYMENT_' . mb_strtoupper($this->storeName, 'UTF-8') . '_FORM_SUBSCRIPTION_FIELD_SELECT_PRODUCT_ID_LABEL"
                description="PLG_QUIZTOOLSPAYMENT_' . mb_strtoupper($this->storeName, 'UTF-8') . '_FORM_SUBSCRIPTION_FIELD_SELECT_PRODUCT_ID_DESC"
                class="form-select"
                id="select_product_id][' . $this->storeName . '"
                default=""
                showon="payment_method:' . $this->storeName . '"
            >
                <option value="">JSELECT</option>
            </field>
        ';
        $form->setField(new \SimpleXMLElement($xml));

        if (!empty($data->id)) {
            $form->setFieldAttribute('select_product_id][' . $this->storeName, 'disabled', 'true');
        }
    }
}
