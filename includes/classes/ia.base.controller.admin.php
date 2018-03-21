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

abstract class iaAbstractControllerBackend
{
    const EQUAL = 'equal';
    const LIKE = 'like';

    protected $_iaCore;
    protected $_iaDb;

    /**
     * @var string smarty/javascript controller files
     */
    protected $_name;

    /**
     * @var string table to work with
     */
    protected $_table;

    /**
     * @var string URL to be used
     */
    protected $_path;

    /**
     * @var string custom smarty template controller
     */
    protected $_template;

    /**
     * @var object php class helper
     */
    protected $_helper;

    protected $_gridColumns;
    protected $_gridFilters;
    protected $_gridSorting;
    protected $_gridQueryMainTableAlias = '';

    protected $_entryId = 0;

    protected $_processAdd = true;
    protected $_processEdit = true;
    protected $_processDelete = true;

    protected $_systemFieldsEnabled = true;
    protected $_permissionsEdit = false;
    protected $_tooltipsEnabled = false;

    protected $_messages = [];

    protected $_phraseAddSuccess = 'saved';
    protected $_phraseEditSuccess = 'saved';
    protected $_phraseSaveError = 'db_error';
    protected $_phraseGridEntryDeleted = 'deleted';
    protected $_phraseGridEntriesDeleted = 'items_deleted';


    public function __construct()
    {
        $this->_iaCore = iaCore::instance();
        $this->_iaDb = &$this->_iaCore->iaDb;

        $this->_gridQueryMainTableAlias = empty($this->_gridQueryMainTableAlias) ? '' : $this->_gridQueryMainTableAlias . '.';

        $this->_iaCore->factory('util');

        $this->_table || $this->setTable($this->getName());
        $this->_path = IA_ADMIN_URL . $this->getName() . IA_URL_DELIMITER;
        $this->_template = $this->getName();
    }

    // common flow processing
    final public function process()
    {
        $iaView = &$this->_iaCore->iaView;

        $this->_iaDb->setTable($this->getTable());

        if (iaView::REQUEST_JSON == $iaView->getRequestType()) {
            switch ($iaView->get('action')) {
                case iaCore::ACTION_READ:
                    $output = $this->_gridRead($_GET);
                    break;

                case iaCore::ACTION_EDIT:
                    $output = $this->_gridUpdate($_POST);
                    break;

                case iaCore::ACTION_DELETE:
                    $output = $this->_gridDelete($_POST);
                    break;

                default:
                    $output = $this->_jsonAction($iaView);
            }

            $iaView->assign($output);
        }

        if (iaView::REQUEST_HTML == $iaView->getRequestType()) {
            switch ($iaView->get('action')) {
                case iaCore::ACTION_READ:
                    $this->_indexPage($iaView);

                    break;

                case iaCore::ACTION_ADD:
                    if (!$this->_processAdd) {
                        return iaView::errorPage(iaView::ERROR_NOT_FOUND);
                    }

                    $entry = [];
                    $this->_setDefaultValues($entry);

                    // intentionally missing BREAK stmt

                case iaCore::ACTION_EDIT:
                    if (iaCore::ACTION_EDIT == $iaView->get('action')) {
                        if (!$this->_processEdit) {
                            return iaView::errorPage(iaView::ERROR_NOT_FOUND);
                        }

                        $this->_retrieveEntryId();

                        if (is_null($this->getEntryId())) {
                            return iaView::errorPage(iaView::ERROR_NOT_FOUND);
                        }

                        $entry = $this->getById($this->getEntryId());
                        if (empty($entry)) {
                            return iaView::errorPage(iaView::ERROR_NOT_FOUND);
                        }

                        unset($entry['id']);
                    }

                    $data = $_POST; // own variable copy
                    if (isset($data['save'])) {
                        $reopenOption = empty($data['goto']) ? null : $data['goto'];
                        unset($data['save'], $data['goto'], $data['v']);

                        $result = $this->_saveEntry($iaView, $entry, $data);

                        $iaView->setMessages($this->getMessages(), $result ? iaView::SUCCESS : iaView::ERROR);
                        empty($result) || $this->_reopen($reopenOption, $iaView->get('action'));
                    }

                    $this->_assignValues($iaView, $entry);
                    $this->_assignGoto($iaView);

                    empty($this->_permissionsEdit) || $this->_assignPermissionsValues($iaView, $entry);

                    $this->_unwrapValues($entry);
                    $this->_defaultAssigns($iaView, $entry);

                    if ($this->_tooltipsEnabled) {
                        $iaView->assign('tooltips', iaLanguage::getTooltips());
                    }
                    if (!$this->_systemFieldsEnabled) {
                        $iaView->assign('noSystemFields', true);
                    }

                    $this->_setPageTitle($iaView, $entry, $iaView->get('action'));

                    $iaView->display($this->_template);

                    break;

                default:
                    $this->_htmlAction($iaView);
            }
        }

        $this->_iaDb->resetTable();
    }

    protected function _preSaveEntry(array &$entry, array $data, $action)
    {
        $entry = array_merge($entry, $data);

        return true;
    }

    protected function _postSaveEntry(array &$entry, array $data, $action)
    {
    }

    final protected function _saveEntry(&$iaView, array &$entry, array $data)
    {
        $result = $this->_preSaveEntry($entry, $data, $iaView->get('action'));

        if ($result) {
            $result = (iaCore::ACTION_ADD == $iaView->get('action'))
                ? $this->_entryAdd($entry)
                : $this->_entryUpdate($entry, $this->getEntryId());

            if ($result && iaCore::ACTION_ADD == $iaView->get('action')) {
                $this->_entryId = $result;
            }
        }

        if ($result) {
            if ($this->_permissionsEdit) {
                $this->_savePermissions($entry);
            }

            $this->_postSaveEntry($entry, $data, $iaView->get('action'));

            $message = (iaCore::ACTION_ADD == $iaView->get('action'))
                ? $this->_phraseAddSuccess
                : $this->_phraseEditSuccess;
            $this->addMessage($message);
        } else {
            $this->getMessages() || $this->addMessage($this->_phraseSaveError);
        }

        return (bool)$result;
    }

    protected function _indexPage(&$iaView)
    {
        $iaView->grid('admin/' . $this->getName());
    }

    /**
     * Retrieves ID of entry currently being managed
     *
     * Should return NULL if no ID
     *
     * @return string or NULL
     */
    protected function _retrieveEntryId()
    {
        $this->_entryId = isset($this->_iaCore->requestPath[0])
            ? $this->_iaCore->requestPath[0]
            : null;
    }

    protected function _assignValues(&$iaView, array &$entryData)
    {
    }

    protected function _defaultAssigns(&$iaView, array &$entryData)
    {
        $iaView->assign('id', $this->getEntryId());
        $iaView->assign('item', $entryData);
    }

    protected function _unwrapValues(array &$entryData)
    {
    }

    protected function _setDefaultValues(array &$entry)
    {
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function getMessages()
    {
        return $this->_messages;
    }

    public function addMessage($messageText, $translate = true)
    {
        $this->_messages[] = $translate ? iaLanguage::get($messageText) : $messageText;
    }

    public function getEntryId()
    {
        return $this->_entryId;
    }

    public function getById($id)
    {
        return $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));
    }

    public function getTable()
    {
        return $this->_table;
    }
    public function setTable($tableName)
    {
        $this->_table = $tableName;
    }

    public function getHelper()
    {
        return $this->_helper;
    }
    public function setHelper($helperClassInstance)
    {
        $this->_helper = &$helperClassInstance;
    }

    protected function _gridRead($params)
    {
        $params || $params = [];

        $start = isset($params['start']) ? (int)$params['start'] : 0;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 15;
        $order = $this->_gridGetSorting($params);

        $conditions = $values = [];

        $this->_gridApplyFilters($conditions, $values, $params);
        $this->_gridModifyParams($conditions, $values, $params);

        $conditions || $conditions[] = iaDb::EMPTY_CONDITION;
        $conditions = implode(' AND ', $conditions);
        $this->_iaDb->bind($conditions, $values);

        $columns = $this->_gridUnpackColumnsArray();

        $output = [
            'data' => $this->_gridQuery($columns, $conditions, $order, $start, $limit),
            'total' => $this->_iaDb->foundRows()
        ];

        if ($output['data']) {
            $this->_gridModifyOutput($output['data']);
            $this->_gridEscapeOutput($output['data']);
        }

        return $output;
    }

    protected function _gridGetSorting(array $params)
    {
        if (empty($params['sort']) || empty($params['dir'])) {
            return '';
        }

        $direction = in_array($params['dir'], [iaDb::ORDER_ASC, iaDb::ORDER_DESC])
            ? $params['dir']
            : iaDb::ORDER_ASC;
        $column = isset($this->_gridSorting[$params['sort']])
            ? (is_array($this->_gridSorting[$params['sort']]) ? $this->_gridSorting[$params['sort']][0] : $this->_gridSorting[$params['sort']])
            : $params['sort'];
        $tableAlias = isset($this->_gridSorting[$params['sort']][1]) && is_array($this->_gridSorting[$params['sort']])
            ? $this->_gridSorting[$params['sort']][1] . '.'
            : $this->_gridQueryMainTableAlias;

        return sprintf(' ORDER BY %s`%s` %s', $tableAlias, $column, $direction);
    }

    protected function _gridApplyFilters(&$conditions, &$values, array $params)
    {
        if (!is_array($this->_gridFilters) || !$this->_gridFilters) {
            return;
        }

        foreach ($this->_gridFilters as $name => $type) {
            if (!empty($params[$name])) {
                $value = $params[$name];

                switch ($type) {
                    case self::EQUAL:
                        $conditions[] = sprintf('%s`%s` = :%s', $this->_gridQueryMainTableAlias, $name, $name);
                        $values[$name] = $value;
                        break;
                    case self::LIKE:
                        $conditions[] = sprintf('%s`%s` LIKE :%s', $this->_gridQueryMainTableAlias, $name, $name);
                        $values[$name] = '%' . $value . '%';
                }
            }
        }
    }

    // to be overloaded if required to modify the DB query params
    protected function _gridModifyParams(&$conditions, &$values, array $params)
    {

    }

    protected function _gridUpdate($params)
    {
        $output = [
            'result' => false,
            'message' => iaLanguage::get('invalid_parameters')
        ];

        $params || $params = [];

        if (isset($params['id']) && is_array($params['id']) && count($params) > 1) {
            $ids = $params['id'];
            unset($params['id']);

            $total = count($ids);
            $affected = 0;

            foreach ($ids as $id) {
                if ($this->_entryUpdate($params, $id)) {
                    $affected++;
                }
            }

            if ($affected) {
                $output['result'] = true;
                $output['message'] = ($affected == $total)
                    ? iaLanguage::get('saved')
                    : iaLanguage::getf('items_updated_of', ['num' => $affected, 'total' => $total]);
            } else {
                $output['message'] = iaLanguage::get($this->_phraseSaveError);
            }
        }

        return $output;
    }

    protected function _gridDelete($params)
    {
        $output = [
            'result' => false,
            'message' => iaLanguage::get('invalid_parameters')
        ];

        if (isset($params['id']) && is_array($params['id']) && $params['id']) {
            $affected = 0;
            $total = count($params['id']);

            foreach ($params['id'] as $id) {
                if ($this->_entryDelete($id)) {
                    $affected++;
                }
            }

            $output['result'] = ($affected == $total);

            if (1 == $total) {
                $output['message'] = iaLanguage::get($output['result'] ? $this->_phraseGridEntryDeleted : $this->_phraseSaveError);
            } else {
                $output['message'] = $output['result']
                    ? iaLanguage::getf($this->_phraseGridEntriesDeleted, ['num' => $affected])
                    : iaLanguage::getf('items_deleted_of', ['num' => $affected, 'total' => $total]);
            }
        }

        return $output;
    }

    protected function _gridUnpackColumnsArray()
    {
        $result = '';

        if (is_array($this->_gridColumns)) {
            $persistentColumns = ['id'];

            if ($this->_processEdit) {
                $persistentColumns['update'] = 1;
            }
            if ($this->_processDelete) {
                $persistentColumns['delete'] = 1;
            }

            $this->_gridColumns = array_merge($persistentColumns, $this->_gridColumns);

            foreach ($this->_gridColumns as $key => $field) {
                $result.= is_int($key)
                    ? $this->_gridQueryMainTableAlias . '`' . $field . '`'
                    : sprintf('%s `%s`', is_numeric($field) ? $field : $this->_gridQueryMainTableAlias . '`' . $field . '`', $key);
                $result.= ', ';
            }

            $result = substr($result, 0, -2);
        } else {
            $result = $this->_gridColumns;
        }

        $result = iaDb::STMT_CALC_FOUND_ROWS . ' ' . $result;

        return $result;
    }

    protected function _gridQuery($columns, $where, $order, $start, $limit)
    {
        return $this->_iaDb->all($columns, $where . $order, $start, $limit);
    }

    protected function _gridEscapeOutput(array &$entries)
    {
        // 'array_map()' may be used instead
        foreach ($entries as &$entry) {
            foreach ($entry as $key => &$value) {
                if (is_string($value) && !is_numeric($value)) {
                    $value = iaSanitize::html($value);
                }
            }
        }
    }

    // to be overloaded if required to modify the resulting array
    protected function _gridModifyOutput(array &$entries)
    {

    }

    protected function _entryAdd(array $entryData)
    {
        return $this->_iaDb->insert($entryData);
    }

    protected function _entryDelete($entryId)
    {
        return (bool)$this->_iaDb->delete(iaDb::convertIds($entryId));
    }

    protected function _entryUpdate(array $entryData, $entryId)
    {
        $this->_iaDb->update($entryData, iaDb::convertIds($entryId));

        return (0 === $this->_iaDb->getErrorNumber());
    }

    protected function _reopen($option, $action)
    {
        $options = [
            'add' => $this->getPath() . 'add/',
            'list' => $this->getPath(),
            'stay' => $this->getPath() . 'edit/' . $this->getEntryId() . '/',
        ];
        $option = isset($options[$option]) ? $option : 'list';

        if ((iaCore::ACTION_EDIT == $action && 'stay' != $option)
            || iaCore::ACTION_ADD == $action) {
            $this->_iaCore->factory('util');
            iaUtil::go_to($options[$option]);
        }
    }

    protected function _assignPermissionsValues(&$iaView, $entryData)
    {
        $iaAcl = $this->_iaCore->factory('acl');
        $iaUsers = $this->_iaCore->factory('users');

        $objectId = empty($entryData['name']) ? '' : $entryData['name'];
        $objectName = substr($this->getName(), 0, -1);
        $actionCode = $iaAcl->encodeAction($objectName, iaCore::ACTION_READ, $objectId);
        $data = [];
        $modified = false;
        $usergroups = $this->_iaDb->all(['id', 'name', 'system'], null, null, null, iaUsers::getUsergroupsTable());

        foreach ($usergroups as $entry) {
            if (iaUsers::MEMBERSHIP_ADMINISTRATOR == $entry['id']) {
                continue;
            }

            $custom = [
                'group' => $entry['id'],
                'perms' => $iaAcl->getPermissions(0, $entry['id'])
            ];

            $data[] = [
                'id' => $entry['id'],
                'title' => iaLanguage::get('usergroup_' . $entry['name']),
                'default' => $iaAcl->checkAccess($objectName, $objectId, 0, 0, true),
                'access' => (int)$iaAcl->checkAccess($objectName, $objectId, 0, 0, $custom),
                'system' => $entry['system']
            ];

            if (isset($custom['perms'][$actionCode]) && !$modified) {
                $modified = true;
            }
        }

        $iaView->assign('ugp_modified', $modified);
        $iaView->assign('ugp', $data);
    }

    protected function _savePermissions($entryData)
    {
        $iaAcl = $this->_iaCore->factory('acl');

        $objectName = substr($this->getName(), 0, -1);

        $iaAcl->drop($objectName, $entryData['name'], iaAcl::GROUP);

        if (!isset($_POST['permissions_defaults'])) {
            foreach ($_POST['permissions'] as $usergroupId => $access) {
                $defaultAccess = $iaAcl->checkAccess($objectName, $entryData['name'], 0, 0, true);

                if ($access != $defaultAccess) { // populate the DB only when differs from default access
                    $iaAcl->set($objectName, $entryData['name'], iaAcl::GROUP, $usergroupId, $access);
                }
            }
        }
    }

    protected function _assignGoto(&$iaView)
    {
        $options = ['list' => 'go_to_list'];
        empty($this->_processAdd) || $options['add'] = 'add_another_one';
        empty($this->_processEdit) || $options['stay'] = 'stay_here';

        $iaView->assign('goto', $options);
    }

    protected function _setPageTitle(&$iaView, array $entryData, $action)
    {
        $phraseKey = $action . '_' . substr(str_replace('-', '_', $this->getName()), 0, -1);

        $iaView->title(iaLanguage::get($phraseKey, $iaView->title()));
    }

    protected function _jsonAction(&$iaView)
    {
        return ['code' => 501, 'error' => true]; // reply with "Not implemented" HTTP response code
    }

    protected function _htmlAction(&$iaView)
    {
        iaView::errorPage(iaView::ERROR_NOT_FOUND);
    }
}
