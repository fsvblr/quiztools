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
     * Get the list of Learning Paths route.
     *
     * @param   integer|null  $catid The category ID.
     * @return  string  The list of Learning Paths route.
     * @since   1.1.0
     */
    public static function getLpathsRoute($catid = null)
    {
        $link = 'index.php?option=com_quiztools&view=lpaths';

        if (!empty($catid)) {
            $link .= '&catid=' . $catid;
        }

        return $link;
    }

    /**
     * Get the Learning Path route.
     *
     * @param   integer  $id  The route of the item.
     * @param   integer|null  $catid  The category ID.
     * @param   integer|null  $order_id  The order ID.
     * @return  string  The Learning Path route.
     * @since   1.1.0
     */
    public static function getLpathRoute($id, $catid = null, $order_id = null)
    {
        $link = 'index.php?option=com_quiztools&view=lpath&id=' . $id;

        if (!empty($catid)) {
            $link .= '&catid=' . $catid;
        }

        if (!empty($order_id)) {
            $link .= '&order_id=' . $order_id;
        }

        return $link;
    }

    /**
     * Get the list of quizzes route.
     *
     * @param   integer|null  $catid The category ID.
     * @return  string  The list of quizzes route.
     * @since   1.0.0
     */
    public static function getQuizzesRoute($catid = null)
    {
        $link = 'index.php?option=com_quiztools&view=quizzes';

        if (!empty($catid)) {
		    $link .= '&catid=' . $catid;
	    }

        return $link;
    }

    /**
     * Get the quiz route.
     *
     * @param   integer  $id  The route of the item.
     * @param   integer|null  $catid  The category ID.
     * @param   integer|null  $order_id  The order ID.
     * @return  string  The quiz route.
     * @since   1.0.0
     */
    public static function getQuizRoute($id, $catid = null, $order_id = null)
    {
        $link = 'index.php?option=com_quiztools&view=quiz&id=' . $id;

        if (!empty($catid)) {
            $link .= '&catid=' . $catid;
        }

        if (!empty($order_id)) {
            $link .= '&order_id=' . $order_id;
        }

        return $link;
    }

    /**
     * Get the list of results route.
     *
     * @return  string  The list of results route.
     * @since   1.0.0
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
     * @return  string  The result route.
     * @since   1.0.0
     */
    public static function getResultRoute($id)
    {
        $link = 'index.php?option=com_quiztools&view=result&id=' . $id;

        return $link;
    }

    /**
     * Get the list of orders route.
     * @return  string  The list of orders route.
     * @since   1.2.0
     */
    public static function getOrdersRoute()
    {
        $link = 'index.php?option=com_quiztools&view=orders';

        return $link;
    }
}
