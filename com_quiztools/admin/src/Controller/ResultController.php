<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Controller;

use Joomla\CMS\Event\Content;
use Joomla\CMS\Event\Model;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Qt\Component\Quiztools\Administrator\Model\ResultModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Component Controller
 *
 * @since  1.5
 */
class ResultController extends BaseController
{
    /**
     * The default view.
     *
     * @var    string
     * @since  1.6
     */
    protected $default_view = 'result';

    /**
     * Method to cancel a result display.
     *
     * @since   1.6
     */
    public function cancelQuiz()
    {
        $this->checkToken();
        $this->setRedirect(Route::_('index.php?option=com_quiztools&view=results', false));
        return true;
    }

    /**
     * Method to cancel a result display.
     *
     * @since   1.6
     */
    public function cancelQuestion()
    {
        $this->checkToken();
        $id = $this->input->getInt('id', 0);
        $this->setRedirect(Route::_('index.php?option=com_quiztools&view=result&layout=default&id=' . $id, false));
        return true;
    }

    /**
     * Generating a certificate with the user's result.
     *
     * @param int|null $result_id
     * @return true
     * @throws \Exception
     */
    public function getCertificate($result_id = null)
    {
        $this->checkToken('get');

        $input = $this->input;

        if (empty($result_id)) {
            $result_id = $input->getInt('id', 0);
        }

        /** @var ResultModel $model */
        $model = $this->factory->createModel('Result', 'Administrator', ['ignore_request' => true]);

        $result = $model->getItem($result_id);

        $userNameArr = explode(' ', $result->user_name, 2);
        $userName = !empty($userNameArr[0]) ? $userNameArr[0] : '';
        $userSurname = !empty($userNameArr[1]) ? $userNameArr[1] : '';
        if ((float) $result->total_score > 0) {
            $userPercent = number_format(((float) $result->sum_points_received / (float) $result->total_score) * 100, 2, '.', '');
        } else {
            $userPercent = 0;
        }

        $data = [];
        $data['result_id'] = $result_id;
        $data['certificate_id'] = (int) $result->certificate_id;
        $data['client'] = 'administrator';
        $data['urlList'] = Route::_('index.php?option=com_quiztools&view=results', false);
        $data['urlItem'] = Route::_('index.php?option=com_quiztools&view=results', false);
        $data['shortcodes'] = [
            'name' => $userName,
            'surname' => $userSurname,
            'course' => htmlspecialchars($result->quiz_title, ENT_QUOTES, 'UTF-8'),
            'points' => $result->sum_points_received,
            'percent' => $userPercent,
            'date' => $result->start_datetime_for_display,
            'output' => 'attachment',
        ];

        $controllerCertificate = $this->factory->createController('Certificate', 'Administrator', [], $this->app, $this->input);
        $controllerCertificate->generateCertificate($data);

        return true;
    }

    /**
     * Get a PDF-file with the result.
     *
     * @param int|null $result_id
     * @return void
     * @throws \Exception
     */
    public function getPdf($result_id = null)
    {
        $this->checkToken('get');

        $input = $this->input;

        if (empty($result_id)) {
            $result_id = $input->getInt('id', 0);
        }

        $pdfData = $this->generationPdf($result_id);

        if (!empty($pdfData) && \is_array($pdfData)) {
            $pdfFileName = $pdfData['pdfFileName'];
            $pdf = $pdfData['pdf']->Output('', 'S');
            @ob_end_clean();
            header("Content-type: application/pdf");
            header("Content-Length: ".strlen(ltrim($pdf)));
            header("Content-Disposition: attachment; filename=$pdfFileName");
            echo $pdf;
            jexit();
        } else {
            $referer = $_SERVER['HTTP_REFERER'] ?: '/';
            $this->setRedirect($referer);
        }
    }

    /**
     * Generate a PDF-file with the result.
     *
     * @param int $result_id
     * @return array|null
     * @throws \Exception
     */
    public function generationPdf($result_id = null)
    {
        $input = $this->input;

        if (!$result_id) {
            $result_id = $input->getInt('id', 0);
        }

        if (!$result_id) {
            return null;
        }

        $dispatcher = $this->getDispatcher();

        // The ability to override this method to generate custom PDF reports:
        PluginHelper::importPlugin('system', null, true, $dispatcher);
        $methodOverride = new \stdClass();
        $methodOverride->resultQuizId = $result_id;
        $methodOverride->isOverride = false;
        $methodOverride = $dispatcher->dispatch(
            'onCustomResultPdfReport',
            new Model\PrepareDataEvent('onCustomResultPdfReport', [
                'context' => 'com_quiztools.custom.result.pdf.report',
                'data'    => $methodOverride,
                'subject' => new \stdClass(),
            ])
        )->getArgument('result', $methodOverride);
        if ($methodOverride->isOverride) {
            return null;
        }
        //end override

        PluginHelper::importPlugin('content', null, true, $dispatcher);
        PluginHelper::importPlugin('quiztools', null, true, $dispatcher);

        /** @var ResultModel $model */
        $model = $this->factory->createModel('Result', 'Administrator', ['ignore_request' => true]);

        // Forces the populateState() method to be called (and filling the '__state_set' property).
        // If it is called later, it will override the model's State.
        $model->getState();

        $model->setState('result.id', $result_id);
        $model->setState('result.layout', 'pdf');

        $result = $model->getItem();

        if ($result->feedback_question_pdf) {
            $quizDataForFeedback = new \stdClass();
            $quizDataForFeedback->id = $result->quiz_id;
            $quizDataForFeedback->feedback_msg_right = $result->feedback_msg_right;
            $quizDataForFeedback->feedback_msg_wrong = $result->feedback_msg_wrong;
        }

        require(__DIR__ . '/../../vendor/autoload.php');
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator(Text::_('COM_QUIZTOOLS_RESULT_PDF_DOCUMENT_CREATOR'));
        $pdf->SetAuthor(Text::_('COM_QUIZTOOLS_RESULT_PDF_DOCUMENT_AUTHOR'));
        $pdf->SetSubject(Text::_('COM_QUIZTOOLS_RESULT_PDF_DOCUMENT_SUBJECT'));
        $pdf->SetTitle(Text::_('COM_QUIZTOOLS_RESULT_PDF_DOCUMENT_TITLE'));
        $pdf->SetKeywords(Text::_('COM_QUIZTOOLS_RESULT_PDF_DOCUMENT_KEYWORDS'));

        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT, 10, PDF_MARGIN_RIGHT);
        $pdf->setPrintHeader(false);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $lang = $this->app->getLanguage()->getTag();
        $altLang = [
            'ar-AA', //Arabic (Unitag)
            'ar-SA', //Arabic (Saudi Arabia)
            'he-IL', //Hebrew (Israel)
            'ja-JP', //Japanese (Japan)
            'zh-CN', //Chinese (China)
            'zh-HK', //Chinese (Hong Kong)
            'zh-TW'  //Chinese (Taiwan)
        ];
        //if(\in_array($lang, $altLang)){
        //    $pdf->SetFont('javiergb');  // ToDo ?
        //} else {
            $pdf->SetFont('helvetica', '', 12);
        //}

        // If a different font is needed, take it in
        // https://github.com/tecnickcom/TCPDF/tree/main/fonts
        // and put it in
        // /administrator/components/com_quiztools/vendor/tecnickcom/tcpdf/fonts/ .

        $pdf->AddPage();

        $html = '';
        $html .= '<table style="width: 50%;">';
        $html .= '<tr>';
        $html .= '<td><b>' . Text::_('COM_QUIZTOOLS_RESULT_PDF_QUIZ_TITLE') . ': </b></td>';
        $html .= '<td>' . ($result->quiz_title ?: '-') . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td><b>' . Text::_('COM_QUIZTOOLS_RESULT_PDF_USER_NAME') . ': </b></td>';
        $html .= '<td>' . ($result->user_name ?: '-') . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td><b>' . Text::_('COM_QUIZTOOLS_RESULT_PDF_USER_EMAIL') . ': </b></td>';
        $html .= '<td>' . ($result->user_email ?: '-') . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td><b>' . Text::_('COM_QUIZTOOLS_RESULT_PDF_DATETIME') . ': </b></td>';
        $html .= '<td>' . ($result->start_datetime_for_display ?: '-') . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td><b>' . Text::_('COM_QUIZTOOLS_RESULT_PDF_TIME_SPENT') . ': </b></td>';
        $html .= '<td>' . ($result->sum_time_spent ?: '-') . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td><b>' . Text::_('COM_QUIZTOOLS_RESULT_PDF_TOTAL_SCORE') . ': </b></td>';
        $html .= '<td>' . (!empty($result->total_score) ? number_format((float) $result->total_score, 2, '.', '') : '-') . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $passingScore = round((float) $result->total_score * ((float) $result->passing_score / 100 ), 2);
        $passingScore = number_format($passingScore, 2, '.', '') . ' (' . $result->passing_score . '%)';
        $html .= '<td><b>' . Text::_('COM_QUIZTOOLS_RESULT_PDF_PASSING_SCORE') . ': </b></td>';
        $html .= '<td>' . $passingScore . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $userPercent = 0;
        if (!empty($result->total_score)) {
            $userPercent = round(((float) $result->sum_points_received / (float) $result->total_score) * 100, 2);
        }
        $txt = !empty($result->sum_points_received)
            ? number_format((float) $result->sum_points_received, 2, '.', '') . ' (' . $userPercent . '%)'
            : '-';
        $html .= '<td><b>' . Text::_('COM_QUIZTOOLS_RESULT_PDF_USER_SCORE') . ': </b></td>';
        $html .= '<td>' . $txt . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td><b>' . Text::_('COM_QUIZTOOLS_RESULT_PDF_RESULT') . ': </b></td>';
        $html .= '<td>' . ($result->passed ? Text::_('COM_QUIZTOOLS_RESULT_PDF_RESULT_PASSED') : Text::_('COM_QUIZTOOLS_RESULT_PDF_RESULT_FAILED')) . '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        if ($result->results_by_categories) {
            $html .= '<table>';
            $html .= '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';
            $html .= '<tr><td><b>' . Text::_('COM_QUIZTOOLS_RESULT_PDF_BY_CATEGORIES_TITLE') . ': </b></td><td>&nbsp;</td></tr>';
            if (!empty($result->byCategories))  {
                foreach ($result->byCategories as $categoryTitle => $category) {
                    $html .= '<tr>';
                    $html .= '<td><b>' . $categoryTitle . ': </b></td>';
                    $html .= '<td>' . $category['userScore'] .
                        ' ' . Text::_('COM_QUIZTOOLS_RESULT_PDF_BY_CATEGORIES_OUT_OF') .
                        ' ' . $category['totalScore'] .
                        ' (' . $category['userPercent'] . '%)</td>';
                    $html .= '</tr>';
                }
            }
            $html .= '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';
            $html .= '</table>';
        }

        if (!empty($result->results_questions)) {
            $i = 1;
            foreach ($result->results_questions as $question) {
                // Call 'onQuestionOptionsGetPdfData' BEFORE output the question text.
                // So that the plugin can change the question text.
                if (!empty($question->results)) {
                    $question->withFeedback = (bool) $result->feedback_question_pdf;
                    if ($question->withFeedback) {
                        $question->quizDataForFeedback = $quizDataForFeedback;
                    }

                    // Get question's options HTML for PDF:
                    $question = $dispatcher->dispatch(
                        'onQuestionOptionsGetPdfData',
                        new Model\PrepareDataEvent('onQuestionOptionsGetPdfData', [
                            'context' => 'com_quiztools.question.options.pdfData',
                            'data' => $question,
                            'subject' => new \stdClass(),
                        ])
                    )->getArgument('result', $question);
                }

                $html .= '<p><b>' . $i . '. [' . $question->points_received . '/' . $question->total_points . '] ' .
                    Text::_('PLG_QUIZTOOLS_QUESTION_TYPE_' . strtoupper($question->type) . '_NAME') . '</b></p>';
                $html .= '<div>' . $question->text . '</div>';

                if (!empty($question->results)) {
                    if (!empty($question->pdfOptions)) {
                        $html .= $question->pdfOptions;
                    }

                    if (!empty($question->pdfResume)) {
                        $html .= $question->pdfResume;
                    }

                    if (!empty($question->pdfFeedback)) {
                        $html .= $question->pdfFeedback;
                    }
                }

                $i++;
            }
        }

        $pdf->writeHTML($html);

        $pdf->lastPage();

        $pdfFileName = $result->quiz_title . '_' . $result->user_name . '_' . $result->start_datetime_for_display . '.pdf';

        // Possibility to change the PDF-file name via the content plugin.
        // Note: the "text" property is processed by other content plugins.
        $changed = false;
        if (!isset($result->text)) {
            $result->text = $pdfFileName;
            $changed = true;
        }
        $pdfFileName = $dispatcher->dispatch(
            'onContentPrepare',
            new Content\ContentPrepareEvent('onContentPrepare', [
                'context' => 'com_quiztools.pdf.fileName',
                'subject' => $result,
                'params'  => new \stdClass(),
                'page'    => 0,
            ])
        )->getArgument('result', $result)->text;
        if (isset($result->text) && $changed) {
            unset($result->text);
        }
        // end

        return ['pdf' => $pdf, 'pdfFileName' => $pdfFileName];
    }
}
