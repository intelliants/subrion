<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2017 Intelliants, LLC <https://intelliants.com>
 *
 * This file is part of Subrion.
 *
 * Subrion is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Subrion is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Subrion. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @link https://subrion.org/
 *
 ******************************************************************************/

$iaDb->setTable('blog_entries');

if (iaView::REQUEST_HTML == $iaView->getRequestType()) {
    if (!isset($iaCore->requestPath[1])) {
        iaBreadcrumb::preEnd('Blog', 'blog');

        if ($dates = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`status` = 'active' ORDER BY `date_added`", 0, null)) {
            $years = [];
            $months = [];

            $months['01']['name'] = 'month1';
            $months['02']['name'] = 'month2';
            $months['03']['name'] = 'month3';
            $months['04']['name'] = 'month4';
            $months['05']['name'] = 'month5';
            $months['06']['name'] = 'month6';
            $months['07']['name'] = 'month7';
            $months['08']['name'] = 'month8';
            $months['09']['name'] = 'month9';
            $months['10']['name'] = 'month10';
            $months['11']['name'] = 'month11';
            $months['12']['name'] = 'month12';

            foreach ($dates as $key => $date) {
                $fullDate = substr($date['date_added'], 0, strpos($date['date_added'], ' '));
                $fullDate = explode('-', $fullDate);
                $years[$fullDate[0]] = [];
            }

            foreach ($years as $y => $year) {
                $years[$y]['months'] = $months;

                foreach ($months as $j => $t) {
                    foreach ($dates as $key => $date) {
                        $fullDate = substr($date['date_added'], 0, strpos($date['date_added'], ' '));
                        $fullDate = explode('-', $fullDate);

                        if ($fullDate[1] == $j && $fullDate[0] == $y) {
                            if (isset($iaCore->requestPath[0]) && $iaCore->requestPath[0] == $y) {
                                $months[$j]['blogs'] = true;
                                $show['months'] = true;
                            } elseif (!isset($iaCore->requestPath[0]) && !isset($iaCore->requestPath[1])) {
                                $years[$y]['months'][$j]['blogs'] = true;
                                $show['years'] = true;
                            }
                        }
                    }
                }
            }

            if (isset($iaCore->requestPath[0]) && !isset($iaCore->requestPath[1])) {
                iaBreadcrumb::preEnd('Blog Archive', 'blog/date');
                iaBreadcrumb::replaceEnd($iaCore->requestPath[0], IA_SELF);

                $iaView->title($iaCore->requestPath[0]);
            }

            $iaView->assign('show', $show);
            $iaView->assign('years', $years);
            $iaView->assign('months', $months);
        } else {
            $iaView->setMessages(iaLanguage::get('no_blog_entries'), iaView::ALERT);
        }
    } elseif (isset($iaCore->requestPath[0]) && isset($iaCore->requestPath[1])) {
        $page = empty($_GET['page']) ? 0 : (int)$_GET['page'];
        $page = ($page < 1) ? 1 : $page;

        $pageUrl = $iaCore->factory('page', iaCore::FRONT)->getUrlByName('blog_date');
        $monthNumber = (int)$iaCore->requestPath[1];
        $month = iaLanguage::get('month' . $monthNumber);
        $year = (int)$iaCore->requestPath[0];

        $pagination = [
            'start' => ($page - 1) * $iaCore->get('blog_number'),
            'limit' => (int)$iaCore->get('blog_number'),
            'template' => $pageUrl . $year . IA_URL_DELIMITER . $monthNumber . '?page={page}'
        ];

        $stmt = "`status` = 'active' AND MONTH(b.`date_added`) = '" . $monthNumber . "' AND YEAR(b.`date_added`) = '" . $year . "' ";
        $order = ('date' == $iaCore->get('blog_order')) ? 'ORDER BY b.`date_added` DESC' : 'ORDER BY b.`title` ASC';

        $sql =
            'SELECT SQL_CALC_FOUND_ROWS ' .
            'b.`id`, b.`title`, b.`date_added`, b.`body`, b.`alias`, b.`image`, m.`fullname` ' .
            'FROM `:prefix:table_blog_entries` b ' .
            'LEFT JOIN `:prefix:table_members` m ON (b.`member_id` = m.`id`) ' .
            'WHERE b.' . $stmt . $order . ' LIMIT :start, :limit';

        $sql = iaDb::printf($sql, [
            'prefix' => $iaDb->prefix,
            'table_blog_entries' => 'blog_entries',
            'table_members' => 'members',
            'start' => $pagination['start'],
            'limit' => $pagination['limit']
        ]);

        $blogs  = $iaDb->getAll($sql);

        iaBreadcrumb::toEnd($year, $pageUrl . $year);
        iaBreadcrumb::toEnd($month);
        $pagination['total'] = $iaDb->foundRows();

        $iaView->assign('blogs', $blogs);
        $iaView->assign('pagination', $pagination);

        $iaView->title($month . ' ' . $year);
    }

    $iaView->display('date');
}
