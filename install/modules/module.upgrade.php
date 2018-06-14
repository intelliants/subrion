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

$iaOutput->layout()->title = 'Upgrade Wizard';

$iaOutput->steps = [
    'check' => 'Pre-Upgrade Check',
    'download' => 'Download Patch',
    'backup' => 'Backup',
    'finish' => 'Upgrade'
];

if (!isset($iaOutput->steps[$iaOutput->step]) && $iaOutput->step != 'rollback') {
    iaHelper::redirect('upgrade/');
}

if (!iaUsers::hasIdentity() || iaUsers::MEMBERSHIP_ADMINISTRATOR != iaUsers::getIdentity()->usergroup_id) {
    $iaOutput->steps = ['check' => $iaOutput->layout()->title];
    $iaOutput->errorCode = 'authorization';

    $step = 'check';

    return false;
}

switch ($step) {
    case 'check':
        $patchVersion = trim($_SERVER['REQUEST_URI'], '/');
        $patchVersion = explode('/', $patchVersion);
        $patchVersion = end($patchVersion);

        if (!preg_match('#\d{1}\.\d{1}\.\d{1}#', $patchVersion)) {
            if (!isset($_SESSION['upgrade_to']) && empty($_SESSION['upgrade_to'])) {
                $iaOutput->errorCode = 'version';
            }
        } else {
            $_SESSION['upgrade_to'] = $patchVersion;
        }

        if (!iaHelper::hasAccessToRemote()) {
            $iaOutput->errorCode = 'remote';
        }

        if (isset($_SESSION['upgrade_to'])) {
            $iaOutput->version = $_SESSION['upgrade_to'];
        }

        break;

    case 'download':
        $patchUrl = 'https://tools.subrion.org/get/patch/%s/%s/';
        $patchUrl = sprintf($patchUrl, IA_VERSION, $_SESSION['upgrade_to']);

        $patchFileContent = iaHelper::getRemoteContent($patchUrl);

        if ($patchFileContent !== false) {
            $file = fopen(IA_HOME . 'tmp/patch.iap', 'wb');
            fwrite($file, $patchFileContent);
            fclose($file);

            $iaOutput->size = strlen($patchFileContent);
        } else {
            $iaOutput->error = true;
        }

        break;

    case 'backup':
        require_once IA_INSTALL . 'classes/ia.backup.php';
        $iaBackup = new iaBackup();

        if (iaHelper::isAjaxRequest()) {
            iaHelper::loadCoreClass('view', 'core')->set('nodebug', true);

            echo $iaBackup->save()
                ? 'success'
                :  array_shift($iaBackup->messages);
            exit();
        } else {
            $iaOutput->backupFile = str_replace(IA_HOME, '', $iaBackup->filePath);
        }

        break;

    case 'finish':
        require_once IA_INSTALL . 'classes/ia.patch.parser.php';
        require_once IA_INSTALL . 'classes/ia.patch.applier.php';

        $iaOutput->adminPath = iaCore::instance()->factory('config')->get('admin_page');

        $options = isset($_GET['options']) && is_array($_GET['options']) ? $_GET['options'] : [];

        try {
            $patchFileContent = @file_get_contents(IA_HOME . 'tmp/patch.iap');
            if (false === $patchFileContent) {
                throw new Exception('Could not get downloaded patch file. Please download it again.');
            }

            $patchParser = new iaPatchParser($patchFileContent);
            $patch = $patchParser->patch;

            if ($patch['info']['version_from'] != str_replace('.', '', IA_VERSION)) {
                throw new Exception('Patch is not applicable to your version of Subrion CMS.');
            }

            $patchApplier = new iaPatchApplier(IA_HOME, [
                'host' => INTELLI_DBHOST,
                'port' => INTELLI_DBPORT,
                'database' => INTELLI_DBNAME,
                'user' => INTELLI_DBUSER,
                'password' => INTELLI_DBPASS,
                'prefix' => INTELLI_DBPREFIX
            ], in_array('force-mode', $options));
            $patchApplier->process($patch, $_SESSION['upgrade_to']);

            $textLog = $patchApplier->getLog();

            $logFile = 'upgrade-log-' . $patch['info']['version_to'] . '_' . date('d-m-y-Hi') . '.txt';
            if ($fh = fopen(IA_HOME . 'uploads/' . $logFile, 'wt')) {
                fwrite($fh, $textLog);
                fclose($fh);
            }

            // log this event
            $iaLog = iaHelper::loadCoreClass('log', 'core');
            $iaLog->write(iaLog::ACTION_UPGRADE, [
                'type' => 'app',
                'from' => IA_VERSION,
                'to' => $_SESSION['upgrade_to'],
                'file' => $logFile
            ]);
            //

            // processing the upgrade log to show nicely
            $textLog = htmlspecialchars($textLog);
            $textLog = str_replace(
                [PHP_EOL, 'INFO', 'SUCCESS', 'ERROR', 'ALERT'],
                ['',
                    '<p>',
                    '<p><span class="label label-success">SUCCESS</span>',
                    '<p><span class="label label-danger">ERROR</span>',
                    '<p><span class="label label-warning">ALERT</span>'
                ], $textLog
            );
            //

            $iaOutput->log = $textLog;

            // clean up cache files
            $tempFolder = IA_HOME . 'tmp/';
            iaHelper::cleanUpDirectoryContents($tempFolder);

            unset($_SESSION['upgrade_to']);
        } catch (Exception $e) {
            @unlink(IA_HOME . 'tmp/patch.iap');

            $iaOutput->message = $e->getMessage();
        }

        break;

    case 'rollback':
        $iaOutput->steps = [
            'check' => 'Upgrade Wizard',
            'rollback' => 'Rollback'
        ];

        $fileList = glob(IA_HOME . 'backup/backup_*_*.zip');
        $backups = [];

        if ($fileList) {
            foreach ($fileList as $fileName) {
                $fileName = basename($fileName);
                $array = explode('_', $fileName);
                if (3 == count($array)) {
                    $backups[$array[1]][$fileName] = date('M d, Y', strtotime(substr($array[2], 0, -4)));
                }
            }
        }

        $iaOutput->backups = $backups;

        if (!empty($_POST['backup'])) {
            $fileName = $_POST['backup'];
            iaSanitize::filenameEscape($fileName);
            $fileName = IA_HOME . 'backup/' . $fileName;

            if (file_exists($fileName)) {
                require_once IA_INSTALL . 'classes/ia.backup.php';
                $iaBackup = new iaBackup();

                $iaBackup->restore($fileName)
                    ? $iaOutput->success = true
                    : $iaOutput->error = array_shift($iaBackup->messages);
            } else {
                $iaOutput->error = 'Incorrect backup file specified.';
            }
        }
}
