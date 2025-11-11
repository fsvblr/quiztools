<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Site\Model;

use Joomla\CMS\Event\Content;
use Joomla\CMS\Event\Model;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper as ArticleRouteHelper;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Qt\Component\Quiztools\Administrator\Helper\QuiztoolsHelper;
use Qt\Component\Quiztools\Site\Helper\RouteHelper as QuizRouteHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * QuizTools Component Lpath Model
 *
 * @since  1.5
 */
class LpathModel extends ItemModel
{
    /**
     * Model context string.
     *
     * @var        string
     */
    protected $_context = 'com_quiztools.lpath';

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @since   1.6
     *
     * @return void
     */
    protected function populateState()
    {
        $app = Factory::getApplication();

        $pk = $app->getInput()->getInt('id');
        $this->setState('lpath.id', $pk);

        $params = $app->getParams();
        $this->setState('params', $params);
    }

    /**
     * Method to get lpath data.
     *
     * @param   integer  $pk  The id of the lpath.
     *
     * @return  object|boolean  Item data object on success, boolean false
     */
    public function getItem($pk = null)
    {
	    $user = $this->getCurrentUser();

        $pk = (int) ($pk ?: $this->getState('lpath.id'));

        if ($this->_item === null) {
            $this->_item = [];
        }

        if (!isset($this->_item[$pk])) {
            try {
                $db    = $this->getDatabase();
                $query = $db->createQuery();

                $query->select(
                    $this->getState(
                        'item.select',
                        [
                            $db->qn('a.id'),
                            $db->qn('a.title'),
                            $db->qn('a.description'),
                            $db->qn('a.show_progressbar', 'showProgressbar'),
                            $db->qn('a.lpath_items'),
                            $db->qn('a.metatitle'),
                            $db->qn('a.metakey'),
                            $db->qn('a.metadesc'),
                            $db->qn('a.params'), // This field is for custom jobs (?)
                        ]
                    )
                )
                    ->from($db->qn('#__quiztools_lpaths', 'a'))
                    ->join(
                        'INNER',
                        $db->qn('#__categories', 'c'),
                        $db->qn('c.id') . ' = ' . $db->qn('a.catid')
                    )
                    ->where(
                        [
                            $db->qn('a.id') . ' = :pk',
	                        $db->qn('a.state') . ' = 1',
                            $db->qn('c.published') . ' = 1',
                        ]
                    )
                    ->bind(':pk', $pk, ParameterType::INTEGER);

	            // Filter by access level.
	            $groups = $user->getAuthorisedViewLevels();
	            $query->whereIn($db->qn('a.access'), $groups)
		            ->whereIn($db->qn('c.access'), $groups);

                $db->setQuery($query);
                $data = $db->loadObject();

                if (empty($data)) {
                    throw new \Exception(Text::_('COM_QUIZTOOLS_LPATH_ERROR_LPATH_NOT_FOUND'), 404);
                }

	            if (isset($data->params)) {
		            $registry = new Registry($data->params);
		            $data->params = $registry->toArray();
	            }

				if (isset($data->lpath_items)) {
					$registry = new Registry($data->lpath_items);
					$data->lpath_items = $registry->toArray();
				}

				// If the Learning Path description contains the "readmore" insert, the first part of the description
	            // will be shown in the category, and the second part in the Learning Path.
	            // Otherwise, the entire description will be shown in the Learning Path.
                if (!empty($data->description)) {
                    $data->description = QuiztoolsHelper::getDescriptionInItem($data->description);
                }

                $stepsData = $this->getSteps($data->id);
                $data->steps       = !empty($stepsData['steps']) ? $stepsData['steps'] : [];
                $data->countStepsTotal  = !empty($stepsData['countStepsTotal']) ? $stepsData['countStepsTotal'] : 0;
                $data->countStepsPassed = !empty($stepsData['countStepsPassed']) ? $stepsData['countStepsPassed'] : 0;

	            $dispatcher = $this->getDispatcher();
	            PluginHelper::importPlugin('content', null, true, $dispatcher);

	            // Processing Learning Path description.
	            // Note: the "text" property is processed by other content plugins.
	            $changed = false;
	            if (!isset($data->text)) {
		            $data->text = $data->description;
		            unset($data->description);
		            $changed = true;
	            }
	            $data->description = $dispatcher->dispatch(
		            'onContentPrepare',
		            new Content\ContentPrepareEvent('onContentPrepare', [
			            'context' => 'com_quiztools.lpath.prepareDescription',
			            'subject' => $data,
			            'params'  => new \stdClass(),
			            'page'    => 0,
		            ])
	            )->getArgument('result', $data)->text;
	            if (isset($data->text) && $changed) {
		            unset($data->text);
	            }

				// This is the change point of the Learning Path object retrieved from the database.
	            $data = $dispatcher->dispatch(
		            'onLpathAfterLoad',
		            new Model\PrepareDataEvent('onLpathAfterLoad', [
			            'context' => 'com_quiztools.lpath.afterLoad',
			            'data'    => $data,
			            'subject' => new \stdClass(),
		            ])
	            )->getArgument('result', $data);

                $this->_item[$pk] = $data;

            } catch (\Exception $e) {
				//$msg = Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage());
	            $msg = 'Error "lpath-01"';
		        Factory::getApplication()->enqueueMessage($msg, 'warning');

                $this->_item[$pk] = false;
            }
        }

        return $this->_item[$pk];
    }

    /**
     * Get steps data of the Learning Path.
     *
     * @param int $id Learning Path's ID
     * @return array
     */
    public function getSteps($id = 0)
    {
        $stepsData = [
            'steps' => [],
            'countStepsTotal' => 0,
            'countStepsPassed' => 0,
        ];

        if (empty($id)) {
            return $stepsData;
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->qn('lpath_items'))
            ->from($db->qn('#__quiztools_lpaths'))
            ->where($db->qn('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query);
        $items = $db->loadResult();

        if (!empty($items)) {
            $items = json_decode($items, true);
        } else {
            return $stepsData;
        }

        $steps = [];
        $quizzes_ids = [];
        $articles_ids = [];

        // A step may be removed from the original set for various reasons (see code below).
        // However, the original step count may be useful to us.
        $countStepsTotal = count($items);
        $countStepsPassed = 0;

        foreach ($items as $item) {
            $step = new \stdClass;
            $step->type = $item['type'];

            if ($item['type'] === 'a') {
                $step->type_id = $item['article_id'];
                $articles_ids[] = (int) $item['article_id'];
                if (isset($item['min_time_article'])) {
                    $step->minTime = $item['min_time_article'];
                }
            } else if ($item['type'] === 'q') {
                $step->type_id = $item['quiz_id'];
                $quizzes_ids[] = (int) $item['quiz_id'];
            }

            $step->uniqueId = $item['unique_id'];
            $step->passed = false;
            $step->title = '';
            $step->desc = '';
            $step->link = '';
            $steps[] = $step;
        }

        $quizzes_ids = array_values(array_unique($quizzes_ids));
        $articles_ids = array_values(array_unique($articles_ids));

        $nowDate = Factory::getDate()->toSql();

        $user = $this->getCurrentUser();
        $groups = $user->getAuthorisedViewLevels();

        $articles = [];
        if (!empty($articles_ids)) {
            $query->clear();
            $query->select($db->qn(['id', 'title', 'catid']))
                ->select($db->qn('introtext', 'desc'))
                ->from($db->qn('#__content'))
                ->where($db->qn('state') . '=' . $db->q(1))
                ->where($db->qn('id') . " IN ('" . implode("','", $articles_ids) . "')")
                ->where('(' . $db->qn('publish_up') . ' IS NULL OR ' . $db->qn('publish_up') . ' <= :publishUp' . ')')
                ->where('(' . $db->qn('publish_down') . ' IS NULL OR ' . $db->qn('publish_down') . ' >= :publishDown' . ')')
                ->bind([':publishUp', ':publishDown'], $nowDate);
            // Filter by access level.
            $query->whereIn($db->qn('access'), $groups);
            $db->setQuery($query);
            $articles = $db->loadObjectList('id');
        }

        $quizzes = [];
        if (!empty($quizzes_ids)) {
            $query->clear();
            $query->select($db->qn(['id', 'title', 'catid']))
                ->select($db->qn('description', 'desc'))
                ->from($db->qn('#__quiztools_quizzes'))
                ->where($db->qn('state') . '=' . $db->q(1))
                ->where($db->qn('id') . " IN ('" . implode("','", $quizzes_ids) . "')");
            // Filter by access level.
            $query->whereIn($db->qn('access'), $groups);
            $db->setQuery($query);
            $quizzes = $db->loadObjectList('id');

            if (!empty($quizzes)) {
                foreach ($quizzes as $quiz) {
                    $quiz->desc = QuiztoolsHelper::getDescriptionInCategory($quiz->desc);
                }
            }
        }

        // If 'lpath_items' contains a step but $articles or $quizzes doesn't, remove that step from $steps.
        // This can happen if the article or quiz's access level or publishing settings
        // were changed after setting up the Learning Path.
        for ($i = 0; $i < count($steps); $i++) {
            if ($steps[$i]->type === 'a') {
                if (isset($articles[$steps[$i]->type_id])) {
                    $steps[$i]->title = $articles[$steps[$i]->type_id]->title;
                    $steps[$i]->catid = $articles[$steps[$i]->type_id]->catid;
                    $steps[$i]->desc = $articles[$steps[$i]->type_id]->desc;
                } else {
                    unset($steps[$i]);
                }
            } else if ($steps[$i]->type === 'q') {
                if (isset($quizzes[$steps[$i]->type_id])) {
                    $steps[$i]->title = $quizzes[$steps[$i]->type_id]->title;
                    $steps[$i]->catid = $quizzes[$steps[$i]->type_id]->catid;
                    $steps[$i]->desc = $quizzes[$steps[$i]->type_id]->desc;
                } else {
                    unset($steps[$i]);
                }
            }
        }
        $steps = array_values($steps);

        $passedSteps = $this->getPassedSteps($id);

        foreach ($steps as $step) {
            foreach ($passedSteps as $passedStep) {
                if ($step->type === $passedStep->type
                    && (int) $step->type_id === (int) $passedStep->type_id
                        && $step->uniqueId === $passedStep->unique_id
                ) {
                    $step->passed = true;
                    $countStepsPassed++;
                    break;
                }
            }
        }

        $first_not_passed = false;
        $steps = array_map(function ($step) use (&$first_not_passed, $id) {
            $deleteId = true;
            $step->canStart = false;
            if ($step->passed || !$first_not_passed) {
                if ($step->type === 'a') {
                    $step->link = Route::_(ArticleRouteHelper::getArticleRoute($step->type_id, $step->catid) . '&tmpl=component', false);
                } else if ($step->type === 'q') {
                    $step->link = Route::_(QuizRouteHelper::getQuizRoute($step->type_id, $step->catid)
                        . '&lp[id]=' . $id .  '&lp[uniqueId]=' . urlencode($step->uniqueId) . '&tmpl=component', false);
                }
                $step->canStart = true;
                $deleteId = false;
            }
            if (!$step->passed) {
                $first_not_passed = true;
            }
            if ($deleteId) {
                unset($step->type_id);
            }
            unset($step->catid);
            return $step;
        }, $steps);

        $stepsData = [
            'steps' => $steps,
            'countStepsTotal' => $countStepsTotal,
            'countStepsPassed' => $countStepsPassed,
        ];

        return $stepsData;
    }

    /**
     * Get the user's completed learning path steps.
     *
     * @param int $id Learning Path's ID
     * @return array
     */
    public function getPassedSteps($id = 0)
    {
        $user = $this->getCurrentUser();

        if (empty($id) || empty($user->id)) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->qn(['type', 'type_id', 'unique_id']))
            ->from($db->qn('#__quiztools_lpaths_users'))
            ->where($db->qn('user_id') . ' = :userId')
            ->where($db->qn('lpath_id') . ' = :lpathId')
            ->where($db->qn('passed') . '=' . $db->q(1))
            ->bind(':userId', $user->id, ParameterType::INTEGER)
            ->bind(':lpathId', $id, ParameterType::INTEGER);
        $db->setQuery($query);
        $passedSteps = $db->loadObjectList();

        return !empty($passedSteps) ? $passedSteps : [];
    }

    /**
     * Get next step of the Learning Path.
     *
     * @param integer $id Learning Path's ID
     * @param  array $currentStep
     * @return null|array
     */
    public function getNextStep($id = 0, $currentStep = [])
    {
        $nextStep = null;

        if (empty($id)) {
            return $nextStep;
        }

        $stepsData = $this->getSteps($id);

        if (!empty($stepsData['steps'])) {
            $steps = $stepsData['steps'];
        } else {
            return $nextStep;
        }

        if (!empty($steps) && is_array($steps)) {
            $isCurrent = false;

            foreach ($steps as $step) {
                if ($isCurrent) {
                    $nextStep = $step;
                    break;
                }
                if ($step->uniqueId === $currentStep['uniqueId']) {
                    $isCurrent = true;
                }
            }
        }

        return $nextStep;
    }
}
