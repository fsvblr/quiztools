<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 * @license     GNU General Public License version 2 or later
 */

namespace Qt\Component\Quiztools\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Order controller class.
 *
 * @since  1.6
 */
class OrderController extends FormController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  1.6
     */
    protected $text_prefix = 'COM_QUIZTOOLS_ORDER';

    /**
     * Order reactivate
     *
     * @return bool
     * @throws \Exception
     */
    public function reactivate()
    {
        $this->checkToken();

        $app = $this->app;
        $user = $app->getIdentity();

        if (!$user->authorise('core.create', 'com_quiztools')
            && !$user->authorise('core.edit', 'com_quiztools')
        ) {
            throw new \Exception(Text::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
        }

        $now = Factory::getDate()->toSql();

        $id = $this->input->getInt('id', 0);
        $reactivate = $this->input->get('reactivate', [], 'ARRAY');
        $return = Route::_('index.php?option=com_quiztools&view=order&layout=edit&id=' . $id, false);

        $order = new \stdClass;
        $order->id = $id;
        $order->attempts_max = !empty($reactivate['attempts']) ? $reactivate['attempts'] : 0;
        $order->reActivated = $now;
        $order->modified = $now;
        $order->modified_by = $user->id;

        $access_to = !empty($reactivate['access_to']) ? $reactivate['access_to'] : null;

        if (!empty($access_to)) {
            if ($access_to === '0000-00-00 00:00:00') {
                $access_to = '';
            } else {
                if (strtotime($access_to) < strtotime($now)) {
                    $app->enqueueMessage(Text::_('COM_QUIZTOOLS_ORDER_DIALOG_REACTIVATE_ERROR_ACCESS_TO'), 'warning');
                    $this->setRedirect($return);
                    return false;
                }
            }
        }

        $order->end_datetime = $access_to;

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $db->updateObject('#__quiztools_orders', $order, 'id', true);
            $app->enqueueMessage(Text::_('COM_QUIZTOOLS_ORDER_DIALOG_REACTIVATE_SUCCESS'));
        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
            $this->setRedirect($return);
            return false;
        }

        $this->setRedirect($return);
        return true;
    }
}
