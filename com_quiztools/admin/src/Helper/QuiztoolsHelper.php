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
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserFactoryInterface;
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
     * Converting a time string from UTC to the user's time zone, locale and native calendar.
     * Supports user profile language, frontend/backend multilingualism and custom formatting.
     * Automatically handles non-Gregorian calendars (Thai, Persian, etc.) if the language pack is installed.
     *
     * @param string $utcTimeString
     * @param int $user_id Target user ID (e.g. the student)
     * @param string $format Date format (PHP style)
     * @param string $clientContext Context for language lookup ('site' or 'administrator')
     * @param string $explicitLanguage Explicit language tag (e.g. 'fa-IR') to override detection
     * @throws \Exception
     * @since 1.5.1
     * @return string
     */
    public static function fromUtcToUsersTimeZone(
        string $utcTimeString,
        int $user_id = 0,
        string $format = 'Y-m-d H:i:s',
        string $clientContext = 'site',
        string $explicitLanguage = ''
    ): string {
        $app = Factory::getApplication();

        $user = null;
        if ((int) $user_id > 0) {
            $userFactory = Factory::getContainer()->get(UserFactoryInterface::class);
            $user = $userFactory->loadUserById((int) $user_id);
        }

        if ($user && (int) $user->id > 0) {
            $userTimezone = $user->getParam('timezone', $app->getConfig()->get('offset', 'UTC'));
        } else {
            $userTimezone = $app->getConfig()->get('offset', 'UTC');
        }

        // Determining the exact language based on setting priorities
        $languageTag = self::getLanguageTag($user_id, $clientContext, $explicitLanguage);

        // Including the localization file for an alternative calendar.
        // If the target language differs from the language of the current session
        //  (for example, when generating a certificate for a student in Persian while using an English-language administration interface),
        //  PHP will not yet be aware of the calendar class (such as `Fa_IRDate`).
        // We explicitly include the `localise.php` file for the required language.
        if ($languageTag !== $app->getLanguage()->getTag()) {
            $localiseFile = JPATH_SITE . '/language/' . $languageTag . '/localise.php';
            if (!file_exists($localiseFile)) {
                $localiseFile = JPATH_ADMINISTRATOR . '/language/' . $languageTag . '/localise.php';
            }

            if (file_exists($localiseFile)) {
                try {
                    include_once $localiseFile;
                } catch (\Throwable $e) {
                    // The file is corrupted or contains a syntax error — ignoring it; the global fallback will take effect.
                }
            }
        }  // ELSE : the core has already loaded localise.php for the current language

        // Determining the class name for a date
        $dateClassName = '\\Joomla\\CMS\\Date\\Date'; // By default, we use a standard core class.

        try {
            // Constructing the class name
            $cleanTag = str_replace('-', '_', $languageTag);

            // Option 1: Modern standard using namespaces (Joomla 5/6): \Joomla\Language\Fa_IR\Fa_IRDate
            $nsClassName = '\\Joomla\\Language\\' . $cleanTag . '\\' . $cleanTag . 'Date';

            // Option 2: Old standard without namespaces (Joomla 3/4): Fa_IRDate
            $oldClassName = $cleanTag . 'Date';

            if (class_exists($nsClassName)) {
                $dateClassName = $nsClassName;
            } elseif (class_exists($oldClassName)) {
                $dateClassName = $oldClassName;
            }
        } catch (\Throwable $e) {
            // In the event of any unforeseen failures during class lookup, we fall back to the default core class.
            $dateClassName = '\\Joomla\\CMS\\Date\\Date';
        }

        // Creating a date object
        try {
            $date = new $dateClassName($utcTimeString, new \DateTimeZone('UTC'));
        } catch (\Throwable $e) {
            // If the date is empty or invalid, Date won't throw a fatal error but will use the current time instead.
            try {
                $date = new Date('now', new \DateTimeZone('UTC'));
            } catch (\Exception $finalException) {
                return '';
            }
        }

        // Setting the user's time zone and generating the date.
        try {
            $date->setTimezone(new \DateTimeZone($userTimezone));

            // The calendar() method will invoke the overridden logic for an alternative calendar from the localise.php file.
            $localTimeString = $date->calendar($format, false, true);
        } catch (\Throwable $e) {
            try {
                $fallbackDate = new Date($utcTimeString, new \DateTimeZone('UTC'));
                $fallbackDate->setTimezone(new \DateTimeZone($userTimezone));
                $localTimeString = $fallbackDate->format($format);
            } catch (\Throwable $lastError) {
                $localTimeString = date($format);
            }
        }

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

    /**
     * Get language tag
     *
     * @param int $user_id Target user ID (e.g. the student)
     * @param string $clientContext Context for language lookup ('site' or 'administrator')
     * @param string $explicitLanguage Explicit language tag (e.g. 'fa-IR') to override detection
     * @return string
     * @throws \Exception
     * @since 1.5.1
     */
    public static function getLanguageTag(
        int $user_id = 0,
        string $clientContext = 'site',
        string $explicitLanguage = ''): string
    {
        $app = Factory::getApplication();

        $user = null;
        if ((int) $user_id > 0) {
            $userFactory = Factory::getContainer()->get(UserFactoryInterface::class);
            $user = $userFactory->loadUserById((int) $user_id);
        }

        // Determining the exact language based on setting priorities
        $languageTag = '';

        // Priority 1: Explicitly passed language
        if (!empty($explicitLanguage)) {
            $languageTag = $explicitLanguage;
        }

        // Priority 2: Language from the target user profile
        if (empty($languageTag) && $user && (int) $user->id > 0) {
            $langParam = ($clientContext === 'administrator') ? 'admin_language' : 'language';
            $languageTag = $user->getParam($langParam, '');
        }

        // Priority 3: Current active system language, taking the call context into account.
        if (empty($languageTag)) {
            if ($app->isClient($clientContext)) {
                // If we execute the code in the same place where we request the context (e.g., frontend for frontend)
                $languageTag = $app->getLanguage()->getTag();
            } else {
                // If we are in the admin area but need the frontend (or vice versa), we use the default language for that context.
                try {
                    $langParams  = ComponentHelper::getParams('com_languages');
                    $languageTag = $langParams->get($clientContext, '');
                } catch (\Exception $e) {
                    $languageTag = '';
                }
            }
        }

        // Ultimate fallback for unforeseen situations
        if (empty($languageTag)) {
            $languageTag = 'en-GB';
        }

        return $languageTag;
    }

    /**
     * Checks whether the provided language tag is an RTL  language, with a fallback based on the ISO code.
     *
     * @param string $langTag Language tag (e.g. 'fa-IR', 'en-GB')
     * @since 1.5.1
     * @return bool
     */
    public static function isLanguageRtl(string $langTag): bool
    {
        $installedLanguages = LanguageHelper::getLanguages('lang_code');

        if (isset($installedLanguages[$langTag])) {
            return (bool) $installedLanguages[$langTag]->rtl;
        }

        // Fallback: verify the language code itself against international standards for RTL locales.
        $rtlLanguages = ['ar', 'fa', 'he', 'ur', 'ps', 'syr', 'dv', 'yi'];
        $baseLang = strtolower(explode('-', str_replace('_', '-', $langTag))[0]);
        return in_array($baseLang, $rtlLanguages);
    }

    /**
     * Checks whether the provided text contains an RTL language.
     *
     * @param string $text Text
     * @since 1.5.1
     * @return bool
     */
    public static function isTextRtl(string $text): bool
    {
        return preg_match('/[\x{0590}-\x{05FF}\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $text);
    }

    /**
     * Prepares text for correct rendering in GD (fixes RTL)
     *
     * @param string $text    Source text
     * @param string $langTag The language tag for which the text is being processed (for example, when a certificate is being generated)
     * @since 1.5.1
     * @return string
     */
    public static function prepareRtlText(string $text, string $langTag): string
    {
        $isRtl = self::isLanguageRtl($langTag);

        // If the language is defined as LTR, but the text itself (for example, a user's name) contains RTL characters, we still enable processing.
        if (!$isRtl) {
            $isRtl = self::isTextRtl($text);
        }

        if (!$isRtl) {
            return str_replace(["\u{200E}", "\u{200F}"], '', $text);
        }

        if (extension_loaded('intl')) {
            $baseLang = strtolower(explode('-', str_replace('_', '-', $langTag))[0]);

            // If a language uses the Arabic script (Persian, Arabic, Urdu, etc.)
            if (in_array($baseLang, ['ar', 'fa', 'ur', 'ps', 'syr'])) {
                $transliterator = \Transliterator::create('Any-Name; Arabic-Visual; Any-Name');
            } else {
                // For other RTL languages (e.g., Hebrew 'he' or Yiddish 'yi'), where only the reversal of lines (Bidi)
                // is required, we use the standard ICU rule for logical-to-visual mapping.
                $transliterator = \Transliterator::create('Bidi-LogicalVisual');
            }

            if ($transliterator) {
                // ICU correctly handles the markers \u200E} and \u200F}, preventing dates from being reversed.
                $visualText = $transliterator->transliterate($text);
                if ($visualText !== false) {
                    return $visualText;
                }
            }
        }

        if (extension_loaded('mbstring')) {
            // We use the correct PCRE syntax for UTF-8 control characters (\x{200E} and \x{200F}).
            $parts = preg_split('/(\x{200E}.*?\x{200F})/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

            // If the regular expression returns an error, perform a safe rollback.
            if (!is_array($parts)) {
                $cleanText = str_replace(["\u{200E}", "\u{200F}"], '', $text);
                return self::mbStrRev($cleanText);
            }

            foreach ($parts as $key => &$part) {
                // Checking for the presence of an LRM marker at the beginning of the block.
                if (mb_strpos($part, "\u{200E}") === 0) {
                    // This is a protected block. We are removing the markers, but we are NOT reversing the date itself.
                    $part = str_replace(["\u{200E}", "\u{200F}"], '', $part);
                } else {
                    // This is a standard RTL text. We reverse it character by character.
                    $part = self::mbStrRev($part);
                }
            }

            // Reassemble the string by reversing the order of the blocks.
            return implode('', array_reverse($parts));
        }

        $cleanText = str_replace(["\u{200E}", "\u{200F}"], '', $text);
        return strrev($cleanText);
    }

    /**
     * Safe reversal of a UTF-8 string.
     *
     * @param string $str
     * @since 1.5.1
     * @return string
     */
    private static function mbStrRev(string $str): string
    {
        preg_match_all('/./us', $str, $ar);
        return isset($ar[0]) ? implode('', array_reverse($ar[0])) : $str;
    }
}
