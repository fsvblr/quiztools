<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Site\Service;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Routing class of com_quiztools
 *
 * @since  3.3
 */
class Router extends RouterView
{
	/**
	 * The db
	 *
	 * @var DatabaseInterface
	 *
	 * @since  4.0.0
	 */
	private $db;

	/**
	 * QuizTools Component router constructor
	 *
	 * @param   SiteApplication           $app              The application object
	 * @param   AbstractMenu              $menu             The menu object to work with
	 * @param   CategoryFactoryInterface  $categoryFactory  The category object
	 * @param   DatabaseInterface         $db               The database object
	 */
	public function __construct(SiteApplication $app, AbstractMenu $menu, CategoryFactoryInterface $categoryFactory, DatabaseInterface $db)
	{
		$this->db = $db;

		$quizzes = (new RouterViewConfiguration('quizzes'));
		$this->registerView($quizzes);

		$quiz = new RouterViewConfiguration('quiz');
		$quiz->setKey('id')->setParent($quizzes, 'catid');
		$this->registerView($quiz);

        $results = (new RouterViewConfiguration('results'));
        $this->registerView($results);

        $result = new RouterViewConfiguration('result');
        $result->setKey('id')->setParent($results);
        $this->registerView($result);

		parent::__construct($app, $menu);

		$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}

	/**
	 * Method to get the segment(s) for a quiz
	 *
	 * @param   string  $id     ID of the quiz to retrieve the segments for
	 * @param   array   $query  The request that is built right now
	 *
	 * @return  array  The segments of this item
	 */
	public function getQuizSegment(int $id, array $query): array
	{
		$db = $this->db;
		$querydb = $db->createQuery()
			->select($db->qn('alias'))
			->from($db->qn('#__quiztools_quizzes'))
			->where($db->qn('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$segment = $db->setQuery($querydb)->loadResult() ?: null;

		if ($segment === null) {
			return [];
		}

		return [$segment];
	}

	/**
	 * Method to get quiz ID
	 *
	 * @param   string  $segment  Segment of the quiz to retrieve the ID for
	 * @param   array   $query    The request that is parsed right now
	 *
	 * @return  mixed   The id of this item or false
	 */
	public function getQuizId(string $segment, array $query): bool|int
	{
		$db = $this->db;
		$querydb = $db->createQuery()
			->select($db->qn('id'))
			->from($db->qn('#__quiztools_quizzes'))
			->where($db->qn('alias') . ' = :segment')
			->bind(':segment', $segment);

		if (!empty($query['catid'])) {
			$querydb->where($db->qn('catid') . ' = :catId')
				->bind(':catId', $query['catid'], ParameterType::INTEGER);
		}

		return  $db->setQuery($querydb)->loadResult() ?: false;
	}

    /**
     * Method to get the segment(s) for a result
     *
     * @param   string  $id     ID of the result to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array  The segments of this item
     */
    public function getResultSegment(int $id, array $query): array
    {
        return [(string) $id];
    }

    /**
     * Method to get result ID
     *
     * @param   string  $segment  Segment of the quiz to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  mixed   The id of this item or false
     */
    public function getResultId(string $segment, array $query): bool|int
    {
        return  (int) $segment ?: false;
    }

	/**
	 * Build method for URLs
	 *
	 * @param   array  &$query  Array of query elements
	 *
	 * @return  array  Array of URL segments
	 *
	 * @since   3.5
	 */
	public function build(&$query)
	{
		if (isset($query['view']) && $query['view'] == 'quizzes' && isset($query['catid'])) {
			unset($query['catid']);
		}

		return parent::build($query);
	}
}
