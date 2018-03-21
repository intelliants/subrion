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

if (isset($_GET['file'])) {
    switch ($_GET['file']) {
        case 'default.css':
            header('Content-Type: text/css');
            echo file_get_contents(IA_INCLUDES . 'adminer/adminer.css');
            break;
        case 'functions.js':
            header('Content-Type: text/javascript; charset=utf-8');
            echo file_get_contents(IA_INCLUDES . 'adminer/adminer.js');
            break;
        case 'plus.gif':
        case 'cross.gif':
        case 'up.gif':
        case 'down.gif':
        case 'arrow.gif':
            header("Content-Type: image/gif");
            echo file_get_contents(IA_INCLUDES . 'adminer/' . $_GET['file']);
            break;
    }
    die();
}

if (iaCore::ACCESS_ADMIN == $iaCore->getAccessType()) {
    $_GET['username'] = INTELLI_DBUSER;
    $_GET['server'] = INTELLI_DBHOST;
    $_GET['db'] = INTELLI_DBNAME;
    $_GET['driver'] = INTELLI_CONNECT;

    $iaView->set('nodebug', 1);
    $iaView->disableLayout();

    $iaView->display(iaView::NONE);

    function adminer_object()
    {
        class AdminerSoftware extends Adminer
        {
            public function name()
            {
                // custom name in title and heading
                return 'Subrion DB';
            }

            public function credentials()
            {
                // server, username and password for connecting to database
                return [INTELLI_DBHOST, INTELLI_DBUSER, INTELLI_DBPASS];
            }

            public function database()
            {
                // database name, will be escaped by Adminer
                return INTELLI_DBNAME;
            }

            public function databases()
            {
                // database name, will be escaped by Adminer
                return [INTELLI_DBNAME];
            }

            public function login($login, $password)
            {
                // validate user submitted credentials
                return true;
            }
        }

        return new AdminerSoftware;
    }

    include IA_INCLUDES . 'adminer/adminer.script.inc';
}