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
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Qt\Component\Quiztools\Administrator\Helper\QuiztoolsHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * QuizTools Component Quiz Model
 *
 * @since  1.5
 */
class QuizModel extends ItemModel
{
    /**
     * Model context string.
     *
     * @var        string
     */
    protected $_context = 'com_quiztools.quiz';

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
        $this->setState('quiz.id', $pk);

        $params = $app->getParams();
        $this->setState('params', $params);
    }

    /**
     * Method to get quiz data.
     *
     * @param   integer  $pk  The id of the quiz.
     *
     * @return  object|boolean  Item data object on success, boolean false
     */
    public function getItem($pk = null)
    {
	    $user = $this->getCurrentUser();

        $pk = (int) ($pk ?: $this->getState('quiz.id'));

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
                            $db->qn('a.quiz_autostart'),
                            $db->qn('a.allow_continue'),
                            $db->qn('a.timer_show'),
                            $db->qn('a.timer_style'),
                            $db->qn('a.limit_time'),
                            $db->qn('a.skip_questions'),
                            $db->qn('a.enable_prev_button'),
                            $db->qn('a.question_number'),
                            $db->qn('a.question_points'),
                            $db->qn('a.feedback_question'),
                            $db->qn('a.metatitle'),
                            $db->qn('a.metakey'),
                            $db->qn('a.metadesc'),
                            $db->qn('a.params'), // This field is for custom jobs

	                        $db->qn('a.total_score'),
	                        $db->qn('a.passing_score'),
	                        $db->qn('a.question_pool'),
	                        $db->qn('a.question_pool_randon_qty'),
	                        $db->qn('a.question_pool_categories'),
	                        $db->qn('a.shuffle_questions'),
	                        $db->qn('a.questions_on_page'),
                        ]
                    )
                )
                    ->from($db->qn('#__quiztools_quizzes', 'a'))
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
                    throw new \Exception(Text::_('COM_QUIZTOOLS_QUIZ_ERROR_QUIZ_NOT_FOUND'), 404);
                }

	            if (isset($data->params)) {
		            $registry = new Registry($data->params);
		            $data->params = $registry->toArray();
	            }

				if (isset($data->question_pool_categories)) {
					$registry = new Registry($data->question_pool_categories);
					$data->question_pool_categories = $registry->toArray();
				}

				if (isset($data->feedback_final_msg)) {
					$registry = new Registry($data->feedback_final_msg);
					$data->feedback_final_msg = $registry->toArray();
				}

				// If the quiz description contains the "readmore" insert, the first part of the description
	            // will be shown in the category, and the second part in the quiz.
	            // Otherwise, the entire description will be shown in the quiz.
                $data->description = QuiztoolsHelper::getDescriptionInItem($data->description);

	            $dispatcher = $this->getDispatcher();
	            PluginHelper::importPlugin('content', null, true, $dispatcher);
	            PluginHelper::importPlugin('quiztools', null, true, $dispatcher);

	            // Processing quiz description.
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
			            'context' => 'com_quiztools.quiz.prepareDescription',
			            'subject' => $data,
			            'params'  => new \stdClass(),
			            'page'    => 0,
		            ])
	            )->getArgument('result', $data)->text;
	            if (isset($data->text) && $changed) {
		            unset($data->text);
	            }

				// This is the change point of the quiz object retrieved from the database.
	            $data = $dispatcher->dispatch(
		            'onQuizAfterLoad',
		            new Model\PrepareDataEvent('onQuizAfterLoad', [
			            'context' => 'com_quiztools.quiz.afterLoad',
			            'data'    => $data,
			            'subject' => new \stdClass(),
		            ])
	            )->getArgument('result', $data);

                $this->_item[$pk] = $data;

            } catch (\Exception $e) {
				//$msg = Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage());
	            $msg = 'Error "quiz-01"';
		        Factory::getApplication()->enqueueMessage($msg, 'warning');

                $this->_item[$pk] = false;
            }
        }

        return $this->_item[$pk];
    }
}
