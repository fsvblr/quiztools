<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Component\Content\Administrator\Model\ArticlesModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * List of active articles on the site.
 */
class ArticleslistField extends ListField
{
	protected $type = 'Articleslist';

	protected function getOptions()
	{
        $app = Factory::getApplication();

        /** @var ArticlesModel $model_articles */
        $model_articles = $app->bootComponent('com_content')->getMVCFactory()
            ->createModel('Articles', 'Administrator', ['ignore_request' => true]);

        $model_articles->setState('filter.published', 1);
        $model_articles->setState('list.ordering', 'a.title');
        $model_articles->setState('list.direction', 'ASC');

        $articles = $model_articles->getItems();

        $options = [];

        if (!empty($articles)) {
            foreach ($articles as $article) {
                $option = new \stdClass();
                $option->value = (int) $article->id;
                $option->text = htmlspecialchars($article->title, ENT_QUOTES, 'UTF-8');
                $options[] = $option;
            }
        }

        return $options;
	}

    protected function getInput()
    {
        $options = $this->getOptions();
        $options = array_merge(parent::getOptions(), $options);

        $idtag = $this->id ?: false;
        $class = $this->element['class'] ?: null;
        $attribs = $class ? 'class="'. $class .'"' : null;

        return HTMLHelper::_('select.genericlist',  $options,  $this->name, $attribs, 'value', 'text', $this->value, $idtag);
    }
}
