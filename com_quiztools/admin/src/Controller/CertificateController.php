<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 * @license     GNU General Public License version 2 or later
 */

namespace Qt\Component\Quiztools\Administrator\Controller;

use Joomla\CMS\Event\Content;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\File;
use Qt\Component\Quiztools\Administrator\Helper\QuiztoolsHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Certificate controller class.
 *
 * @since  1.6
 */
class CertificateController extends FormController
{
    private $allowedImageExtensions = ['png', 'jpeg', 'jpg'];

    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  1.6
     */
    protected $text_prefix = 'COM_QUIZTOOLS_CERTIFICATE';

    /**
     * Method to upload certificate image file.
     *
     * @return bool
     * @throws \Exception
     */
    public function uploadImage()
    {
        $this->checkToken();

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_quiztools')) {
            throw new \Exception(Text::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
        }

        $input = $this->app->getInput();
        $id = $input->getInt('id', 0);

        $files = $input->files->get('jform', [], 'ARRAY');
        $file = !empty($files['upload_image']) ? $files['upload_image'] : null;

        if (!$file) {
            $this->setRedirect(Route::_('index.php?option=com_quiztools&view=certificate&layout=edit&id=' . $id, false));
            return false;
        }

        $filename = File::makeSafe($file['name']);
        $filename = preg_replace('/\s+/', '_', $filename);

        if (!in_array(strtolower(File::getExt($filename)), $this->allowedImageExtensions)) {
            $this->setMessage(Text::_('COM_QUIZTOOLS_CERTIFICATE_WARNING_UPLOAD_IMAGES_FILE_FORMAT'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_quiztools&view=certificate&layout=edit&id=' . $id, false));
            return false;
        }

        $source = $file['tmp_name'];
        $dest = JPATH_SITE . '/images/quiztools/certificates/' . $filename;

        if (File::upload($source, $dest)) {
            $this->setMessage(Text::_('COM_QUIZTOOLS_CERTIFICATE_SUCCESS_UPLOAD_IMAGES'), 'message');
        } else {
            $this->setMessage(Text::_('COM_QUIZTOOLS_CERTIFICATE_WARNING_UPLOAD_IMAGES_ERROR'), 'warning');
        }

        $this->setRedirect(Route::_('index.php?option=com_quiztools&view=certificate&layout=edit&id=' . $id, false));

        return true;
    }

    /**
     * Method to generate a preview of a certificate.
     *
     * @return bool
     */
    public function previewCertificate()
    {
        $this->checkToken('get');

        $input = $this->input;
        $certificate_id = $input->getInt('id', 0);

        $data = [];
        $data['result_id'] = 0;
        $data['certificate_id'] = $certificate_id;
        $data['client'] = 'administrator';
        $data['urlList'] = Route::_('index.php?option=com_quiztools&view=certificates', false);
        $data['urlItem'] = Route::_('index.php?option=com_quiztools&view=certificate&layout=edit&id=' . $certificate_id, false);
        $data['shortcodes'] = [
            'output' => 'inline',
        ];

        $this->generateCertificate($data);

        return true;
    }

    /**
     * Generates a certificate image dynamically based on provided data.
     *
     * @param array $data An associative array containing certificate details and settings.
     *    Example structure:
     *    [
     *        'result_id' => 12345
     *        'user_id' => int User ID
     *        'certificate_id' => int Certificate ID,
     *        'client' => string User client type ('administrator' or other),
     *        'urlList' => string URL to list view,
     *        'urlItem' => string URL to item view,
     *        'shortcodes' => array Key-value pairs for text replacements,
     *          [
     *              'name' => 'John',
     *              'surname' => 'Smith',
     *              'course' => 'My First Quiz',
     *              'points' => '176',
     *              'percent' => '80.24',
     *              'date' => '2025-04-30 12:00:00', // MySQL DATETIME format ($result->start_datetime)
     *              'output' => 'inline / attachment',
     *          ]
     *    ]
     *
     * @return bool Returns false if the certificate generation fails due to missing data
     *              or issues with the certificate file. On success, outputs the certificate image and exits.
     * @throws \Exception
     */
    public function generateCertificate($data)
    {
        if (empty((int) $data['certificate_id'])) {
            if ($data['client'] == 'administrator') {
                $this->setMessage(Text::_('COM_QUIZTOOLS_CERTIFICATE_WARNING_CERTIFICATE_NOT_FOUND'), 'warning');
                $this->setRedirect($data['urlList']);
            } else {
                $this->setMessage(Text::_('COM_QUIZTOOLS_CERTIFICATE_GENERATE_WARNING'), 'warning');
                $this->setRedirect(Uri::root());
            }

            return false;
        }

        /** @var \Qt\Component\Quiztools\Administrator\Model\CertificateModel $model */
        $model = $this->factory->createModel('Certificate', 'Administrator', ['ignore_request' => true]);

        $certificate_id = (int) $data['certificate_id'];
        $certificate = $model->getItem($certificate_id);

        if (empty($certificate) || empty($certificate->file)) {
            return false;
        }

        $safeFileName = InputFilter::getInstance()->clean($certificate->file, 'path');

        if (!in_array(strtolower(File::getExt($safeFileName)), $this->allowedImageExtensions)) {
            return false;
        }

        $bg = JPATH_SITE . '/images/quiztools/certificates/' . $safeFileName;

        if (!file_exists($bg) || !is_file($bg)) {
            if ($data['client'] == 'administrator') {
                $this->setMessage(Text::_('COM_QUIZTOOLS_CERTIFICATE_WARNING_UPLOAD_IMAGES_ERROR'), 'warning');
                $this->setRedirect($data['urlItem']);
            } else if ($data['client'] == 'site') {
                $this->setMessage(Text::_('COM_QUIZTOOLS_CERTIFICATE_GENERATE_WARNING'), 'warning');
                $this->setRedirect(Uri::root());
            }

            return false;
        }

        $imageFullSize = @getimagesize($bg);

        if (!$imageFullSize || !in_array($imageFullSize[2], [2, 3], true)) {
            if ($data['client'] == 'administrator') {
                $this->setMessage(Text::_('COM_QUIZTOOLS_CERTIFICATE_WARNING_UPLOAD_IMAGES_ERROR'), 'warning');
                $this->setRedirect($data['urlItem']);
            } else {
                $this->setMessage(Text::_('COM_QUIZTOOLS_CERTIFICATE_GENERATE_WARNING'), 'warning');
                $this->setRedirect(Uri::root());
            }

            return false;
        }

        $dispatcher = $this->getDispatcher();
        PluginHelper::importPlugin('content', null, true, $dispatcher);

        // Prepare fields once before rendering
        $preparedFields = [];
        $languageTagForCertificate = QuiztoolsHelper::getLanguageTag((int) ($data['user_id'] ?? 0), 'site');

        if (!empty($certificate->fields) && is_array($certificate->fields)) {
            // For RTL: dates and percentage values should not be reversed when rendered.
            // Wrap them in a marker \u{200E} (left-to-right) and restore the context \u{200F} (right-to-left).
            $lrm = "\u{200E}";
            $rlm = "\u{200F}";

            $fontsDir    = JPATH_SITE . '/media/com_quiztools/fonts/';
            $defaultFont = $fontsDir . 'DejaVuSans.ttf';

            foreach ($certificate->fields as $field) {
                if (!empty($data['shortcodes']) && is_array($data['shortcodes'])) {
                    foreach ($data['shortcodes'] as $code => $val) {
                        if ($code === 'date') {
                            $certificateDate = QuiztoolsHelper::fromUtcToUsersTimeZone($val, (int) ($data['user_id'] ?? 0), 'd.m.Y');
                            $certificateDate = $lrm . $certificateDate . $rlm;
                            $field['text'] = str_replace('#' . $code . '#', $certificateDate, $field['text']);

                            // Handling custom date formats
                            $pattern = '/#date\(["\']?(.*?)["\']?\)#/';
                            $field['text'] = preg_replace_callback($pattern, function ($matches) use ($val, $data, $lrm, $rlm) {
                                $format = $matches[1];
                                $certificateDate = QuiztoolsHelper::fromUtcToUsersTimeZone($val, (int) ($data['user_id'] ?? 0), $format);
                                return $lrm . $certificateDate . $rlm;
                            }, $field['text']);
                        }
                        else if ($code === 'percent') {
                            $val = $lrm . $val . '%' . $rlm;
                            $field['text'] = str_replace('#' . $code . '#', $val, $field['text']);
                        }
                        else if ($code === 'points') {
                            $val = $lrm . $val . $rlm;
                            $field['text'] = str_replace('#' . $code . '#', $val, $field['text']);
                        }
                        else {
                            $field['text'] = str_replace('#' . $code . '#', $val, $field['text']);
                        }
                    }
                }

                // Possibility to use custom shortcodes and process them with plugins.
                $textObj = new \stdClass();
                $textObj->text = $field['text'];
                $textObj->data = $data;
                $field['text'] = $dispatcher->dispatch(
                    'onContentPrepare',
                    new Content\ContentPrepareEvent('onContentPrepare', [
                        'context' => 'com_quiztools.admin.certificate.field.text',
                        'subject' => $textObj,
                        'params'  => new \stdClass(),
                        'page'    => 0,
                    ])
                )->getArgument('result', $textObj)->text;
                // end

                // Resolve font file with fallback to NotoSans-Regular.ttf if old font (arial/times) was deleted
                $fontName = basename((string) ($field['font_family'] ?? ''));
                $fontName = preg_replace('/\.ttf$/i', '', $fontName);

                // Mapping of removed proprietary fonts to free DejaVu equivalents
                $legacyFontMap = [
                    'times' => 'DejaVuSerif',
                    'arial' => 'DejaVuSans',
                ];

                $lowerFontName = strtolower($fontName);
                if (isset($legacyFontMap[$lowerFontName])) {
                    $fontName = $legacyFontMap[$lowerFontName];
                }
                // end mapping

                $fontPath = $fontsDir . $fontName . '.ttf';

                if (empty($fontName) || !is_file($fontPath)) {
                    $fontPath = $defaultFont;
                }

                // f the text contains Cyrillic characters but the selected font does not support them (e.g., an Arabic/Hebrew font),
                // switch to DejaVuSans (while preserving the Bold weight, if it was selected).
                if (preg_match('/\p{Cyrillic}/u', (string) $field['text'])
                    && (stripos($fontName, 'Arabic') !== false || stripos($fontName, 'Hebrew') !== false)
                ) {
                    $fallbackName = (stripos($fontName, 'Bold') !== false) ? 'DejaVuSans-Bold.ttf' : 'DejaVuSans.ttf';
                    if (is_file($fontsDir . $fallbackName)) {
                        $fontPath = $fontsDir . $fallbackName;
                    }
                }

                if (!is_file($fontPath)) {
                    return false;
                }

                // Parse RGB color string e.g. "rgb(180, 30, 30)" or hex "#b41e1e"
                $colorInput = trim((string) ($field['color'] ?? ''));

                if (str_starts_with($colorInput, '#')) {
                    $hex = ltrim($colorInput, '#');
                    if (strlen($hex) === 3) {
                        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
                    }
                    $r = hexdec(substr($hex, 0, 2));
                    $g = hexdec(substr($hex, 2, 2));
                    $b = hexdec(substr($hex, 4, 2));
                } else {
                    $rgbStr = preg_replace('/[^0-9,]/', '', $colorInput);
                    $rgb = array_pad(explode(',', $rgbStr), 3, 0);
                    $r = (int) $rgb[0];
                    $g = (int) $rgb[1];
                    $b = (int) $rgb[2];
                }

                $preparedFields[] = [
                    'raw_text'  => str_replace(["\u{200E}", "\u{200F}"], '', (string) $field['text']),
                    'text'      => QuiztoolsHelper::prepareRtlText((string) $field['text'], $languageTagForCertificate),
                    'font_path' => $fontPath,
                    'font_size' => (float) $field['font_size'],
                    'x'         => (float) $field['x'],
                    'y'         => round((float) $field['y'] + ((float) $field['font_size'] * 0.328147)),
                    'rgb'       => [$r, $g, $b],
                ];
            }
        }

        $imageBlob = null;

        // =========================================================================
        // PRIMARY ENGINE: Imagick + ImagickDraw
        // =========================================================================
        if (extension_loaded('imagick') && class_exists('\Imagick')) {
            try {
                $imagick = new \Imagick($bg);
                $imagick->setImageFormat('png');

                foreach ($preparedFields as $field) {
                    if ($field['raw_text'] === '') {
                        continue;
                    }

                    [$r, $g, $b] = $field['rgb'];
                    $draw = new \ImagickDraw();
                    $draw->setFont($field['font_path']);
                    $draw->setFontSize($field['font_size']);
                    $draw->setFillColor(new \ImagickPixel(sprintf('rgb(%d,%d,%d)', $r, $g, $b)));
                    $draw->setTextEncoding('UTF-8');

                    // In the JS-based certificate generator, the font size was being converted from `pt` to `px`,
                    // whereas the PHP `imagefttext()` function expects the size in `pt`.
                    // While `ImagickDraw::setFontSize()` defaults to a resolution of 72 DPI (meaning `1 pt = 1 px`),
                    // the FreeType rasterization process within ImageMagick also uses 72 DPI;
                    // in contrast, GD’s `imagefttext()` defaults to **96 DPI**.
                    // Consequently, without the `96 / 72` multiplier, text rendered via Imagick appears 25% smaller
                    // than it does in GD. Applying the `96 / 72` multiplier in the code ensures that the visual size
                    // of the characters remains consistent—matching one-to-one—between Imagick and GD.

                    // ImagickDraw expects font size in points (1 px = 0.75 pt at 96 DPI),
                    // matching PHP GD's imagefttext() behavior
                    $draw->setFontSize($field['font_size'] * (96 / 72));

                    $imagick->annotateImage($draw, round($field['x']), $field['y'], 0, $field['raw_text']);
                    $draw->clear();
                    $draw->destroy();
                }

                $imageBlob = $imagick->getImagesBlob();
                $imagick->clear();
                $imagick->destroy();  // deprecated

            } catch (\Throwable $e) {
                $imageBlob = null; // Fall back to GD below
            }
        }

        // =========================================================================
        // FALLBACK ENGINE: PHP GD (imagefttext)
        // =========================================================================
        if ($imageBlob === null) {
            $im = ($imageFullSize[2] == 2) ? imagecreatefromjpeg($bg) : imagecreatefrompng($bg);

            if (!$im) {
                return false;
            }

            foreach ($preparedFields as $field) {
                if ($field['text'] === '') {
                    continue;
                }

                [$r, $g, $b] = $field['rgb'];
                $color = imagecolorallocate($im, $r, $g, $b);

                imagefttext(
                    $im,
                    $field['font_size'],
                    0,
                    round($field['x']),
                    $field['y'],
                    $color,
                    $field['font_path'],
                    $field['text']
                );
            }

            ob_start();
            imagepng($im);
            $imageBlob = ob_get_clean();
            imagedestroy($im);
        }

        // =========================================================================
        // OUTPUT IMAGE TO BROWSER
        // =========================================================================
        $fileName = [];
        $fileName[] = Text::_('COM_QUIZTOOLS_CERTIFICATE_GENERATE_FILE_NAME');
        $fileName[] = !empty($data['shortcodes']['course']) ? '_' . $data['shortcodes']['course'] : '';
        $fileName[] = !empty($data['shortcodes']['name']) ? '_' . $data['shortcodes']['name'] : '';
        $fileName[] = !empty($data['shortcodes']['surname']) ? '_' . $data['shortcodes']['surname'] : '';
        $fileName[] = '.png';
        $fileName = implode('', $fileName);

        // Possibility to change the certificate file name via the content plugin.
        $fileNameObj = new \stdClass();
        $fileNameObj->text = $fileName;
        $fileNameObj->data = $data;
        $fileName = $dispatcher->dispatch(
            'onContentPrepare',
            new Content\ContentPrepareEvent('onContentPrepare', [
                'context' => 'com_quiztools.certificate.fileName',
                'subject' => $fileNameObj,
                'params'  => new \stdClass(),
                'page'    => 0,
            ])
        )->getArgument('result', $fileNameObj)->text;
        // end

        $output = !empty($data['shortcodes']['output']) ? $data['shortcodes']['output'] : 'attachment';

        header('Content-Type: image/png');
        header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Content-Disposition: ' . $output . '; filename="' . $fileName . '";');
        header('Cache-Control: max-age=0');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Pragma: no-cache');

        @ob_end_clean();
        echo $imageBlob;

        exit;
    }
}
