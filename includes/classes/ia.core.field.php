<?php
//##copyright##

class iaField extends abstractCore
{
	const CHECKBOX = 'checkbox';
	const COMBO = 'combo';
	const DATE = 'date';
	const IMAGE = 'image';
	const NUMBER = 'number';
	const PICTURES = 'pictures';
	const RADIO = 'radio';
	const STORAGE = 'storage';
	const TEXT = 'text';
	const TEXTAREA = 'textarea';
	const URL = 'url';

	const RELATION_DEPENDENT = 'dependent';
	const RELATION_PARENT = 'parent';
	const RELATION_REGULAR = 'regular';

	const DEFAULT_LENGTH = 100;

	protected static $_table = 'fields';
	protected static $_tableFieldGroups = 'fields_groups';
	protected static $_tableFieldPages = 'fields_pages';
	protected static $_tableFieldRelations = 'fields_relations';


	public static function getTableGroups()
	{
		return self::$_tableFieldGroups;
	}

	public static function getTablePages()
	{
		return self::$_tableFieldPages;
	}

	public static function getTableRelations()
	{
		return self::$_tableFieldRelations;
	}

	public function insert(array $fieldData)
	{
		$iaCore = &$this->iaCore;
		$iaDb = &$this->iaDb;

		if (isset($fieldData['parents']))
		{
			if ($this->_setParents($fieldData['name'], $fieldData['parents']))
			{
				$fieldData['relation'] = self::RELATION_DEPENDENT;
			}
			unset($fieldData['parents']);
		}

		if (isset($fieldData['children']))
		{
			$this->_setChildren($fieldData['name'], $fieldData['item'], $fieldData['values'], $fieldData['children']);
			unset($fieldData['children']);
		}

		$this->_setRelations();

		$pagesList = $fieldData['pages'];

		unset($fieldData['pages'], $fieldData['groups']);

		foreach ($iaCore->languages as $code => $l)
		{
			if (!empty($fieldData['title'][$code]))
			{
				iaLanguage::addPhrase('field_' . $fieldData['name'], $fieldData['title'][$code], $code, $fieldData['extras']);
			}

			if (isset($fieldData['annotation'][$code]) && !empty($fieldData['annotation'][$code]))
			{
				iaLanguage::addPhrase('field_' . $fieldData['name'] . '_annotation', $fieldData['annotation'][$code], $code, $fieldData['extras']);
			}
		}
		unset($fieldData['title'], $fieldData['annotation']);

		if (!isset($fieldData['relation']) || $fieldData['relation'] != self::RELATION_PARENT)
		{
			$fieldData['relation'] = self::RELATION_REGULAR;
		}
		if (isset($fieldData['parents']))
		{
			if ($this->_setParents($fieldData['name'], $fieldData['parents']))
			{
				$fieldData['relation'] = self::RELATION_DEPENDENT;
			}
			unset($fieldData['parents']);
		}

		if (isset($fieldData['group']) && !isset($fieldData['fieldgroup_id']))
		{
			$fieldData['fieldgroup_id'] = 0;

			$rows = $iaDb->all(array('id', 'name', 'item'), "`item` = '{$fieldData['name']}' ORDER BY `item`, `name`", null, null, self::getTableGroups());
			foreach ($rows as $val)
			{
				if ($fieldData['group'] == $val['name'])
				{
					$fieldData['fieldgroup_id'] = $val['id'];
				}
			}
			unset($fieldData['group']);
		}

		//add language number field search ranges
		if (isset($fieldData['_numberRangeForSearch']) && is_array($fieldData['_numberRangeForSearch']))
		{
			foreach ($fieldData['_numberRangeForSearch'] as $number)
			{
				iaLanguage::addPhrase('field_' . $fieldData['name'] . '_range_' . $number, $number, null, $fieldData['extras']);
			}
		}
		unset($fieldData['_numberRangeForSearch']);
		$keys = array();
		if (isset($fieldData['values']) && is_array($fieldData['values']))
		{
			foreach ($fieldData['values'] as $key => $value)
			{
				$key = $keys[$key] = isset($fieldData['keys'][$key]) ? $fieldData['keys'][$key] : $key;
				iaLanguage::addPhrase('field_' . $fieldData['name'] . '_' . $key, $value, null, $fieldData['extras']);
			}
		}
		else
		{
			unset($fieldData['values']);
		}

		if (isset($fieldData['lang_values']) && is_array($fieldData['lang_values']))
		{
			foreach ($fieldData['lang_values'] as $lng_code => $lng_phrases)
			{
				foreach ($lng_phrases as $ph_key => $ph_value)
				{
					iaLanguage::addPhrase('field_' . $fieldData['name'] . '_' . $ph_key, $ph_value, $lng_code, $fieldData['extras']);
				}
			}
			unset($fieldData['lang_values']);
		}

		if (isset($fieldData['values']) && $fieldData['values'] && isset($fieldData['keys']))
		{
			$fieldData['values'] = implode(',', $fieldData['keys']);
		}
		unset($fieldData['keys']);

		if (isset($fieldData['lang_values']))
		{
			unset($fieldData['lang_values']);
		}

		$fieldData['order'] = $iaDb->getMaxOrder(self::getTable()) + 1;

		$fieldId = $iaDb->insert($fieldData, null, self::getTable());

		if ($fieldId && $pagesList)
		{
			$this->_setPagesList($fieldId, $pagesList, $fieldData['extras']);
		}

		$fieldData['table_name'] = $iaCore->factory('item')->getItemTable($fieldData['item']);

		$fields = $iaDb->describe($fieldData['table_name']);
		$exist = false;
		foreach ($fields as $f)
		{
			if ($f['Field'] == $fieldData['name'])
			{
				$exist = true;
				break;
			}
		}

		if (!$exist)
		{
			$this->_alterAdd($fieldData);
		}

		return $fieldId;
	}

	protected function _alterAdd($fieldData)
	{
		$iaDb = &$this->iaDb;

		$sql = 'ALTER TABLE `' . INTELLI_DBPREFIX . $fieldData['table_name'] . '` ';
		$sql .= 'ADD `' . $fieldData['name'] . '` ';

		switch ($fieldData['type'])
		{
			case self::DATE:
				$sql .= 'DATE ';
				break;
			case self::NUMBER:
				$sql .= 'DOUBLE ';
				break;
			case self::TEXT:
				$sql .= "VARCHAR(" . $fieldData['length'] . ") ";
				$sql .= $fieldData['default'] ? "DEFAULT '{$fieldData['default']}' " : '';
				break;
			case self::URL:
				$sql .= 'TINYTEXT ';
				break;
			case self::IMAGE:
			case self::STORAGE:
			case self::PICTURES:
			case self::TEXTAREA:
				$sql .= 'TEXT ';
				break;
			default:
				if (isset($fieldData['values']))
				{
					$values = explode(',', $fieldData['values']);

					$sql .= ($fieldData['type'] == self::CHECKBOX) ? 'SET' : 'ENUM';
					$sql .= "('" . implode("','", $values) . "')";

					if (!empty($fieldData['default']))
					{
						$sql .= " DEFAULT '{$fieldData['default']}' ";
					}
				}
		}
		$sql .= 'NOT null';

		$iaDb->query($sql);

		if ($fieldData['searchable'] && in_array($fieldData['type'], array(self::TEXT, self::TEXTAREA)))
		{
			$indexes = $iaDb->getAll("SHOW INDEX FROM `" . INTELLI_DBPREFIX . $fieldData['table_name'] . "`");
			$keyExists = false;
			if ($indexes)
			{
				foreach ($indexes as $i)
				{
					if ($i['Key_name'] == $fieldData['name'] && $i['Index_type'] == 'FULLTEXT')
					{
						$keyExists = true;
						break;
					}
				}
			}

			if (!$keyExists)
			{
				$iaDb->query("ALTER TABLE `" . INTELLI_DBPREFIX . $fieldData['table_name'] . "` ADD FULLTEXT (`{$fieldData['name']}`)");
			}
		}
		return true;
	}

	/**
	* Deletes item field from database
	*
	* @param str $aName field name
	*
	* @return bool
	*/
	public function delete($id)
	{
		$iaDb = &$this->iaDb;

		$iaDb->setTable(self::getTable());
		if ($iaDb->exists('`id` = :id AND `editable` = :editable', array('id' => $id, 'editable' => 1)))
		{
			$field = $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));

			$iaDb->delete(iaDb::convertIds($id));
			$iaDb->delete(iaDb::convertIds($id, 'field_id'), self::getTablePages());

			// we will delete language entries if there isn't similar field for another package
			if (!$iaDb->exists('`name` = :name', $field))
			{
				$iaDb->delete("`key` LIKE 'field_{$field['name']}%' ", iaLanguage::getTable());
			}
			if ($field['item'])
			{
				$itemTable = $this->iaCore->factory('item')->getItemTable($field['item']);

				// just additional checking
				$iaDb->setTable($itemTable);
				$fields = $iaDb->describe();
				$iaDb->resetTable();

				foreach ($fields as $f)
				{
					if ($f['Field'] == $field['name'])
					{
						$iaDb->query("ALTER TABLE `{$this->iaDb->prefix}{$itemTable}` DROP `{$field['name']}`");
						break;
					}
				}
			}
		}
		$iaDb->resetTable();

		return true;
	}

	protected function _setParents($name, $parents = array())
	{
		$iaDb = &$this->iaDb;

		$iaDb->setTable(self::getTableRelations());
		foreach ($parents as $item => $item_list)
		{
			$iaDb->delete('`child` = :name AND `item` = :item', null, array('name' => $name, 'item' => $item));
			foreach ($item_list as $field => $field_list)
			{
				foreach ($field_list as $element => $value)
				{
					$iaDb->insert(array(
						'field' => $field,
						'element' => $element,
						'child' => $name,
						'extras' => '',
						'item' => $item
					));
				}
			}
		}

		$iaDb->resetTable();

		return true;
	}

	protected function _setChildren($name, $item, $values, $children = array())
	{
		$iaDb = &$this->iaDb;

		$values = array_keys($values);

		$iaDb->setTable(self::getTableRelations());
		$iaDb->delete('`field` = :field AND `item` = :item', null, array('field' => $name, 'item' => $item));
		foreach ($children as $index => $fieldsList)
		{
			$fieldsList = explode(',', $fieldsList);

			foreach ($fieldsList as $field)
			{
				if (trim($field))
				{
					$iaDb->insert(array(
						'field' => $name,
						'element' => $values[$index],
						'child' => $field,
						'extras' => '',
						'item' => $item,
					));
				}
			}
		}
		$iaDb->resetTable();

		return true;
	}

	protected function _setRelations()
	{
		$sql =
			'UPDATE `:prefix:table` f ' .
			"SET f.relation = ':dependent' " .
			'WHERE (' .
				'SELECT COUNT(*) FROM `:prefix:table_relations` fr WHERE fr.`child` = f.`name`' .
			') > 0';

		$sql = iaDb::printf($sql, array(
			'prefix' => $this->iaDb->prefix,
			'table' => self::getTable(),
			'dependent' => self::RELATION_DEPENDENT,
			'table_relations' => self::getTableRelations()
		));

		$this->iaDb->query($sql);
	}

	/**
	* Updates field information
	*
	* @param array $fieldData field data
	*
	* @return bool
	*/
	public function update(array $fieldData, $id)
	{
		$iaDb = &$this->iaCore->iaDb;

		$field = $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id), self::getTable());
		if (empty($field) || $field['type'] != $fieldData['type'])
		{
			return false;
		}

		if (isset($fieldData['parents']))
		{
			if ($this->_setParents($field['name'], $fieldData['parents']))
			{
				$fieldData['relation'] = self::RELATION_DEPENDENT;
			}
			unset($fieldData['parents']);
		}

		if (isset($fieldData['children']))
		{
			$this->_setChildren($field['name'], $field['item'], $fieldData['values'], $fieldData['children']);
			unset($fieldData['children']);
		}

		$this->_setRelations();

		$iaDb->setTable(iaLanguage::getTable());
		$iaDb->delete("`key` LIKE 'field\_" . $field['name'] . "\_%'");

		foreach ($this->iaCore->languages as $code => $l)
		{
			iaLanguage::addPhrase('field_' . $field['name'], $fieldData['title'][$code], $code, $fieldData['extras']);

			if (isset($fieldData['annotation'][$code]) && $fieldData['annotation'][$code])
			{
				iaLanguage::addPhrase('field_' . $field['name'] . '_annotation', $fieldData['annotation'][$code], $code, $fieldData['extras']);
			}
		}

		unset($fieldData['title'], $fieldData['annotation']);

		$keys = array();
		if (isset($fieldData['values']) && is_array($fieldData['values']))
		{
			$newKeys = array();
			foreach ($fieldData['values'] as $key => $value)
			{
				$key = $keys[$key] = isset($fieldData['keys'][$key]) ? $fieldData['keys'][$key] : $key;
				iaLanguage::addPhrase('field_' . $field['name'] . '_' . $key, $value, null, $fieldData['extras']);

				$newKeys[] = $key;
			}
			$fieldData['values'] = implode(',', $newKeys);
		}
		unset($fieldData['keys']);

		if (isset($fieldData['lang_values']) && is_array($fieldData['lang_values']))
		{
			foreach ($fieldData['lang_values'] as $languageCode => $phrases)
			{
				foreach ($phrases as $phraseKey => $phraseValue)
				{
					iaLanguage::addPhrase('field_' . $field['name'] . '_' . $phraseKey, $phraseValue, $languageCode, $fieldData['extras']);
				}
			}
		}
		if (isset($fieldData['lang_values']))
		{
			unset($fieldData['lang_values']);
		}

		if ($fieldData['searchable'] && $fieldData['type'] == self::NUMBER && isset($fieldData['_numberRangeForSearch']) && is_array($fieldData['_numberRangeForSearch']) && !empty($fieldData['_numberRangeForSearch']))
		{
			$iaDb->delete("`key` LIKE 'field\_" . $field['name'] . "\_range\_%'");

			foreach ($fieldData['_numberRangeForSearch'] as $value)
			{
				iaLanguage::addPhrase('field_' . $field['name'] . '_range_' . $value, $value, null, $fieldData['extras']);
			}
			unset($fieldData['_numberRangeForSearch']);
		}
		else
		{
			$iaDb->delete("`key` LIKE 'field\_" . $field['name'] . "\_range\_%'");
		}

		$iaDb->resetTable();

		// avoid making fulltext second time
		if (!$field['searchable'] && $fieldData['searchable'] && in_array($fieldData['type'], array(self::TEXT, self::TEXTAREA)))
		{
			$indexes = $iaDb->getAll("SHOW INDEX FROM `{$this->iaDb->prefix}{$field['item']}`");
			$keyExists = false;
			foreach ($indexes as $i)
			{
				if ($i['Key_name'] == $field['name'] && $i['Index_type'] == 'FULLTEXT')
				{
					$keyExists = true;
					break;
				}
			}

			if (!$keyExists)
			{
				$iaDb->query("ALTER TABLE `{$this->iaDb->prefix}{$field['item']}` ADD FULLTEXT (`{$field['name']}`)");
			}
		}
		if ($field['searchable'] && !$fieldData['searchable'] && in_array($fieldData['type'], array(self::TEXT, self::TEXTAREA)))
		{
			$indexes = $iaDb->getAll("SHOW INDEX FROM `{$this->iaDb->prefix}{$field['item']}`");
			$keyExists = false;
			foreach ($indexes as $i)
			{
				if ($i['Key_name'] == $field['name'] && $i['Index_type'] == 'FULLTEXT')
				{
					$keyExists = true;
					break;
				}
			}

			if ($keyExists)
			{
				$iaDb->query("ALTER TABLE `{$this->iaDb->prefix}{$field['item']}` DROP INDEX `{$field['name']}`");
			}
		}

		$pagesList = $fieldData['pages'];

		unset($fieldData['pages'], $fieldData['groups'], $fieldData['item']);

		$result = (bool)$iaDb->update($fieldData, iaDb::convertIds($id), null, self::getTable());

		if ($pagesList)
		{
			$this->_setPagesList($id, $pagesList, $fieldData['extras']);
		}

		if ($result)
		{
			if (in_array($fieldData['type'], array(self::TEXT, self::COMBO, self::RADIO, self::CHECKBOX)))
			{
				$sql = "ALTER TABLE `{$this->iaDb->prefix}{$field['item']}` ";
				$sql .= "CHANGE `{$field['name']}` `{$field['name']}` ";

				switch ($fieldData['type'])
				{
					case self::TEXT:
						$sql .= "VARCHAR ({$fieldData['length']}) ";
						$sql .= $fieldData['default'] ? "DEFAULT '{$fieldData['default']}' " : '';
						break;
					default:
						if (isset($fieldData['values']))
						{
							$values = explode(',', $fieldData['values']);

							$sql .= $fieldData['type'] == self::CHECKBOX ? 'SET' : 'ENUM';
							$sql .= "('" . implode("','", $values) . "')";

							if (!empty($fieldData['default']))
							{
								$sql .= " DEFAULT '{$fieldData['default']}' ";
							}
						}
						break;
				}
				$sql .= 'NOT NULL';
				$iaDb->query($sql);
			}
		}

		return $result;
	}

	public function getByItemName($itemName)
	{
		$sql =
			'SELECT f.*, ' .
			'(SELECT GROUP_CONCAT(CONCAT(`element`,"-",`child`)) FROM `:prefix:table_relations` fr ' .
			'WHERE fr.`field` = f.`name` AND fr.`item` = f.`item`) `children` ' .
			'FROM `:table` f ' .
			"WHERE f.`status` = ':status' AND f.`item` = ':item' " .
			'ORDER BY f.`order`';
		$sql = iaDb::printf($sql, array(
			'prefix' => $this->iaDb->prefix,
			'table_relations' => self::getTableRelations(),
			'table' => self::getTable(true),
			'status' => iaCore::STATUS_ACTIVE,
			'item' => $itemName
		));

		$fields = array();

		if ($rows = $this->iaDb->getAll($sql))
		{
			foreach ($rows as $row)
			{
				if (in_array($row['type'], array(self::CHECKBOX, self::COMBO, self::RADIO)))
				{
					if (self::CHECKBOX == $row['type'])
					{
						$row['default'] = explode(',', $row['default']);
					}

					$values = explode(',', $row['values']);

					$row['values'] = array();
					foreach ($values as $v)
					{
						$row['values'][$v] = iaLanguage::get('field_' . $row['name'] . '_' . $v);
					}

					if (isset($row['children']) && $row['children'])
					{
						$children = array();
						$array = explode(',', $row['children']);
						foreach ($array as $value)
						{
							$info = explode('-', $value);
							$children[$info[0]][] = $info[1];
						}
						$row['children'] = $children;
					}
				}

				$fields[] = $row;
			}
		}

		return $fields;
	}

	public function filterByGroup(&$items, $item = false, $params = array())
	{
		foreach (array('page', 'where', 'not_empty') as $key)
		{
			if (!isset($params[$key]))
			{
				$params[$key] = false;
			}
		}
		if (!$item && isset($items['item']))
		{
			$item = $items['item'];
		}

		$sections = $this->_getFieldsSection($params['page'], $item, $params['where'], $items, $params);
		if ($params['not_empty'])
		{
			if ($sections)
			{
				foreach ($sections as $section)
				{
					if (isset($section['fields']) && $section['fields'] && is_array($section['fields']))
					{
						foreach ($section['fields'] as $field)
						{
							if (isset($items[$field['name']]) && $items[$field['name']])
							{
								return $sections;
							}
						}
					}
				}
			}

			return false;
		}

		return $sections;
	}

	public function filter(&$items, $item, $params = array())
	{
		foreach (array('page', 'where', 'filter') as $key)
		{
			if (!isset($params[$key]))
			{
				$params[$key] = false;
			}
		}

		if ($params['page'] === false && iaCore::ACCESS_ADMIN == $this->iaCore->getAccessType())
		{
			$params['page'] = 'admin';
		}

		if (!isset($params['info']))
		{
			$params['info'] = true;
		}
		if ($params['filter'] !== false && !is_array($params['filter']))
		{
			$params['filter'] = explode(',', $params['filter']);
		}

		$fieldsList = self::getAcoFieldsList($params['page'], $item, $params['where'], $params['info'], $items, $params);

		if (!is_array($items))
		{
			return $fieldsList;
		}

		if ($params['page'] == iaCore::ADMIN)
		{
			return $fieldsList;
		}

		$type = 'simple';
		if (is_array(current($items)))
		{
			$type = 'group';
		}

		$forPlans = array();
		$fields = array();
		$empty = array();
		foreach ($fieldsList as $key => $field)
		{
			$empty[$field['name']] = $field['empty_field'];
			if ($field['for_plan'] == 0 || $field['required'] == 1)
			{
				$fields[] = $field['name'];
			}
			else
			{
				$forPlans[] = $field['name'];
			}
			if ($params['filter'] && in_array($field['name'], $params['filter']))
			{
				unset($fieldsList[$key]);
			}
		}

		if ($type == 'simple')
		{
			$items = $this->_checkItem($items, $item, $fields, $forPlans, $empty);
		}
		else
		{
			foreach ($items as $key => $value)
			{
				$items[$key] = $this->_checkItem($value, $item, $fields, $forPlans, $empty);
			}
		}

		return $fieldsList;
	}

	protected function _checkItem($items, $item, $fields, $forPlans, $empty)
	{
		if ($forPlans)
		{
			$iaPlan = $this->iaCore->factory('plan', iaCore::FRONT);

			$plans = $iaPlan->getPlans($item);

			if (isset($items[iaPlan::SPONSORED_PLAN_ID]) && $items[iaPlan::SPONSORED_PLAN_ID] != 0 && isset($plans[$items[iaPlan::SPONSORED_PLAN_ID]]))
			{
				if (isset($plans[$items[iaPlan::SPONSORED_PLAN_ID]]['data']['fields']))
				{
					$planFields = $plans[$items[iaPlan::SPONSORED_PLAN_ID]]['data']['fields'];
					foreach ($forPlans as $field)
					{
						if (in_array($field, $planFields))
						{
							$fields[] = $field;
						}
					}
				}
			}
		}

		foreach ($items as $field => $value)
		{
			if (!in_array($field, $fields))
			{
				if (isset($empty[$field]))
				{
					$items[$field] = $empty[$field];
				}
			}
		}

		return $items;
	}

	/**
	 * getAcoFieldsList
	 *
	 * @obsolete should not be used
	 */
	public static function getAcoFieldsList($aAco = false, $aItem = false, $aWhere = '', $aAllFieldInfo = false, $aItemData = false, $params = array())
	{
		$iaCore = iaCore::instance();
		$iaView = &$iaCore->iaView;
		$iaAcl = $iaCore->factory('acl');

		$aAco = $aAco ? $aAco : $iaView->name();

		$aItem = $aItem ? $aItem : $iaView->get('extras');

		$selection = $aAllFieldInfo || $aAco == 'admin' ? '`f`.*' : '`f`.name';
		if (isset($params['selection']) && $params['selection'])
		{
			$selection = $params['selection'];
		}
		$order = 'ORDER BY f.`order`';
		if (isset($params['order']) && $params['order'])
		{
			$order = 'ORDER BY ' . $params['order'];
		}

		$sql_plan = '';
		$children_select = ", (SELECT GROUP_CONCAT(CONCAT(`element`,'-',`child`)) FROM `" . $iaCore->iaDb->prefix . self::getTableRelations() . '` fr WHERE fr.`field` = f.`name` AND fr.`item` = f.`item`) `children`';

		if (iaCore::ADMIN == $aAco)
		{
			$aAllFieldInfo = true;
			$sql =
				"SELECT {$selection}{$children_select} " .
				"FROM `" . self::getTable(true) . "` `f` " .
				"WHERE `f`.`status` = 'active' AND `f`.`item` = '{$aItem}' "
				. ($aWhere ? ' AND ' . $aWhere : '')
				. ' GROUP BY `f`.`id`'
				. $order;

		}
		elseif ('all' == $aAco)
		{
			$sql = "SELECT {$selection}{$children_select}
				FROM `" . self::getTable(true) . "` f
				WHERE " .
					"f.`status` = 'active' AND " .
					"f.`item` = '{$aItem}' AND " .
					"f.`adminonly` = 0 "
				. ($aWhere ? ' AND ' . $aWhere : '')
				. " GROUP BY `f`.`id`"
				. $order;
			//			$sql .= $aPlan && (!$aItemData || $aItemData['sponsored']) ? " AND (`plans`='' OR FIND_IN_SET('{$aPlan}', `plans`)) " : " AND `plans`='' ";
		}
		else
		{
			$sql = "SELECT {$selection}{$children_select} " .
					'FROM `' . $iaCore->iaDb->prefix . self::getTablePages() . '` fp ' .
					'LEFT JOIN `' . $iaCore->iaDb->prefix . self::getTable() . '` f ON (fp.`field_id` = f.`id`) ' .
					"WHERE `fp`.`page_name` = '{$aAco}' AND f.`status` = 'active' AND f.`item` = '{$aItem}' AND f.`adminonly` = 0 "
						. ($aWhere ? ' AND ' . $aWhere : '')
						. $sql_plan
						. ' GROUP BY f.`id` '
						. $order;
			//			$sql .= $aPlan && (!$aItemData || $aItemData['sponsored']) ? " AND (`plans`='' OR FIND_IN_SET('{$aPlan}', `plans`)) " : " AND `plans`='' ";
		}

		$rows = $iaCore->iaDb->getAll($sql);

		foreach ($rows as $key => $value)
		{
			if (isset($value['name']) && $iaAcl->checkAccess('field', 0, 0, $aItem . '_' . $value['name']) === false)
			{
				unset($rows[$key]);
			}
			else
			{
				if (isset($value['children']) && $value['children'])
				{
					$children = array();
					$array = explode(',', $value['children']);
					foreach ($array as $entry)
					{
						$info = explode('-', $entry);
						$children[$info[0]][] = $info[1];
					}
					$rows[$key]['children'] = $children;
				}
			}
		}

		if ($aAllFieldInfo)
		{
			return $rows;
		}

		$fields = array();
		if ($rows)
		{
			foreach ($rows as $row)
			{
				$fields[] = $row['name'];
			}
		}

		return $fields;
	}

	protected function _getFieldsSection($aco = false, $aItem = false, $aWhere = '', &$aItemData, $params = array())
	{
		$aco = $aco ? $aco : (iaCore::ACCESS_ADMIN == $this->iaCore->getAccessType() ? 'admin' : $this->iaCore->iaView->name());
		$aItem = $aItem ? $aItem : $this->iaCore->iaView->get('extras');

		$_params = array('page' => $aco, 'where' => $aWhere,'filter' => '');
		foreach ($_params as $key => $value)
		{
			if (!isset($params[$key]))
			{
				$params[$key] = $value;
			}
		}

		$fields = $this->filter($aItemData, $aItem, $params);
		if (empty($fields))
		{
			return array();
		}

		// get all available groups for item
		$groups = $this->iaDb->assoc(array('id', 'name', 'order', 'collapsible', 'collapsed', 'tabview', 'tabcontainer'), "`item` = '{$aItem}' ORDER BY `order`", self::getTableGroups());

		foreach ($fields as $value)
		{
			$value['item'] = $aItem;

			if (self::PICTURES == $value['type'])
			{
				$value['values'] = empty($value['values']) ? array() : explode(',', $value['values']);
			}

			if (in_array($value['type'], array(self::CHECKBOX, self::COMBO, self::RADIO)))
			{
				if ($value['type'] == self::CHECKBOX)
				{
					$value['default'] = explode(',', $value['default']);
				}

				$values = explode(',', $value['values']);

				$value['values'] = array();
				if ($values)
				{
					foreach ($values as $v)
					{
						$k = 'field_' . $value['name'] . '_' . $v;
						$value['values'][$v] = iaLanguage::get($k);
					}
				}
			}

			if (!isset($value['class']))
			{
				$value['class'] = 'fieldzone';
			}

			if ($value['plans'])
			{
				foreach (explode(',', $value['plans']) as $p)
				{
					$value['class'] .= sprintf(' plan_%d ', $p);
				}
			}

			$array = $value;

			if (empty($array['fieldgroup_id']) || empty($groups[$array['fieldgroup_id']]))
			{
				$array['fieldgroup_id'] = '___empty___';

				// emulate tab to avoid isset checks
				$groups[$array['fieldgroup_id']]['name'] = $array['fieldgroup_id'];
				$groups[$array['fieldgroup_id']]['tabview'] = '';
				$groups[$array['fieldgroup_id']]['tabcontainer'] = '';
				$groups[$array['fieldgroup_id']]['collapsible'] = 0;
				$groups[$array['fieldgroup_id']]['collapsed'] = 0;
			}

			$groups[$array['fieldgroup_id']]['fields'][$array['id']] = $array;
		}

		// clear groups that don't have any fields
		foreach ($groups as $key => $group)
		{
			// update description & titles
			$groups[$key]['description'] = iaLanguage::get('fieldgroup_description_' . $aItem . '_' . $group['name'], '');

			if (!isset($group['fields']))
			{
				unset($groups[$key]);
			}
		}

		return $groups;
	}

	public function getValues($field, $item)
	{
		$row = $this->iaDb->one_bind(array('values'), '`name` = :field AND `item` = :item', array('field' => $field, 'item' => $item), self::getTable());

		if (empty($row))
		{
			return false;
		}

		$keys = explode(',', $row);
		$values = array();

		foreach ($keys as $key)
		{
			$values[$key] = iaLanguage::get('field_' . $field . '_' . $key, $key);
		}

		return $values;
	}

	public function getGroups($itemName)
	{
		$groups = $this->iaDb->assoc(array('id', 'name', 'order', 'collapsed'), iaDb::EMPTY_CONDITION . ' ORDER BY `order`', self::getTableGroups());
		$fields = $this->getByItemName($itemName);

		if (empty($fields))
		{
			return $groups;
		}

		foreach ($fields as $value)
		{
/*			if ($isSearchPage && self::NUMBER == $value['type'])
			{
				$value['ranges'] = array();

				if ($ranges = $iaDb->keyvalue(array('key', 'value'), "`key` LIKE 'field\_" . $value['name'] . "\_range\_%'", iaLanguage::getTable()))
				{
					foreach ($ranges as $k2 => $v2)
					{
						$k2 = array_pop(explode('_', $k2));
						$value['ranges'][$k2] = $v2;
					}
				}

				('desc' == $value['sort_order'])
					? krsort($value['ranges'])
					: ksort($value['ranges']);
			}
*/

			if (empty($value['fieldgroup_id']) || empty($groups[$value['fieldgroup_id']]))
			{
				$value['fieldgroup_id'] = '___empty___';

				// emulate tab to avoid isset checks
				$groups[$value['fieldgroup_id']]['name'] = $value['fieldgroup_id'];
				$groups[$value['fieldgroup_id']]['tabview'] = '';
				$groups[$value['fieldgroup_id']]['tabcontainer'] = '';
				$groups[$value['fieldgroup_id']]['collapsible'] = 0;
				$groups[$value['fieldgroup_id']]['collapsed'] = 0;
			}

			$groups[$value['fieldgroup_id']]['fields'][] = $value;
		}

		return $groups;
	}

	public function parsePost($fieldsList, $previousValues = null, $isBackend = false)
	{
		$iaCore = &$this->iaCore;

		$error = false;
		$messages = array();
		$invalidFields = array();

		$item = array();
		// check plan
		if (isset($_POST['plan_id']))
		{
			$iaCore->factory('plan', iaCore::FRONT);
			$item[iaPlan::SPONSORED_PLAN_ID] = (int)$_POST['plan_id'];
			if (!isset($previousValues[iaPlan::SPONSORED_PLAN_ID])
				|| !isset($item[iaPlan::SPONSORED_PLAN_ID])
				|| $item[iaPlan::SPONSORED_PLAN_ID] != $previousValues[iaPlan::SPONSORED_PLAN_ID])
			{
				$item[iaPlan::SPONSORED] = 0;
				$item[iaPlan::SPONSORED_DATE_START] = null;
				$item[iaPlan::SPONSORED_DATE_END] = null;
			}
			if ($isBackend)
			{
				$item[iaPlan::SPONSORED] = (int)$_POST['sponsored'];
				$item[iaPlan::SPONSORED_DATE_START] = date(iaDb::DATETIME_SHORT_FORMAT);
				$item[iaPlan::SPONSORED_DATE_END] = $_POST['sponsored_end'];
			}
		}
		// end

		if ($isBackend)
		{
			if (isset($_POST['featured']))
			{
				$item['featured'] = (int)$_POST['featured'];
				if ($item['featured'])
				{
					if (isset($_POST['featured_end']) && $_POST['featured_end'])
					{
						$item['featured_start'] = date(iaDb::DATETIME_SHORT_FORMAT);
						$item['featured_end'] = iaSanitize::html($_POST['featured_end']);
					}
					else
					{
						$error = true;
						$messages[] = iaLanguage::get('featured_status_finished_date_is_empty');
						$invalidFields[] = 'featured_end';
					}
				}
				else
				{
					$item['featured_start'] = null;
					$item['featured_end'] = null;
				}
			}

			if (isset($_POST['status']))
			{
				$item['status'] = iaSanitize::html($_POST['status']);
			}

			if (isset($_POST['date_added']))
			{
				$time = strtotime($_POST['date_added']);
				if (!$time)
				{
					$error = true;
					$messages[] = iaLanguage::get('added_date_is_incorrect');
				}
				elseif ($time > time())
				{
					$error = true;
					$messages[] = iaLanguage::get('future_date_specified_for_added_date');
				}
				else
				{
					$item['date_added'] = date(iaDb::DATETIME_SHORT_FORMAT, $time);
				}
			}

			if (isset($_POST['owner']))
			{
				if (empty($_POST['owner']))
				{
					$error = true;
					$messages[] = iaLanguage::get('owner_is_not_specified');
				}
				else
				{
					if ($memberId = $iaCore->iaDb->one_bind('id', '`username` = :name OR `fullname` = :name', array('name' => iaSanitize::sql($_POST['owner'])), iaUsers::getTable()))
					{
						$item['member_id'] = $memberId;
					}
					else
					{
						$error = true;
						$messages[] = iaLanguage::get('incorrect_owner_specified');
					}
				}
			}
		}

		$iaUtil = $iaCore->factory('util');
		iaUTF8::loadUTF8Util('validation', 'bad');

		$aFields = array();
		$parents = array();
		$dependent = array();

		foreach ($fieldsList as $field)
		{
			$fieldName = $field['name'];
			$aFields[$fieldName] = $field;
			if (self::RELATION_PARENT == $field['relation'])
			{
				$parents[$fieldName] = $field['children'];
			}
			elseif (self::RELATION_DEPENDENT == $field['relation'])
			{
				$dependent[$fieldName] = 1;
			}
		}

		foreach ($parents as $fieldName => $elementList)
		{
			if (isset($_POST[$fieldName]))
			{
				$post = $_POST[$fieldName];
				if (isset($elementList[$post]))
				{
					foreach ($elementList[$post] as $child)
					{
						if (isset($dependent[$child]))
						{
							unset($dependent[$child]);
						}
					}
				}
			}
		}

		// clear field list by dependent list
		foreach ($dependent as $fieldName => $value)
		{
			unset($aFields[$fieldName]);
		}

		$iaView = &$iaCore->iaView;
		$iaDb = &$iaCore->iaDb;

		foreach ($aFields as $fieldName => $field)
		{
			if (!isset($_POST[$fieldName]))
			{
				$_POST[$fieldName] = '';
				if (in_array($field['type'], array(self::STORAGE, self::PICTURES, self::IMAGE)))
				{
					iaDebug::debug('[' . $fieldName . '] not uploaded', null, 'info');
				}
			}

			// Check the UTF-8 is well formed
			if (!is_array($_POST[$fieldName]) && !utf8_is_valid($_POST[$fieldName]))
			{
				$_POST[$fieldName] = utf8_bad_replace($_POST[$fieldName]);
			}

			if ($field['extra_actions'])
			{
				eval($field['extra_actions']);
			}

			if (in_array($field['type'], array(self::TEXT, self::TEXTAREA, self::NUMBER, self::RADIO, self::CHECKBOX, self::COMBO)))
			{
				if ($field['required'])
				{
					if ($field['required_checks'])
					{
						eval($field['required_checks']);
					}

					if (empty($_POST[$fieldName]))
					{
						$error = true;

						$messages[] = (in_array($field['type'], array(self::RADIO, self::CHECKBOX, self::COMBO)))
							? iaLanguage::getf('field_is_not_selected', array('field' => iaLanguage::get('field_' . $fieldName)))
							: iaLanguage::getf('field_is_empty', array('field' => iaLanguage::get('field_' . $fieldName)));

						$invalidFields[] = $fieldName;
					}
				}

				if (self::TEXTAREA == $field['type'])
				{
					$item[$fieldName] = $field['use_editor']
						? iaUtil::safeHTML($_POST[$fieldName])
						: strip_tags($_POST[$fieldName]);
				}
				else
				{
					$item[$fieldName] = is_array($_POST[$fieldName])
						? implode(',', $_POST[$fieldName])
						: $_POST[$fieldName];
				}

				if (self::NUMBER == $field['type'])
				{
					$item[$fieldName] = (float)str_replace(' ', '', $item[$fieldName]);
				}
			}
			elseif (self::DATE == $field['type'])
			{
				if ($field['required'] && $field['required_checks'])
				{
					eval($field['required_checks']);
				}
				elseif ($field['required'] && empty($_POST[$fieldName]))
				{
					$error = true;
					$messages[] = iaLanguage::getf('field_is_empty', array('field' => iaLanguage::get('field_' . $fieldName)));
					$invalidFields[] = $fieldName;
				}

				if ($_POST[$fieldName])
				{
					$array = explode('-', $_POST[$fieldName]);

					$year = (int)$array[0];
					$month = max(1, (int)$array[1]);
					$day = max(1, (int)$array[2]);

					$year = (strlen($year) == 4) ? $year : 2000;
					$month = (strlen($month) < 2) ? '0' . $month : $month;
					$day = (strlen($day) < 2) ? '0' . $day : $day;

					$item[$fieldName] = $year . '-' . $month . '-' . $day;
				}
			}
			elseif (self::URL == $field['type'])
			{
				$validProtocols = array('http://', 'https://');
				$item[$fieldName] = '';

				$req_error = false;
				if ($field['required'])
				{
					if ($field['required_checks'])
					{
						eval($field['required_checks']);
					}
					elseif (empty($_POST[$fieldName]['url']) || in_array($_POST[$fieldName]['url'], $validProtocols))
					{
						$error = $req_error = true;
						$messages[] = iaLanguage::getf('field_is_empty', array('field' => iaLanguage::get('field_' . $fieldName)));
						$invalidFields[] = $fieldName;
					}
				}

				if (!$req_error && !empty($_POST[$fieldName]['url']) && !in_array($_POST[$fieldName]['url'], $validProtocols))
				{
					if (iaValidate::isUrl($_POST[$fieldName]['url']))
					{
						$item[$fieldName] = array();
						$item[$fieldName]['url'] = iaSanitize::html($_POST[$fieldName]['url']);
						$item[$fieldName]['title'] = empty($_POST[$fieldName]['title']) ? $item[$fieldName]['url'] : iaSanitize::html($_POST[$fieldName]['title']);
						$item[$fieldName] = implode('|', $item[$fieldName]);
					}
					else
					{
						$error = true;
						$messages[] = iaLanguage::get('field_' . $fieldName) . ': ' . iaLanguage::get('error_url');
						$invalidFields[] = $fieldName;
					}
				}
			}
			elseif (in_array($field['type'], array(self::PICTURES, self::STORAGE, self::IMAGE)) && is_array($_FILES[$fieldName]['tmp_name']) && !empty($_FILES[$fieldName]['tmp_name']))
			{
				if (!is_writable(IA_UPLOADS))
				{
					$error = true;
					$messages[] = iaLanguage::get('error_directory_readonly');
				}
				else
				{
					// run required field checks
					if ($field['required'] && $field['required_checks'])
					{
						eval($field['required_checks']);
					}
					elseif ($field['required'] && empty($_FILES[$fieldName]['tmp_name']))
					{
						$error = true;
						$messages[] = iaLanguage::getf('field_is_empty', array('field' => iaLanguage::get('field_' . $fieldName)));
						$invalidFields[] = $fieldName;
					}

					// custom folder for uploaded images
					if (!empty($field['folder_name']))
					{
						if (!is_dir(IA_UPLOADS . $field['folder_name']))
						{
							mkdir(IA_UPLOADS . $field['folder_name']);
						}
						$path = $field['folder_name'] . IA_DS;
					}
					else
					{
						$path = iaUtil::getAccountDir();
					}

					$item[$fieldName] = empty($item[$fieldName]) ? array() : unserialize($item[$fieldName]);

					// get previous values for the files to keep them
					$previousValues[$fieldName] = empty($previousValues[$fieldName]) ? array() : $previousValues[$fieldName];
					$previousValues[$fieldName] = is_array($previousValues[$fieldName]) ? $previousValues[$fieldName] : unserialize($previousValues[$fieldName]);

					// initialize class to work with images
					$methodName = '_processImageField';
					if (self::STORAGE == $field['type'])
					{
						$methodName = '_processFileField';
					}

					// process uploaded files
					foreach ($_FILES[$fieldName]['tmp_name'] as $id => $tmp_name)
					{
						if ($_FILES[$fieldName]['error'][$id])
						{
							continue;
						}

						// files limit exceeded or rewrite image value
						if (self::IMAGE != $field['type'] && count($previousValues[$fieldName]) + count($item[$fieldName]) >= $field['length'])
						{
							break;
						}

						$file = array();
						foreach ($_FILES[$fieldName] as $key => $value)
						{
							$file[$key] = $_FILES[$fieldName][$key][$id];
						}

						list($filename, $error, $messages) = self::$methodName($field, $file, $path);

						$fieldValue = array(
							'title' => (isset($_POST[$fieldName . '_title'][$id]) ?
								iaSanitize::html(substr($_POST[$fieldName . '_title'][$id], 0, 100)) : ''),
							'path' => $filename
						);

						if (self::IMAGE == $field['type'])
						{
							$item[$fieldName] = $fieldValue;
						}
						else
						{
							$item[$fieldName][] = $fieldValue;
						}
					}
				}

				// If already has images, append them. Previous value can be either array or comma delimited string
				$item[$fieldName] = array_merge($previousValues[$fieldName], $item[$fieldName]);
				$item[$fieldName] = empty($item[$fieldName]) ? false : serialize($item[$fieldName]);
			}

			if (isset($item[$fieldName]))
			{
				// process hook if field value exists
				$iaCore->startHook('phpParsePostAfterCheckField', array(
					'field_name' => $fieldName,
					'item' => &$item[$fieldName],
					'value' => $field,
					'error' => &$error,
					'error_fields' => &$invalidFields,
					'msg' => &$messages
				));
			}
		}

		return array($item, $error, $messages, is_array($invalidFields) ? implode(',', $invalidFields) : '');
	}

	protected static function _generateFileName($filename = '', $prefix = '', $glue = true)
	{
		if (empty($filename))
		{
			return $prefix . (iaUtil::generateToken());
		}

		$extension = '';
		if (false !== strpos($filename, '.'))
		{
			// get extension
			$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

			// get filename
			$filename = $prefix . pathinfo($filename, PATHINFO_FILENAME);
		}

		return $glue ? $filename . '.' . $extension : array($filename, $extension);
	}

	protected static function _processFileField(array $field, array $file, $path)
	{
		$error = $message = false;

		list($filename, $extension) = self::_generateFileName($file['name'], $field['file_prefix'], false);
		$filename = $path . $filename . '.' . $extension;

		// get available extensions
		$allowedExtensions = empty($field['file_types']) ? false : explode(',', str_replace(' ', '', $field['file_types']));

		if ($extension && $allowedExtensions && in_array($extension, $allowedExtensions))
		{
			move_uploaded_file($file['tmp_name'], IA_UPLOADS . $filename);
			chmod(IA_UPLOADS . $filename, 0644);
		}
		else
		{
			$error = true;
			$message = iaLanguage::getf('file_type_error', array('extension' => $field['file_types']));
		}

		return array($filename, $error, $message);
	}

	protected static function _processImageField(array $field, array $file, $path)
	{
		$error = false;
		$messages = array();

		$iaCore = iaCore::instance();
		$iaPicture = $iaCore->factory('picture');

		list($filename, ) = self::_generateFileName($file['name'], $field['file_prefix'], false);

		$imageName = $iaPicture->processImage($file, $path, $filename, $field);
		if ($imageName)
		{
			$imageName = str_replace(IA_DS, '/', $imageName);
		}
		else
		{
			$error = true;
			$messages[] = $iaPicture->getMessage();
		}

		return array($imageName, $error, $messages);
	}

	/**
	 * Sets elements of array according to provided fields structure
	 *
	 * @param array $itemData resulting array
	 * @param array $fieldsList standard fields structure returned by methods of this class
	 * @param array $extraValues values that will be merged to $itemData
	 * @param array $data source data (POST values are used if nothing specified)
	 *
	 * @return void
	 */
	public static function keepValues(array &$itemData, array $fields, array $extraValues = array(), $data = null)
	{
		if (is_null($data))
		{
			$data = $_POST;
		}
		if (empty($data))
		{
			return;
		}

		foreach ($fields as $field)
		{
			if ($field['type'] != self::PICTURES && $field['type'] != self::IMAGE)
			{
				$fieldName = $field['name'];
				if (isset($data[$fieldName]) && $data[$fieldName])
				{
					$itemData[$fieldName] = in_array($field['type'], array(self::CHECKBOX))
						? implode(',', $data[$fieldName])
						: $data[$fieldName];
				}
			}
		}

		if (iaCore::ACCESS_ADMIN == iaCore::instance()->getAccessType())
		{
			if (isset($data['featured']))
			{
				$itemData['featured'] = $data['featured'];
				$itemData['featured_end'] = date(iaDb::DATETIME_SHORT_FORMAT, strtotime($data['featured_end']));
			}

			if (isset($data['sponsored']))
			{
				$itemData['sponsored'] = $data['sponsored'];
				if (isset($data['sponsored_end']))
				{
					$itemData['sponsored_end'] = date(iaDb::DATETIME_SHORT_FORMAT, strtotime($data['sponsored_end']));
				}
			}

			empty($data['date_added']) || $itemData['date_added'] = iaSanitize::html($data['date_added']);
			empty($data['status']) || $itemData['status'] = iaSanitize::html($data['status']);
			empty($data['owner']) || $itemData['owner'] = iaSanitize::html($data['owner']);
		}

		if ($extraValues)
		{
			$itemData = array_merge($itemData, $extraValues);
		}
	}

	public function generateTabs(array $fieldgroups)
	{
		$tabs = $groups = array();

		foreach ($fieldgroups as $key => $group)
		{
			if ($group['tabview'])
			{
				$tabs['fieldgroup_' . $group['name']][$key] = $group;
			}
			elseif ($group['tabcontainer'])
			{
				$tabs['fieldgroup_' . $group['tabcontainer']][$key] = $group;
			}
			else
			{
				$groups[$key] = $group;
			}
		}

		return array($tabs, $groups);
	}

	public function getImageFields($pluginFilter = null)
	{
		$conditions = array("`type` IN ('image','pictures')");
		empty($pluginFilter) || $conditions[] = "`extras` = '" . iaSanitize::sql($pluginFilter) . "'";
		$conditions = implode(' AND ', $conditions);

		return $this->iaDb->onefield('name', $conditions, null, null, self::getTable());
	}

	protected function _setPagesList($fieldId, array $pages, $extras)
	{
		$this->iaDb->setTable(self::getTablePages());

		$this->iaDb->delete(iaDb::convertIds($fieldId, 'field_id'));

		foreach ($pages as $pageName)
		{
			if (trim($pageName))
			{
				$this->iaDb->insert(array('page_name' => $pageName, 'field_id' => $fieldId, 'extras' => $extras));
			}
		}

		$this->iaDb->resetTable();
	}
}