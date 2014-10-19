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

class iaBackendController extends iaAbstractControllerBackend
{
	protected $_name = 'languages';

	protected $_gridColumns = "`id`, `key`, `original`, `value`, `code`, `category`, IF(`original` != `value`, 1, 0) `modified`, 1 `delete`";
	protected $_gridFilters = array('key' => 'like', 'value' => 'like', 'category' => 'equal', 'extras' => 'equal');

	protected $_processAdd = false;
	protected $_processEdit = false;

	protected $_phraseAddSuccess = 'phrase_added';


	public function __construct()
	{
		parent::__construct();

		$this->setTable(iaLanguage::getTable());

		$iaCache = $this->_iaCore->factory('cache');
		$this->setHelper($iaCache);
	}

	protected function _gridRead($params)
	{
		$output = array();
		$iaDb = &$this->_iaDb;

		switch ($params['get'])
		{
			case 'plugins':
				if ($plugins = $this->_iaDb->onefield('name', null, null, null, 'extras'))
				{
					foreach ($plugins as $plugin)
					{
						$output['data'][] = array('value' => $plugin, 'title' => iaLanguage::get($plugin, ucfirst($plugin)));
					}
				}

				break;

			case 'comparison':
				if (isset($params['lang1']) && isset($params['lang2']) && $params['lang1'] != $params['lang2']
					&& array_key_exists($params['lang1'], $this->_iaCore->languages) && array_key_exists($params['lang2'], $this->_iaCore->languages))
				{
					$start = isset($params['start']) ? (int)$params['start'] : 0;
					$limit = isset($params['limit']) ? (int)$params['limit'] : 15;

					$values = array();

					if (!empty($params['key']))
					{
						$conditions[] = '`key` LIKE :key OR `value` LIKE :key';
						$values['key'] = '%' . $params['key'] . '%';
					}

					if (!empty($params['category']))
					{
						$conditions[] = '`category` = :category';
						$values['category'] = $params['category'];
					}

					if (!empty($params['plugin']))
					{
						$conditions[] = '`extras` = :plugin';
						$values['plugin'] = $params['plugin'];
					}

					$where = empty($conditions) ? iaDb::EMPTY_CONDITION : implode(' AND ', $conditions);
					$iaDb->bind($where, $values);

					$rows = $iaDb->all('SQL_CALC_FOUND_ROWS DISTINCT `key`, `category`', $where, $start, $limit);
					$output = array('data' => array(), 'total' => $iaDb->foundRows());

					$keys = array();
					foreach ($rows as $row)
					{
						$keys[] = $row['key'];
					}

					$stmt = "`code` = ':lang' AND `key` IN('" . implode("','", $keys) . "')";

					$lang1 = $iaDb->keyvalue(array('key', 'value'), iaDb::printf($stmt, array('lang' => $params['lang1'])));
					$lang2 = $iaDb->keyvalue(array('key', 'value'), iaDb::printf($stmt, array('lang' => $params['lang2'])));

					foreach ($rows as $row)
					{
						$key = $row['key'];
						$output['data'][] = array(
							'key' => $key,
							'lang1' => isset($lang1[$key]) ? $lang1[$key] : null,
							'lang2' => isset($lang2[$key]) ? $lang2[$key] : null,
							'category' => $row['category']
						);
					}
				}

				break;

			default:
				$output = parent::_gridRead($params);
		}

		return $output;
	}

	protected function _modifyGridParams(&$conditions, &$values)
	{
		if (isset($_GET['lang']) && $_GET['lang'] && array_key_exists($_GET['lang'], $this->_iaCore->languages))
		{
			$conditions[] = '`code` = :language';
			$values['language'] = $_GET['lang'];
		}
	}

	protected function _jsonAction(&$iaView)
	{
		$error = false;
		$output = array('message' => iaLanguage::get('invalid_parameters'), 'success' => false);

		if (empty($_POST['key']))
		{
			$error = true;
			$output['message'] = iaLanguage::get('incorrect_key');
		}

		if (empty($_POST['value']))
		{
			$error = true;
			$output['message'] = iaLanguage::get('incorrect_value');
		}

		if (!$error)
		{
			$lang = (isset($_POST['language']) && array_key_exists($_POST['language'], $this->_iaCore->languages))
				? $_POST['language']
				: $iaView->language;
			$key = iaSanitize::paranoid($_POST['key']);
			$value = $_POST['value'];
			$category = iaSanitize::paranoid($_POST['category']);

			if (empty($key))
			{
				$error = true;
				$output['message'] = iaLanguage::get('key_not_valid');
			}

			if (empty($value))
			{
				$error = true;
				$output['message'] = iaLanguage::get('incorrect_value');
			}

			if ($this->_iaDb->exists('`key` = :key AND `code` = :language AND `category` = :category', array('key' => $key, 'language' => $lang, 'category' => $category)))
			{
				$error = true;
				$output['message'] = iaLanguage::get('key_exists');
			}
		}

		if (!$error)
		{
			$output['success'] = (bool)$this->_iaDb->insert(array('key' => $key, 'original' => $value, 'value' => $value, 'code' => $lang, 'category' => $category));
			$output['message'] = iaLanguage::get($output['success'] ? $this->_phraseAddSuccess : $this->_phraseSaveError);

			if ($output['success'])
			{
				$this->getHelper()->createJsCache(true);
			}
		}

		return $output;
	}

	protected function _gridDelete($params)
	{
		$output = parent::_gridDelete($params);

		if ($output['result'])
		{
			$this->getHelper()->createJsCache(true);
		}

		return $output;
	}

	protected function _gridUpdate($params)
	{
		$output = array(
			'result' => false,
			'message' => iaLanguage::get('invalid_parameters')
		);

		$params = $_POST;

		if (isset($params['id']) && $params['id'])
		{
			$stmt = '`id` IN (' . implode($params['id']) . ')';

			unset($params['id']);
		}
		elseif (isset($params['key']))
		{
			$stmt = '`key` = :key';
			empty($params['lang']) || $stmt.= ' AND `code` = :lang';
			$this->_iaDb->bind($stmt, $params);

			unset($params['key'], $params['lang']);
		}

		if (isset($stmt))
		{
			$output['result'] = (bool)$this->_iaDb->update($params, $stmt);
			$output['message'] = iaLanguage::get($output['result'] ? $this->_phraseEditSuccess : $this->_phraseSaveError);

			if ($output['result'])
			{
				$this->getHelper()->createJsCache(true);
			}
		}

		return $output;
	}

	protected function _indexPage(&$iaView)
	{
		parent::_indexPage($iaView);
		$iaView->display($this->getName());

		$action = isset($this->_iaCore->requestPath[0]) ? $this->_iaCore->requestPath[0] : 'list';
		switch ($action)
		{
			case 'phrases':
				$pageCaption = iaLanguage::get('phrase_manager');
				break;

			case 'search':
				$pageCaption = iaLanguage::get('search_in_phrases');
				break;

			case 'download':
				$pageCaption = iaLanguage::get('export_language');

				if ((isset($_POST['lang']) && $_POST['lang'])
					|| (isset($this->_iaCore->requestPath[1])
						&& array_key_exists($this->_iaCore->requestPath[1], $this->_iaCore->languages)))
				{
					$this->_downloadLanguage($iaView);
				}

				break;

			case 'comparison':
				$pageCaption = iaLanguage::get('languages_comparison');

				$this->_compareLanguages($iaView);

				break;

			case 'copy':
				$pageCaption = iaLanguage::get('copy_language');

				$this->_copyLanguage($iaView);

				break;

			case 'rm':
				// TODO: set checkAccess
				$this->_removeLanguage($iaView);
				iaUtil::go_to($this->getPath());

				break;

			case 'default':
				$this->_setLanguageAsDefault($iaView);
				iaUtil::go_to($this->getPath());

				break;

			case 'import':
				$result = $this->_importLanguage($iaView);
				iaUtil::go_to($this->getPath() . ($result ? '' : 'download/'));
		}

		if (isset($pageCaption))
		{
			iaBreadcrumb::toEnd($pageCaption, IA_SELF);
			$iaView->title($pageCaption);
		}

		$iaView->assign('action', $action);
	}


	private function _importLanguage(&$iaView)
	{
		if (!isset($_POST['form-import']))
		{
			return false;
		}

		list($result, $messages, $languageCode) = self::_importDump($this->_iaDb);
		if ($result)
		{
			$this->_iaCore->languages[$languageCode] = $_POST['title'];
			$this->_iaCore->set('languages', serialize($this->_iaCore->languages), true);

			$this->getHelper()->clearAll();
		}

		$iaView->setMessages($messages, $result ? iaView::SUCCESS : iaView::ERROR);

		return $result;
	}

	private function _setLanguageAsDefault(&$iaView)
	{
		if (isset($this->_iaCore->requestPath[1]) && array_key_exists($this->_iaCore->requestPath[1], $this->_iaCore->languages))
		{
			$this->_iaCore->set('lang', $this->_iaCore->requestPath[1], true);
			$this->getHelper()->clearAll();

			$iaView->setMessages(iaLanguage::get('saved'), iaView::SUCCESS);
		}
		else
		{
			$iaView->setMessages(iaLanguage::get('invalid_parameters'));
		}
	}

	private function _removeLanguage(&$iaView)
	{
		if (!isset($this->_iaCore->requestPath[1])
			|| $this->_iaCore->get('lang') == $this->_iaCore->requestPath[1])
		{
			return;
		}

		$language = $this->_iaCore->requestPath[1];

		$this->_iaDb->delete('`code` = :language', null, array('language' => $language));

		$languages = $this->_iaCore->languages;
		unset($languages[$language]);
		$this->_iaCore->set('languages', serialize($languages), true);

		$iaView->setMessages(iaLanguage::get($this->_phraseGridEntryDeleted), iaView::SUCCESS);

		$this->getHelper()->clearAll();
	}

	private function _downloadLanguage(&$iaView)
	{
		$language = isset($_POST['lang']) ? iaSanitize::paranoid($_POST['lang']) : $this->_iaCore->requestPath[1];
		$format = isset($_POST['file_format']) && in_array($_POST['file_format'], array('csv', 'sql')) ? $_POST['file_format'] : 'sql';

		$phrases = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`code` = '" . $language . "'");
		$fileName = urlencode(isset($_POST['filename']) ? $_POST['filename'] . '.' . $format : 'subrion_' . IA_VERSION . '_' . $this->_iaCore->requestPath[1] . '.' . $format);

		header('Content-Type: text/plain; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $fileName . '"');

		$stream = fopen('php://output', 'w');

		if ('sql' == $format)
		{
			fwrite($stream, 'INSERT INTO `{prefix}language` (`id`, `key`, `original`, `value`, `category`, `code`, `extras`) VALUES' . PHP_EOL);
		}

		foreach ($phrases as $i => $entry)
		{
			switch ($format)
			{
				case 'sql':
					$data = '(';
					foreach ($entry as $key => $value)
					{
						$data .= $value
							? ('id' == $key) ? 'NULL' : "'" . iaSanitize::sql($value) . "'"
							: "''";
						$data .= ', ';
					}
					$data = substr($data, 0, -2);
					$data .= isset($phrases[$i + 1])
						? '),' . PHP_EOL
						: ');';

					fwrite($stream, $data);

					break;

				default:
					unset($entry['id']);

					$entry['value'] = str_replace(array("\r\n", "\r", "\n"), '\n', $entry['value']);
					$entry['original'] = str_replace(array("\r\n", "\r", "\n"), '\n', $entry['original']);

					fputcsv($stream, $entry, '|', '"');
			}
		}

		fclose($stream);

		$iaView->set('nodebug', true);

		exit;
	}

	private function _compareLanguages(&$iaView)
	{
		if (count($this->_iaCore->languages) > 1)
		{
			$languages = array_keys($this->_iaCore->languages);

			$lang1 = isset($_GET['l1']) && in_array($_GET['l1'], $languages)
				? $_GET['l1']
				: $languages[0];
			$lang2 = isset($_GET['l2']) && in_array($_GET['l2'], $languages)
				? $_GET['l2']
				: $languages[1];

			$iaView->assign('lang1', $lang1);
			$iaView->assign('lang2', $lang2);
		}
		else
		{
			$iaView->setMessages(iaLanguage::get('impossible_to_compare_single_language'));
		}
	}

	private function _copyLanguage(&$iaView)
	{
		if (!isset($_POST['form-copy']))
		{
			return;
		}

		$messages = array();

		if (empty($_POST['title']) || strlen(trim($_POST['title'])) == 0)
		{
			$messages[] = iaLanguage::get('title_incorrect');
		}

		if (preg_match('/^[a-z]{2}$/i', $_POST['code']))
		{
			if (array_key_exists($_POST['code'], $this->_iaCore->languages))
			{
				$messages[] = iaLanguage::get('language_already_exists');
			}
		}
		else
		{
			$messages[] = iaLanguage::get('bad_iso_code');
		}

		if (empty($messages))
		{
			$counter = 0;
			$languageCode = strtolower($_POST['code']);
			$rows = $this->_iaDb->all(array('key', 'value', 'category', 'extras'), "`code` = '" . $iaView->language . "'");
			foreach ($rows as $value)
			{
				$row = array(
					'key' => $value['key'] ,
					'value' => $value['value'],
					'extras' => $value['extras'],
					'code' => $languageCode,
					'category' => $value['category']
				);

				if ($this->_iaDb->insert($row))
				{
					$counter++;
				}
			}

			$iaView->setMessages(iaLanguage::getf('language_copied', array('count' => $counter)), iaView::SUCCESS);

			$this->_iaCore->languages[$languageCode] = $_POST['title'];
			$this->_iaCore->set('languages', serialize($this->_iaCore->languages), true);

			$this->_iaDb->update(null, iaDb::convertIds($languageCode, 'code'), array('original' => '`value`'));

			$this->getHelper()->clearAll();

			iaUtil::go_to($this->getPath());
		}

		$iaView->setMessages($messages, $messages ? iaView::ERROR : iaView::SUCCESS);
	}


	private static function _importDump(&$iaDb)
	{
		$filename = $_FILES ? $_FILES['language_file']['tmp_name'] : $_POST['language_file2'];
		$format = isset($_POST['format']) && in_array($_POST['format'], array('csv', 'sql')) ? $_POST['format'] : 'sql';

		$error = false;
		$messages = array();

		if (empty($filename))
		{
			$error = true;
			$messages[] = iaLanguage::get('choose_import_file');
		}
		elseif (!($f = fopen($filename, 'r')))
		{
			$error = true;
			$messages[] = iaLanguage::getf('cant_open_sql', array('filename' => $filename));
		}

		if ($format == 'csv' && isset($_POST['title']) && trim($_POST['title']) == '')
		{
			$error = true;
			$messages[] = iaLanguage::get('title_is_empty');
		}

		if (!$error)
		{
			$error = true;
			$languageCode = '';

			if ('sql' == $format)
			{
				$sql = '';

				while ($s = fgets($f, 10240))
				{
					$s = trim ($s);
					if ($s[0] == '#' || $s[0] == '') continue;
					$sql .= $s;
					if ($s[strlen($s) - 1] != ';') continue;
					$sql = str_replace('{prefix}', $iaDb->prefix, $sql);
					$iaDb->query($sql);
					if (empty($languageCode))
					{
						$matches = array();
						if (preg_match('#, \'([a-z]{2})\', \'#', $sql, $matches) || preg_match('#,\'([a-z]{2})\',\'#', $sql, $matches))
						{
							$languageCode = $matches[1];
						}
					}
					$sql = '';
				}

				fclose($f);

				$error = false;
			}

			if ('csv' == $format)
			{
				if ($csvContent = file($filename))
				{
					$array = array();

					foreach ($csvContent as $i=>$row)
					{
						if (empty($row)) break;

						$fields = explode('|', trim($row));

						if (count($fields) != 6) break;

						$fields = array_map(array('iaSanitize', 'sql'), $fields);

						$languageCode = isset($fields[4]) ? $fields[4] : null;
						$array[] = "('" . implode("','", $fields) . "')";
					}

					if (count($fields) == 6 && strlen($languageCode) == 2)
					{
						$error = false;

						$sql = "INSERT INTO `{$iaDb->prefix}language` (`key`, `original`, `value`, `category`, `code`, `extras`) VALUES ";
						$sql .= implode(',', $array);
						$sql .= ';';

						$iaDb->query($sql);
					}
				}
			}

			$messages[] = iaLanguage::get($error ? 'incorrect_file_format' : 'saved');
		}

		return array(!$error, $messages, isset($languageCode) ? $languageCode : null);
	}
}