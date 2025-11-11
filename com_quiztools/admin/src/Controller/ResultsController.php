<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Qt\Component\Quiztools\Administrator\Helper\QuiztoolsHelper;
use Qt\Component\Quiztools\Administrator\Model\ResultsModel;

require(__DIR__ . '/../../vendor/autoload.php');
use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Results list controller class.
 *
 * @since  1.6
 */
class ResultsController extends AdminController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  1.6
     */
    protected $text_prefix = 'COM_QUIZTOOLS_RESULTS';

    /**
     * Method to get a model object, loading it if required.
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel  The model.
     *
     * @since   1.6
     */
    public function getModel($name = 'Result', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Export data to excel (.xlsx).
     *
     * @return void
     * @throws \Exception
     */
    public function exportExcel()
    {
        $this->checkToken();

        $items = $this->prepareExport();

        if (empty($items)) {
            $this->app->enqueueMessage(Text::_('COM_QUIZTOOLS_RESULTS_ERROR_EXCEL_NO_DATA_FOUND'), 'warning');

            $this->setRedirect(
                Route::_(
                    'index.php?option=' . $this->option . '&view=' . $this->view_list
                    . $this->getRedirectToListAppend(),
                    false
                )
            );

            return;
        }

        $helper = new Sample();
        if ($helper->isCli()) {
            throw new \Exception('This task should only be run from a Web Browser.');
        }

        $spreadsheet = new Spreadsheet();

        $columns_names = [];
        $columns_names[] = Text::_('COM_QUIZTOOLS_RESULTS_EXCEL_HEADER_DATETIME');
        $columns_names[] = Text::_('COM_QUIZTOOLS_RESULTS_EXCEL_HEADER_USER');
        $columns_names[] = Text::_('COM_QUIZTOOLS_RESULTS_EXCEL_HEADER_QUIZ');
        $columns_names[] = Text::_('COM_QUIZTOOLS_RESULTS_EXCEL_HEADER_TOTAL_SCORE');
        $columns_names[] = Text::_('COM_QUIZTOOLS_RESULTS_EXCEL_HEADER_PASSING_SCORE');
        $columns_names[] = Text::_('COM_QUIZTOOLS_RESULTS_EXCEL_HEADER_USER_SCORE');
        $columns_names[] = Text::_('COM_QUIZTOOLS_RESULTS_EXCEL_HEADER_PASSED');
        $columns_names[] = Text::_('COM_QUIZTOOLS_RESULTS_EXCEL_HEADER_FINISHED');
        $columns_names[] = Text::_('COM_QUIZTOOLS_RESULTS_EXCEL_HEADER_SPENT_TIME');
        $columns_names[] = Text::_('COM_QUIZTOOLS_RESULTS_EXCEL_HEADER_RESULT_ID');

        $col = 'A';
        foreach($columns_names as $name) {
            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue($col.'1', $name);
            $col++;
        }

        $i = 2;
        foreach($items as $item) {
            $rowData = [];
            $rowData[] = !empty($item->start_datetime_for_display) ? $item->start_datetime_for_display : '';
            $rowData[] = !empty($item->user_name) ? htmlspecialchars($item->user_name, ENT_QUOTES, 'UTF-8') : '';
            $rowData[] = !empty($item->title) ? htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8') : '';
            $rowData[] = !empty($item->total_score) ? number_format($item->total_score, 2, '.', '') : '';

            $passingScore = (float) $item->total_score * ((float) $item->passing_score / 100 );
            $passingScore = round($passingScore, 2);

            $rowData[] = number_format($passingScore, 2, '.', '') . ' (' . $item->passing_score . '%)';
            $rowData[] = !empty($item->sum_points_received) ? number_format($item->sum_points_received, 2, '.', '') : '';
            $rowData[] = (int) $item->passed ? Text::_('JYES') : Text::_('JNO');
            $rowData[] = (int) $item->finished ? Text::_('JYES') : Text::_('JNO');
            $rowData[] = !empty($item->sum_time_spent) ? QuiztoolsHelper::secondsToTimeString($item->sum_time_spent) : '';
            $rowData[] = $item->id;

            $col = 'A';
            foreach($rowData as $data) {
                $spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue($col.$i, $data);
                $col++;
            }

            $i++;
        }

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="results.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');

        exit;
    }

    /**
     * Get data for export (in different formats).
     *
     * @return array|mixed
     * @throws \Exception
     */
    private function prepareExport()
    {
        $cid = (array) $this->input->post->get('cid', [], 'int');
        // Remove zero values resulting from input filter
        $cid = array_filter($cid);

        /** @var ResultsModel $model */
        $model = $this->factory->createModel('Results', 'Administrator', ['ignore_request' => true]);

        // Forces the populateState() method to be called (and filling the '__state_set' property).
        // If it is called later, it will override the model's State.
        $model->getState();

        if (!empty($cid)) {
            // Export only selected items
            $model->setState('filter.selectedItems', $cid);
            $model->setState('filter.task', 'exportExcel');
        } else {
            // Export all items based on selected filters
            // Receive & set filters
            if ($filters = $this->input->get('filter', [], 'array')) {
                foreach ($filters as $name => $value) {
                    if (!\in_array($name, $model->filterForbiddenList)) {
                        $model->setState('filter.' . $name, $value);
                    }
                }
            }

            $order_col = $this->input->get('filter_order', '');
            if (empty($order_col) || !\in_array($order_col, $model->filter_fields)) {
                $order_col = 'a.start_datetime';
            }
            $model->setState('list.ordering', $order_col);

            $list_order = $this->input->get('filter_order_Dir', '');
            if (empty($list_order) || !\in_array(strtoupper($list_order), ['ASC', 'DESC'])) {
                $list_order = 'DESC';
            }
            $model->setState('list.direction', $list_order);
        }

        $model->setState('list.start', 0);
        $model->setState('list.limit', 0);

        $items = $model->getItems();

        return !empty($items) ? $items : [];
    }
}
