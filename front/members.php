<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2018 Intelliants, LLC <https://intelliants.com>
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

if (!$iaCore->get('members_enabled')) {
    return iaView::errorPage(iaView::ERROR_NOT_FOUND);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType()) {
    $iaUsers = $iaCore->factory('users');

    // filter by usergroups
    $usergroups = $iaUsers->getUsergroups(true);
    $iaView->assign('usergroups', $usergroups);

    $activeGroup = '';
    if (isset($_GET['group'])) {
        $_SESSION['group'] = (int)$_GET['group'];

        if ('all' == $_GET['group']) {
            unset($_SESSION['group']);
        }
    }

    if (isset($_SESSION['group']) && in_array($_SESSION['group'], array_keys($usergroups))) {
        $activeGroup = $_SESSION['group'];

        $stmt = '`usergroup_id` = ' . $activeGroup . ' AND ';
    } else {
        $stmt = '`usergroup_id` IN (' . implode(',', array_keys($usergroups)) . ') AND ';
    }
    $iaView->assign('activeGroup', $activeGroup);

    $filterBy = 'username';

    /* check values */
    if (isset($_GET['account_by'])) {
        $_SESSION['account_by'] = $_GET['account_by'];
    }
    if (!isset($_SESSION['account_by'])) {
        $_SESSION['account_by'] = 'username';
    }
    $filterBy = ($_SESSION['account_by'] == 'fullname') ? 'fullname' : 'username';

    $letters['all'] = iaUtil::getLetters();
    $letters['active'] = (isset($iaCore->requestPath[0]) && in_array($iaCore->requestPath[0], $letters['all'])) ? $iaCore->requestPath[0] : false;
    $letters['existing'] = [];

    $iaDb->setTable(iaUsers::getTable());
    if ($array = $iaDb->all('DISTINCT UPPER(SUBSTR(`' . $filterBy . '`, 1, 1)) `letter`', $stmt . "`status` = 'active' GROUP BY `username`")) {
        foreach ($array as $item) {
            $letters['existing'][] = $item['letter'];
        }
    }
    $iaDb->resetTable();

    $stmt .= $letters['active'] ? ('0-9' == $letters['active'] ? "(`$filterBy` REGEXP '^[0-9]') AND " : "(`$filterBy` LIKE '{$letters['active']}%') AND ") : '';
    if ($letters['active']) {
        $iaView->set('subpage', array_search($letters['active'], $letters['all']) + 1);
    }

    // gets current page and defines start position
    $pagination = [
        'limit' => $iaCore->get('members_per_page', 20),
        'url' => IA_URL . 'members/' . ($letters['active'] ? $letters['active'] . '/' : '') . '?page={page}'
    ];
    $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
    $start = (max($page, 1) - 1) * $pagination['limit'];

    list($pagination['total'], $membersList) = $iaUsers->coreSearch($stmt . "`status` = 'active' ", $start, $pagination['limit'], '`date_reg`');
    $fields = $iaCore->factory('field')->filter($iaUsers->getItemName(), $membersList);

    // breadcrumb formation
    if ($activeGroup) {
        iaBreadcrumb::toEnd(iaLanguage::get('usergroup_' . $usergroups[$activeGroup]), IA_URL . 'members/?group=' . $activeGroup);
    }
    if ($letters['active']) {
        iaBreadcrumb::toEnd($letters['active'], IA_SELF);
    }

    if ($membersList) {
        $membersList = $iaCore->factory('item')->updateItemsFavorites($membersList, $iaUsers->getItemName());
    }

    $iaView->assign('title', iaLanguage::get('members') . ($letters['active'] ? " [ {$letters['active']} ] " : ''));
    $iaView->assign('filter', $filterBy);
    $iaView->assign('letters', $letters);
    $iaView->assign('members', $membersList);
    $iaView->assign('pagination', $pagination);
    $iaView->assign('fields', $fields);

    $iaView->title(iaLanguage::get('members') . ($letters['active'] ? " [{$letters['active']}] " : ''));

    $iaView->set('filtersItemName', $iaUsers->getItemName());
}
