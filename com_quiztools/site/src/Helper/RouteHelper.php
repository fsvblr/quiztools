<?php

/**
 * @package     QuizTools.Site
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * QuizTools Component Route Helper.
 *
 * @since  1.5
 */
abstract class RouteHelper
{
    /**
     * Get the list of quizzes route.
     *
     * @param   integer  $catid     The category ID.
     *
     * @return  string  The list of quizzes route.
     *
     * @since   1.5
     */
    public static function getQuizzesRoute($catid = 0)
    {
        $link = 'index.php?option=com_quiztools&view=quizzes';

	    if ((int) $catid > 1) {
		    $link .= '&catid=' . $catid;
	    }

        return $link;
    }

    /**
     * Get the quiz route.
     *
     * @param   integer  $id        The route of the item.
     * @param   integer  $catid     The category ID.
     *
     * @return  string  The quiz route.
     *
     * @since   1.5
     */
    public static function getQuizRoute($id, $catid = 0)
    {
        $link = 'index.php?option=com_quiztools&view=quiz&id=' . $id;

        if ((int) $catid > 1) {
            $link .= '&catid=' . $catid;
        }

        return $link;
    }

    /**
     * Get the list of results route.
     *
     * @return  string  The list of results route.
     *
     * @since   1.5
     */
    public static function getResultsRoute()
    {
        $link = 'index.php?option=com_quiztools&view=results';

        return $link;
    }

    /**
     * Get the result route.
     *
     * @param   integer  $id        The route of the item.
     *
     * @return  string  The result route.
     *
     * @since   1.5
     */
    public static function getResultRoute($id)
    {
        $link = 'index.php?option=com_quiztools&view=result&id=' . $id;

        return $link;
    }
}
