<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Site\Service\RouterRules;

use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\Rules\RulesInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

final class OrderIdSegmentRules implements RulesInterface
{
    public function __construct(private RouterView $router)
    {
    }

    public function preprocess(&$query): void
    {
        // no need
    }

    public function build(&$query, &$segments): void
    {
        if (empty($query['order_id'])) {
            return;
        }

        // Define the view by segments, since the view has already been removed in StandardRules
        //quiz and lpath always have an alias as the last segment
        if (empty($segments)) {
            return;
        }

        $last = end($segments);

        if (!is_string($last)) {
            return;
        }

        $segments[] = (string) (int) $query['order_id'];

        // Important: remove from query, otherwise will remain ?order_id=...
        unset($query['order_id']);
    }

    public function parse(&$segments, &$vars): void
    {
        if (empty($segments)) {
            return;
        }

        // Extract the potential order_id from the end (only if it is a number)
        $last = end($segments);

        if (!\is_string($last) || !ctype_digit($last)) {
            return;
        }

        // At this stage, the view may not yet be defined.
        //Therefore, we'll set it temporarily and "bind" it after StandardRules (see below).
        $vars['order_id'] = (int) array_pop($segments);
    }
}
