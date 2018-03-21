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

if (!$iaCore->get('api_enabled')) {
    return iaView::errorPage(iaView::ERROR_FORBIDDEN);
}

if (count($iaCore->requestPath) < 2) {
    return iaView::errorPage(iaView::ERROR_NOT_FOUND);
}

$iaView->disableLayout();
$iaView->display(iaView::NONE);

$iaView->set('nodebug', 1);
$iaView->set('nocsrf', 1);

$iaCore->factory('api', iaCore::FRONT)->process($iaCore->requestPath);

die;
