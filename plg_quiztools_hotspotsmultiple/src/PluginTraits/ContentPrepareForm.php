<?php

/**
 * @package     QuizTools.Plugin
 * @subpackage  QuizTools.hotspotsmultiple
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 * @license     GNU General Public License version 2 or later
 */

namespace Qt\Plugin\Quiztools\Hotspotsmultiple\PluginTraits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Event\Model;

/**
 * Add additional fields to the supported forms
 *
 * @since   1.4.0
 */
trait ContentPrepareForm
{
    /**
     * Add additional fields to the supported forms
     *
     * @param   Model\PrepareFormEvent $event  The event instance.
     *
     * @return  void
     *
     * @since   1.4.0
     */
    public function ContentPrepareForm(Model\PrepareFormEvent $event)
    {
        $supportedContext = [
            'question_hotspotsmultiple',
        ];

        $form = $event->getForm();
        $data = $event->getData();
        $name = $form->getName();

        if (!$this->getApplication()->isClient('administrator')
            || !\in_array($name, $supportedContext)
        ) {
            return;
        }

        if (\is_array($data)) {
            $data = (object) $data;
        }

        if ($name === 'question_hotspotsmultiple' && !empty((int) $data->id)) {
            $form->setFieldAttribute('image', 'directory', 'quiztools/questions/' . (int) $data->id . '/');
        }
    }
}
