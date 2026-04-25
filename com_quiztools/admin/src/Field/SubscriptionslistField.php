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
use Qt\Component\Quiztools\Administrator\Model\SubscriptionsModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class SubscriptionslistField extends ListField
{
	protected $type = 'Subscriptionslist';

    protected function getOptions()
    {
        $app = Factory::getApplication();

        /** @var SubscriptionsModel $model_subscriptions */
        $model_subscriptions = $app->bootComponent('com_quiztools')->getMVCFactory()
            ->createModel('Subscriptions', 'Administrator', ['ignore_request' => true]);

        $model_subscriptions->setState('filter.state', 1);

        if (isset($this->element['paymentMethod'])) {
            $model_subscriptions->setState('filter.payment_method', $this->element['paymentMethod']);
        }

        $subscriptions = $model_subscriptions->getSubscriptionsList();

        return $subscriptions;
    }

	protected function getInput()
	{
        $options = $this->getOptions();
        $options = array_merge(parent::getOptions(), $options);

        $idtag = $this->id ?: false;

        $class = $this->element['class'] ?: null;
        $attribs = $class ? 'class="'. $class .'"' : null;

		return HTMLHelper::_('select.genericlist',  $options,  $this->name, $attribs, 'value', 'text', $this->value, $idtag);
	}
}
