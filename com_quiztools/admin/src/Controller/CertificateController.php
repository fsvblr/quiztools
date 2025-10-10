<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Controller;

use Joomla\CMS\Event\Content;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\Filesystem\File;

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

        $app = Factory::getApplication();
        $input = $app->getInput();
        $id = $input->getInt('id', 0);

        $files = $input->files->get('jform', [], 'ARRAY');
        $file = !empty($files['upload_image']) ? $files['upload_image'] : null;

        if (!$file) {
            $this->setRedirect(Route::_('index.php?option=com_quiztools&view=certificate&layout=edit&id=' . $id, false));
            return false;
        }

        $filename = File::makeSafe($file['name']);

        $allowed_ext = ['png', 'jpeg', 'jpg'];

        if (!in_array(strtolower(File::getExt($filename)), $allowed_ext)) {
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
     *              'date' => '2025-04-30 12:00:00', // MySQL DATETIME format
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
                $this->setRedirect('/');
            }

            return false;
        }

        /** @var \Qt\Component\Quiztools\Administrator\Model\CertificateModel $model */
        $model = $this->factory->createModel('Certificate', 'Administrator', ['ignore_request' => true]);

        $certificate_id = (int) $data['certificate_id'];
        $certificate = $model->getItem($certificate_id);
        $bg = JPATH_SITE . '/images/quiztools/certificates/' . $certificate->file;

        if (!file_exists($bg)) {
            if ($data['client'] == 'administrator') {
                $this->setMessage(Text::_('COM_QUIZTOOLS_CERTIFICATE_WARNING_UPLOAD_IMAGES_ERROR'), 'warning');
                $this->setRedirect($data['urlItem']);
            } else {
                $this->setMessage(Text::_('COM_QUIZTOOLS_CERTIFICATE_GENERATE_WARNING'), 'warning');
                $this->setRedirect('/');
            }

            return false;
        }

        $imageFullSize = getimagesize($bg);

        if ($imageFullSize[2] == 2) {
            $im = imagecreatefromjpeg($bg);
        } elseif ($imageFullSize[2] == 3) {
            $im = imagecreatefrompng($bg);
        } else {
            if ($data['client'] == 'administrator') {
                $this->setMessage(Text::_('COM_QUIZTOOLS_CERTIFICATE_WARNING_UPLOAD_IMAGES_ERROR'), 'warning');
                $this->setRedirect($data['urlItem']);
            } else {
                $this->setMessage(Text::_('COM_QUIZTOOLS_CERTIFICATE_GENERATE_WARNING'), 'warning');
                $this->setRedirect('/');
            }
            return false;
        }

        $dispatcher = $this->getDispatcher();
        PluginHelper::importPlugin('content', null, true, $dispatcher);

        if (!empty($certificate->fields) && is_array($certificate->fields)) {
            foreach ($certificate->fields as $key => $field) {
                if (!empty($data['shortcodes']) && is_array($data['shortcodes'])) {
                    foreach ($data['shortcodes'] as $code => $val) {
                        if ($code === 'date') {
                            $field['text'] = str_replace('#' . $code . '#', date('d.m.Y', strtotime($val)), $field['text']);
                            if (str_contains($field['text'], '#date(')) {
                                $pattern = '/#date\(["\']?(.*?)["\']?\)#/';
                                preg_match($pattern, $field['text'], $matches);
                                $customFormattedDate = date($matches[1], strtotime($val));
                                $field['text'] = preg_replace($pattern, $customFormattedDate, $field['text']);
                            }
                        } else if ($code === 'percent') {
                            $field['text'] = str_replace('#' . $code . '#', $val . '%', $field['text']);
                        } else {
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

                $y = round($field['y'] + ($field['font_size'] * 0.328147));
                $fontFamily = JPATH_SITE . '/media/com_quiztools/fonts/' . $field['font_family'] . '.ttf';
                $rgb = preg_replace('/[^0-9,]/', '', $field['color']);
                $rgb = explode(',', $rgb);
                $color = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
                imagefttext($im, $field['font_size'], 0,  round($field['x']), $y, $color, $fontFamily, $field['text']);
            }
        }

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
        imagepng($im);
        imagedestroy($im);

        exit;
    }
}
