<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Controller;

use Joomla\CMS\MVC\Controller\FormController;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Subscription controller class.
 *
 * @since  1.6
 */
class SubscriptionController extends FormController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  1.6
     */
    protected $text_prefix = 'COM_QUIZTOOLS_SUBSCRIPTION';

    /**
     * Method to save a record.
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
     *
     * @return  boolean  True if successful, false otherwise.
     *
     * @since   1.6
     */
    public function save($key = null, $urlVar = null)
    {
        $this->checkToken();

        $data = $this->input->post->get('jform', [], 'array');

        // Adding the 'product_id' field BEFORE the parent controller where the data validation is performed.
        $data['product_id'] = 0;
        if (isset($data['select_product_id']) && \is_array($data['select_product_id']) && isset($data['payment_method'])) {
            $data['product_id'] = isset($data['select_product_id'][$data['payment_method']]) ? (int) $data['select_product_id'][$data['payment_method']] : 0;
        }

        $this->input->post->set('jform', $data);

        return parent::save($key, $urlVar);
    }
}
