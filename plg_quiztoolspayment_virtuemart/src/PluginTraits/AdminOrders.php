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

use Joomla\CMS\Event\Model;

/**
 * Processing orders list in the admin panel.
 *
 * @since   3.9.0
 */
trait AdminOrders
{
    /**
     * Processing the filter_orders form in the admin panel.
     *
     * @param   Model\PrepareFormEvent $event
     * @return void
     * @since   3.9.0
     */
    public function adminOrdersPrepareForm(Model\PrepareFormEvent $event): void
    {
        if (!$this->getApplication()->isClient('administrator')) {
            return;
        }

        $form     = $event->getForm();
        $data     = $event->getData();
        $formName = $form->getName();

        $allowedFormNames = [
            'com_quiztools.orders.filter',
        ];

        if (!\in_array($formName, $allowedFormNames, true)) {
            return;
        }

        // Adding the current payment method to the form:
        $fieldXml = $form->getFieldXml('store_type', 'filter');
        $fieldXml->addChild('option', ucfirst($this->storeName))
            ->addAttribute('value', $this->storeName);
    }
}
