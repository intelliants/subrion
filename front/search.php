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

$iaSearch = $iaCore->factory('search', iaCore::FRONT);
$iaItem = $iaCore->factory('item');

if (iaView::REQUEST_JSON == $iaView->getRequestType()) {
    if (isset($_POST['action'])) {
        if ('save' == $_POST['action'] && isset($_POST['item']) && isset($_POST['params']) && isset($_POST['name'])) {
            if (!iaUsers::hasIdentity()) {
                return iaView::errorPage(iaView::ERROR_UNAUTHORIZED, iaLanguage::get('do_authorize_to_save_search'));
            }

            $url = str_replace(IA_URL, '', $_POST['params']);

            $result = $iaSearch->save($_POST['item'], $url, $_POST['name']);

            $iaView->assign('result', $result);
            $iaView->assign('message', iaLanguage::get($result ? 'saved' : 'db_error'));

            return;
        } elseif ('delete' == $_POST['action'] && !empty($_POST['id'])) {
            if (!iaUsers::hasIdentity()) {
                return iaView::errorPage(iaView::ERROR_FORBIDDEN);
            }

            $result = false;
            if (iaUsers::getIdentity()->id == $iaDb->one('member_id', iaDb::convertIds($_POST['id']), iaSearch::getTable())) {
                $result = $iaSearch->delete($_POST['id'], $_POST['member_id']);
            }

            $iaView->assign('result', $result);
            $iaView->assign('message', iaLanguage::get($result ? 'deleted' : 'error'));
        }
    }

    $itemName = (1 == count($iaCore->requestPath)) ? $iaCore->requestPath[0] : str_replace('search_', '', $iaView->name());

    if (in_array($itemName, $iaItem->getItems())) {
        if (!empty($_SERVER['HTTP_REFERER'])) { // this makes possible displaying filters block everywhere, but displaying results at the right page
            $referrerUri = str_replace(IA_CLEAR_URL, '', $_SERVER['HTTP_REFERER']);
            $lang = '';

            if (IA_URL_LANG) { // we should keep user's current language
                $l = explode(IA_URL_DELIMITER, trim($referrerUri, IA_URL_DELIMITER));
                $l = array_shift($l);
                $lang = (isset($iaCore->languages[$l]) ? $l : $iaView->language) . IA_URL_DELIMITER;
            }

            $pageUri = $iaCore->factory('page', iaCore::FRONT)->getUrlByName('search_' . $itemName, false);
            $pageUri || $pageUri = 'search/' . $itemName . '/';
            $pageUri = $lang . $pageUri;

            if ($pageUri != $referrerUri || false !== stripos($_SERVER['HTTP_REFERER'], '?q=')) {
                $pageUri = IA_CLEAR_URL . $pageUri . '#' . $iaSearch->httpBuildQuery($_GET);

                $iaView->assign('url', $pageUri);
                return;
            }
        }

        $iaView->loadSmarty(true);
        $iaView->assign($iaSearch->doAjaxItemSearch($itemName, $_GET));
    }
}

if (iaView::REQUEST_HTML == $iaView->getRequestType()) {
    if (1 == count($iaCore->requestPath) && 'my' == $iaCore->requestPath[0]) {
        $iaView->assign('searches', $iaSearch->get());

        $iaView->display('my-searches');
        $iaView->disableLayout();
        $iaView->set('nodebug', true);

        return;
    }

    $results = null;
    $regular = false;
    $query = isset($_GET['q']) && is_string($_GET['q']) ? $_GET['q'] : null;

    $pagination = [
        'limit' => 10,
        'start' => 0,
        'total' => 0,
        'url' => IA_SELF . '?q=' . urlencode($query) . '&page={page}'
    ];

    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max($_GET['page'], 1) : 1;
    $pagination['start'] = ($page - 1) * $pagination['limit'];

    if ('search' != $iaView->name() || isset($iaCore->requestPath[0])) {
        $itemName = ('search' != $iaView->name())
            ? str_replace('search_', '', $iaView->name())
            : $iaCore->requestPath[0];

        if (!in_array($itemName, $iaItem->getItems())) {
            return iaView::errorPage(iaView::ERROR_NOT_FOUND);
        }

        $empty = empty($_GET) && !$iaCore->requestPath;

        if (!$empty) {
            $params = $query ? $query : $_GET;
            $results = $iaSearch->doItemSearch($itemName, $params, $pagination['start'], $pagination['limit']);

            $pagination['total'] = $results[0];

            $params = $_GET;
            unset($params['page']);
            $pagination['url'] = IA_SELF . '?' . http_build_query($params) . ($params ? '&' : '') . 'page={page}';
        }

        $iaView->set('filtersItemName', $itemName);
        $iaView->set('filtersParams', $iaSearch->getParams());

        $iaView->assign('itemName', $itemName);
        $iaView->assign('empty', $empty);
        $iaView->assign('searches', $iaSearch->get());

        $iaView->title($iaSearch->getCaption() ? $iaSearch->getCaption() : $iaView->title());
    } else {
        $regular = true;

        $iaCore->startHook('phpSearchAfterGetQuery', ['query' => &$query]);

        empty($query) || $results = $iaSearch->doRegularSearch($query, $pagination['limit']);
    }

    $iaView->assign('pagination', $pagination);
    $iaView->assign('results', $results);
    $iaView->assign('regular', $regular);
    $iaView->assign('query', $query);

    $iaView->display('search');
}
