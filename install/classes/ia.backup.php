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

class iaBackup
{
    protected $_foldersList = [
        'admin',
        'front',
        'includes',
        'install',
        'js',
        'modules',
        'templates'
    ];

    protected $_filesList = [
        '.htaccess',
        'changelog.txt',
        'favicon.ico',
        'index.php',
        'license.txt',
        'robots.txt'
    ];

    protected $dumpFilePath;

    public $filePath;
    public $messages = [];


    public function __construct()
    {
        $this->_dumpFilePath = IA_TMP . 'data_backup_' . IA_VERSION . '.sql';
        $this->filePath = IA_HOME . 'backup/' . sprintf('backup_%s_%s.zip', IA_VERSION, (new \DateTime())->format(iaDb::DATE_FORMAT));
    }

    protected function _decompressFiles($backupFile)
    {
        if (!class_exists('ZipArchive')) {
            $this->messages[] = 'ZIP extension is not available. Could not continue.';
            return false;
        }

        $zip = new ZipArchive();
        $result = $zip->open($backupFile);

        if (true === $result) {
            $zip ->extractTo(IA_HOME);
            if ($zip->close()) {
                return true;
            }

            $message = 'Could not extract from backup file. ';
            $message.= method_exists($zip, 'getStatusString')
                ? 'ZIP engine error: ' . $zip->getStatusString()
                : 'ZIP engine error code: ' . $zip->status;

            $this->messages[] = $message;
        } else {
            $this->messages[] = 'Invalid ZIP archive.';
        }

        return false;
    }

    protected function _applyDbDump($backupFile)
    {
        // look for the dump file
        $array = explode('_', basename($backupFile));
        $this->_dumpFilePath = IA_HOME . 'backup/data_backup_' . $array[1] . '.sql';
        //

        return iaHelper::loadCoreClass('dbcontrol')->splitSQL($this->_dumpFilePath);
    }

    protected function _prepareDbDump()
    {
        if (file_exists($this->_dumpFilePath) && filesize($this->_dumpFilePath) > 0) {
            return true;
        }

        $iaDbControl = iaHelper::loadCoreClass('dbcontrol');

        $tablesList = $iaDbControl->getTables();
        if (empty($tablesList)) {
            $this->messages[] = 'Could not get the list of DB tables to dump.';
            return false;
        }

        $sqlDump = '';
        $onlyStructureExceptions = ['online'];
        foreach ($tablesList as $tableName) {
            $sqlDump.= $iaDbControl->makeStructureBackup($tableName, true) . PHP_EOL;
            in_array(str_replace(INTELLI_DBPREFIX, '', $tableName), $onlyStructureExceptions) || $sqlDump.= $iaDbControl->makeDataBackup($tableName) . PHP_EOL;
            $sqlDump.= PHP_EOL;
        }

        $result = (bool)file_put_contents($this->_dumpFilePath, $sqlDump);
        $result || ($this->messages[] = 'Could not write DB dump file.');

        return $result;
    }

    protected function _compressFiles()
    {
        if (!class_exists('ZipArchive')) {
            $this->messages[] = 'ZIP extension is not available. Could not continue.';
            return false;
        }

        $zip = new ZipArchive();

        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
        $zh = $zip->open($this->filePath, ZipArchive::CREATE);

        if ($zh === true) {
            $stack = [IA_HOME];
            $cutFrom = strrpos(IA_HOME, IA_DS) + 1;

            while ($stack) {
                $currentDir = array_pop($stack);
                $localDir = substr($currentDir, $cutFrom);
                $files = [];

                $directory = dir($currentDir);
                while (false !== ($node = $directory->read())) {
                    if ($node == '..' || $node == '.') {
                        continue;
                    }
                    if (is_dir($currentDir . $node)) {
                        if (empty($localDir) && !in_array($node, $this->_foldersList)) {
                            continue;
                        }
                        array_push($stack, $currentDir . $node . IA_DS);
                    }
                    if (is_file($currentDir . $node)) {
                        if (empty($localDir) && !in_array($node, $this->_filesList)) {
                            continue;
                        }
                        $files[] = $node;
                    }
                }

                $zip->addEmptyDir($localDir);
                foreach ($files as $file) {
                    $zip->addFile($currentDir . $file, $localDir . $file);
                }
            }

            // finally, add the SQL dump file
            $zip->addEmptyDir('backup');
            $zip->addFile($this->_dumpFilePath, 'backup/' . basename($this->_dumpFilePath));

            if (!$zip->close()) {
                $message = 'Could not finalize ZIP archive. ';
                $message.= method_exists($zip, 'getStatusString')
                    ? 'ZIP engine error: ' . $zip->getStatusString()
                    : 'ZIP engine error code: ' . $zip->status;

                $this->messages[] = $message;
            }
        } else {
            $this->messages[] = 'ZIP file creation error.';
        }

        return (true === $zh && file_exists($this->filePath));
    }

    public function save()
    {
        $result1 = $this->_prepareDbDump();
        $result2 = $this->_compressFiles();

        return ($result1 && $result2);
    }

    public function restore($backupFile)
    {
        $result1 = $this->_decompressFiles($backupFile);
        $result2 = $this->_applyDbDump($backupFile);

        return ($result1 && $result2);
    }
}
