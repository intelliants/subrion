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

if (iaView::REQUEST_JSON == $iaView->getRequestType()) {
    return iaView::errorPage(iaView::ERROR_NOT_FOUND);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType()) {
    iaBreadcrumb::add(iaLanguage::get('main_page'), IA_URL);

    $redirectUrl = IA_URL;
    if (isset($_SESSION['redir'])) {
        $iaView->assign('redir', $_SESSION['redir']);
        $redirectUrl = $_SESSION['redir']['url'];
        unset($_SESSION['redir']);
    }

    $iaView->disableLayout();
    $iaView->assign('redirect_url', $redirectUrl);
    $iaView->title($iaCore->get('site'));
}
