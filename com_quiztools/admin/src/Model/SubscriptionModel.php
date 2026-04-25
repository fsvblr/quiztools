<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Model;

use Joomla\CMS\Event\Model;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Subscription model.
 *
 * @since  1.6
 */
class SubscriptionModel extends AdminModel
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
    public $typeAlias = 'com_quiztools.subscription';

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
            $item->select_product_id = [
                'manual' => 0,
            ];

            // Getting data from payment plugins:
            $dispatcher = $this->getDispatcher();
            PluginHelper::importPlugin('quiztoolspayment', null, true, $dispatcher);
            $item = $dispatcher->dispatch(
                'onAdminSubscriptionGetData',
                new Model\PrepareDataEvent('onAdminSubscriptionGetData', [
                    'context' => 'com_quiztools.admin.subscription.data',
                    'data'    => $item,
                    'subject' => new \stdClass(),
                ])
            )->getArgument('result', $item);
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
        $form = $this->loadForm('com_quiztools.subscription', 'subscription', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to allow derived classes to preprocess the form.
     *
     * @param   Form    $form   A Form object.
     * @param   mixed   $data   The data expected for the form.
     * @param   string  $group  The name of the plugin group to import (defaults to "content").
     *
     * @return  void
     *
     * @see     FormField
     * @since   4.0.0
     * @throws  \Exception if there is an error in the form event.
     */
    protected function preprocessForm(Form $form, $data, $group = 'quiztoolspayment')
    {
        if ($this instanceof DispatcherAwareInterface) {
            $dispatcher = $this->getDispatcher();
        } else {
            $dispatcher = Factory::getContainer()->get(DispatcherInterface::class);
        }

        // Import the appropriate plugin group.
        PluginHelper::importPlugin($group, null, true, $dispatcher);

        // Trigger the form preparation event.
        $dispatcher->dispatch(
            'onContentPrepareForm',
            new Model\PrepareFormEvent('onContentPrepareForm', ['subject' => $form, 'data' => $data])
        );
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
        $data = Factory::getApplication()->getUserState('com_quiztools.edit.subscription.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

	    // Plugin's folder 'quiztoolspayment': event 'onContentPrepareData'
        $this->preprocessData('com_quiztools.subscription', $data, 'quiztoolspayment');

        return $data;
    }
}
