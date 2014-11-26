<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2014 Intelliants, LLC <http://www.intelliants.com>
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
 * @link http://www.subrion.org/
 *
 ******************************************************************************/

class iaCache extends abstractUtil
{
	protected $_cachingEnabled;

	protected $_savePath = IA_CACHEDIR;

	protected $_filePath;


	public function init()
	{
		parent::init();

		$this->_cachingEnabled = true;
		if (!file_exists($this->_savePath))
		{
			iaCore::instance()->util()->makeDirCascade($this->_savePath, 0777);
		}

		$mask = !function_exists('posix_getuid') || function_exists('posix_getuid') && posix_getuid() != fileowner(IA_HOME . 'index' . iaSystem::EXECUTABLE_FILE_EXT) ? 0777 : 0755;
		chmod($this->_savePath, $mask);
	}

	/**
	 * Retrieve cache file and return either an object or a string
	 *
	 * @param string $fileName cache file name to get data from
	 * @param int $seconds number of seconds until file is considered old
	 * @param bool $isObject true - return an object, false - return an array
	 *
	 * @return bool|mixed|string
	 */
	public function get($fileName, $seconds = 0, $isObject = false)
	{
		$this->_filePath = $this->_savePath . str_replace('.', '', $fileName) . '.inc';

		if (!$this->_cachingEnabled || !is_file($this->_filePath))
		{
			return false;
		}

		if ($seconds == 0)
		{
			$return = $this->_read();
		}
		else
		{
			if (isset($_SERVER['REQUEST_TIME']) && filemtime($this->_filePath) > ($_SERVER['REQUEST_TIME'] - $seconds))
			{
				$return = $this->_read();
			}
			else
			{
				$this->remove($fileName);

				return false;
			}
		}
		if ($isObject && $return)
		{
			$return = unserialize($return);
		}

		return $return;
	}

	/**
	 * Write data to cache files
	 *
	 * @param string $file file name to write data to (may be encrypted)
	 * @param string $Data data to be written
	 *
	 * @return bool
	 */
	public function write($file, $Data)
	{
		if (!$this->_cachingEnabled)
		{
			return true;
		}
		if (is_array($Data) || is_object($Data))
		{
			$Data = serialize($Data);
		}
		$this->_filePath = $this->_savePath . str_replace('.', '', $file) . '.inc';

		if (!$file = fopen($this->_filePath, 'wb'))
		{
			trigger_error('Cache::write(): Could not open file for writing.', E_USER_WARNING);

			return false;
		}

		if (flock($file, LOCK_EX))
		{
			$len_data = strlen($Data);
			fwrite($file, $Data, $len_data);
			flock($file, LOCK_UN);
		}
		else
		{
			trigger_error('Cache::write(): Could not LOCK the file ' . $this->_filePath . ' for writing.', E_USER_WARNING);

			return false;
		}
		fclose($file);

		return true;
	}

	/**
	 * Delete cache file from the cache directory
	 *
	 * @param string $fileName file name to be deleted
	 *
	 * @return bool
	 */
	public function remove($fileName)
	{
		$this->_filePath = $this->_savePath . $fileName;

		$iaView = &$this->iaCore->iaView;
		$iaView->loadSmarty(true);

		if (!file_exists($this->_filePath))
		{
			$iaView->iaSmarty->clearCache(null);
			clearstatcache();

			return true;
		}
		else
		{
			if (unlink($this->_filePath))
			{
				$iaView->iaSmarty->clearCache(null);
				clearstatcache();

				return true;
			}
			else
			{
				trigger_error(__CLASS__ . 'Unable to remove from cache file', E_USER_NOTICE);

				return false;
			}
		}
	}

	/**
	 * Read the local file from cache directory
	 *
	 * @return bool|string
	 */
	protected function _read()
	{
		if (false === ($return_data = file_get_contents($this->_filePath)))
		{
			trigger_error(__CLASS__ . '::_read(): Unable to read file(' . $this->_filePath . ' contents', E_USER_WARNING);

			return false;
		}

		return $return_data;
	}

	public function clearAll()
	{
		$this->iaCore->getConfig(true);
		$this->iaCore->setPackagesData(true);
		$this->createJsCache(true);
	}

	public function clearGlobalCache()
	{
		$this->clearAll();
		$this->_cascadeRemoveFiles(IA_TMP, true);
	}

	public function _cascadeRemoveFiles($directory, $removeDirectories = true)
	{
		if (substr($directory, -1) == IA_DS)
		{
			$directory = substr($directory, 0, -1);
		}
		if (!file_exists($directory) || !is_dir($directory))
		{
			return false;
		}
		elseif (is_readable($directory))
		{
			$handle = opendir($directory);

			while ($item = readdir($handle))
			{
				if ($item != '.' && $item != '..' && $item != '.htaccess')
				{
					$path = $directory . IA_DS . $item;

					if (is_dir($path))
					{
						$this->_cascadeRemoveFiles($path, true);
					}
					else
					{
						iaUtil::deleteFile($path);
					}
				}
			}
			closedir($handle);

			if ($removeDirectories)
			{
				$objects = scandir($directory);
				foreach ($objects as $object)
				{
					if ($object != '.' && $object != '..')
					{
						if (filetype($directory . IA_DS . $object) == 'dir')
						{
							rmdir($directory . IA_DS . $object);
						}
					}
				}
				reset($objects);
			}
		}

		return true;
	}

	public function createJsCache($forceRebuild = false)
	{
		$currentLanguage = $this->iaCore->iaView->language;

		$fileList = array(
			'lang' => "intelli.lang.{$currentLanguage}.js",
			'admin_lang' => "intelli.admin.lang.{$currentLanguage}.js",
			'config' => 'intelli.config.js',
		);

		foreach ($fileList as $type => $file)
		{
			$file = $this->_savePath . $file;
			$bool = false;
			if (is_array($forceRebuild))
			{
				if (in_array($type, $forceRebuild))
				{
					$bool = true;
				}
			}
			else
			{
				$bool = $forceRebuild;
			}

			if ($bool || !file_exists($file))
			{
				$this->_createJsFile($file, $type);
			}
		}
	}

	protected function _createJsFile($file, $type = 'config')
	{
		$this->iaCore->factory('util'); // required in order the class iaUtil to be loaded

		switch ($type)
		{
			case 'lang':
			case 'admin_lang':
				$stmt = "`code` = :lang AND `category` NOT IN ('tooltip', 'page', :category)";
				$this->iaCore->iaDb->bind($stmt, array('lang' => $this->iaCore->iaView->language, 'category' => $type == 'admin_lang' ? 'frontend' : iaCore::ADMIN));

				$phrases = $this->iaCore->iaDb->keyvalue(array('key', 'value'), $stmt, iaLanguage::getTable());
				$languagesList = iaUtil::jsonEncode(unserialize($this->iaCore->get('languages')));

				$fileContent = 'intelli.' . ($type == 'admin_lang' ? 'admin.' : '') . 'lang = '
					. iaUtil::jsonEncode($phrases) . ';'
					. 'intelli.languages = ' . $languagesList . ';';
				break;

			case 'config':
				$iaDb = &$this->iaCore->iaDb;

				$stmt = "`private` = 0 && `type` != 'divider' && `config_group` != 'email_templates'";
				$config = $iaDb->keyvalue(array('name', 'value'), $stmt, iaCore::getConfigTable());

				if (file_exists(IA_INCLUDES . 'custom.inc.php'))
				{
					include IA_INCLUDES . 'custom.inc.php';
				}

				$config['ia_url'] = IA_CLEAR_URL;
				$config['admin_url'] = IA_URL . $this->iaCore->get('admin_page');
				$config['tpl_url'] = IA_FRONT_TEMPLATES . $this->iaCore->iaView->theme . IA_URL_DELIMITER;
				$config['packages'] = $this->iaCore->setPackagesData();
				$config['items'] = array();
				$config['extras'] = array(array('core', iaLanguage::get('core', 'Core')));

				$array = $iaDb->all(array('name', 'title'), "`status` = 'active' ORDER BY `type`", null, null, 'extras');
				foreach ($array as $item)
				{
					$config['extras'][] = array($item['name'], $item['title']);
				}

				$array = $iaDb->onefield('`item`', "`item` != 'transactions'", null, null, 'items');
				foreach ($array as $item)
				{
					$config['items'][] = array($item, iaLanguage::get($item, $item));
				}

				$fileContent = 'intelli.config = ' . iaUtil::jsonEncode($config) . ';';
		}

		if (isset($fileContent))
		{
			if ($fh = fopen($file, 'w'))
			{
				fwrite($fh, $fileContent);
				fclose($fh);
			}
		}
	}
}