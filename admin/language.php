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

$iaDb->setTable(iaLanguage::getTable());

$iaCache = $iaCore->factory('cache');

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	$iaGrid = $iaCore->factory('grid', iaCore::ADMIN);

	switch ($pageAction)
	{
		case iaCore::ACTION_READ:
			if ('plugins' == $_GET['get'])
			{
				$output = array();

				if ($plugins = $iaDb->onefield('name', null, null, null, 'extras'))
				{
					foreach ($plugins as $plugin)
					{
						$output['data'][] = array('value' => $plugin, 'title' => iaLanguage::get($plugin, ucfirst($plugin)));
					}
				}

				break;
			}

			$conditions = array();

			if ('comparison' == $_GET['get'])
			{
				if (isset($_GET['lang1']) && isset($_GET['lang2']) && $_GET['lang1'] != $_GET['lang2']
					&& array_key_exists($_GET['lang1'], $iaCore->languages) && array_key_exists($_GET['lang2'], $iaCore->languages))
				{
					$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
					$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;

					$values = array();

					if (isset($_GET['key']) && $_GET['key'])
					{
						$conditions[] = '`key` LIKE :key OR `value` LIKE :key';
						$values['key'] = '%' . $_GET['key'] . '%';
					}

					if (isset($_GET['category']) && $_GET['category'])
					{
						$conditions[] = '`category` = :category';
						$values['category'] = $_GET['category'];
					}

					if (isset($_GET['plugin']) && $_GET['plugin'])
					{
						$conditions[] = '`extras` = :plugin';
						$values['plugin'] = $_GET['plugin'];
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

					$lang1 = $iaDb->keyvalue(array('key', 'value'), iaDb::printf($stmt, array('lang' => $_GET['lang1'])));
					$lang2 = $iaDb->keyvalue(array('key', 'value'), iaDb::printf($stmt, array('lang' => $_GET['lang2'])));

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
			}

			if (isset($_GET['lang']) && $_GET['lang'] && array_key_exists($_GET['lang'], $iaCore->languages))
			{
				$stmt = '`code` = :language';
				$iaDb->bind($stmt, array('language' => $_GET['lang']));

				$conditions[] = $stmt;
			}

			$output = $iaGrid->gridRead($_GET,
				"`id`, `key`, `original`, `value`, `code`, `category`, IF(`original` != `value`, 1, 0) `modified`, 1 `delete`",
				array('key' => 'like', 'value' => 'like', 'category' => 'equal', 'extras' => 'equal'),
				$conditions
			);

			break;

		case iaCore::ACTION_EDIT:
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
				$iaDb->bind($stmt, $params);

				unset($params['key'], $params['lang']);
			}

			if (isset($stmt))
			{
				$output['result'] = (bool)$iaDb->update($params, $stmt);
				$output['message'] = iaLanguage::get($output['result'] ? 'saved' : 'db_error');

				if ($output['result'])
				{
					$iaCache->createJsCache(true);
				}
			}

			break;

		case iaCore::ACTION_DELETE:
			$output = $iaGrid->gridDelete($_POST);

			if ($output['result'])
			{
				$iaCache->createJsCache(true);
			}

			break;

		case iaCore::ACTION_ADD:
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
				$lang = (isset($_POST['language']) && array_key_exists($_POST['language'], $iaCore->languages))
					? $_POST['language']
					: $iaView->language;
				$key = preg_replace("#[^a-z0-9_]#", '', $_POST['key']);
				$value = iaSanitize::sql($_POST['value']);
				$category = preg_replace("#[^a-z0-9_]#", '', $_POST['category']);

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

				if ($iaDb->exists('`key` = :key AND `code` = :language AND `category` = :category', array('key' => $key, 'language' => $lang, 'category' => $category)))
				{
					$error = true;
					$output['message'] = iaLanguage::get('key_exists');
				}
			}

			if (!$error)
			{
				$output['success'] = (bool)$iaDb->insert(array('key' => $key, 'value' => $value, 'code' => $lang, 'category' => $category));
				$output['message'] = iaLanguage::get($output['success'] ? 'phrase_added' : 'db_error');
				$error = !$output['success'];
				$error || $iaCache->createJsCache(true);
			}
	}

	$iaView->assign($output);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$action = isset($iaCore->requestPath[0]) ? $iaCore->requestPath[0] : 'list';
	$url = IA_ADMIN_URL . 'language';

	$iaView->grid('admin/language');

	$iaView->display('language');

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
				|| (isset($iaCore->requestPath[1]) && array_key_exists($iaCore->requestPath[1], $iaCore->languages)))
			{
				$language = isset($_POST['lang']) ? iaSanitize::paranoid($_POST['lang']) : $iaCore->requestPath[1];
				$format = isset($_POST['file_format']) && in_array($_POST['file_format'], array('csv', 'sql')) ? $_POST['file_format'] : 'sql';

				$phrases = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`code` = '" . $language . "'");
				$fileName = urlencode(isset($_POST['filename']) ? $_POST['filename'] . '.' . $format : 'subrion_' . IA_VERSION . '_' . $iaCore->requestPath[1] . '.' . $format);

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
							$entry['value'] = str_replace(PHP_EOL, '\r\n', $entry['value']);
							fputcsv($stream, $entry, '|', '"');
					}
				}

				fclose($stream);

				$iaView->set('nodebug', true);

				exit;
			}

			break;

		case 'comparison':
			$pageCaption = iaLanguage::get('languages_comparison');

			if (count($iaCore->languages) > 1)
			{
				$languages = array_keys($iaCore->languages);

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
				$iaView->setMessages(iaLanguage::get('impossible_to_compare_single_language'), iaView::ERROR);
			}

			break;

		case 'copy':
			$pageCaption = iaLanguage::get('copy_language');

			if (isset($_POST['form-copy']))
			{
				$messages = array();

				if (empty($_POST['title']) || strlen(trim($_POST['title'])) == 0)
				{
					$messages[] = iaLanguage::get('title_incorrect');
				}

				if (preg_match('/^[a-z]{2}$/i', $_POST['code']))
				{
					if (array_key_exists($_POST['code'], $iaCore->languages))
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
					$rows = $iaDb->all(array('key', 'value', 'category', 'extras'), "`code` = '" . $iaView->language . "'");
					foreach ($rows as $value)
					{
						$row = array(
							'key' => $value['key'] ,
							'value' => $value['value'],
							'extras' => $value['extras'],
							'code' => $languageCode,
							'category' => $value['category']
						);

						if ($iaDb->insert($row))
						{
							$counter++;
						}
					}

					$iaView->setMessages(iaLanguage::getf('language_copied', array('count' => $counter)), iaView::SUCCESS);

					$iaCore->languages[$languageCode] = $_POST['title'];
					$iaCore->set('languages', serialize($iaCore->languages), true);

					$iaDb->update(null, "`code` = '$languageCode'", array('original' => '`value`'));

					$iaCache->clearAll();

					iaUtil::go_to($url);
				}

				$iaView->setMessages($messages, $messages ? iaView::ERROR : iaView::SUCCESS);
			}

			break;

		case 'rm':
			// TODO: set checkAccess
			if (isset($iaCore->requestPath[1]) && $iaCore->get('lang') != $iaCore->requestPath[1])
			{
				$language = $iaCore->requestPath[1];

				$iaDb->delete('`code` = :language', null, array('language' => $language));

				$languages = $iaCore->languages;
				unset($languages[$language]);
				$iaCore->set('languages', serialize($languages), true);

				$iaView->setMessages(iaLanguage::get('language_deleted'), iaView::SUCCESS);

				$iaCache->clearAll();
			}

			iaUtil::go_to($url);

			break;

		case 'default':
			if (isset($iaCore->requestPath[1]) && array_key_exists($iaCore->requestPath[1], $iaCore->languages))
			{
				$iaCore->set('lang', $iaCore->requestPath[1], true);
				$iaCache->clearAll();

				$iaView->setMessages(iaLanguage::get('saved'), iaView::SUCCESS);
			}
			else
			{
				$iaView->setMessages(iaLanguage::get('invalid_parameters'), iaView::ERROR);
			}

			iaUtil::go_to($url);

			break;

		case 'import':
			if (isset($_POST['form-import']))
			{
				list($result, $messages, $languageCode) = dumpImport($iaDb);
				if ($result)
				{
					$iaCore->languages[$languageCode] = $_POST['title'];
					$iaCore->set('languages', serialize($iaCore->languages), true);

					$iaCache->clearAll();
				}
				$iaView->setMessages($messages, $result ? iaView::SUCCESS : iaView::ERROR);
			}

			iaUtil::go_to($url);
	}

	if (isset($pageCaption))
	{
		iaBreadcrumb::toEnd($pageCaption, IA_SELF);
		$iaView->title($pageCaption);
	}

	$iaView->assign('action', $action);
}



function dumpImport($iaDb)
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
					if (preg_match('#, \'([a-z]{2})\', \'#', $sql, $matches))
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

				foreach ($csvContent as $row)
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