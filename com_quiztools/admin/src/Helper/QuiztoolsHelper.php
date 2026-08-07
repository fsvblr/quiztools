<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 * @license     GNU General Public License version 2 or later
 */

namespace Qt\Component\Quiztools\Administrator\Helper;

use enshrined\svgSanitize\Sanitizer;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Exception\FilesystemException;
use Joomla\String\StringHelper;

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
     * The method takes a number of seconds and returns a formatted time string 'hh:mm:ss'.
     *
     * @param int $seconds
     * @return string
     */
    public static function secondsToTimeString($seconds)
    {
        $seconds = (int) $seconds;
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

    /**
     * Recursively delete a directory.
     *
     * @param string $dir
     * @return bool
     */
    public static function deleteDirRecursive(string $dir)
    {
        if (!is_dir($dir)) {
            return false;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                self::deleteDirRecursive($path);
            } else {
                try {
                    unlink($path);
                } catch (FilesystemException $e) {
                    echo Text::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $item) . '<br>';
                }
            }
        }

        return rmdir($dir);
    }

    /**
     * HTML Cleanup.
     *
     * @param string $html
     * @return  string
     *
     * @since   1.4.1
     */
    public static function cleanHtml($html): string
    {
        $params = ComponentHelper::getParams('com_quiztools');

        $blockedTags = $params->get('html_blocked_tags');
        $blockedAttributes = $params->get('html_blocked_attributes');

        if (empty($blockedTags) || empty($blockedAttributes)) {
            $xmlPath = JPATH_ADMINISTRATOR . '/components/com_quiztools/config.xml';

            if (file_exists($xmlPath)) {
                if ($xml = simplexml_load_file($xmlPath)) {
                    if (empty($blockedTags)) {
                        $blockedTagsFields = $xml->xpath('//field[@name="html_blocked_tags"]');
                        if (!empty($blockedTagsFields) && isset($blockedTagsFields[0]['default'])) {
                            $blockedTags = (string) $blockedTagsFields[0]['default'];
                        }
                    }

                    if (empty($blockedAttributes)) {
                        $blockedAttributesFields = $xml->xpath('//field[@name="html_blocked_attributes"]');
                        if (!empty($blockedAttributesFields) && isset($blockedAttributesFields[0]['default'])) {
                            $blockedAttributes = (string) $blockedAttributesFields[0]['default'];
                        }
                    }
                }
            }
        }

        if (!empty($blockedTags)) {
            $blockedTags = explode(',', $blockedTags);
            $blockedTags = array_map(function ($value) {
                return StringHelper::trim($value);
            }, $blockedTags);
            $blockedTags = array_filter($blockedTags);
        } else {
            $blockedTags = [];
        }

        if (!empty($blockedAttributes)) {
            $blockedAttributes = explode(',', $blockedAttributes);
            $blockedAttributes = array_map(function ($value) {
                return StringHelper::trim($value);
            }, $blockedAttributes);
            $blockedAttributes = array_filter($blockedAttributes);
        } else {
            $blockedAttributes = [];
        }

        $filter = new InputFilter(
            $blockedTags,
            $blockedAttributes,
            InputFilter::ONLY_BLOCK_DEFINED_TAGS,
            InputFilter::ONLY_BLOCK_DEFINED_ATTRIBUTES,
            1 // Enable auto-XSS cleaning (removes hidden "javascript:" in links)
        );

        $html = $filter->clean($html);

        return $html;
    }

    /**
     * Preparing a data for saving in JSON.
     *
     * @param mixed $data
     * @return array
     *
     * @since 1.4.1
     */
    public static function sanitizeDataForJson($data) {
        if (empty($data) || !is_array($data)) {
            return [];
        }

        $sanitized = [];
        $filter = InputFilter::getInstance();

        foreach ($data as $key => $value) {
            $key = preg_replace('/[^a-zA-Z0-9_\-]/', '', $key);
            if (empty($key)) {
                continue;
            }

            if (is_string($value)) {
                $sanitized[$key] = $filter->clean($value, 'STRING');
            } elseif (is_array($value) || is_object($value)) {
                $sanitized[$key] = self::sanitizeDataForJson($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Safely retrieve the contents of an image file by its path.
     *
     * @param string $imagePath
     * @return string
     *
     * @since 1.4.1
     */
    public static function getCleanImageData($imagePath)
    {
        if (!file_exists($imagePath)) {
            return '';
        }

        $imageInfo = @getimagesize($imagePath);

        if ($imageInfo === false && mime_content_type($imagePath) === 'image/svg+xml') {
            // getimagesize returns false for some SVGs without dimensions; double-check with MIME
            $mimeType = 'image/svg+xml';
            //$width = 0;
            //$height = 0;
        } else {
            //$width = $imageInfo[0] ?? 0;
            //$height = $imageInfo[1] ?? 0;
            $mimeType = image_type_to_mime_type($imageInfo[2] ?? 0);
        }

        $cleanData = '';

        if ($mimeType === 'image/svg+xml') {
            // SVG SECURITY: Cutting Out <script>, XSS Injections, and XML Bombs
            $svgContent = file_get_contents($imagePath);
            $sanitizer = new Sanitizer();
            $sanitizer->minify(true); // Optional: Compresses SVG code
            $cleanData = $sanitizer->sanitize($svgContent);

        } elseif (in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
            // RASTER SAFETY: Removing PHP code from EXIF metadata by rebuilding via GD
            ob_start();
            switch ($mimeType) {
                case 'image/jpeg':
                    $im = @imagecreatefromjpeg($imagePath);
                    if($im) {
                        // The third parameter (100) sets the maximum quality
                        imagejpeg($im, NULL, 100);
                    }
                    break;
                case 'image/png':
                    $im = @imagecreatefrompng($imagePath);
                    if ($im) {
                        // Turn off the blending mode and maintain full transparency
                        imagealphablending($im, false);
                        imagesavealpha($im, true);
                        // The third parameter (0) disables PNG compression, preserving the original pixel sharpness.
                        imagepng($im, NULL, 0);
                    }
                    break;
                case 'image/webp':
                    $im = @imagecreatefromwebp($imagePath);
                    if($im) {
                        imagewebp($im, NULL, 100);
                    }
                    break;
            }

            if (isset($im) && $im !== false) {
                $cleanData = ob_get_clean();
                imagedestroy($im);
            } else {
                ob_end_clean();
                return ''; // The file is corrupted or fake (script with .jpg extension)
            }
        } else {
            // Unknown or dangerous file type (.php, .exe, .html disguised as an image)
            return '';
        }

        return $cleanData;
    }

    /**
     * Check that this is a real user with a valid session (can be "guest").
     *
     * @return bool
     * @throws \Exception
     * @since 1.4.1
     */
    public static function validateRealSession(): bool
    {
        $app = Factory::getApplication();
        $session = $app->getSession();
        $input = $app->getInput();

        if (!$session->checkToken()) {
            return false;
        }

        // Checking whether the browser has passed a cookie with the current session ID
        $sessionName     = $session->getName();
        $cookieSessionId = $input->cookie->get($sessionName, '', 'string');

        if (empty($cookieSessionId)) {
            // The client has cookies disabled or this is a direct request from a bot without cookies.
            return false;
        }

        // Check that the session exists and was not "just" created as part of this AJAX request
        if ($session->isNew()) {
            return false;
        }

        return true;
    }
}
