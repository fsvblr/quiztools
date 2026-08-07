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
use Joomla\CMS\Event\Table\AfterStoreEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\TableInterface;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\ParameterType;
use Qt\Component\Quiztools\Administrator\Table\QuestionTable;
use Qt\Plugin\Quiztools\Hotspotsmultiple\Table\QuestionHotspotsmultipleOptionsTable;
use Qt\Plugin\Quiztools\Hotspotsmultiple\Table\QuestionHotspotsmultipleTable;

/**
 * Saving question options in the admin panel.
 *
 * @since   1.0.0
 */
trait QuestionOptionsSave
{
	/**
	 * Saving question options in the admin panel.
	 *
	 * @param   AfterStoreEvent  $event
	 * @return bool
     * @since   1.0.0
	 */
    public function QuestionOptionsSave($event): bool
    {
	    if (!($this->getApplication() instanceof CMSApplication)) {
		    return false;
	    }

	    if (!$this->getApplication()->isClient('administrator')) {
		    return false;
	    }

	    // Extract arguments
	    /** @var TableInterface $table */
	    $table  = $event['subject'];
	    $result = $event['result'];

	    if (!$result || !is_object($table)) {
		    return false;
	    }

	    if ($table instanceof QuestionTable) {
		    $typeAlias = $table->getTypeAlias();

		    if ($typeAlias != 'com_quiztools.question') {
			    return false;
		    }

		    $question_id = $table->getId();

			// Check that the question being saved is of the current plugin type.
		    if ($table->type != $this->name) {
			    return false;
		    }
	    } else {
		    return true;
	    }

        if (empty($question_id)) {
            return false;
        }

		$app = $this->getApplication();
		$input = $app->getInput();
        $filter = InputFilter::getInstance();
		$formData = $input->get('jform', [], 'ARRAY');

	    $db = $this->getDatabase();
	    $query = $db->createQuery()
		    ->select($db->qn('id'))
		    ->from($db->qn('#__quiztools_questions_' . $this->name))
		    ->where($db->qn('question_id') . ' = :questionId')
		    ->bind(':questionId', $question_id, ParameterType::INTEGER);
	    try {
		    $question_type_id = $db->setQuery($query)->loadResult();
	    } catch (ExecutionFailureException $e) {
		    return false;
	    }

	    $questionTable = new QuestionHotspotsmultipleTable(
			Factory::getContainer()->get('DatabaseDriver'),
			$this->getDispatcher()
	    );

        $typeFields = [];
	    $typeFields['id'] = !empty($question_type_id) ? (int) $question_type_id : 0;
		$typeFields['question_id'] = (int) $question_id;
        $typeFields['check_order'] = !empty($formData['check_order']) ? (int) $formData['check_order'] : 0;

        $typeFields['image'] = !empty($formData['image']) ? $formData['image'] : '';
        $typeFields['image'] = explode('#', $typeFields['image'])[0];
        $typeFields['image'] = $filter->clean($typeFields['image'], 'path');

        if (!$questionTable->save($typeFields)) {
			return false;
		}

		$query->clear();
		$query->delete($db->qn('#__quiztools_questions_' . $this->name . '_options'))
			->where($db->qn('question_id') . ' = :questionId')
			->bind(':questionId', $question_id, ParameterType::INTEGER);
	    $db->setQuery($query);
	    try {
		    $db->execute();
	    } catch (\RuntimeException $e) {
		    $app->enqueueMessage($e->getMessage(), 'error');
	    }

        $questionOptions = !empty($formData['hotspots']) ? $formData['hotspots'] : [];

		if (empty($questionOptions)) {
			return false;
		}

	    $questionOptionsTable = new QuestionHotspotsmultipleOptionsTable(
			Factory::getContainer()->get('DatabaseDriver'),
			$this->getDispatcher()
	    );

		$i = 0;
		foreach ($questionOptions as $questionOption) {
            if (empty($questionOption['coordinates'])) {
                $this->getApplication()->enqueueMessage(Text::_('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_WARNING_PROVIDE_COORDINATES'), 'warning');
                continue;
            }

            // Filtering polygon coordinates: start
            $polygon = json_decode($questionOption['coordinates'], true);
            $cleanPolygon = [];

            if (is_array($polygon)) {
                foreach ($polygon as $point) {
                    if (is_array($point) && count($point) >= 2) {
                        $x = (float) $point[0];
                        $y = (float) $point[1];
                        $cleanPolygon[] = [$x, $y];
                    }
                }
            }

            if (empty($cleanPolygon)) {
                $cleanPolygon = [[0.0, 0.0]];
            }

            $questionOption['coordinates']  = $cleanPolygon;
            // Filtering polygon coordinates: end

            if (!$this->polygonIsSimple($questionOption['coordinates'])) {
                $this->getApplication()->enqueueMessage(Text::_('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_WARNING_POLYGON_NOT_SIMPLE'), 'warning');
                continue;
            }
            $questionOption['coordinates'] = json_encode($questionOption['coordinates']);

			$questionOption['id'] = 0;
			$questionOption['question_id'] = (int) $question_id;
			$questionOption['ordering'] = $i;

			$questionOptionsTable->save($questionOption);
			$i++;
		}

	    return true;
    }

    /**
     * Checks a polygon for self-intersection.
     *
     * @param $polygon
     * @return bool
     * @since   1.4.0
     */
    public function polygonIsSimple($polygon)
    {
        if (empty($polygon) || !is_array($polygon)) {
            return false;
        }

        // Clear the duplicate of the first point at the end, if there is one.
        if (count($polygon) > 1 && end($polygon) === reset($polygon)) {
            array_pop($polygon);
        }

        $count = count($polygon);
        // A polygon must have at least 3 vertices.
        if ($count < 3) {
            return false;
        }

        // Create an array of segments (sides) of a polygon
        $lines = [];
        for ($i = 0; $i < $count; $i++) {
            $p1 = $polygon[$i];
            $p2 = $polygon[($i + 1) % $count]; // Connect the last with the first
            $lines[] = ['p1' => $p1, 'p2' => $p2, 'index' => $i];
        }

        $lineCount = count($lines);

        // Compare each segment with each other in pairs.
        for ($i = 0; $i < $lineCount; $i++) {
            for ($j = $i + 1; $j < $lineCount; $j++) {
                // Check whether the segments are adjacent
                $isAdjacent = ($i === $j) ||
                    ($i === 0 && $j === $lineCount - 1) ||
                    (abs($i - $j) === 1);

                if ($this->polygonLineSegmentsIntersect($lines[$i]['p1'], $lines[$i]['p2'], $lines[$j]['p1'], $lines[$j]['p2'])) {
                    // If the segments are adjacent, they intersect at a common vertex.
                    // But they must not intersect elsewhere.
                    if (!$isAdjacent) {
                        return false; // Illegal line crossing found.
                    }
                }
            }
        }

        return true;
    }

    /**
     * Algorithm for checking the intersection of two segments
     * (using the oblique product method)
     *
     * @param $p1
     * @param $p2
     * @param $p3
     * @param $p4
     * @return bool
     * @since   1.4.0
     */
    public function polygonLineSegmentsIntersect($p1, $p2, $p3, $p4)
    {
        $ccw1 = $this->polygonCcw($p1, $p3, $p4);
        $ccw2 = $this->polygonCcw($p2, $p3, $p4);
        $ccw3 = $this->polygonCcw($p1, $p2, $p3);
        $ccw4 = $this->polygonCcw($p1, $p2, $p4);

        return (($ccw1 > 0 && $ccw2 < 0) || ($ccw1 < 0 && $ccw2 > 0)) &&
            (($ccw3 > 0 && $ccw4 < 0) || ($ccw3 < 0 && $ccw4 > 0));
    }

    /**
     * Direction of bypass (Counter-ClockWise test).
     *
     * @param $a
     * @param $b
     * @param $c
     * @return float|int
     * @since   1.4.0
     */
    public function polygonCcw($a, $b, $c)
    {
        // (Bx - Ax) * (Cy - Ay) - (By - Ay) * (Cx - Ax)
        // $a[0] -> X, $a[1] -> Y
        return ($b[0] - $a[0]) * ($c[1] - $a[1]) - ($b[1] - $a[1]) * ($c[0] - $a[0]);
    }
}
