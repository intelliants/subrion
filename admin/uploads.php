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

$iaAcl = $iaCore->factory('acl');
if (!$iaAcl->isAdmin()) {
    die('No permissions.');
}

if (iaView::REQUEST_JSON == $iaView->getRequestType()) {
    define('IA_ENABLE_FULL_ACCESS', false);

    error_reporting(0); // Set E_ALL for debugging

    $pluginPath = IA_INCLUDES . 'elfinder/php/';
    include_once $pluginPath . 'elFinderConnector.class.php';
    include_once $pluginPath . 'elFinder.class.php';
    include_once $pluginPath . 'elFinderVolumeDriver.class.php';
    include_once $pluginPath . 'elFinderVolumeLocalFileSystem.class.php';

    // Required for MySQL storage connector
    // include_once $pluginPath . 'elFinderVolumeMySQL.class.php';
    // Required for FTP connector support
    // include_once $pluginPath . 'elFinderVolumeFTP.class.php';

    /**
     * Simple function to demonstrate how to control file access using "accessControl" callback.
     * This method will disable accessing files/folders starting from '.' (dot)
     *
     * @param  string $attr attribute name (read|write|locked|hidden)
     * @param  string $path file path relative to volume root directory started with directory separator
     * @return bool|null
     **/
    function access($attr, $path, $data, $volume)
    {
        return strpos(basename($path), '.') === 0       // if file/folder begins with '.' (dot)
            ? !($attr == 'read' || $attr == 'write')    // set read+write to false, other (locked+hidden) set to true
            : null;                                    // else elFinder decide it itself
    }

    $path = IA_UPLOADS;
    $url = IA_CLEAR_URL . 'uploads/';
    if (!IA_ENABLE_FULL_ACCESS && iaUsers::MEMBERSHIP_ADMINISTRATOR != iaUsers::getIdentity()->usergroup_id) {
        iaCore::factory('util');

        $path .= iaUtil::getAccountDir();
        $url .= strtolower(substr(iaUsers::getIdentity()->username, 0,
                1)) . IA_URL_DELIMITER . iaUsers::getIdentity()->username . IA_URL_DELIMITER;
    }

    // Documentation for connector options:
    // https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options
    $opts = [
        // 'debug' => true,
        'roots' => [
            [
                'driver' => 'LocalFileSystem',   // driver for accessing file system (REQUIRED)
                'path' => $path,         // path to files (REQUIRED)
                'URL' => $url, // URL to files (REQUIRED)
                'accessControl' => 'access'             // disable and hide dot starting files (OPTIONAL)
            ]
        ]
    ];

    // run elFinder
    $connector = new elFinderConnector(new elFinder($opts));
    $connector->run();
}

if (iaView::REQUEST_HTML == $iaView->getRequestType()) {
    if (isset($_GET['mode'])) {
        $iaView->set('nodebug', 1);
        $iaView->disableLayout();
    }
    $iaView->display('uploads');
}
