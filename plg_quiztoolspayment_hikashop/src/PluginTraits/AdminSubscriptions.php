<?php

/**
 * @package     QuizToolsPayment.Plugin
 * @subpackage  QuizToolsPayment.hikashop
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 * @license     GNU General Public License version 2 or later
 */

namespace Qt\Plugin\Quiztoolspayment\Hikashop\PluginTraits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\Model;
use Joomla\Event\Event;

/**
 * Processing subscriptions list in the admin panel.
 *
 * @since  1.2.0
 */
trait AdminSubscriptions
{
    /**
     * Runs on content preparation of subscriptions list in the admin panel.
     *
     * @param   Event $event  The event instance.
     * @return  bool
     * @since  1.2.0
     */
    public function adminSubscriptionsGetData(Event $event): bool
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

        if (!\in_array($context, ['com_quiztools.admin.subscriptions.data'])) {
            return false;
        }

        if (\is_array($data)) {
            $data = (object) $data;
        }

        if (empty($data)) {
            $event->setArgument('result', (array) $data);
            return true;
        }

        // Let's add the following data from the e-store component to the subscriptions:
        // - product name
        // - link to the product in the admin panel

        $product_ids = [];

        foreach ($data as $subscription) {
            if ((string) $subscription->payment_method === (string) $this->storeName) {
                $product_ids[] = (int) $subscription->product_id;
            }
        }

        $product_ids = array_filter($product_ids);

        if (empty($product_ids)) {
            $event->setArgument('result', (array) $data);
            return true;
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select('DISTINCT ' . $db->qn('p.product_id', 'product_id'))
            ->select($db->qn('p.product_name', 'product_title'))
            ->from($db->qn('#__hikashop_product', 'p'))
            ->where($db->qn('p.product_id') . " iN ('" . implode("','", $product_ids) . "')")
        ;
        $db->setQuery($query);

        try {
            $products = $db->loadObjectList('product_id');
        } catch (\Exception $e) {
            $products = [];
        }

        foreach ($data as $subscription) {
            if ((string) $subscription->payment_method === (string) $this->storeName) {
                $subscription->product_title = !empty($products[$subscription->product_id]->product_title)
                    ? htmlspecialchars($products[$subscription->product_id]->product_title, ENT_QUOTES, 'UTF-8')
                    : '';
                $subscription->product_admin_link = 'index.php?option=com_hikashop&ctrl=product&task=edit&cid[]=' . (int) $subscription->product_id;
            }
        }

        $event->setArgument('result', (array) $data);

        return true;
    }

    /**
     * Processing the filter_subscriptions form in the admin panel.
     *
     * @param   Model\PrepareFormEvent $event
     * @return void
     * @since  1.2.0
     */
    public function adminSubscriptionsPrepareForm(Model\PrepareFormEvent $event): void
    {
        if (!$this->getApplication()->isClient('administrator')) {
            return;
        }

        $form     = $event->getForm();
        $data     = $event->getData();
        $formName = $form->getName();

        $allowedFormNames = [
            'com_quiztools.subscriptions.filter',
        ];

        if (!\in_array($formName, $allowedFormNames, true)) {
            return;
        }

        // Adding the current payment method to the form:
        $fieldXml = $form->getFieldXml('payment_method', 'filter');
        $fieldXml->addChild('option', ucfirst($this->storeName))
            ->addAttribute('value', $this->storeName);
    }
}
