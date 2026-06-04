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

use Joomla\CMS\Application\CMSApplication;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;

/**
 * Get the results of the answer to the question.
 *
 * @since   1.0.0
 */
trait QuestionOptionsGetResults
{
	/**
	 * Get the results of the answer to the question.
	 *
	 * @param   Event  $event
	 * @return bool
     * @since   1.0.0
	 */
    public function QuestionOptionsGetResults($event): bool
    {
	    if (!($this->getApplication() instanceof CMSApplication)) {
		    return false;
	    }

        if (!$this->getApplication()->isClient('administrator')
            && !$this->getApplication()->isClient('site')
        ) {
            return false;
        }

	    /**
	     * @var   string|null        $context  The context for the data
	     * @var   array|object|null  $data     An object or array containing the data for the form.
	     */
	    [$context, $data] = array_values($event->getArguments());

	    if (!\in_array($context, ['com_quiztools.question.results'])) {
		    return false;
	    }

	    if (\is_array($data)) {
		    $data = (object) $data;
	    }

	    // Check that the question is of the current plugin type.
	    if ($data->type != $this->name) {
		    return false;
	    }

	    if (empty($data->id)) {
		    return false;
	    }

        $db = $this->getDatabase();
        $query = $db->createQuery();

        $query->select($db->qn(['check_order', 'image']))
            ->from($db->qn('#__quiztools_questions_' . $this->name))
            ->where($db->qn('question_id') . ' = :questionId')
            ->bind(':questionId', $data->question_id, ParameterType::INTEGER);

        try {
            $questionData = $db->setQuery($query)->loadObject();
        } catch (ExecutionFailureException $e) {
            return false;
        }

        $query->clear();
        $query->select('*')
            ->from($db->qn('#__quiztools_questions_' . $this->name . '_options', 'qo'))
            ->where($db->qn('qo.question_id') . ' = :questionId')
            ->bind(':questionId', $data->question_id, ParameterType::INTEGER)
            ->order($db->qn('qo.ordering') . ' ASC');

        try {
            $options = $db->setQuery($query)->loadObjectList();
        } catch (ExecutionFailureException $e) {
            return false;
        }

        $query->clear();
        $query->select($db->qn('answer_coordinates'))
            ->from($db->qn('#__quiztools_results_questions_' . $this->name, 'ro'))
            ->where($db->qn('ro.results_question_id') . ' = :resultsQuestionId')
            ->bind(':resultsQuestionId', $data->id, ParameterType::INTEGER)
            ->order($db->qn('ro.answer_ordering') . ' ASC');

        try {
            $answers = $db->setQuery($query)->loadColumn();
        } catch (ExecutionFailureException $e) {
            return false;
        }

        if (!empty($answers)) {
            $answers = array_map('json_decode', $answers);
        }

        $answersResults = $this->hotspotsCheckAnswers($options, $answers, $questionData->check_order);
        $resultImage = $this->hotspotsGeneratingResultImage($questionData->image, $options, $answers, $questionData->check_order);

        $originalImage = JPATH_SITE . '/' . $questionData->image;
        list($originalImageWidth, $originalImageHeight) = getimagesize($originalImage);
        $questionData->imageSize = ['width' => $originalImageWidth, 'height' => $originalImageHeight];
        $questionData->imagePath = $originalImage;

        $data->results = [
            'questionData' => $questionData,
            'options' => $options,
            'answers' => $answers,
            'answersResults' => $answersResults,
            'resultImage' => $resultImage,
        ];

	    $event->setArgument('result', $data);

	    return true;
    }

    /**
     * Checking answers for correctness and scoring.
     *
     * @param array $options
     * @param array $answers
     * @param bool $check_order
     * @return array
     * @since   1.4.0
     */
    public function hotspotsCheckAnswers($options, $answers, $check_order = false)
    {
        $result = [
            'is_correct' => 0,
            'pointsTotal' => 0,
            'pointsReceived' => 0,
            'byHotspots' => [],
        ];

        if (empty($options)) {
            return $result;
        }

        $polygons = [];
        foreach ($options as $option) {
            if (!empty($option->coordinates)) {
                $polygons[] = json_decode($option->coordinates, false);
            }
        }

        if (count($polygons) != count($answers)) {
            return $result;
        }

        $countHotspotsCorrect = 0;

        if ($check_order) {
            for ($i=0; $i<count($polygons); $i++) {
                $result['pointsTotal'] = (float) $result['pointsTotal'] + (float) $options[$i]->points;
                $result['byHotspots'][$i] = false;
                if ($this->isPointInPolygon($answers[$i], $polygons[$i])) {
                    $result['pointsReceived'] = (float) $result['pointsReceived'] + (float) $options[$i]->points;
                    $result['byHotspots'][$i] = true;
                    $countHotspotsCorrect++;
                }
            }
        } else {
            $tempAnswers = $answers;
            $countAnswers = count($tempAnswers);
            for ($i=0; $i<count($polygons); $i++) {
                $result['pointsTotal'] = (float) $result['pointsTotal'] + (float) $options[$i]->points;
                $result['byHotspots'][$i] = false;
                for ($j=0; $j<$countAnswers; $j++) {
                    if (isset($tempAnswers[$j])) {
                        if ($this->isPointInPolygon($tempAnswers[$j], $polygons[$i])) {
                            $result['pointsReceived'] = (float) $result['pointsReceived'] + (float) $options[$i]->points;
                            $result['byHotspots'][$i] = true;
                            $countHotspotsCorrect++;
                            unset($tempAnswers[$j]);
                            break;
                        }
                    }
                }
            }
        }

        if ($countHotspotsCorrect === count($polygons)) {
            $result['is_correct'] = 1;
        }

        return $result;
    }

    /**
     * Check: is the point inside the polygon?
     *
     * Example:
     * $point = [50.0, 50.0]
     * $polygon = [[18.15625,29.62109375],[79.65625,22.87109375],[86.40625,78.87109375],[30.90625,85.12109375],[8.65625,67.37109375],[18.15625,29.62109375]]
     *
     * @param array $point
     * @param array $polygon
     * @return bool
     * @since   1.4.0
     */
    public function isPointInPolygon($point, $polygon) {
        $x = $point[0];
        $y = $point[1];

        // If the last point duplicates the first, delete it to ensure a correct cycle
        if (end($polygon) === reset($polygon)) {
            array_pop($polygon);
        }

        $numPoints = count($polygon);
        $inside = false;

        // The loop connects the current point (i) to the next (j)
        // When i = 0, j will be equal to the last index ($numPoints - 1)
        for ($i = 0, $j = $numPoints - 1; $i < $numPoints; $j = $i++) {
            $xi = $polygon[$i][0];
            $yi = $polygon[$i][1];
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];

            // Ray tracing formula (Ray-Casting)
            $intersect = (($yi > $y) != ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Generating an SVG image with the results of the user's answer.
     *
     * @param string $image
     * @param array $options
     * @param array $answers
     * @param bool $check_order
     * @return string
     * @since   1.4.0
     */
    public function hotspotsGeneratingResultImage($image, $options, $answers, $check_order = false)
    {
        if (empty($image) || empty($options) || empty($answers)) {
            return '';
        }

        $task = $this->getApplication()->getInput()->get('task', '');
        $isPdf = in_array($task, ['result.getPdf', 'getPdf']);

        $image = JPATH_SITE . '/' . $image;

        $polygons = [];
        foreach ($options as $option) {
            if (!empty($option->coordinates)) {
                $polygons[] = [
                    'color' => $option->color,
                    'points' =>json_decode($option->coordinates, false),
                ];
            }
        }

        if (count($polygons) != count($answers)) {
            return '';
        }

        list($width, $height, $type) = getimagesize($image);
        $imageData = base64_encode(file_get_contents($image));
        $mimeType = image_type_to_mime_type($type);
        $base64Image = "data:{$mimeType};base64,{$imageData}";

        // Coefficients for converting percentages to actual viewBox pixels
        $scaleX = $width / 100;
        $scaleY = $height / 100;

        // We calculate the scale compensation coefficient.
        // We take a width of 500px as a standard (at which the markers r=12 and font=12 look perfect).
        // If the image is 4000px, $compensationScale will be equal to 8.
        $compensationScale = $width / 500;

        // We multiply the marker's base pixel dimensions by the compensation factor.
        // On a 4000px image, the marker will appear physically huge within the viewBox,
        // but when TCPDF compresses the overall image to fit the PDF page, the markers will become their normal size.
        $markerRadius = 12 * $compensationScale;
        $markerStroke = 2 * $compensationScale;
        $markerFontSize = 12 * $compensationScale;
        $markerDy = 4 * $compensationScale; // Offset for perfect vertical alignment in PDF
        $markerDx = $isPdf ? (4 * $compensationScale) : 0;

        // Scaling lines and polygon fonts so that they don't appear microscopic in larger images.
        $polyStrokeWidth = 3 * $compensationScale;
        $polyFontSize = 16 * $compensationScale;
        $polyTextStroke = 2 * $compensationScale;

        $svg = '';
        $svg .= '<svg viewBox="0 0 ' . $width . ' ' . $height . '" xmlns="http://w3.org">
            <image href="' . $base64Image . '" width="' . $width . '" height="' . $height . '" x="0" y="0" />';

        foreach ($polygons as $i => $poly) {
            // Convert the percentage of each point to viewBox pixels
            $svgPoints = array_map(function($pt) use ($scaleX, $scaleY) {
                $pxX = $pt[0] * $scaleX;
                $pxY = $pt[1] * $scaleY;
                return "{$pxX},{$pxY}";
            }, $poly['points']);

            $pointsString = implode(' ', $svgPoints);
            $polyColor = !empty($poly['color']) ? $poly['color'] : '#ff0000';

            $svg .= '<g class="polygon-group">
                    <polygon points="' . $pointsString . '" fill="' . $polyColor . '" fill-opacity="0.25" stroke="' . $polyColor . '" stroke-width="' . $polyStrokeWidth . '" />';

            // Polygon numbering (if necessary)
            if ($check_order && !empty($poly['points'])) {
                // The first vertex of the polygon in pixels
                $firstX = $poly['points'][0][0] * $scaleX;
                $firstY = $poly['points'][0][1] * $scaleY;

                // Slight offset for text (adapts to image size)
                $offset = min($width, $height) * 0.02;
                $textX = $firstX + $offset;
                if ($textX > $width) { $textX = $firstX - $offset; }
                $textY = $firstY + $offset;
                if ($textY > $height) { $textY = $firstY - $offset; }

                // Adaptive text parameters
                $svg .= '<text x="' . $textX . '" y="' . $textY . '" dy="' . ($polyFontSize * 0.3) . '" font-family="Arial, sans-serif" font-size="' . $polyFontSize . '" 
                            font-weight="bold" fill="' . $polyColor . '" paint-order="stroke" stroke="#fff" stroke-width="' . $polyTextStroke . '" 
                            text-anchor="middle">' . ($i + 1) . '</text>';
            }
            $svg .= '</g>';
        }

        // Drawing numeric markers
        foreach ($answers as $i => $marker) {
            // Converting marker percentages to viewBox pixels
            $markerX = $marker[0] * $scaleX;
            $markerY = $marker[1] * $scaleY;
            $number = $i + 1;

            // Marker code with dynamic compensation variables.
            // By multiplying by $compensationScale, the physical size of the circle on the PDF sheet will always be static.
            $svg .= '<g class="marker" style="cursor: pointer;">
                    <circle cx="' . $markerX . '" cy="' . $markerY . '" r="' . $markerRadius . '" fill="#ff0000" stroke="#fff" stroke-width="' . $markerStroke . '" />
                    <text x="' . $markerX . '" y="' . $markerY . '" font-family="Arial, sans-serif" font-size="' . $markerFontSize . '" 
                        font-weight="bold" fill="#fff" text-anchor="middle" 
                        dx="-' . $markerDx .'" 
                        dy="' . $markerDy . '">
                        ' . $number . ' 
                    </text>
                </g>';
        }

        $svg .= '</svg>';

        return $svg;
    }
}
