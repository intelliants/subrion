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

if (iaView::REQUEST_HTML == $iaView->getRequestType()) {
    $preview = $previewMode = false;
    $content = '';
    $name = $iaView->name();

    $iaView->assign('protect', false);

    if (isset($_GET['preview']) && isset($iaCore->requestPath[0])) {
        $tname = iaSanitize::sql($iaCore->requestPath[0]);
        if (isset($_SESSION['preview_pages'][$tname])) {
            $previewMode = true;
            $newPage = $_SESSION['preview_pages'][$tname];
            $name = $tname;
            if (isset($newPage['titles'])) {
                if (!is_array($newPage['titles'])) {
                    $pageTitle = $newPage['titles'];
                } elseif (isset($newPage['titles'][$iaView->language])) {
                    $pageTitle = $newPage['titles'][$iaView->language];
                }
                $iaView->assign('titles', $pageTitle);
            }
            if (isset($newPage['contents'])) {
                if (!is_array($newPage['contents'])) {
                    $iaView->assign('content', $newPage['contents']);
                } elseif (isset($newPage['contents'][$iaView->language])) {
                    $iaView->assign('content', $newPage['contents'][$iaView->language]);
                }
            }
            if (!empty($newPage['passw'])) {
                $iaView->assign('page_protect', iaLanguage::get('page_protected', 'Page protected'));
            }
        }
    }

    if (isset($_GET['page_preview']) && isset($iaCore->requestPath[0])) {
        $preview = true;
        $name = iaSanitize::sql($iaCore->requestPath[0]);
    }

    $passw = '';
    if (isset($_POST['password'])) {
        $passw = iaSanitize::sql($_POST['password']);
        $_SESSION['page_passwords'][$name] = $passw;
    } elseif (isset($_SESSION['page_passwords'][$name])) {
        $passw = $_SESSION['page_passwords'][$name];
    }

    $iaPage = $iaCore->factory('page', iaCore::FRONT);
    $page = $iaPage->getByName($name, $preview ? iaCore::STATUS_DRAFT : iaCore::STATUS_ACTIVE);

    if (!$previewMode && (empty($page) || $iaCore->requestPath && !('index' == $iaCore->requestPath[0] && 1 == count($iaCore->requestPath)))) {
        return iaView::errorPage(iaView::ERROR_NOT_FOUND);
    }

    // check read permissions
    if (isset($_POST['password']) && $page['passw'] && $passw != $page['passw']) {
        $iaView->setMessages(iaLanguage::get('password_incorrect'), iaView::ERROR_NOT_FOUND);
    }

    if ($page['passw'] && $passw != $page['passw'] && !$previewMode) {
        if (!$preview) {
            $page = [
                'meta_description' => $page['meta_description'],
                'meta_keywords' => $page['meta_keywords'],
            ];
            $iaView->assign('protect', true);
        }
    }

    if ($preview) {
        $iaView->assign('page_protect', iaLanguage::get('page_preview'));
    }
    $iaView->assign('page', $page);

    $iaDb->setTable(iaLanguage::getTable());
    $jt_where = "`category` = 'page' AND `key` = 'page_{DATA_REPLACE}_{$name}' AND `code` = '";

    if (!$previewMode) {
        $page_title_check = iaLanguage::get('page_title_' . $name, $name);
        $pageTitle = $page_title_check ? $page_title_check : $iaDb->one('`value`', str_replace('{DATA_REPLACE}', 'title', $jt_where) . $iaCore->get('lang') . "'");

        $iaView->title($pageTitle);

        if (iaLanguage::exists('page_meta_title_' . $name)) {
            $iaView->set('meta_title', iaLanguage::get('page_meta_title_' . $name));
        }
    }

    if ($page && !$previewMode) {
        $page_content_check = $iaDb->one('`value`', str_replace('{DATA_REPLACE}', 'content', $jt_where) . $iaView->language . "'");
        $content = $page_content_check ? $page_content_check : $iaDb->one('`value`', str_replace('{DATA_REPLACE}', 'content', $jt_where) . $iaCore->get('lang') . "'");
    }
    $iaDb->resetTable();

    if ($page['custom_tpl'] && $page['template_filename']) {
        $iaView->iaSmarty->assign('img', IA_TPL_URL . 'img/');

        $content = $iaView->iaSmarty->fetch($page['template_filename']);
    }

    $iaView->assign('content', $content);

    $iaView->set('description', $page['meta_description']);
    $iaView->set('keywords', $page['meta_keywords']);

    $iaView->display('page');
}
