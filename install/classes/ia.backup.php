<?php
//##copyright##

class iaBackup
{
	protected $_foldersList = array(
		'admin',
		'front',
		'includes',
		'install',
		'js',
		'packages',
		'plugins',
		'templates'
	);

	protected $_filesList = array(
		'.htaccess',
		'changelog.txt',
		'favicon.ico',
		'index.php',
		'license.txt',
		'robots.txt'
	);

	protected $dumpFilePath;

	public $filePath;
	public $messages = array();


	public  function __construct()
	{
		$this->_dumpFilePath = IA_TMP . 'data_backup_' . IA_VERSION . '.sql';
		$this->filePath = IA_HOME . 'backup/' . sprintf('backup_%s_%s.zip', IA_VERSION, date('Y-m-d'));
	}

	protected function _decompressFiles($backupFile)
	{
		if (!class_exists('ZipArchive'))
		{
			$this->messages[] = 'ZIP extension is not available. Could not continue.';
			return false;
		}

		$zip = new ZipArchive();
		$result = $zip->open($backupFile);

		if (true === $result)
		{
			$zip ->extractTo(IA_HOME);
			if ($zip->close())
			{
				return true;
			}

			$message = 'Could not extract from backup file. ';
			$message.= method_exists($zip, 'getStatusString')
				? 'ZIP engine error: ' . $zip->getStatusString()
				: 'ZIP engine error code: ' . $zip->status;

			$this->messages[] = $message;
		}
		else
		{
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
		if (file_exists($this->_dumpFilePath) && filesize($this->_dumpFilePath) > 0)
		{
			return true;
		}

		$iaDbControl = iaHelper::loadCoreClass('dbcontrol');

		$tablesList = $iaDbControl->getTables();
		if (empty($tablesList))
		{
			$this->messages[] = 'Could not get the list of DB tables to dump.';
			return false;
		}

		$sqlDump = '';
		$onlyStructureExceptions = array('online');
		foreach ($tablesList as $tableName)
		{
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
		if (!class_exists('ZipArchive'))
		{
			$this->messages[] = 'ZIP extension is not available. Could not continue.';
			return false;
		}

		$zip = new ZipArchive();

		if (file_exists($this->filePath))
		{
			unlink($this->filePath);
		}
		$zh = $zip->open($this->filePath, ZipArchive::CREATE);

		if ($zh === true)
		{
			$stack = array(IA_HOME);
			$cutFrom = strrpos(IA_HOME, IA_DS) + 1;

			while ($stack)
			{
				$currentDir = array_pop($stack);
				$localDir = substr($currentDir, $cutFrom);
				$files = array();

				$directory = dir($currentDir);
				while (false !== ($node = $directory->read()))
				{
					if ($node == '..' || $node == '.')
					{
						continue;
					}
					if (is_dir($currentDir . $node))
					{
						if (empty($localDir) && !in_array($node, $this->_foldersList))
						{
							continue;
						}
						array_push($stack, $currentDir . $node . IA_DS);
					}
					if (is_file($currentDir . $node))
					{
						if (empty($localDir) && !in_array($node, $this->_filesList))
						{
							continue;
						}
						$files[] = $node;
					}
				}

				$zip->addEmptyDir($localDir);
				foreach ($files as $file)
				{
					$zip->addFile($currentDir . $file, $localDir . $file);
				}
			}

			// finally, add the SQL dump file
			$zip->addEmptyDir('backup');
			$zip->addFile($this->_dumpFilePath, 'backup/' . basename($this->_dumpFilePath));

			if (!$zip->close())
			{
				$message = 'Could not finalize ZIP archive. ';
				$message.= method_exists($zip, 'getStatusString')
					? 'ZIP engine error: ' . $zip->getStatusString()
					: 'ZIP engine error code: ' . $zip->status;

				$this->messages[] = $message;
			}


		}
		else
		{
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