<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Helper;

use Joomla\CMS\Factory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * QuizTools Component Helper.
 *
 * @since  1.5
 */
class QuiztoolsHelper
{
    /**
     * Preparing an object with HTML fields for saving in JSON.
     *
     * @param $object
     * @return object
     */
    public static function sanitizeObjectForJson($object) {
        $sanitized = [];

        foreach ($object as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            } elseif (is_array($value) || is_object($value)) {
                $sanitized[$key] = self::sanitizeObjectForJson((array)$value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return (object) $sanitized;
    }

    /**
     * The method takes a number of seconds and returns a formatted time string 'hh:mm:ss'.
     *
     * @param int $seconds
     * @return string
     */
    public static function secondsToTimeString($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    /**
     * Converting a time string from UTC to the user's time zone.
     *
     * @param string $utcTimeString
     * @return string
     * @throws \DateInvalidTimeZoneException
     * @throws \DateMalformedStringException
     */
    public static function fromUtcToUsersTimeZone($utcTimeString)
    {
        $utcTime = new \DateTime($utcTimeString, new \DateTimeZone('UTC'));

        $user = Factory::getApplication()->getIdentity();
        $userTimezone = $user->getParam('timezone', Factory::getApplication()->getConfig()->get('offset', 'UTC'));

        $userTimezone = new \DateTimeZone($userTimezone);
        $utcTime->setTimezone($userTimezone);

        $localTimeString = $utcTime->format('Y-m-d H:i:s');

        return $localTimeString;
    }

    /**
     * If Item's description contains the "readmore" insert,
     * the first part of the description will be shown in the category.
     * Otherwise, there is no description in the category.
     *
     * @param $description
     * @return mixed|string
     */
    public static function getDescriptionInCategory($description = '')
    {
        $separator = '|||';

        $description = preg_replace('#<hr\s+id=("|\')system-readmore("|\')\s*\/*>#i', $separator, $description);
        $descriptions = explode($separator, $description);

        if (count($descriptions) > 1 && !empty(trim($descriptions[0]))) {
            $description = trim($descriptions[0]);
        } else {
            $description = '';
        }

        return $description;
    }

    /**
     * If Item's description contains the "readmore" insert,
     * the first part of the description will be shown in the category, and the second part in the item.
     * Otherwise, the entire description will be shown in the item.
     *
     * @param $description
     * @return mixed|string
     */
    public static function getDescriptionInItem($description = '')
    {
        $separator = '|||';

        $description = preg_replace('#<hr\s+id=("|\')system-readmore("|\')\s*\/*>#i', $separator, $description);
        $descriptions = explode($separator, $description);

        if (count($descriptions) > 1 && !empty(trim($descriptions[1]))) {
            $description = trim($descriptions[1]);
        }

        return $description;
    }
}
