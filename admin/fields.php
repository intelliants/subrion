<?php
//##copyright##

$iaField = $iaCore->factory('field');

$iaDb->setTable(iaField::getTable());

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	switch ($pageAction)
	{
		case iaCore::ACTION_READ:
			$output = array();

			if (isset($_GET['get']) && 'groups' == $_GET['get'])
			{
				$output = $iaDb->all(array('id', 'name'), "`item` = '" . iaSanitize::sql($_GET['item']) . "'", null, null, iaField::getTableGroups());
			}
/*			elseif (isset($_GET['a']))
			{
				$ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : array();
				$item = isset($_GET['item']) ? " AND `item` = '{$_GET['item']}'" : '';
				$fields = $iaDb->all(array('id', 'item', 'name', 'extras', 'relation'), "`relation` != 'parent'" . $item);

				foreach ($fields as $c)
				{
					if ('fields' == $_GET['a'])
					{
						$item = array(
							'id' => $c['name'],
							'text' => iaLanguage::get('field_' . $c['name']),
							'leaf' => true,
							'cls' => 'none',
							'checked' => (in_array($c['name'], $ids) ? true : false),
						);
						$output[] = $item;
					}
				}
			}*/
			else
			{
				$iaGrid = $iaCore->factory('grid', iaCore::ADMIN);

				$output = $iaGrid->gridRead($_GET,
					array('name', 'item', 'group', 'fieldgroup_id', 'type', 'relation', 'length', 'order', 'status', 'delete' => 'editable'),
					array('status' => 'equal', 'id' => 'equal', 'item' => 'equal', 'relation' => 'equal')
				);

				if ($output['data'])
				{
					$groups = $iaDb->keyvalue(array('id', 'name'), '1 ORDER BY `item`, `name`', iaField::getTableGroups());

					foreach ($output['data'] as &$row)
					{
						$row['title'] = iaLanguage::get('field_' . $row['name'], $row['name']);
						$row['group'] = isset($groups[$row['fieldgroup_id']]) ? iaLanguage::get('fieldgroup_' . $groups[$row['fieldgroup_id']], $row['fieldgroup_id']) : iaLanguage::get('other');
					}
				}
			}

			break;

		case iaCore::ACTION_EDIT:
			$output = $iaCore->factory('grid', iaCore::ADMIN)->gridUpdate($_POST);

			break;

		case iaCore::ACTION_DELETE:
			$output = array(
				'result' => false,
				'message' => iaLanguage::get('invalid_parameters')
			);

			if (isset($_POST['id']) && is_array($_POST['id']))
			{
				$affected = 0;
				foreach ($_POST['id'] as $id)
				{
					if ($iaField->delete($id))
					{
						$affected++;
					}
				}

				if (1 == count($_POST['id']))
				{
					$output['result'] = (1 == $affected);
					$output['message'] = $output['result']
						? iaLanguage::get('field_deleted')
						: iaLanguage::get('db_error');
				}
				else
				{
					$total = count($_POST['id']);

					$output['result'] = ($affected == $total);
					$output['message'] = $output['result']
						? iaLanguage::getf('items_deleted', array('num' => $affected))
						: iaLanguage::getf('items_deleted_of', array('num' => $affected, 'total' => $total));
				}
			}
	}

	$iaView->assign($output);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$fieldItem = null;

	if ($pageAction == iaCore::ACTION_EDIT || $pageAction == iaCore::ACTION_ADD)
	{
		iaBreadcrumb::toEnd(iaLanguage::get('field_' . $pageAction), IA_ADMIN_URL . 'fields/' . $pageAction);
	}
	if ($iaView->name() != 'fields')
	{
		$fieldItem = str_replace('_fields', '', $iaView->name());
		if ('members' == $fieldItem)
		{
			iaBreadcrumb::preEnd(iaLanguage::get('members'), IA_ADMIN_URL . 'members/');
		}
	}
	$iaView->assign('field_item', $fieldItem);

	$iaUtil = $iaCore->factory('util');

	if ($pageAction == iaCore::ACTION_ADD || $pageAction == iaCore::ACTION_EDIT)
	{
		$fieldId = 0;
		$pages = array();
		$groups = array();
		$titles = array();
		$error = false;
		$messages = array();

		if ($pageAction == iaCore::ACTION_EDIT)
		{
			$fieldId = (int)$_GET['id'];
		}

		$field = array(
			'item' => iaUtil::checkPostParam('item'),
			'default' => iaUtil::checkPostParam('default'),
			'lang_values' => iaUtil::checkPostParam('lang_values'),
			'text_default' => iaSanitize::html(iaUtil::checkPostParam('text_default')),
			'type' => iaUtil::checkPostParam('field_type'),
			'annotation' => iaUtil::checkPostParam('annotation'),
			'fieldgroup_id' => (int)iaUtil::checkPostParam('fieldgroup_id'),
			'name' => iaSanitize::alias(iaUtil::checkPostParam('name')),
			'text_length' => (int)iaUtil::checkPostParam('text_length', 100),
			'length' => iaUtil::checkPostParam('length', false),
			'title' => iaSanitize::html(iaUtil::checkPostParam('title')),
			'pages' => iaUtil::checkPostParam('pages', array()),
			'required' => iaUtil::checkPostParam('required'),
			'use_editor' => (int)iaUtil::checkPostParam('use_editor'),
			'empty_field' => iaSanitize::html(iaUtil::checkPostParam('empty_field')),
			'groups' => iaUtil::checkPostParam('groups'),
			'searchable' => (int)iaUtil::checkPostParam('searchable'),
			'adminonly' => (int)iaUtil::checkPostParam('adminonly'),
			'for_plan' => (int)iaUtil::checkPostParam('for_plan'),
			'required_checks' => iaUtil::checkPostParam('required_checks'),
			'extra_actions' => iaUtil::checkPostParam('extra_actions'),
			'link_to' => (int)iaUtil::checkPostParam('link_to'),
			'extras' => '',
			'values' => '',
			'relation' => iaUtil::checkPostParam('relation', iaField::RELATION_REGULAR),
			'parents' => isset($_POST['parents']) && is_array($_POST['parents']) ? $_POST['parents'] : array(),
			'children' => isset($_POST['children']) && is_array($_POST['children']) ? $_POST['children'] : array(),
			'status' => iaUtil::checkPostParam('status', iaCore::STATUS_ACTIVE)
		);

		$iaItem = $iaCore->factory('item');

		if ($packageName = $iaItem->getPackageByItem($field['item']))
		{
			$field['extras'] = $packageName;
		}

		if (isset($_POST['data-field']))
		{
			iaUtil::loadUTF8Functions('ascii', 'validation', 'bad');

			if (!$iaDb->exists(iaDb::convertIds($field['fieldgroup_id']), null, iaField::getTableGroups()))
			{
				$field['fieldgroup_id'] = 0;
			}

			$relationType = iaUtil::checkPostParam('relation_type', -1);
			if ($relationType != -1)
			{
				if ($relationType == 0 && !empty($field['children']))
				{
					$field['relation'] = iaField::RELATION_DEPENDENT;
				}
				else
				{
					$field['relation'] = iaField::RELATION_REGULAR;
					unset($field['parents']);
				}
				unset($field['children']);
			}
			else
			{
				if (!in_array($field['relation'], array(iaField::RELATION_REGULAR, iaField::RELATION_DEPENDENT, iaField::RELATION_PARENT)))
				{
					$field['relation'] = iaField::RELATION_REGULAR;
				}

				if ($field['relation'] != iaField::RELATION_DEPENDENT)
				{
					unset($field['parents']);
				}
				if ($field['relation'] != iaField::RELATION_PARENT)
				{
					unset($field['children']);
				}
			}

			foreach ($iaCore->languages as $code => $l)
			{
				if (!empty($field['annotation'][$code]))
				{
					if (!utf8_is_valid($field['annotation'][$code]))
					{
						$field['annotation'][$code] = utf8_bad_replace($field['annotation'][$code]);
					}
				}
				if (!empty($field['title'][$code]))
				{
					if (!utf8_is_valid($field['title'][$code]))
					{
						$field['title'][$code] = utf8_bad_replace($field['title'][$code]);
					}
				}
				else
				{
					$error = true;
					$messages[] = iaLanguage::getf('field_is_empty', array('field' => $l . ' ' . iaLanguage::get('title')));
					break;
				}
			}

			if ($pageAction == iaCore::ACTION_ADD)
			{
				$field['name'] = trim(strtolower(iaSanitize::paranoid($field['name'])));
				if (empty($field['name']))
				{
					$error = true;
					$messages[] = iaLanguage::get('field_name_incorrect');
				}
			}
			else
			{
				unset($field['name']);
			}

			$fieldTypes = $iaDb->getEnumValues(iaField::getTable(), 'type');
			if ($fieldTypes['values'] && !in_array($field['type'], $fieldTypes['values']))
			{
				$error = true;
				$messages[] = 'Field type not supported!';
			}
			else
			{
				if (!$field['length'])
				{
					$field['length'] = iaField::DEFAULT_LENGTH;
				}

				switch($field['type'])
				{
					case iaField::TEXT:
						if (empty($field['text_length']))
						{
							$field['text_length'] = 100;
						}
						$field['length'] = min(255, max(1, $field['text_length']));
						$field['default'] = $field['text_default'];

						break;

					case iaField::TEXTAREA:
						$field['default'] = '';

						break;

					case iaField::COMBO:
					case iaField::RADIO:
					case iaField::CHECKBOX:
						if (!empty($_POST['values']) && is_array($_POST['values']))
						{
							$keys = array();
							$lang_values = array();

							$multiDefault = explode('|', iaUtil::checkPostParam('multiple_default'));
							$_keys = iaUtil::checkPostParam('keys');
							$_values = iaUtil::checkPostParam('values');
							$_lang_values = iaUtil::checkPostParam('lang_values');

							foreach ($_keys as $index => $key)
							{
								if (trim($key) == '') // add key if not exists
								{
									$key = $index + 1;
									$_keys[$index] = $key;
								}

								if (isset($_values[$index]) && trim($_values[$index]) != '') // add values if not exists
								{
									$values[$key] = $_values[$index];
									$keys[$key] = $key;
								}
								else
								{
									unset($_keys[$index], $_values[$index]);
								}

								if ($_lang_values)
								{
									foreach ($iaCore->languages as $lang_code => $lang_title)
									{
										if ($lang_code != $iaView->language)
										{
											if (!isset($_values[$index]))
											{
												unset($_lang_values[$lang_code][$index]);
											}
											elseif (!isset($_lang_values[$lang_code][$index]) || trim($_lang_values[$lang_code][$index]) == '') // add values if not exists
											{
												$lang_values[$lang_code][$key] = $values[$key];
											}
											else
											{
												$lang_values[$lang_code][$key] = $_lang_values[$lang_code][$index];
											}
										}
									}
								}

							}

							// delete default values if not exists in values
							foreach ($multiDefault as $index => $default)
							{
								if (!in_array($default, $values))
								{
									unset($multiDefault[$index]);
								}
								else
								{
									$k = array_search($default, $values);
									$multiDefault[$index] = $k;
								}
							}
							$multiDefault = array_values($multiDefault);

							if ($field['type'] == iaField::CHECKBOX)
							{
								$multiDefault = implode(',', $multiDefault);
							}
							elseif (isset($multiDefault[0]))
							{
								// multiple default is available for checkboxes only
								$_POST['multiple_default'] = $multiDefault = $multiDefault[0];
							}
							else
							{
								$_POST['multiple_default'] = $multiDefault = '';
							}

							$field['default'] = $multiDefault;
							$field['keys'] = $keys;
							$field['values'] = $values;
							$field['lang_values'] = $lang_values;
						}
						else
						{
							$error = true;
							$messages[] = iaLanguage::get('one_value');
						}

						break;

					case iaField::STORAGE:
						if (!empty($_POST['file_types']))
						{
							$field['file_prefix'] = iaUtil::checkPostParam('file_prefix');
							$field['file_types'] = str_replace(' ', '', iaUtil::checkPostParam('file_types'));
							$field['length'] = (int)iaUtil::checkPostParam('max_files', 5);
						}
						else
						{
							$error = true;
							$messages[] = iaLanguage::get('file_types_empty');
						}

						break;

					case iaField::URL:
						$field['url_nofollow'] = (int)iaUtil::checkPostParam('url_nofollow');

						break;

					case iaField::IMAGE:
						$field['length'] = 1;
						$field['image_height'] = (int)iaUtil::checkPostParam('image_height');
						$field['image_width'] = (int)iaUtil::checkPostParam('image_width');
						$field['thumb_height'] = (int)iaUtil::checkPostParam('thumb_height');
						$field['thumb_width'] = (int)iaUtil::checkPostParam('thumb_width');
						$field['file_prefix'] = iaUtil::checkPostParam('file_prefix');
						$field['resize_mode'] = iaUtil::checkPostParam('resize_mode');

						break;

					case iaField::NUMBER:
						$field['length'] = (int)iaUtil::checkPostParam('number_length', 8);
						$field['default'] = iaUtil::checkPostParam('number_default');

						break;

					case iaField::PICTURES:
						$field['length'] = (int)iaUtil::checkPostParam('pic_max_images', 5);
						$field['file_prefix'] = iaUtil::checkPostParam('pic_file_prefix');
						$field['image_height'] = (int)iaUtil::checkPostParam('pic_image_height');
						$field['image_width'] = (int)iaUtil::checkPostParam('pic_image_width');
						$field['thumb_height'] = (int)iaUtil::checkPostParam('pic_thumb_height');
						$field['thumb_width'] = (int)iaUtil::checkPostParam('pic_thumb_width');
						$field['resize_mode'] = iaUtil::checkPostParam('pic_resize_mode');
				}

				unset($field['text_length'], $field['text_default']);
			}

			if (empty($field['pages']) && !$field['adminonly'])
			{
				$error = true;
				$messages[] = iaLanguage::get('mark_at_least_one_page');
			}

			$field['required'] = (int)iaUtil::checkPostParam('required');
			if ($field['required'])
			{
				$field['required_checks'] = iaUtil::checkPostParam('required_checks');
			}
			$field['extra_actions'] = iaUtil::checkPostParam('extra_actions');

			if ($field['searchable'])
			{
				if (isset($_POST['show_as']) && $field['type'] != iaField::NUMBER && in_array($_POST['show_as'], array(iaField::COMBO, iaField::RADIO, iaField::CHECKBOX)))
				{
					$field['show_as'] = $_POST['show_as'];
				}
				elseif ($field['type'] == 'number' && !empty($_POST['_values']))
				{
					$field['sort_order'] = ('asc' == $_POST['sort_order']) ? $_POST['sort_order'] : 'desc';
					$field['_numberRangeForSearch'] = $_POST['_values'];
				}
			}

			$iaCore->startHook('phpAdminFieldsEdit', array('field' => &$field));

			if (!$error)
			{
				if (iaCore::ACTION_EDIT == $pageAction)
				{
					$iaField->update($field, $fieldId);

					$iaView->setMessages(iaLanguage::get('saved'), iaView::SUCCESS);
				}
				else
				{
					if (!$iaDb->exists("`name` = '" . $field['name'] . "' AND `item` = '" . iaSanitize::sql($_POST['item']) . "'", array(), iaField::getTable()))
					{
						$field['id'] = $iaField->insert($field);
						if ($field['id'])
						{
							$iaView->setMessages(iaLanguage::get('field_added'), iaView::SUCCESS);
							$url = IA_ADMIN_URL . 'fields/';
							iaUtil::post_goto(array(
								'add' => $url . 'add/',
								'list' => $url,
								'stay' => $url . 'edit/?id=' . $field['id'],
							));
						}
						else
						{
							$iaView->setMessages(iaLanguage::get('db_error'), iaView::ERROR);
						}
					}
					else
					{
						$error = true;
						$messages[] = iaLanguage::get('field_exists');
					}
				}
			}
			else
			{
				$iaView->setMessages($messages, iaView::ERROR);
			}
		}

		if ($pageAction == iaCore::ACTION_EDIT)
		{
			$field = $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($fieldId), iaField::getTable());
			$values = $field['values'] = explode(',', $field['values']);
			$rows = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`key` = 'field_" . $field['name'] . "' AND `category` = 'common'", null, null, iaLanguage::getTable());
			foreach ($rows as $row)
			{
				$titles[$row['code']] = $row['value'];
			}

			$field['values_titles'] = array();
			if ($field['default'] != '')
			{
				if (iaField::CHECKBOX == $field['type'])
				{
					$field['default'] = explode(',', $field['default']);
					foreach ($field['default'] as $key_d => $key)
					{
						$field['default'][$key_d] = iaLanguage::get('field_' . $field['name'] . '_' . $key, $key);
					}
				}
				else
				{
					$field['default'] = iaLanguage::get('field_' . $field['name'] . '_' . $field['default'], $field['default']);
				}
			}
			foreach ($values as $key)
			{
				$rows = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`key` = 'field_{$field['name']}_$key'", null, null, iaLanguage::getTable());
				foreach ($rows as $row)
				{
					$field['values_titles'][$key][$row['code']] = $row['value'];
				}

			}

			if (is_array($field['default']))
			{
				$field['default'] = implode('|', $field['default']);
			}

			if (empty($field['values_titles']))
			{
				unset($field['values_titles']);
			}

			if (!$field['editable'])
			{
				unset($field['status']);
			}

			foreach ($iaCore->languages as $code => $val)
			{
				$field['title'][$code] = (isset($titles[$code]) ? $titles[$code] : '');
			}

			$field['pages'] = empty($fieldId)
				? array()
				: $iaDb->keyvalue(array('id', 'page_name'), iaDb::convertIds($fieldId, 'field_id'), iaField::getTablePages());
		}

		$stmt = (iaCore::ACTION_ADD == $pageAction)
			? isset($_POST['item']) ? "`item` = '{$_POST['item']}'" : (isset($_GET['item']) ? "`item` = '{$_GET['item']}'" : iaDb::EMPTY_CONDITION)
			: "`item` = '" . $field['item'] . "'";

		// get items pages
		$itemPagesList = $iaDb->all(array('id', 'page_name', 'item'), $stmt . ' ORDER BY `item`, `page_name`', null, null, 'items_pages');
		foreach ($itemPagesList as $entry)
		{
			$pages[$entry['id']] = array(
				'name' => $entry['page_name'],
				'title' => iaLanguage::get('page_title_' . $entry['page_name'], $entry['page_name']),
				'item' => $entry['item']
			);
		}

		// get field groups
		$fieldGroups = $iaDb->all(array('id', 'name', 'item'), $stmt . ' ORDER BY `item`, `name`', null, null, iaField::getTableGroups());
		foreach ($fieldGroups as $entry)
		{
			$groups[$entry['id']] = array(
				'name' => iaLanguage::get('fieldgroup_' . $entry['name'], $entry['name']),
				'item' => $entry['item']
			);
		}

		// Set default length for field
		if ($field['type'])
		{
			if ($field['type'] == iaField::PICTURES)
			{
				$field['pic_max_images'] = ($field['length'] === false) ? 10 : $field['length'];
			}
			else
			{
				$field['length'] = ($field['length'] === false) ? iaField::DEFAULT_LENGTH : $field['length'];
			}
		}
		else
		{
			$field['pic_max_images'] = 5;
		}

		// Check for default values for some columns
		isset($field['url_nofollow']) || $field['url_nofollow'] = false;

		$fieldTypes = $iaDb->getEnumValues(iaField::getTable(), 'type');
		$items = $iaItem->getItems();
		$parents = array();

		if (iaCore::ACTION_EDIT == $pageAction)
		{
			$iaDb->setTable(iaField::getTableRelations());
			$list = array();
			$rows = $iaDb->all(array('field', 'element'), "`item` = '{$field['item']}' AND `child` = '{$field['name']}'");
			foreach ($rows as $row)
			{
				$list[$field['item']][$row['field']][$row['element']] = 1;
			}
			$field['parents'] = $list;

			$list = array();
			$titles = array();
			$rows = $iaDb->all(array('child', 'element'), "`item` = '{$field['item']}' AND `field` = '{$field['name']}'");
			foreach ($rows as $row)
			{
				$list[$row['element']][] = $row['child'];
				$titles[$row['element']][] = iaLanguage::get('field_' . $row['child']);
			}
			foreach ($list as $element => $row)
			{
				$key = array_search($element, $field['values']);
				$list[$key] = array(
					'values' => implode(',', $row),
					'titles' => implode(', ', $titles[$element])
				);
			}

			$field['children'] = $list;
			$iaDb->resetTable();
		}

		if (!isset($field['children']))
		{
			$field['children'] = array();
		}

		// Get all fields
		$fieldsList = $iaDb->all(array('item', 'name'), ($field['name'] ? "`name` != '{$field['name']}' AND "  : '') . " `relation` = 'parent' AND `type` IN ('combo', 'radio', 'checkbox') AND " . $stmt);
		foreach ($fieldsList as $row)
		{
			isset($parents[$row['item']]) || $parents[$row['item']] = array();
			$array = $iaDb->getEnumValues($iaItem->getItemTable($row['item']), $row['name']);
			$parents[$row['item']][$row['name']] = $array['values'];
		}

		if (empty($field['pages']))
		{
			$field['pages'] = array();
		}

		if (iaField::RELATION_PARENT == $field['relation'])
		{
			$field['main_field'] = 1;
			$field['regular_field'] = 0;
		}
		else
		{
			$field['main_field'] = 0;
			$field['regular_field'] = (int)($field['relation'] == iaField::RELATION_REGULAR);
		}

		$iaView->title(iaLanguage::getf($pageAction . '_field', array('field' => $field['name'] ? iaLanguage::get('field_' . $field['name']) : '')));

		$iaView->assign('field', $field);
		$iaView->assign('parents', $parents);
		$iaView->assign('field_types', $fieldTypes['values']);
		$iaView->assign('groups', $groups);
		$iaView->assign('items', $items);
		$iaView->assign('pages', $pages);
	}
	else
	{
		$iaView->grid('admin/fields');
	}

	$iaView->display('fields');
}

$iaDb->resetTable();