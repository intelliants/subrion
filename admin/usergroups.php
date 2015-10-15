<?php
//##copyright##

class iaBackendController extends iaAbstractControllerBackend
{
	protected $_name = 'usergroups';

	protected $_processEdit = false;
	protected $_tooltipsEnabled = true;

	protected $_phraseAddSuccess = 'usergroup_added';
	protected $_phraseGridEntryDeleted = 'usergroup_deleted';

	protected $_iaUsers;


	public function __construct()
	{
		parent::__construct();

		$this->setTable(iaUsers::getUsergroupsTable());

		$this->_iaUsers = $this->_iaCore->factory('users');
	}

	protected function _gridRead($params)
	{
		return ($this->_iaCore->requestPath && 'store' == end($this->_iaCore->requestPath))
			? $this->_getUsergroups()
			: parent::_gridRead($params);
	}

	protected function _entryDelete($entryId)
	{
		return $this->_iaUsers->deleteUsergroup($entryId);
	}

	protected function _gridQuery($columns, $where, $order, $start, $limit)
	{
		$sql = 'SELECT u.*, IF(u.`id` = 1, 0, u.`id`) `permissions`, u.`id` `config`, IF(u.`system` = 1, 0, 1) `delete` '
			. ', IF(u.`id` = 1, 1, p.`access`) `admin` '
			. ',(SELECT GROUP_CONCAT(m.`fullname` SEPARATOR \', \') FROM `' . iaUsers::getTable(true) . '` m WHERE m.`usergroup_id` = u.`id` GROUP BY m.`usergroup_id` LIMIT 10) `members` '
			. ',(SELECT COUNT(m.`id`) FROM `' . iaUsers::getTable(true) . '` m WHERE m.`usergroup_id` = u.`id` GROUP BY m.`usergroup_id`) `count`'
			. 'FROM `' . $this->_iaDb->prefix . $this->getTable() . '` u '
			. 'LEFT JOIN `' . $this->_iaDb->prefix . 'acl_privileges` p '
			. "ON (p.`type` = 'group' "
			. 'AND p.`type_id` = u.`id` '
			. "AND `object` = 'admin_access' "
			. "AND `action` = 'read' "
			. ')'
			. $order . ' '
			. 'LIMIT ' . $start . ', ' . $limit;

		$usergroups = $this->_iaDb->getAll($sql);
		foreach ($usergroups as &$usergroup)
		{
			$usergroup['title'] = iaLanguage::get('usergroup_' . $usergroup['name']);
		}

		return $usergroups;
	}

	protected function _preSaveEntry(array &$entry, array $data, $action)
	{
		$entry['assignable'] = (int)$data['visible'];
		$entry['visible'] = (int)$data['visible'];

		if (iaCore::ACTION_ADD == $action)
		{
			if (empty($data['name']))
			{
				$this->addMessage('error_usergroup_incorrect');
			}
			else
			{
				$entry['name'] = strtolower(iaSanitize::paranoid($data['name']));
				if (!iaValidate::isAlphaNumericValid($entry['name']))
				{
					$this->addMessage('error_usergroup_incorrect');
				}
				elseif ($this->_iaDb->exists('`name` = :name', array('name' => $entry['name'])))
				{
					$this->addMessage('error_usergroup_exists');
				}
			}
		}

		foreach ($this->_iaCore->languages as $code => $language)
		{
			if (empty($data['title'][$code]))
			{
				$this->addMessage(iaLanguage::getf('error_lang_title', array('lang' => $language['title'])), false);
			}
		}

		return !$this->getMessages();
	}

	protected function _postSaveEntry(array &$entry, array $data, $action)
	{
		iaUtil::loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

		foreach ($this->_iaCore->languages as $code => $language)
		{
			$title = utf8_is_valid($data['title'][$code]) ? $data['title'][$code] : utf8_bad_replace($data['title'][$code]);
			iaLanguage::addPhrase('usergroup_' . $entry['name'], $title, $code);
		}

		// copy privileges
		$copyFrom = isset($data['copy_from']) ? (int)$data['copy_from'] : 0;
		if ($copyFrom)
		{
			$this->_iaDb->setTable('acl_privileges');

			$rows = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`type_id` = '{$copyFrom}' AND `type` = 'group'");
			foreach ($rows as $key => &$row)
			{
				$row['type_id'] = $entry['id'];
				unset($rows[$key]['id']);
			}
			$this->_iaDb->insert($rows);

			$this->_iaDb->resetTable();
		}
	}

	protected function _assignValues(&$iaView, array &$entryData)
	{
		iaBreadcrumb::replaceEnd(iaLanguage::get('add_usergroup'), IA_SELF);

		$iaView->assign('groups', $this->_iaDb->keyvalue(array('id', 'name')));
	}

	private function _getUsergroups()
	{
		$result = array('data' => array());

		foreach ($this->_iaUsers->getUsergroups() as $id => $name)
		{
			$result['data'][] = array('value' => $id, 'title' => iaLanguage::get('usergroup_' . $name));
		}

		return $result;
	}
}
