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
	protected $_name = 'usergroups';

	protected $_processEdit = false;
	protected $_tooltipsEnabled = true;

	protected $_phraseAddSuccess = 'usergroup_added';
	protected $_phraseGridEntryDeleted = 'usergroup_deleted';


	public function __construct()
	{
		parent::__construct();

		$this->setTable(iaUsers::getUsergroupsTable());
	}

	protected function _gridRead($params)
	{
		return ($this->_iaCore->requestPath && 'store' == end($this->_iaCore->requestPath))
			? $this->_getUsergroups()
			: parent::_gridRead($params);
	}

	protected function _entryAdd(array $entryData)
	{
		parent::_entryAdd($entryData);

		return $this->_iaDb->getAffected() ? $entryData['id'] : false;
	}

	protected function _entryDelete($entryId)
	{
		$result = parent::_entryDelete($entryId);

		if ($result)
		{
			$this->_iaDb->delete('`type` = :type AND `type_id` = :id', 'acl_privileges', array('type' => 'group', 'id' => $entryId)); // TODO: use the class method for this
			$this->_iaDb->update(array('usergroup_id' => iaUsers::MEMBERSHIP_REGULAR), iaDb::convertIds((int)$entryId, 'usergroup_id'), null, iaUsers::getTable());
		}

		return $result;
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
			. $order
			. 'LIMIT ' . $start . ', ' . $limit;

		return $this->_iaDb->getAll($sql);
	}

	protected function _preSaveEntry(array &$entry, array $data)
	{
		$iaAcl = $this->_iaCore->factory('acl');

		$entry['title'] = empty($data['title']) ? '' : $data['title'];
		$entry['id'] = $iaAcl->obtainFreeId();
		$entry['assignable'] = (int)$_POST['assignable'];

		if (empty($entry['title']))
		{
			$this->addMessage('error_usergroup_incorrect');
		}

		if ($this->_iaDb->exists('`title` = :title', $entry))
		{
			$this->addMessage('error_usergroup_exists');
		}

		return !$this->getMessages();
	}

	protected function _postSaveEntry(array $entry, array $data, $action)
	{
		// copying privileges
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
		//
	}

	protected function _assignValues(&$iaView, array &$entryData)
	{
		iaBreadcrumb::replaceEnd(iaLanguage::get('add_usergroup'), IA_SELF);

		$iaView->assign('groups', $this->_iaDb->keyvalue(array('id', 'title')));
	}

	private function _getUsergroups()
	{
		$result = array('data' => array());

		$iaUsers = $this->_iaCore->factory('users');
		foreach ($iaUsers->getUsergroups() as $id => $title)
		{
			$result['data'][] = array('value' => $id, 'title' => $title);
		}

		return $result;
	}
}