<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * QuizTools Component AjaxLpath Model
 *
 * @since  1.5
 */
class AjaxLpathModel extends BaseDatabaseModel
{
    /**
     * Get steps of the Learning Path.
     *
     * @return array
     * @throws \Exception
     */
    public function lpathSteps()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();

        $data = $input->get('lpath', [], 'ARRAY');
        $lpath_id = (int) $data['id'] ?: 0;

        if (empty($lpath_id)) {
            throw new \Exception(Text::_('COM_QUIZTOOLS_LPATH_ERROR_LPATH_NOT_FOUND'));
        }

        $model_lpath = $this->getLpathModel();
        $steps = $model_lpath->getSteps($lpath_id);

        return ['steps' => $steps];
    }

    /**
     * Marking an article as started/completed.
     *
     * @return array
     * @throws \Exception
     */
    public function lpathMarkArticle()
    {
        $return = ['markedType' => 'a', 'nextStep' => null];

        $user = $this->getCurrentUser();

        $app = Factory::getApplication();
        $input = $app->getInput();

        $data = $input->get('lpath', [], 'ARRAY');
        $lpath_id = (int) $data['id'] ?: 0;

        $stepStage = !empty($data['stepStage']) ? (string) $data['stepStage'] : null;
        $return['markedArticlePrevStage'] = $stepStage;

        $step = !empty($data['step']) ? json_decode($data['step'], true) : [];
        $order_id = !empty($data['orderId']) ? (int) $data['orderId'] : 0;

        if (empty($user->id)
            || empty($lpath_id)
                || empty($stepStage)
                    || (empty($step['type']) || $step['type'] !== 'a')
                        || (empty($step['type_id']))
                            || (empty($step['uniqueId']))
        ) {
            throw new \Exception(Text::_('COM_QUIZTOOLS_LPATH_ERROR_LPATH_ARTICLE_NOT_FOUND'));
        }

        $db = $this->getDatabase();
        $query = $db->createQuery();

        $query->select($db->qn(['id', 'passed']))
            ->from($db->qn('#__quiztools_lpaths_users'))
            ->where($db->qn('user_id') . ' = :userId')
            ->where($db->qn('lpath_id') . ' = :lpathId')
            ->where($db->qn('type') . '=' . $db->q('a'))
            ->where($db->qn('type_id') . ' = :typeId')
            ->where($db->qn('order_id') . ' = :orderId')
            ->where($db->qn('unique_id') . ' = :uniqueId')
            ->bind(':userId', $user->id, ParameterType::INTEGER)
            ->bind(':lpathId', $lpath_id, ParameterType::INTEGER)
            ->bind(':typeId', $step['type_id'], ParameterType::INTEGER)
            ->bind(':orderId', $order_id, ParameterType::INTEGER)
            ->bind(':uniqueId', $step['uniqueId'], ParameterType::STRING)
        ;
        $db->setQuery($query);

        try {
            $stage = $db->loadObject();
        } catch (\Exception $e) {
            throw new \Exception(Text::_('COM_QUIZTOOLS_LPATH_ERROR_LPATH_STAGE_NOT_FOUND'));
        }

        $model_lpath = $this->getLpathModel();

        if (!empty($stage->id)) {
            // If a step has already been completed, don't change it to "started."
            // Otherwise, if the step isn't completed, the entire chain of completed steps will break.
            // This means it will be impossible to selectively repeat steps.
            if ((int) $stage->passed === 1) {
                try {
                    $return['nextStep'] = $model_lpath->getNextStep($lpath_id, $step);
                } catch (\Exception $e) {}

                return $return;
            }
            // If a step has already been started and not completed,
            // and now we are starting it again, do not change anything.
            else if ((int) $stage->passed === 0) {
                if ($stepStage === 'start') {
                    return $return;
                }
            }
        }

        $userStage = new \stdClass();
        $userStage->id = !empty($stage->id) ? (int) $stage->id : '';
        $userStage->user_id = (int) $user->id;
        $userStage->lpath_id = (int) $lpath_id;
        $userStage->type = 'a';
        $userStage->type_id = (int) $step['type_id'];
        $userStage->result_quiz_id = 0;
        $userStage->order_id = (int) $order_id;
        $userStage->unique_id = (string) $step['uniqueId'];
        $userStage->passed = $stepStage === 'finish' ? 1 : 0;
        try {
            if(!empty($stage->id)) {
                $db->updateObject('#__quiztools_lpaths_users', $userStage, 'id');
            } else {
                $db->insertObject('#__quiztools_lpaths_users', $userStage);
            }
        } catch (\Exception $e) {
            throw new \Exception(Text::_('COM_QUIZTOOLS_LPATH_ERROR_LPATH_STAGE_NOT_SAVE'));
        }

        try {
            $return['nextStep'] = $model_lpath->getNextStep($lpath_id, $step);
        } catch (\Exception $e) {}

        return $return;
    }

    /**
     * Marking a quiz as started/completed.
     *
     * @param string $stepStage
     * @return array
     * @throws \Exception
     */
    public function lpathMarkQuiz($stepStage = 'start')
    {
        $return = ['markedType' => 'q', 'nextStep' => null];

        $user = $this->getCurrentUser();

        $app = Factory::getApplication();
        $input = $app->getInput();

        $data = $input->get('quiz', [], 'ARRAY');
        $quiz_id = (int) $data['id'] ?: 0;
        $lp = !empty($data['lp']) ? json_decode($data['lp'], true) : [];
        $order_id = !empty($data['orderId']) ? (int) $data['orderId'] : 0;
        $result_quiz_id = (int) $data['resultQuizId'] ?: 0;

        // This quiz is NOT in the Learning Path
        if (!empty($quiz_id) && empty($lp['id'])) {
            return null;
        }

        if (empty($user->id) || empty($quiz_id) || empty($lp['id']) || empty($lp['uniqueId'])) {
            throw new \Exception(Text::_('COM_QUIZTOOLS_LPATH_ERROR_LPATH_QUIZ_NOT_FOUND'));
        }

        $db = $this->getDatabase();
        $query = $db->createQuery();

        $query->select($db->qn(['id', 'passed']))
            ->from($db->qn('#__quiztools_lpaths_users'))
            ->where($db->qn('user_id') . ' = :userId')
            ->where($db->qn('lpath_id') . ' = :lpathId')
            ->where($db->qn('type') . '=' . $db->q('q'))
            ->where($db->qn('type_id') . ' = :typeId')
            ->where($db->qn('order_id') . ' = :orderId')
            ->where($db->qn('unique_id') . ' = :uniqueId')
            ->bind(':userId', $user->id, ParameterType::INTEGER)
            ->bind(':lpathId', $lp['id'], ParameterType::INTEGER)
            ->bind(':typeId', $quiz_id, ParameterType::INTEGER)
            ->bind(':orderId', $order_id, ParameterType::INTEGER)
            ->bind(':uniqueId', $lp['uniqueId'], ParameterType::STRING)
        ;
        $db->setQuery($query);

        try {
            $stage = $db->loadObject();
        } catch (\Exception $e) {
            throw new \Exception(Text::_('COM_QUIZTOOLS_LPATH_ERROR_LPATH_STAGE_NOT_FOUND'));
        }

        $model_lpath = $this->getLpathModel(true);

        if (!empty($stage->id)) {
            // If a step has already been completed, don't change it to "started."
            // Otherwise, if the step isn't completed, the entire chain of completed steps will break.
            // This means it will be impossible to selectively repeat steps.
            if ((int) $stage->passed === 1) {
                try {
                    $return['nextStep'] = $model_lpath->getNextStep((int) $lp['id'], $lp);
                } catch (\Exception $e) {}

                return $return;
            }
            // If a step has already been started and not completed,
            // and now we are starting it again, do not change anything.
            else if ((int) $stage->passed === 0) {
                if ($stepStage === 'start') {
                    return $return;
                }
            }
        }

        $userStage = new \stdClass();
        $userStage->id = !empty($stage->id) ? (int) $stage->id : '';
        $userStage->user_id = (int) $user->id;
        $userStage->lpath_id = (int) $lp['id'];
        $userStage->type = 'q';
        $userStage->type_id = (int) $quiz_id;
        $userStage->result_quiz_id = (int) $result_quiz_id;
        $userStage->order_id = (int) $order_id;
        $userStage->unique_id = (string) $lp['uniqueId'];
        $userStage->passed = $stepStage === 'finish' ? 1 : 0;
        try {
            if(!empty($stage->id)) {
                $db->updateObject('#__quiztools_lpaths_users', $userStage, 'id');
            } else {
                $db->insertObject('#__quiztools_lpaths_users', $userStage);
            }
        } catch (\Exception $e) {
            throw new \Exception(Text::_('COM_QUIZTOOLS_LPATH_ERROR_LPATH_STAGE_NOT_SAVE'));
        }

        try {
            $return['nextStep'] = $model_lpath->getNextStep((int) $lp['id'], $lp);
        } catch (\Exception $e) {}

        return $return;
    }

    /**
     * Get Lpath Model.
     *
     * @param bool $forQuiz  The request for a model was received from a Quiz included in the Learning Path.
     * @return LpathModel
     * @throws \Exception
     */
    private function getLpathModel($forQuiz = false)
    {
        $app = Factory::getApplication();

        $data = $app->getInput()->get('lpath', [], 'ARRAY');
        if ($forQuiz) {
            $data = $app->getInput()->get('quiz', [], 'ARRAY');
        }

        /** @var LpathModel $model_lpath */
        $model_lpath = $app->bootComponent('com_quiztools')->getMVCFactory()
            ->createModel('Lpath', 'Site', ['ignore_request' => true]);

        // Forces the populateState() method to be called (and filling the '__state_set' property).
        // If it is called later, it will override the model's State.
        $model_lpath->getState();

        // Additional parameters can be added here:
        $order_id = !empty($data['orderId']) ? (int) $data['orderId'] : null;
        $model_lpath->setState('lpath.orderId', $order_id);

        return $model_lpath;
    }
}
