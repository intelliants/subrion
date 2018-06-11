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

class iaModule extends abstractCore
{
    const TYPE_CORE = 'core';
    const TYPE_PACKAGE = 'package';
    const TYPE_PLUGIN = 'plugin';
    const TYPE_TEMPLATE = 'template';

    const ACTION_INSTALL = 'install';
    const ACTION_REINSTALL = 'reinstall';
    const ACTION_UNINSTALL = 'uninstall';
    const ACTION_UPGRADE = 'upgrade';

    const CONFIG_ROLLBACK_DATA = 'tmpl_rollback_data';
    const CONFIG_LAYOUT_DATA = 'tmpl_layout_data';

    const SETUP_INITIAL = 1;
    const SETUP_REPLACE = 2;

    const SQL_STAGE_START = 'start';
    const SQL_STAGE_MIDDLE = 'middle';
    const SQL_STAGE_END = 'end';

    const VERSION_EMPTY = '0.0.0';

    const INSTALL_FILE_NAME = 'install.xml';

    const BLOCK_FILENAME_PATTERN = 'module:%s/%s';

    protected static $_table = 'modules';

    private $_builtinPlugins = ['kcaptcha', 'fancybox'];

    protected $_inTag;
    protected $_currentPath;
    protected $_attributes;
    protected $_section;

    protected $_url;
    protected $_menuGroups = [];

    protected $_parsed = false;
    protected $_quickParseMode = false;

    protected $_notes;

    protected $_xmlContent = '';

    public $itemData;

    public $error = false;
    public $isUpgrade = false;
    public $isUpdate = false;

    protected $iaConfig;


    public function init()
    {
        parent::init();

        $this->iaCore->factory(['acl', 'util', 'item']);
        $this->iaConfig = $this->iaCore->factory('config');

    }

    protected function _resetValues()
    {
        $this->error = false;

        $this->itemData = [
            'type' => self::TYPE_PLUGIN,
            'name' => '',
            'info' => [
                'author' => '',
                'contributor' => '',
                'date' => '',
                'summary' => '',
                'status' => iaCore::STATUS_ACTIVE,
                'title' => '',
                'version' => '',
                'category' => ''
            ],
            'actions' => null,
            'blocks' => null,
            'changeset' => null,
            'code' => [
                'install' => null,
                'upgrade' => null,
                'uninstall' => null
            ],
            'compatibility' => null,
            'config' => null,
            'config_groups' => null,
            'cron_jobs' => null,
            'custom_pages' => null,
            'dependencies' => null,
            'dumps' => null,
            'email_templates' => null,
            'fields' => null,
            'groups' => null,
            'hooks' => null,
            'items' => null,
            'item_fields' => null,
            'item_field_groups' => null,
            'image_types' => null,
            'layout' => null,
            'module' => '',
            'objects' => null,
            'pages' => [
                'admin' => null,
                'custom' => null,
                'front' => null,
            ],
            'permissions' => null,
            'phrases' => null,
            'requirements' => null,
            'screenshots' => null,
            'url' => null,
            'usergroups' => null,
            'sql' => [
                'install' => null,
                'upgrade' => null,
                'uninstall' => null
            ]
        ];

        $this->_notes = null;
    }

    public function setUrl($url)
    {
        $this->_url = $url;
    }

    protected function _lookupGroupId($groupName)
    {
        return (int)$this->iaDb->one_bind(iaDb::ID_COLUMN_SELECTION, '`name` = :name', ['name' => $groupName], 'admin_pages_groups');
    }

    public function parse($quickMode = false)
    {
        $this->_resetValues();
        $this->_quickParseMode = $quickMode;

        require_once IA_INCLUDES . 'xml/xml_saxy_parser.php';

        $xmlParser = new SAXY_Parser();

        $xmlParser->xml_set_element_handler([&$this, '_parserStart'], [&$this, '_parserEnd']);
        $xmlParser->xml_set_character_data_handler([&$this, $quickMode ? '_parserQuickData' : '_parserData']);
        $xmlParser->xml_set_comment_handler([&$this, '_parserComment']);

        $xmlParser->parse($this->_xmlContent);

        $this->_parsed = true;

        $this->_checkDependencies();
    }

    public function doAction($action, $url = '')
    {
        if (empty($action) || !in_array($action, [self::ACTION_INSTALL, self::ACTION_UPGRADE])) {
            $this->error = true;
            $this->setMessage('Fatal error: Action is invalid');

            return false;
        }

        $this->_url = $url;

        $this->_parsed || $this->parse();

        $this->checkValidity();

        if ($this->error) {
            return false;
        } else {
            $action = (self::ACTION_INSTALL == $action && $this->_isExist() && $this->_compare()) ? self::ACTION_UPGRADE : $action;
            return $this->{$action}();
        }
    }

    protected function _getVersion()
    {
        return $this->iaDb->one_bind('version', '`name` = :name', ['name' => $this->itemData['name']], self::getTable());
    }

    protected function _compare()
    {
        return version_compare(
            $this->iaDb->one_bind('version', '`name` = :name', ['name' => $this->itemData['name']], self::getTable()),
            $this->itemData['info']['version'],
            '<'
        );
    }

    protected function _isExist()
    {
        return $this->iaDb->exists('`name` = :name', ['name' => $this->itemData['name']]);
    }

    protected function _checkDependencies()
    {
        if ($this->itemData['dependencies']) {
            $iaCore = iaCore::instance();

            $currentTemplate = $iaCore->get('tmpl');
            $iaItem = $iaCore->factory('item');

            foreach ($this->itemData['dependencies'] as $moduleName => $dependency) {
                $shouldBeExist = (bool)$dependency['exist'];
                switch ($dependency['type']) {
                    case self::TYPE_PACKAGE:
                    case self::TYPE_PLUGIN:
                        $exists = $iaItem->isModuleExist($moduleName, $dependency['type']);
                        break;
                    case self::TYPE_TEMPLATE:
                        $exists = $moduleName == $currentTemplate;
                        break;
                }
                if (isset($exists)) {
                    if (!$exists && $shouldBeExist) {
                        $messageCode = defined('INSTALL') ? 'Requires the &ldquo;:module&rdquo; :type to be installed.'
                            : 'installation_module_requirement_exist';
                    } elseif ($exists && !$shouldBeExist) {
                        $messageCode = 'installation_module_requirement_doesnot_exist';
                    }
                    if (isset($messageCode)) {
                        $this->_notes[] = iaDb::printf(iaLanguage::get($messageCode), ['module' => ucfirst($moduleName), 'type' => $dependency['type']]);
                        $this->error = true;
                    }
                } else {
                    $this->setMessage(iaLanguage::get('installation_module_requirement_incorrect'));
                }
            }
        }
    }

    public function checkValidity($moduleFolder = '')
    {
        $requiredFields = ['title', 'version', 'summary', 'author', 'contributor'];
        $missingFields = [];

        if (empty($this->itemData['name'])) {
            $this->error = true;
            $missingFields[] = 'name';
        } elseif ($moduleFolder && $moduleFolder != $this->itemData['name']) {
            $this->error = true;
            $this->_notes[] = sprintf("Folder name does not match module name in install.xml for '%s' module.",
                !empty($this->itemData['info']['title']) ? $this->itemData['info']['title'] : $moduleFolder);
        } else {
            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $this->itemData['info'])) {
                    $this->error = true;
                    $missingFields[] = $field;
                }
            }
        }

        if ($this->error) {
            if ($this->_notes) {
                $this->setMessage(implode('<br>', $this->_notes));
            } elseif (empty($missingFields)) {
                $this->setMessage('Fatal error: Probably specified file is not XML file or is not acceptable.');
            } else {
                $this->setMessage('Fatal error: The following fields are required: ' . implode(', ', $missingFields));
            }
        }
    }

    protected function _checkPath($items)
    {
        if (is_array($items)) {
            foreach ($items as $item) {
                if (in_array($item, $this->_currentPath)) {
                    return true;
                }
            }
        } else {
            if (in_array($items, $this->_currentPath)) {
                return true;
            }
        }

        return false;
    }

    protected function _attr($key, $default = '', $inArray = false)
    {
        $result = false;

        if (is_array($key)) {
            foreach ($key as $item) {
                if ($result === false && isset($this->_attributes[$item])) {
                    $result = $this->_attributes[$item];
                    break;
                }
            }
        } else {
            if (isset($this->_attributes[$key])) {
                $result = trim($this->_attributes[$key]);
            }
        }

        if ($result !== false) {
            if (is_array($inArray) && !in_array($result, $inArray)) {
                $result = $default;
            }
        } else {
            $result = $default;
        }

        return $result;
    }

    public function upgrade()
    {
        $this->isUpgrade = true;
        $iaDb = &$this->iaDb;

        $this->iaCore->startHook('phpModuleUpgradeBefore', ['module' => $this->itemData['name']]);

        $this->_processQueries('install', self::SQL_STAGE_START, true);
        $this->_processQueries('upgrade', self::SQL_STAGE_START);

        if ($this->itemData['groups']) {
            $iaDb->setTable('admin_pages_groups');

            $maxOrder = $iaDb->getMaxOrder();
            foreach ($this->itemData['groups'] as $title => $entry) {
                $iaDb->exists("`module` = '{$this->itemData['name']}' AND `name` = '{$entry['name']}'")
                    ? $iaDb->update($entry, "`module` = '{$this->itemData['name']}' AND `name` = '{$entry['name']}'")
                    : $iaDb->insert($entry, ['order' => ++$maxOrder]);
                $this->_addPhrase('pages_group_' . $entry['name'], $title, iaLanguage::CATEGORY_ADMIN);
            }

            $iaDb->resetTable();
        }

        if ($this->itemData['pages']['admin']) {
            $this->_processAdminPages($this->itemData['pages']['admin']);
        }

        if ($this->itemData['actions']) {
            $iaDb->setTable('admin_actions');

            foreach ($this->itemData['actions'] as $action) {
                if ($action['name'] = strtolower(str_replace(' ', '_', $action['name']))) {
                    $action['order'] = (empty($action['order']) || !is_numeric($action['order']))
                        ? $iaDb->getMaxOrder() + 1
                        : $action['order'];

                    $iaDb->exists('`name` = :name && `module` = :module', ['name' => $action['name'], 'module' => $this->itemData['name']])
                        ? $iaDb->update($action, "`name` = '{$action['name']}' && `module` = '{$this->itemData['name']}'")
                        : $iaDb->insert($action);
                }
            }

            $iaDb->resetTable();
        }

        if ($this->itemData['phrases']) {
            $this->_processPhrases($this->itemData['phrases']);
        }

        if ($this->itemData['config_groups']) {
            $this->iaConfig->deleteGroup('module', $this->itemData['name']);

            foreach ($this->itemData['config_groups'] as $title => $entry) {
                if ($this->iaConfig->insertGroup($entry)) {
                    $this->_addPhrase('config_group_' . $entry['name'], $title, iaLanguage::CATEGORY_ADMIN);
                }
            }
        }

        if ($this->itemData['objects']) {
            $iaDb->setTable('acl_objects');

            foreach ($this->itemData['objects'] as $obj) {
                $where = "`object` = '{$obj['object']}' AND `action` = '{$obj['action']}'";
                if ($obj['title']) {
                    $key = ($obj['object'] == $obj['pre_object'] ? '' : $obj['pre_object'] . '-') . $obj['object'];
                    iaLanguage::addPhrase($key, $obj['title'], null, $this->itemData['name'], iaLanguage::CATEGORY_COMMON, false);
                    unset($obj['title']);
                }

                $iaDb->exists($where)
                    ? $iaDb->update(['access' => $obj['access']], $where)
                    : $iaDb->insert($obj);
            }

            $iaDb->resetTable();
        }

        if ($this->itemData['config']) {
            $this->_processConfig($this->itemData['config']);
        }

        if ($this->itemData['email_templates']) {
            $this->_processEmailTemplates($this->itemData['email_templates']);
        }

        $iaBlock = $this->iaCore->factory('block', iaCore::ADMIN);

        if ($this->itemData['pages']['front']) {
            $iaDb->setTable('pages');

            $maxOrder = $iaDb->getMaxOrder();

            foreach ($this->itemData['pages']['front'] as $page) {
                if ($page['blocks'] && ($ids = $this->iaDb->onefield(iaDb::ID_COLUMN_SELECTION,
                        "`name` IN ('" . implode("','", $page['blocks']) . "')", null, null, iaBlock::getTable()))) {
                    foreach ($ids as $blockId) {
                        $iaBlock->setVisibility($blockId, true, [$page['name']], false);
                    }
                }

                $title = $page['title'];
                $content = empty($page['contents']) ? null : $page['contents'];

                is_int($page['group']) || $page['group'] = $this->_lookupGroupId($page['group']);
                $page['last_updated'] = date(iaDb::DATETIME_FORMAT);
                $page['order'] = ++$maxOrder;

                unset($page['title'], $page['blocks']);

                $result = $iaDb->exists('`name` = :name', $page)
                    ? $iaDb->update($page, iaDb::convertIds($page['name'], 'name'))
                    : $iaDb->insert($page);

                if ($result) {
                    empty($title) || $this->_addPhrase('page_title_' . $page['name'], $title, iaLanguage::CATEGORY_PAGE);
                    empty($contents) || $this->_addPhrase('page_content_' . $page['name'], $content, iaLanguage::CATEGORY_PAGE);

                    if ($page['fields_item'] && self::TYPE_PACKAGE == $this->itemData['type']
                        && !$iaDb->exists('`page_name` = :name AND `item` = :item', $page, 'items_pages')) {
                        $iaDb->insert(['page_name' => $page['name'], 'item' => $page['fields_item']], null, 'items_pages');
                    }
                }
            }

            $iaDb->resetTable();
        }

        if ($this->itemData['blocks']) {
            foreach ($this->itemData['blocks'] as $block) {
                $blockId = $iaDb->one_bind(iaDb::ID_COLUMN_SELECTION, '`module` = :plugin AND `name` = :block',
                    ['plugin' => $this->itemData['name'], 'block' => $block['name']], iaBlock::getTable());

                if ($blockId && in_array($block['type'], [iaBlock::TYPE_PHP, iaBlock::TYPE_SMARTY])) {
                    unset($block['classname']);
                    $iaBlock->update($block, $blockId);
                } elseif (!$blockId) {
                    $iaBlock->insert($block);
                }
            }
        }

        if ($this->itemData['hooks']) {
            $iaDb->setTable('hooks');
            foreach ($this->itemData['hooks'] as $hook) {
                $array = explode(',', $hook['name']);
                foreach ($array as $hookName) {
                    if (trim($hookName)) {
                        $hook['name'] = $hookName;

                        $stmt = '`module` = :plugin AND `name` = :hook';
                        $iaDb->bind($stmt, ['plugin' => $this->itemData['name'], 'hook' => $hook['name']]);

                        $iaDb->exists($stmt)
                            ? $iaDb->update($hook, $stmt)
                            : $iaDb->insert($hook);
                    }
                }
            }
            $iaDb->resetTable();
        }

        if ($this->itemData['usergroups']) {
            $iaAcl = $this->iaCore->factory('acl');

            $iaDb->setTable(iaUsers::getUsergroupsTable());
            foreach ($this->itemData['usergroups'] as $item) {
                if (!$iaDb->exists('`name` = :name', ['name' => $item['name']])) {
                    $configs = $item['configs'];
                    $permissions = $item['permissions'];
                    $usergroupId = $iaDb->insert([
                        'module' => $item['module'],
                        'name' => $item['name'],
                        'system' => true,
                        'assignable' => $item['assignable'],
                        'visible' => $item['visible']
                    ]);

                    $this->_addPhrase('usergroup_' . $item['name'], $item['title']);

                    $iaDb->setTable(iaCore::getCustomConfigTable());
                    $iaDb->delete("`type` = 'group' AND `type_id` = '$usergroupId'");
                    foreach ($configs as $config) {
                        $iaDb->insert([
                            'name' => $config['name'],
                            'value' => $config['value'],
                            'type' => iaAcl::GROUP,
                            'type_id' => $usergroupId,
                            'module' => $this->itemData['name']
                        ]); // add custom config
                    }
                    $iaDb->resetTable();

                    $iaDb->setTable('acl_privileges');
                    $iaDb->delete('`type` = :type AND `type_id` = :id', null, ['type' => iaAcl::GROUP, 'id' => $usergroupId]);
                    foreach ($permissions as $permission) {
                        $data = [
                            'object' => $permission['object'],
                            'object_id' => $permission['object_id'],
                            'action' => $permission['action'],
                            'access' => $permission['access'],
                            'type' => iaAcl::GROUP,
                            'type_id' => $usergroupId,
                            'module' => $permission['module']
                        ];

                        $iaDb->insert($data); // add privileges for usergroup
                    }
                    $iaDb->resetTable();
                }
            }
            $iaDb->resetTable();
        }

        $this->_processQueries('install', self::SQL_STAGE_MIDDLE, true);
        $this->_processQueries('upgrade', self::SQL_STAGE_MIDDLE);

        if ($this->itemData['items']) {
            $iaDb->setTable('items');

            foreach ($this->itemData['items'] as $item) {
                if (!$this->iaDb->exists('`item` = :item', $item)) {
                    $iaDb->insert(array_merge($item, ['module' => $this->itemData['name']]));
                }
            }

            $iaDb->resetTable();
        }

        if ($this->itemData['item_field_groups']) {
            $this->iaCore->factory('field');

            $iaDb->setTable(iaField::getTableGroups());

            $maxOrder = $iaDb->getMaxOrder();
            foreach ($this->itemData['item_field_groups'] as $entry) {
                $entry['order'] || $entry['order'] = ++$maxOrder;

                $title = $entry['title'];
                $description = $entry['description'];

                unset($entry['title'], $entry['description']);

                if ($id = $iaDb->one_bind(iaDb::ID_COLUMN_SELECTION, '`name` = :name AND `item` = :item', $entry)) {
                    unset($entry['name'], $entry['item']);

                    $iaDb->update($entry, iaDb::convertIds($id));
                    $result = (0 == $iaDb->getErrorNumber());
                } else {
                    $result = $iaDb->insert($entry);
                }

                if ($result) {
                    $key = sprintf(iaField::FIELDGROUP_TITLE_PHRASE_KEY, $entry['item'], $entry['name']);
                    $this->_addPhrase($key, $title);

                    $key = sprintf(iaField::FIELDGROUP_DESCRIPTION_PHRASE_KEY, $entry['item'], $entry['name']);
                    $this->_addPhrase($key, $description);
                }
            }

            $iaDb->resetTable();
        }

        if ($this->itemData['item_fields']) {
            $this->_processFields($this->itemData['item_fields']);
        }

        $this->_processQueries('install', self::SQL_STAGE_END, true);
        $this->_processQueries('upgrade', self::SQL_STAGE_END);

        if ($this->itemData['code']['upgrade']) {
            $this->_runPhpCode($this->itemData['code']['upgrade']);
        }

        $this->iaCore->startHook('phpModuleUpgradeBeforeSql', ['module' => $this->itemData['name'], 'data' => &$this->itemData['info']]);

        $iaDb->update($this->itemData['info'], "`name` = '{$this->itemData['name']}' AND `type` = '{$this->itemData['type']}'",
            ['date' => iaDb::FUNCTION_NOW], self::getTable());

        $this->iaCore->startHook('phpModuleUpgradeAfter', ['module' => $this->itemData['name']]);

        $this->iaCore->iaCache->clearAll();
    }

    public function uninstall($moduleName)
    {
        if (empty($moduleName)) {
            $this->error = true;
            $this->setMessage('Module name is empty.');

            return false;
        }

        $this->iaCore->startHook('phpModuleUninstallBefore', ['module' => $moduleName]);

        if ($this->iaCore->get('default_package') == $moduleName) {
            $this->iaCore->set('default_package', '', true);
        }
        $this->checkValidity();

        $moduleName = iaSanitize::sql($moduleName);

        $iaDb = &$this->iaDb;

        $this->iaCore->factory('field');

        $code = $iaDb->row_bind(['uninstall_code', 'uninstall_sql', 'rollback_data'], '`name` = :name', ['name' => $moduleName], self::getTable());

        if ($itemsList = $iaDb->onefield('item', iaDb::convertIds($moduleName, 'module'), null, null, 'items')) {
            $where = "`item` IN ('" . implode("','", $itemsList) . "')";
            $iaDb->cascadeDelete(['items_pages', 'favorites', 'views_log', 'payment_plans_options'], $where);
        }

        if ($imageTypeIds = $iaDb->onefield(iaDb::ID_COLUMN_SELECTION, iaDb::convertIds($moduleName, 'module'), null, null, iaField::getTableImageTypes())) {
            $iaDb->cascadeDelete([iaField::getTableImageTypesFileTypes(), iaField::getTableFieldsImageTypes()], iaDb::convertIds($imageTypeIds, 'image_type_id'));
        }

        if ($pagesList = $iaDb->onefield('`name`', "`module` = '{$moduleName}'", null, null, 'pages')) {
            if (in_array($this->iaCore->get('home_page'), $pagesList)) {
                $this->iaCore->set('home_page', 'index', true);
            }

            $iaDb->delete("`page_name` IN ('" . implode("','", $pagesList) . "')", 'menus');

            $iaDb->cascadeDelete(['objects_pages'], "`page_name` IN ('" . implode("','", $pagesList) . "')");

            // remove associated phrases
            $iaDb->setTable(iaLanguage::getTable());
            foreach(['title', 'content', 'meta_keywords', 'meta_description', 'meta_title'] as $type) {
                $iaDb->delete(sprintf("`key` IN ('page_%s_%s')", $type, implode("','page_{$type}_", $pagesList)));
            }
            $iaDb->resetTable();
        }

        $tableList = [
            'admin_actions',
            'admin_pages_groups',
            'admin_pages',
            'acl_privileges',
            iaLanguage::getTable(),
            iaConfig::getTable(),
            iaConfig::getCustomConfigTable(),
            iaConfig::getConfigGroupsTable(),
            'email_templates',
            'pages',
            'hooks',
            'acl_objects',
            iaField::getTableGroups(),
            iaField::getTableImageTypes(),
            'fields_tree_nodes',
            'cron'
        ];
        $iaDb->cascadeDelete($tableList, iaDb::convertIds($moduleName, 'module'));

        $iaDb->setTable(iaField::getTable());

        $stmt = '`module` LIKE :module';
        $this->iaDb->bind($stmt, ['module' => '%' . $moduleName . '%']);
        if ($itemsList) {
            $stmt.= " OR `item` IN ('" . implode("','", $itemsList) . "')";
        }

        if ($fields = $iaDb->all(['id', 'module'], $stmt)) {
            $fieldIds = [];

            foreach ($fields as $field) {
                $pluginsList = explode(',', $field['module']);
                if (count($pluginsList) > 1) {
                    unset($pluginsList[array_search($moduleName, $pluginsList)]);
                    $iaDb->update(['module' => implode(',', $pluginsList), 'id' => $field['id']]);
                } else {
                    $iaDb->delete(iaDb::convertIds($field['id']));
                }

                $fieldIds[] = $field['id'];
            }

            $where = '`field_id` IN (' . implode(',', $fieldIds) . ')';

            $this->iaDb->delete($where, iaField::getTablePages());
            $this->iaDb->delete($where, iaField::getTableRelations());
        }

        $iaDb->resetTable();

        $iaBlock = $this->iaCore->factory('block', iaCore::ADMIN);
        if ($blockIds = $iaDb->onefield(iaDb::ID_COLUMN_SELECTION, "`module` = '{$moduleName}'", null, null, iaBlock::getTable())) {
            foreach ($blockIds as $blockId) {
                $iaBlock->delete($blockId, false);
            }
        }

        if ($code['uninstall_sql']) {
            $code['uninstall_sql'] = unserialize($code['uninstall_sql']);
            if ($code['uninstall_sql'] && is_array($code['uninstall_sql'])) {
                foreach ($code['uninstall_sql'] as $sql) {
                    $iaDb->query(str_replace('{prefix}', $iaDb->prefix, $sql['query']));
                }
            }
        }

        $entry = $iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`name` = :name', ['name' => $moduleName], self::getTable());

        $iaDb->delete('`name` = :module', self::getTable(), ['module' => $moduleName]);
        $iaDb->delete('`module` = :module', 'items', ['module' => $moduleName]);

        empty($entry) || $this->_processCategory($entry, self::ACTION_UNINSTALL);

        if ($code['uninstall_code']) {
            $this->_runPhpCode($code['uninstall_code']);
        }

        if ($code['rollback_data']) {
            $rollbackData = unserialize($code['rollback_data']);
            if (is_array($rollbackData)) {
                $existPositions = $this->iaView->positions;
                foreach ($rollbackData as $sectionName => $actions) {
                    foreach ($actions as $name => $itemData) {
                        if (isset($itemData['position'])) {
                            if (!in_array($itemData['position'], $existPositions)) {
                                $itemData['position'] = '';
                                $itemData['status'] = iaCore::STATUS_INACTIVE;
                            }
                        }
                        $stmt = iaDb::printf("`name` = ':name'", ['name' => $name]);
                        $this->iaDb->update($itemData, $stmt, null, $sectionName);
                    }
                }
            }
        }

        // clear usergroups
        if ($usergroups = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($moduleName, 'module'), 0, null, iaUsers::getUsergroupsTable())) {
            $iaUsers = $this->iaCore->factory('users');
            foreach ($usergroups as $usergroup) {
                $iaUsers->deleteUsergroup($usergroup['id']);
            }
        }

        $this->iaCore->startHook('phpModuleUninstallAfter', ['module' => $moduleName]);

        $this->iaCore->iaCache->clearAll();

        return true;
    }

    public function install($type = self::SETUP_REPLACE)
    {
        $iaDb = &$this->iaDb;
        $this->iaCore->startHook('phpModuleInstallBefore', ['module' => $this->itemData['name']]);

        $modulesList = [];
        $array = $iaDb->all(['id', 'name', 'version'], "`status` = 'active'", null, null, self::getTable());
        foreach ($array as $item) {
            $modulesList[$item['name']] = $item;
        }

        // TODO: check for relations and deactivate all needed modules
        if ($this->itemData['requirements']) {
            $messages = [];
            foreach ($this->itemData['requirements'] as $requirement) {
                if ($requirement['min'] || $requirement['max']) {
                    $min = $max = false;
                    if (isset($modulesList[$requirement['name']])) {
                        $info = $modulesList[$requirement['name']];
                        $min = $requirement['min'] ? version_compare($requirement['min'], $info['version'], '<=') : true;
                        $max = $requirement['max'] ? version_compare($requirement['max'], $info['version'], '>=') : true;
                    }
                    if (!$max || !$min) {
                        $ver = '';
                        if ($requirement['min']) {
                            $ver .= $requirement['min'];
                        }
                        if ($requirement['max']) {
                            if ($requirement['min']) {
                                $ver .= '-';
                            }
                            $ver .= $requirement['max'];
                        }

                        $values = [
                            ':module' => $requirement['type'],
                            ':name' => $requirement['name'],
                            ':version' => $ver
                        ];
                        $messages[] = iaLanguage::getf('required_module_error', $values);
                        $this->error = true;
                    } else {
                        // TODO: add relations in database to deactivate when parent is uninstalled
                    }
                }
            }

            if ($this->error) {
                $this->setMessage(implode('<br />', $messages));
                return false;
            }
        }

        $this->uninstall($this->itemData['name']);

        if (self::TYPE_TEMPLATE == $this->itemData['type']) {
            if (self::SETUP_REPLACE == $type) {
                $templateName = $this->iaCore->get('tmpl');

                $tablesList = ['hooks', 'blocks', iaLanguage::getTable(), 'pages', iaConfig::getTable(),
                    iaConfig::getConfigGroupsTable(), iaConfig::getCustomConfigTable()];

                $iaDb->cascadeDelete($tablesList, iaDb::convertIds($templateName, 'module'));
                $iaDb->cascadeDelete($tablesList, iaDb::convertIds($this->itemData['name'], 'module'));
            }

            $this->iaCore->set('tmpl', $this->itemData['name'], true);
            $this->iaCore->set(self::CONFIG_LAYOUT_DATA, serialize($this->itemData['layout']), true);
        }

        if (false !== stristr('update', $this->itemData['name'])) {
            $this->isUpdate = true;
        }

        $this->_processQueries('install', self::SQL_STAGE_START);

        if ($this->itemData['groups']) {
            $iaDb->setTable('admin_pages_groups');

            $maxOrder = $iaDb->getMaxOrder();
            foreach ($this->itemData['groups'] as $title => $entry) {
                $iaDb->insert($entry, ['order' => ++$maxOrder]);
                $this->_addPhrase('pages_group_' . $entry['name'], $title, iaLanguage::CATEGORY_ADMIN);
            }

            $iaDb->resetTable();
        }

        !empty($this->itemData['positions']) && $this->_processPositions($this->itemData['positions']);

        !empty($this->itemData['image_types']) && $this->_processImageTypes($this->itemData['image_types']);

        if ($this->itemData['pages']['admin']) {
            $this->_processAdminPages($this->itemData['pages']['admin']);
        }

        !empty($this->itemData['actions']) && $this->_processActions($this->itemData['actions']);

        if ($this->itemData['phrases']) {
            $this->_processPhrases($this->itemData['phrases']);
        }

        if ($this->itemData['config_groups']) {
            foreach ($this->itemData['config_groups'] as $title => $entry) {
                if ($this->iaConfig->insertGroup($entry)) {
                    $this->_addPhrase('config_group_' . $entry['name'], $title, iaLanguage::CATEGORY_ADMIN);
                }
            }
        }

        if ($this->itemData['objects']) {
            $iaDb->setTable('acl_objects');
            foreach ($this->itemData['objects'] as $obj) {
                if ($obj['title']) {
                    $key = ($obj['object'] == $obj['pre_object'] ? '' : $obj['pre_object'] . '-') . $obj['object'] . '--' . $obj['action'];
                    iaLanguage::addPhrase($key, $obj['title'], null, $this->itemData['name'], iaLanguage::CATEGORY_COMMON, false);
                    unset($obj['title']);
                }
                $iaDb->insert($obj);
            }
            $iaDb->resetTable();
        }

        if ($this->itemData['permissions']) {
            $iaDb->setTable('acl_privileges');

            foreach ($this->itemData['permissions'] as $permission) {
                $iaDb->insert($permission);
            }

            $iaDb->resetTable();
        }

        if ($this->itemData['config']) {
            $this->_processConfig($this->itemData['config']);
        }

        if ($this->itemData['email_templates']) {
            $this->_processEmailTemplates($this->itemData['email_templates']);
        }

        if ($this->itemData['pages']['custom'] && $this->itemData['type'] == self::TYPE_PACKAGE) {
            $iaDb->setTable('items_pages');

            foreach ($this->itemData['pages']['custom'] as $page) {
                $iaDb->insert(['page_name' => $page['name'], 'item' => $page['item']]);
            }

            $iaDb->resetTable();
        }

        $iaBlock = $this->iaCore->factory('block', iaCore::ADMIN);

        $extraPages = [];
        if ($this->itemData['pages']['front']) {
            $pageGroups = $iaDb->keyvalue(['name', 'id'], null, 'admin_pages_groups');

            $iaDb->setTable('pages');

            $maxOrder = $iaDb->getMaxOrder();
            $existPages = $iaDb->keyvalue(['name', 'id']);

            foreach ($this->itemData['pages']['front'] as $page) {
                if (!isset($existPages[$page['name']])) {
                    if (self::TYPE_PACKAGE == $this->itemData['type'] && $page['fields_item']) {
                        $iaDb->insert(['page_name' => $page['name'], 'item' => $page['fields_item']], null, 'items_pages');
                    }

                    $title = $page['title'];
                    $blocks = empty($page['blocks']) ? false : $page['blocks'];
                    $menus = empty($page['menus']) ? [] : explode(',', $page['menus']);
                    $contents = empty($page['contents']) ? false : $page['contents'];

                    unset($page['title'], $page['blocks'], $page['menus'], $page['contents']);

                    $page['group'] = $pageGroups[$page['group']];

                    $pageId = $iaDb->insert($page, ['order' => ++$maxOrder, 'last_updated' => iaDb::FUNCTION_NOW]);

                    empty($title) || $this->_addPhrase('page_title_' . $page['name'], $title, iaLanguage::CATEGORY_PAGE);

                    if ($blocks && ($ids = $this->iaDb->onefield(iaDb::ID_COLUMN_SELECTION,
                            "`name` IN ('" . implode("','", $blocks) . "')", null, null, iaBlock::getTable()))) {
                        foreach ($ids as $blockId) {
                            $iaBlock->setVisibility($blockId, true, [$page['name']], false);
                        }
                    }

                    if (!is_int($page['group'])) {
                        $page['group'] = $this->_lookupGroupId($page['group']);
                    }

                    if ($menus) {
                        $iaDb->setTable(iaBlock::getTable());
                        $added = [];
                        $items = [];
                        $menusData = $iaDb->keyvalue(['id', 'name'], "`type` = 'menu'");
                        $db = false;

                        foreach ($menusData as $id => $name) {
                            if (in_array($name, $menus)) {
                                $added[] = $name;
                                $items[] = [
                                    'parent_id' => 0,
                                    'menu_id' => $id,
                                    'el_id' => $pageId . '_' . iaUtil::generateToken(4),
                                    'level' => 0,
                                    'page_name' => $page['name']
                                ];
                                $db = true;

                                $this->iaCore->iaCache->remove('menu_' . $id);
                            }
                        }

                        $db && $iaDb->insert($items, null, iaBlock::getMenusTable());

                        foreach ($menus as $val) {
                            if (!in_array($val, $added)) {
                                $menuItem = [
                                    'type' => iaBlock::TYPE_MENU,
                                    'status' => iaCore::STATUS_ACTIVE,
                                    'position' => 'left',
                                    'collapsible' => true,
                                    'title' => $this->itemData['info']['title'],
                                    'module' => $this->itemData['name'],
                                    'name' => $this->itemData['name'],
                                    'sticky' => true,
                                    'removable' => false
                                ];

                                $menuItem['id'] = $iaBlock->insert($menuItem);

                                $entry = [
                                    'parent_id' => 0,
                                    'menu_id' => $menuItem['id'],
                                    'el_id' => $pageId . '_' . iaUtil::generateToken(5),
                                    'level' => 0,
                                    'page_name' => $page['name']
                                ];

                                $iaDb->insert($entry, null, iaBlock::getMenusTable());
                            }
                        }

                        $iaDb->resetTable();
                    }

                    empty($contents) || $this->_addPhrase('page_content_' . $page['name'], $contents, iaLanguage::CATEGORY_PAGE);

                    $extraPages[] = $page['name'];
                }
            }

            $iaDb->resetTable();
        }

        $iaBlock = $this->iaCore->factory('block', iaCore::ADMIN);

        if ($this->itemData['blocks']) {
            foreach ($this->itemData['blocks'] as $block) {
                $iaBlock->insert($block);
            }
        }

        if ($this->itemData['hooks']) {
            $iaDb->setTable('hooks');

            $maxOrder = $iaDb->getMaxOrder();

            foreach ($this->itemData['hooks'] as $hook) {
                $array = explode(',', $hook['name']);
                foreach ($array as $hookName) {
                    if (trim($hookName)) {
                        $hook['name'] = $hookName;
                        if (isset($hook['code']) && $hook['code']) {
                            $hook['code'] = str_replace('{extras}', $this->itemData['name'], $hook['code']);
                        }
                        $rawValues = [];
                        if (!isset($hook['order'])) {
                            $rawValues['order'] = ++$maxOrder;
                        }

                        $iaDb->insert($hook, $rawValues);
                    }
                }
            }

            $iaDb->resetTable();
        }

        if (isset($this->itemData['plan_options'])) {
            $this->iaCore->factory('plan');

            $iaDb->setTable(iaPlan::getTableOptions());

            foreach ($this->itemData['plan_options'] as $item) {
                if (empty($item['type']) || empty($item['name']) || empty($item['item'])) {
                    continue;
                } // required fields

                $title = $item['title'];
                unset($item['title']);

                $iaDb->insert($item);
                $this->_addPhrase(sprintf('plan_option_%s_%s', $item['item'], $item['name']), $title);
            }

            $iaDb->resetTable();
        }

        if ($this->itemData['usergroups']) {
            $this->iaCore->factory('acl');

            $iaDb->setTable(iaUsers::getUsergroupsTable());
            $maxOrder = $iaDb->getMaxOrder();
            foreach ($this->itemData['usergroups'] as $item) {
                if (!$iaDb->exists('`name` = :name', ['name' => $item['name']])) {
                    $configs = $item['configs'];
                    $permissions = $item['permissions'];

                    $groupId = $iaDb->insert([
                        'module' => $item['module'],
                        'name' => $item['name'],
                        'system' => true,
                        'assignable' => $item['assignable'],
                        'visible' => $item['visible'],
                    ], ['order' => ++$maxOrder]);

                    // update language records
                    $this->_addPhrase('usergroup_' . $item['name'], $item['title']);

                    $iaDb->setTable(iaConfig::getCustomConfigTable());
                    $iaDb->delete('`type` = :type AND `type_id` = :id', null, ['type' => iaAcl::GROUP, 'id' => $groupId]);
                    foreach ($configs as $config) {
                        $data = [
                            'name' => $config['name'],
                            'value' => $config['value'],
                            'type' => iaAcl::GROUP,
                            'type_id' => $groupId,
                            'module' => $this->itemData['name']
                        ];
                        $iaDb->insert($data);
                    }
                    $iaDb->resetTable();

                    $iaDb->setTable('acl_privileges');
                    $iaDb->delete('`type` = :type AND `type_id` = :id', null, ['type' => iaAcl::GROUP, 'id' => $groupId]);
                    foreach ($permissions as $permission) {
                        $data = [
                            'object' => $permission['object'],
                            'object_id' => $permission['object_id'],
                            'action' => $permission['action'],
                            'access' => $permission['access'],
                            'type' => iaAcl::GROUP,
                            'type_id' => $groupId,
                            'module' => $permission['module']
                        ];

                        $iaDb->insert($data);
                    }
                    $iaDb->resetTable();
                }
            }
            $iaDb->resetTable();
        }

        $extraEntry = array_merge($this->itemData['info'], [
            'name' => $this->itemData['name'],
            'type' => $this->itemData['type']
        ]);
        unset($extraEntry['date']);

        if ($this->itemData['sql']['uninstall']) {
            $extraEntry['uninstall_sql'] = serialize($this->itemData['sql']['uninstall']);
        }

        if ($this->itemData['code']['uninstall']) {
            $extraEntry['uninstall_code'] = $this->itemData['code']['uninstall'];
        }

        $this->_processQueries('install', self::SQL_STAGE_MIDDLE);

        if (self::TYPE_PACKAGE == $this->itemData['type']) {
            $extraEntry['url'] = $this->_url;
        }

        if ($this->itemData['items']) {
            $extraEntry['items'] = serialize($this->itemData['items']);
            $iaDb->setTable('items');
            foreach ($this->itemData['items'] as $item) {
                $iaDb->insert(array_merge($item, ['module' => $this->itemData['name']]));
            }
            $iaDb->resetTable();
        }

        $this->iaCore->factory('field');

        if ($this->itemData['item_field_groups']) {
            $iaDb->setTable(iaField::getTableGroups());

            $maxOrder = $iaDb->getMaxOrder();
            foreach ($this->itemData['item_field_groups'] as $entry) {
                $entry['order'] || $entry['order'] = ++$maxOrder;

                $title = $entry['title'];
                $description = $entry['description'];

                unset($entry['title'], $entry['description']);

                if ($iaDb->insert($entry)) {
                    $key = sprintf(iaField::FIELDGROUP_TITLE_PHRASE_KEY, $entry['item'], $entry['name']);
                    $this->_addPhrase($key, $title);

                    $key = sprintf(iaField::FIELDGROUP_DESCRIPTION_PHRASE_KEY, $entry['item'], $entry['name']);
                    $this->_addPhrase($key, $description);
                }
            }

            $iaDb->resetTable();
        }

        if ($this->itemData['item_fields']) {
            $this->_processFields($this->itemData['item_fields']);
        }

        if ($this->itemData['cron_jobs']) {
            $this->iaCore->factory('cron');

            foreach ($this->itemData['cron_jobs'] as $job) {
                $job['module'] = $this->itemData['name'];
                $iaDb->insert($job, null, iaCron::getTable());
            }
        }

        $rollbackData = empty($this->itemData['changeset'])
            ? []
            : $this->_processChangeset($this->itemData['changeset']);

        $extraEntry['rollback_data'] = empty($rollbackData) ? '' : serialize($rollbackData);

        if (self::TYPE_PLUGIN == $this->itemData['type']) {
            $extraEntry['removable'] = !('blog' != $this->itemData['name'] && in_array($this->itemData['name'], $this->_builtinPlugins));
        }

        if (!$this->isUpdate) {
            $this->iaCore->startHook('phpModuleInstallBeforeSql', ['module' => $this->itemData['name'], 'data' => &$this->itemData['info']]);
            $iaDb->insert($extraEntry, ['date' => iaDb::FUNCTION_NOW], self::getTable());
        }

        $this->_processCategory($extraEntry);

        $this->_processQueries('install', self::SQL_STAGE_END);

        if ($this->itemData['code']['install']) {
            $this->_runPhpCode($this->itemData['code']['install']);
        }

        $this->iaCore->startHook('phpModuleInstallAfter', ['module' => $this->itemData['name']]);

        $this->iaCore->factory('cache')->clearAll();

        return true;
    }

    public function setXml($xmlContent)
    {
        $this->_xmlContent = $xmlContent;
        $this->_parsed = false;
    }

    public function getFromPath($filePath)
    {
        $xmlContent = file_get_contents($filePath);
        if (false !== $xmlContent) {
            $this->setXml($xmlContent);
            return true;
        }

        trigger_error('Could not open the installation XML file: ' . $filePath, E_USER_ERROR);
        return false;
    }

    public function _parserStart($parser, $name, $attributes)
    {
        $this->_inTag = $name;
        $this->_attributes = $attributes;
        $this->_currentPath[] = $name;

        if ('section' == $this->_inTag && isset($attributes['name'])) {
            $this->_section = $attributes['name'];
        }

        if ('module' == $this->_inTag && isset($this->_attributes['name'])) {
            $this->itemData['type'] = $this->_attributes['type'];
            $this->itemData['name'] = $this->_attributes['name'];
        }

        if ('usergroup' == $name) {
            $this->itemData['usergroups'][] = [
                'module' => $this->itemData['name'],
                'name' => $this->itemData['name'] . '_' . ($this->_attr('name', iaUtil::generateToken())),
                'title' => $attributes['title'],
                'assignable' => $this->_attr('assignable', false),
                'visible' => $this->_attr('visible', true),
                'configs' => [],
                'permissions' => []
            ];
        }
    }

    public function _parserQuickData($parser, $text)
    {
        if (in_array($this->_inTag, ['title', 'summary', 'author', 'contributor', 'version', 'date'])) {
            $this->itemData['info'][$this->_inTag] = trim($text);
        }

        if ('compatibility' == $this->_inTag) {
            $this->itemData[$this->_inTag] = $text;
        }

        if ('dependency' == $this->_inTag) {
            $this->itemData['dependencies'][$text] = [
                'type' => $this->_attr('type'),
                'exist' => $this->_attr('exist', true)
            ];
        }
    }

    public function _parserData($parser, $text)
    {
        $text = trim($text);

        switch ($this->_inTag) {
            case 'title':
            case 'summary':
            case 'author':
            case 'contributor':
            case 'version':
            case 'date':
            case 'category':
                $this->itemData['info'][$this->_inTag] = $text;
                break;

            case 'compatibility':
            case 'url':
                $this->itemData[$this->_inTag] = $text;
                break;

            case 'item':
                if ($this->_checkPath('items')) {
                    $this->itemData['items'][$text] = [
                        'item' => iaItem::toSingular($text),
                        'payable' => (int)$this->_attr('payable', true),
                        'pages' => $this->_attr('pages'),
                        'table_name' => $this->_attr('table_name'),
                        'class_name' => $this->_attr('class_name')
                    ];

                    if (isset($this->_attributes['pages']) && $this->_attributes['pages']) {
                        foreach (explode(',', $this->_attributes['pages']) as $val) {
                            $this->itemData['pages']['custom'][] = ['name' => $val, 'item' => $text];
                        }
                    }
                }
                break;

            case 'screenshot':
                if ($this->_checkPath('screenshots')) {
                    $this->itemData['screenshots'][] = [
                        'name' => $this->_attr('name'),
                        'title' => $text,
                        'type' => $this->_attr('type', 'lightbox')
                    ];
                }
                break;

            case 'dependency':
                $this->itemData['dependencies'][$text] = [
                    'type' => $this->_attr('type'),
                    'exist' => $this->_attr('exist', true)
                ];
                break;

            case 'extension':
                if ($this->_checkPath('requires')) {
                    $this->itemData['requirements'][] = [
                        'name' => $text,
                        'type' => $this->_attr('type', 'package', [self::TYPE_PACKAGE, self::TYPE_PLUGIN]),
                        'min' => $this->_attr(['min_version', 'min'], false),
                        'max' => $this->_attr(['max_version', 'max'], false)
                    ];
                }
                break;

            case 'action':
                if ($this->_checkPath('actions')) {
                    $this->itemData['actions'][] = [
                        'attributes' => $this->_attr('attributes'),
                        'module' => $this->itemData['name'],
                        'icon' => $this->_attr('icon'),
                        'name' => $this->_attr('name'),
                        'pages' => $this->_attr('pages'),
                        'text' => $text,
                        'type' => $this->_attr('type', 'regular'),
                        'url' => $this->_attr('url')
                    ];
                }
                break;

            case 'cron':
                $cron = $this->_attributes;
                $cron['data'] = $text;
                $this->itemData['cron_jobs'][] = $cron;
                break;

            case 'page':
                if ($this->_checkPath('adminpages')) {
                    $this->itemData['pages']['admin'][] = [
                        'name' => $this->_attr('name'),
                        'filename' => $this->_attr('filename'),
                        'alias' => $this->_attr('url'),
                        'status' => $this->_attr('status', iaCore::STATUS_ACTIVE, [iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE]),
                        'group' => $this->_attr('group', 'extensions'),
                        'order' => $this->_attr('order', null),
                        'menus' => $this->_attr('menus'),
                        'action' => $this->_attr('action', iaCore::ACTION_READ),
                        'parent' => $this->_attr('parent'),
                        'module' => $this->itemData['name'],
                        'title' => $text
                    ];
                } elseif ($this->_checkPath('pages')) {
                    $url = $this->_attr('url');
                    $url = $this->itemData['url'] && $url ? str_replace('|PACKAGE|', ltrim($this->_url, IA_URL_DELIMITER), $url) : $url;
                    $url = empty($url) ? $this->itemData['name'] . IA_URL_DELIMITER : $url;

                    $blocks = trim($this->_attr('blocks'));
                    $blocks = empty($blocks) ? null : explode(',', $blocks);

                    // TODO: add pages param to display some existing blocks on new page
                    $this->itemData['pages']['front'][] = [
                        'name' => $this->_attr('name'),
                        'filename' => $this->_attr('filename'),
                        'custom_tpl' => (bool)$this->_attr('template', 0),
                        'template_filename' => $this->_attr('template', ''),
                        'menus' => $this->_attr('menus'),
                        'status' => $this->_attr('status', iaCore::STATUS_ACTIVE),
                        'alias' => $url,
                        'custom_url' => $this->_attr('custom_url'),
                        'service' => $this->_attr('service', false),
                        'blocks' => $blocks,
                        'nofollow' => $this->_attr('nofollow', false),
                        'new_window' => $this->_attr('new_window', false),
                        'readonly' => $this->_attr('readonly', true),
                        'module' => $this->itemData['name'],
                        'group' => $this->_attr('group', ($this->itemData['type'] == self::TYPE_PLUGIN) ? 'extensions' : $this->itemData['type']),
                        'action' => $this->_attr('action', iaCore::ACTION_READ),
                        'parent' => $this->_attr('parent'),
                        'suburl' => $this->_attr('suburl'),
                        'fields_item' => iaItem::toSingular($this->_attr('fields_item', '')),
                        'title' => $text
                    ];
                }
                break;

            case 'configgroup':
                $this->itemData['config_groups'][$text] = [
                    'name' => $this->_attr('name'),
                    'module' => $this->itemData['name']
                ];
                break;

            case 'config':
                if ($this->_checkPath('usergroup') && $this->itemData['usergroups']) {
                    $this->itemData['usergroups'][count($this->itemData['usergroups']) - 1]['configs'][] = [
                        'name' => $this->_attr('name'),
                        'value' => $text
                    ];
                } else {
                    $group = $this->_attr('group');

                    // compatibity code
                    // TODO: remove once packages updated
                    if ('email_templates' == $group) {
                        $name = $this->_attr('name');

                        if ('divider' == $this->_attr('type')) {
                            if (!$name) {
                                $name = $this->itemData['name'] . '_div_' . iaUtil::generateToken(4);
                            }

                            $this->itemData['email_templates'][$name] = [
                                'active' => true,
                                'divider' => true,
                                'subject' => '',
                                'body' => '',
                                'description' => $this->_attr('description')
                            ];

                            break;
                        }

                        if ('_body' == substr($name, -5)) {
                            $name = substr($name, 0, -5);

                            $this->itemData['email_templates'][$name]['body'] = $text;
                            $this->itemData['email_templates'][$name]['variables'] = $this->_attr('values');
                        } elseif ('_subject' == substr($name, -8)) {
                            $name = substr($name, 0, -8);

                            $this->itemData['email_templates'][$name]['subject'] = $text;
                        } else {
                            $this->itemData['email_templates'][$name] = [
                                'active' => (int)$text,
                                'description' => $this->_attr('description')
                            ];
                        }

                        break;
                    }
                    //

                    $this->itemData['config'][] = [
                        'config_group' => $group,
                        'name' => $this->_attr('name'),
                        'value' => $text,
                        'multiple_values' => $this->_attr('values'),
                        'type' => $this->_attr('type'),
                        'description' => $this->_attr('description'),
                        'private' => $this->_attr('private', true),
                        'custom' => $this->_attr('custom', true),
                        'options' => [
                            'wysiwyg' => $this->_attr('wysiwyg', 0),
                            'code_editor' => $this->_attr('code_editor', 0),
                            'show' => $this->_attr('show'),
                            'multilingual' => $this->_attr('multilingual', 0)
                        ]
                    ];
                }
                break;

            case 'permission':
                $entry = [
                    'access' => $this->_attr('access', 0, [0, 1]),
                    'action' => $this->_attr('action', iaCore::ACTION_READ),
                    'object' => $this->_attr('object', iaAcl::OBJECT_PAGE),
                    'object_id' => $text,
                    'module' => $this->itemData['name']
                ];

                if ($this->_checkPath('permissions')) {
                    $this->itemData['permissions'][] = $entry + [
                        'type' => $this->_attr('type', iaAcl::GROUP, [iaAcl::USER, iaAcl::GROUP, iaAcl::PLAN]),
                        'type_id' => $this->_attr('type_id')
                        ];
                } elseif ($this->_checkPath('usergroup') && $this->itemData['usergroups']) {
                    $this->itemData['usergroups'][count($this->itemData['usergroups']) - 1]['permissions'][] = $entry;
                }

                break;

            case 'object':
                $this->itemData['objects'][] = [
                    'object' => $this->_attr('id'),
                    'pre_object' => $this->_attr('meta_object', iaAcl::OBJECT_PAGE),
                    'action' => $this->_attr('action', iaCore::ACTION_READ),
                    'access' => $this->_attr('access', '0', [0, 1]),
                    'module' => $this->itemData['name'],
                    'title' => $text
                ];
                break;

            case 'group':
                switch (true) {
                    case $this->_checkPath('fields_groups'):
                        $this->itemData['item_field_groups'][] = [
                            'module' => $this->itemData['name'],
                            'item' => iaItem::toSingular($this->_attr('item')),
                            'name' => $this->_attr('name'),
                            'collapsible' => $this->_attr('collapsible', false),
                            'collapsed' => $this->_attr('collapsed', false),
                            'tabview' => $this->_attr('tabview', false),
                            'tabcontainer' => $this->_attr('tabcontainer'),
                            'order' => $this->_attr('order', 0),
                            'title' => $this->_attr('title'),
                            'description' => $text
                        ];
                        break;
                    case $this->_checkPath('groups'):
                        $this->itemData['groups'][$text] = [
                            'name' => $this->_attr('name'),
                            'module' => $this->itemData['name']
                        ];
                }
                break;

            case 'field':
                if ($this->_checkPath('fields')) {
                    $values = '';

                    if (isset($this->_attributes['values'])) {
                        $values = $this->_attributes['values'];

                        if ('tree' != $this->_attr('type')) {
                            $array = explode((false !== strpos($values, '::')) ? '::' : ',', $values);
                            $values = [];

                            foreach ($array as $k => $v) {
                                $a = explode('||', $v);
                                isset($a[1]) ? ($values[$a[0]] = $a[1]) : ($values[$k + 1] = $v);
                            }
                        }
                    }

                    // get item table & class names
                    $itemTable = empty($this->itemData['items'][$this->_attr('item')]['table_name'])
                        ? $this->_attr('item')
                        : $this->itemData['items'][$this->_attr('item')]['table_name'];

                    $itemClass = empty($this->itemData['items'][$this->_attr('item')]['class_name'])
                        ? $this->_attr('item')
                        : $this->itemData['items'][$this->_attr('item')]['class_name'];

                    $this->itemData['item_fields'][] = [
                        'module' => $this->itemData['name'],
                        'table_name' => $itemTable,
                        'class_name' => $itemClass,
                        'title' => $text,
                        'values' => $values,
                        'order' => $this->_attr('order', 0),
                        'item' => iaItem::toSingular($this->_attr('item')),
                        'item_pages' => $this->_attr('page'),
                        'group' => $this->_attr('group', $this->itemData['name']), // will be changed to the inserted ID by the further code
                        'name' => $this->_attr('name'),
                        'type' => $this->_attr('type'),
                        'use_editor' => $this->_attr('editor', false),
                        'timepicker' => $this->_attr('timepicker', false),
                        'length' => (int)$this->_attr('length'),
                        'default' => $this->_attr('default'),
                        'editable' => $this->_attr('editable', true),
                        'multilingual' => $this->_attr('multilingual', false),
                        'required' => $this->_attr('required', false),
                        'required_checks' => $this->_attr('required_checks'),
                        'extra_actions' => $this->_attr('actions'),
                        'relation' => $this->_attr('relation', 'regular', ['regular', 'dependent', 'parent']),
                        'parent' => $this->_attr('parent', ''),
                        'empty_field' => $this->_attr('empty_field'),
                        'adminonly' => $this->_attr('adminonly', false),
                        'allow_null' => $this->_attr('allow_null', false),
                        'searchable' => $this->_attr('searchable', false),
                        'sort_order' => $this->_attr('sort', 'asc', ['asc', 'desc']),
                        'show_as' => $this->_attr('show_as', 'combo'),
                        'status' => $this->_attr('status', iaCore::STATUS_ACTIVE, [iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE]),
                        'image_width' => $this->_attr('width', 0),
                        'image_height' => $this->_attr('height', 0),
                        'thumb_width' => $this->_attr(['thumb_width', 'width'], 0),
                        'thumb_height' => $this->_attr(['thumb_height', 'height'], 0),
                        'resize_mode' => $this->_attr('mode', 'crop', ['fit', 'crop']),
                        'file_prefix' => $this->_attr(['prefix', 'file_prefix']),
                        'file_types' => $this->_attr(['types', 'file_types']),
                        'folder_name' => $this->_attr('folder_name', ''),
                        // keys below will not be actually written to DB and handled manually
                        'multiselection' => $this->_attr('multiselection', false),
                        'image_types' => $this->_attr('image_types')
                        //'numberRangeForSearch' => isset($this->_attributes['numberRangeForSearch']) ? explode(',', $this->_attributes['numberRangeForSearch']) : '',
                    ];
                } elseif ($this->_checkPath('changeset')) {
                    $this->itemData['changeset'][] = array_merge($this->_attributes, ['type' => $this->_inTag, 'name' => $text]);
                }

                break;

            case 'phrase':
            case 'tooltip':
                if ($this->_checkPath('phrases') || $this->_checkPath('tooltips')) {
                    if ($key = trim($this->_attr('key'))) {
                        $phrases = &$this->itemData['phrases'];

                        if (!isset($phrases[$key])) {
                            $phrases[$key] = [
                                'api' => $this->_attr('api',null),
                                'category' => ('phrase' == $this->_inTag)
                                    ? $this->_attr('category', iaLanguage::CATEGORY_COMMON)
                                    : $this->_inTag,
                                'values' => [],
                            ];
                        }

                        $phrases[$key]['values'][$this->_attr('code', $this->iaView->language)] = $text;
                    }
                }

                break;

            case 'hook':
                $type = $this->_attr('type', 'php', ['php', 'html', 'smarty', 'plain']);

                if ($filename = $this->_attr('filename')) {
                    switch ($type) {
                        case 'php':
                            $filename = 'modules/' . $this->itemData['name'] . '/includes/' . $filename;
                            break;
                        case 'smarty':
                            $filename = sprintf(self::BLOCK_FILENAME_PATTERN, $this->itemData['name'], $filename);
                    }
                }

                $this->itemData['hooks'][] = [
                    'name' => $this->_attr('name'),
                    'type' => $type,
                    'page_type' => $this->_attr('page_type', 'both', ['both', iaCore::ADMIN, iaCore::FRONT]),
                    'filename' => $filename,
                    'pages' => $this->_attr('pages'),
                    'module' => $this->itemData['name'],
                    'code' => $text,
                    'status' => $this->_attr('status', iaCore::STATUS_ACTIVE, [iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE]),
                    'order' => $this->_attr('order', 0)
                ];

                break;

            case 'block':
                if ($this->_checkPath('blocks')) {
                    $filename = $this->_attr('filename');
                    if ($this->itemData['type'] != self::TYPE_TEMPLATE && $filename && 'smarty' == $this->_attr('type')) {
                        $filename = sprintf(self::BLOCK_FILENAME_PATTERN, $this->itemData['name'], $filename);
                    }

                    $this->itemData['blocks'][] = [
                        'name' => $this->_attr('name'),
                        'title' => $this->_attr('title'),
                        'content' => $text,
                        'position' => $this->_attr('position'),
                        'type' => $this->_attr('type'),
                        'order' => $this->_attr('order', false),
                        'module' => $this->itemData['name'],
                        'status' => $this->_attr('status', iaCore::STATUS_ACTIVE, [iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE]),
                        'header' => $this->_attr('header', true),
                        'collapsible' => $this->_attr('collapsible', false),
                        'sticky' => $this->_attr('sticky', true),
                        'pages' => $this->_attr('pages'),
                        'rss' => $this->_attr('rss'),
                        'filename' => $filename,
                        'classname' => $this->_attr('classname')
                    ];
                } elseif ($this->_checkPath('changeset')) {
                    $this->itemData['changeset'][] = array_merge($this->_attributes, ['type' => $this->_inTag, 'name' => $text]);
                }
                break;

            case 'menu':
                if ($this->_checkPath('changeset')) {
                    $this->itemData['changeset'][] = array_merge($this->_attributes, ['type' => $this->_inTag, 'name' => $text]);
                }
                break;

            case 'code':
                if ($this->_checkPath('install')) {
                    $this->itemData['code']['install'] = $text;
                } elseif ($this->_checkPath('uninstall')) {
                    $this->itemData['code']['uninstall'] = $text;
                } elseif ($this->_checkPath('upgrade')) {
                    $this->itemData['code']['upgrade'] = $text;
                }
                break;

            case 'sql':
                $entry = [
                    'query' => $text,
                    'external' => isset($this->_attributes['external'])
                ];

                $version = $this->_attr('version', self::VERSION_EMPTY);
                $stage = $this->_attr('stage', self::SQL_STAGE_MIDDLE, [self::SQL_STAGE_START, self::SQL_STAGE_MIDDLE, self::SQL_STAGE_END]);

                if ($this->_checkPath('install')) {
                    $this->itemData['sql']['install'][$stage][$version][] = $entry;
                } elseif ($this->_checkPath('upgrade')) {
                    $this->itemData['sql']['upgrade'][$stage][$version][] = $entry;
                } elseif ($this->_checkPath('uninstall')) {
                    $this->itemData['sql']['uninstall'][] = $entry;
                }
                break;

            case 'option':
                if ($this->_checkPath('plan_options')) {
                    $this->itemData['plan_options'][] = [
                        'item' => $this->_attr('item'),
                        'name' => $this->_attr('name'),
                        'type' => $this->_attr('type', '', ['bool', 'int', 'float', 'string']),
                        'default_value' => $this->_attr('default'),
                        'chargeable' => $this->_attr('chargeable', false),
                        'title' => $text
                    ];
                }
                break;

            case 'type':
                if ($this->_checkPath('imagetypes')) {
                    $this->itemData['image_types'][] = [
                        'width' => $this->_attr('width'),
                        'height' => $this->_attr('height'),
                        'resize_mode' => $this->_attr('resize_mode', 'crop', ['crop', 'fit']),
                        'cropper' => $this->_attr('cropper', false),
                        'module' => $this->itemData['name'],
                        'name' => $text,
                        'extensions' => $this->_attr('extensions')
                    ];
                }
                break;

            case 'position':
                if ($this->_checkPath('section')) {
                    $this->itemData['layout'][$this->_section][$text] = [
                        'width' => (int)$this->_attr('width', 3),
                        'fixed' => (bool)$this->_attr('fixed', false)
                    ];
                }

                $this->itemData['positions'][] = [
                    'name' => $text,
                    'menu' => $this->_attr('menu', false),
                    'movable' => $this->_attr('movable', true),
                    'pages' => $this->_attr('pages', ''),
                    'access' => $this->_attr('access', null),
                    'default_access' => $this->_attr('default_access', null)
                ];

                break;

            case 'email':
                if ($this->_checkPath('emails')) {
                    $name = $this->_attr('name');

                    if (!$name) {
                        $name = $this->itemData['name'] . '_div_' . iaUtil::generateToken(4);
                    }

                    $this->itemData['email_templates'][$name] = [
                        'subject' => $this->_attr('subject'),
                        'body' => $text,
                        'variables' => $this->_attr('variables'),
                        'description' => $this->_attr('description'),
                        'divider' => $this->_attr('divider', 0),
                        'order' => $this->_attr('order'),
                    ];
                }
        }
    }

    public function getNotes()
    {
        return $this->_notes;
    }

    public function getUrl()
    {
        return $this->_url;
    }

    public function getMenuGroups()
    {
        return array_unique($this->_menuGroups);
    }

    public function _parserEnd($parser, $name)
    {
        array_pop($this->_currentPath);
    }

    public function _parserComment($parser)
    {
    }


    private function _processCategory(array $entryData, $action = self::ACTION_INSTALL)
    {
        switch ($entryData['category']) {
            case 'payments':
                $iaTransaction = $this->iaCore->factory('transaction');

                if (self::ACTION_INSTALL == $action) {
                    $entry = [
                        'name' => $entryData['name'],
                        'title' => $entryData['title']
                    ];

                    $this->iaDb->insert($entry, null, $iaTransaction->getTableGateways());
                } elseif (self::ACTION_UNINSTALL == $action) {
                    $this->iaDb->delete('`name` = :name', $iaTransaction->getTableGateways(), $entryData);
                }

                break;

            case 'lightbox':
            case 'captcha':
                $configKey = ('lightbox' == $entryData['category']) ? 'lightbox_name' : 'captcha_name';

                $iaConfig = $this->iaCore->factory('config');

                $config = $iaConfig->getByKey($configKey);

                $values = $config['multiple_values'];
                $values = $values ? explode(',', $values) : [];

                if (self::ACTION_INSTALL == $action) {
                    $values[] = $entryData['name'];

                    $iaConfig->update(['multiple_values' => implode(',', $values)], $configKey);

                    if (1 == count($values)) {
                        $this->iaCore->set($configKey, $entryData['name'], true);
                    }
                } elseif (self::ACTION_UNINSTALL == $action) {
                    if ($values) {
                        $installed = array_diff($values, [$entryData['name']]);

                        $iaConfig->update(['multiple_values' => implode(',', $installed)], $configKey);

                        if ($this->iaCore->get($configKey) == $entryData['name']) {
                            $value = empty($installed) ? '' : array_shift($installed);

                            if (in_array($entryData['name'], $this->_builtinPlugins)) {
                                $value = $entryData['name'];
                            }

                            $this->iaCore->set($configKey, $value, true);
                        }
                    }
                }
        }
    }

    protected function _processQueries($type, $stage, $ignoreNonVersionedQueries = false)
    {
        if (!isset($this->itemData['sql'][$type][$stage])) {
            return;
        }

        $iaDb = &$this->iaDb;
        $iaDbControl = $this->iaCore->factory('dbcontrol', iaCore::ADMIN);

        require_once IA_INCLUDES . 'utils/pclzip.lib.php';

        $extrasVersion = $this->itemData['info']['version'];

        foreach ($this->itemData['sql'][$type][$stage] as $version => $entries) {
            if (($ignoreNonVersionedQueries && self::VERSION_EMPTY == $version)) {
                continue;
            }

            if (self::VERSION_EMPTY != $version && version_compare($version, $extrasVersion) > 0) {
                continue;
            }

            foreach ($entries as $entry) {
                if ($entry['external']) {
                    $filePath = str_replace('{DS}', IA_DS, $entry['query']);
                    $fileFullPath = IA_MODULES . $this->itemData['name'] . IA_DS . $filePath;

                    if (iaUtil::isZip($fileFullPath)) {
                        $archive = new PclZip($fileFullPath);

                        $files = $archive->extract(PCLZIP_OPT_PATH, IA_TMP);

                        if (0 == $files) {
                            continue;
                        }

                        foreach ($files as $file) {
                            $iaDbControl->splitSQL($file['filename']);
                            iaUtil::deleteFile($file['filename']);
                        }
                    } else {
                        $iaDbControl->splitSQL($fileFullPath);
                    }
                } else {
                    if ($entry['query']) {
                        $query = str_replace(
                            ['{prefix}', '{mysql_version}', '{lang}'],
                            [$iaDb->prefix, $iaDb->tableOptions, iaLanguage::getMasterLanguage()->iso],
                            $entry['query']);
                        $iaDb->query($query);
                    }
                }
            }
        }
    }

    protected function _processPhrases(array $phrases)
    {
        if (!$phrases) {
            return;
        }

        $defaultLangCode = $this->iaView->language;

        foreach ($phrases as $key => $phrase) {
            foreach ($this->iaCore->languages as $isoCode => $language) {
                $value = isset($phrase['values'][$isoCode])
                    ? $phrase['values'][$isoCode]
                    : $phrase['values'][$defaultLangCode];

                $api = $phrase['api'];
                if (is_null($api)) {
                    $api = ('api' == $phrase['category']);
                }

                iaLanguage::addPhrase($key, $value, $isoCode,
                    $this->itemData['name'], $phrase['category'], false, $api);
            }
        }
    }

    protected function _processFields(array $fields)
    {
        if (!$fields) {
            return;
        }

        $iaField = $this->iaCore->factory('field');

        $fieldGroups = $this->iaDb->keyvalue('CONCAT(`item`, `name`) `key`, `id`', null, iaField::getTableGroups());

        $this->iaDb->setTable(iaField::getTable());

        $dependencies = [];

        foreach ($fields as $entry) {
            $entry['order'] || $entry['order'] = $this->iaDb->getMaxOrder(null, ['item', $entry['item']]) + 1;
            $entry['fieldgroup_id'] = isset($fieldGroups[$entry['item'] . $entry['group']])
                ? $fieldGroups[$entry['item'] . $entry['group']]
                : 0;

            $this->_addPhrase(sprintf(iaField::FIELD_TITLE_PHRASE_KEY, $entry['item'], $entry['name']), $entry['title'], iaLanguage::CATEGORY_COMMON, true);

            /*if (is_array($entry['numberRangeForSearch']))
            {
                foreach ($entry['numberRangeForSearch'] as $num)
                {
                    $this->_addPhrase('field_' . $entry['name'] . '_range_' . $num, $num, iaLanguage::CATEGORY_FRONTEND);
                }
            }
            unset($entry['numberRangeForSearch']);*/

            if (is_array($entry['values'])) {
                foreach ($entry['values'] as $key => $value) {
                    $this->_addPhrase(sprintf(iaField::FIELD_VALUE_PHRASE_KEY, $entry['item'], $entry['name'], $key), $value);
                }

                if ($entry['default']) {
                    // TODO: multiple default values for checkboxes should be implemented
                    if (!in_array($entry['default'], array_keys($entry['values']))) {
                        $entry['default'] = array_search($entry['default'], $entry['values']);
                    }
                }

                $entry['values'] = implode(',', array_keys($entry['values']));
            }

            $fieldPages = $entry['item_pages'] ? explode(',', $entry['item_pages']) : [];
            $imageTypes = $entry['image_types'] ? explode(',', $entry['image_types']) : [];
            $tableName = $entry['table_name'];
            $className = $entry['class_name'];
            $parents = $entry['parent'];

            switch ($entry['type']) {
                case iaField::TREE:
                    $entry['timepicker'] = $entry['multiselection'];

                    break;

                case iaField::IMAGE:
                case iaField::PICTURES:
                    if ($entry['timepicker'] = (bool)$imageTypes) {
                        $entry['imagetype_primary'] = isset($imageTypes[1]) ? $imageTypes[1] : $imageTypes[0];
                        $entry['imagetype_thumbnail'] = $imageTypes[0];
                    } else {
                        $entry['imagetype_primary'] = iaField::IMAGE_TYPE_LARGE;
                        $entry['imagetype_thumbnail'] = iaField::IMAGE_TYPE_THUMBNAIL;
                    }

                    if (iaField::IMAGE == $entry['type'] && !$entry['length']) {
                        $entry['length'] = 1;
                    }

                    break;

                case iaField::TEXT:
                    if (!$entry['length']) {
                        $entry['length'] = iaField::DEFAULT_LENGTH;
                    }
            }

            unset($entry['item_pages'], $entry['table_name'], $entry['class_name'], $entry['parent'],
                $entry['group'], $entry['title'], $entry['multiselection'], $entry['image_types']);

            if ($row = $iaField->getField($entry['name'], $entry['item'])) {
                $fieldId = $row['id'];
                $this->iaDb->update($entry, iaDb::convertIds($row['id']));
            } else {
                $fieldId = $this->iaDb->insert($entry);
            }

            $iaField->alterTable($entry);

            $entry['table_name'] = $tableName;
            $entry['class_name'] = $className;

            foreach ($fieldPages as $pageName) {
                if (!$pageName = trim($pageName)) {
                    continue;
                }

                $this->iaDb->insert(['page_name' => $pageName, 'field_id' => $fieldId], null, iaField::getTablePages());
            }

            foreach ($imageTypes as $imageTypeName) {
                if (!$imageTypeName = trim($imageTypeName)) {
                    continue;
                }

                $imageTypeId = $this->iaDb->one(iaDb::ID_COLUMN_SELECTION, iaDb::convertIds($imageTypeName, 'name'), iaField::getTableImageTypes());
                $imageTypeId && $this->iaDb->insert(['field_id' => $fieldId, 'image_type_id' => $imageTypeId], null, iaField::getTableFieldsImageTypes());
            }

            if (iaField::RELATION_DEPENDENT == $entry['relation'] && $parents) {
                $dependencies[$entry['name']] = $parents;
            }
        }

        // setup fields dependencies
        if ($dependencies) {
            $fieldIds = $this->iaDb->keyvalue(['name', 'id'], iaDb::convertIds($this->itemData['name'], 'module'));

            $this->iaDb->setTable(iaField::getTableRelations());

            foreach ($dependencies as $fieldName => $parents) {
                foreach (explode(';', $parents) as $parent) {
                    $list = explode(':', $parent);
                    if (2 == count($list) && isset($fieldIds[$list[0]])) {
                        foreach (explode(',', $list[1]) as $fieldValue) {
                            $entryData = [
                                'field_id' => $fieldIds[$list[0]],
                                'element' => $fieldValue,
                                'child' => $fieldName
                            ];

                            $this->iaDb->insert($entryData);
                        }
                    }
                }
            }

            $this->iaDb->resetTable();
        }

        $this->iaDb->resetTable();
    }

    protected function _processAdminPages(array $entries)
    {
        $this->iaDb->setTable('admin_pages');

        $this->iaDb->delete(iaDb::convertIds($this->itemData['name'], 'module'));

        foreach ($entries as $entry) {
            $title = $entry['title'];
            unset($entry['title']);

            empty($entry['group']) || ($this->_menuGroups[] = $entry['group']);

            $entry['group'] = $this->_lookupGroupId($entry['group']);
            $entry['order'] = (int)(is_null($entry['order'])
                ? $this->iaDb->one_bind('MAX(`order`) + 5', '`group` = :group', $entry)
                : $entry['order']);
            empty($entry['name']) && $entry['attr'] = iaUtil::generateToken(8);

            $this->iaDb->insert($entry);

            $this->_addPhrase('page_title_' . ($entry['name'] ? $entry['name'] : $entry['attr']), $title, iaLanguage::CATEGORY_ADMIN);
        }

        $this->iaDb->resetTable();
    }

    protected function _processConfig(array $entries)
    {
        $iaConfig = $this->iaCore->factory('config');

        $maxOrder = $this->iaDb->getMaxOrder($iaConfig::getTable());

        foreach ($entries as $entry) {
            $config = $iaConfig->getByKey($entry['name']);

            $entry['module'] = $this->itemData['name'];
            $entry['order'] = isset($entry['order']) ? $entry['order'] : ++$maxOrder;

            $description = $entry['description'];
            unset($entry['description']);

            if (!$config || empty($entry['name'])) {
                $iaConfig->insert($entry);
            } elseif ($config) {
                if (isset($entry['value'])) {
                    unset($entry['value']);
                }

                $iaConfig->update($entry, $config['key']);
            }

            self::_addPhrase('config_' . $entry['name'], $description, iaLanguage::CATEGORY_ADMIN);
        }
    }

    protected function _processEmailTemplates(array $entries)
    {
        $this->iaDb->setTable('email_templates');

        $maxOrder = $this->iaDb->getMaxOrder();
        foreach ($entries as $name => $entry) {
            $entry['name'] = $name;
            $entry['module'] = $this->itemData['name'];
            empty($entry['order']) && $entry['order'] = ++$maxOrder;

            $subject = $entry['subject'];
            $body = $entry['body'];
            $description = $entry['description'];

            unset($entry['subject'], $entry['body'], $entry['description']);

            foreach ($this->iaCore->languages as $iso => $language) {
                $entry['subject_' . $iso] = $subject;
                $entry['body_' . $iso] = $body;
            }

            $this->iaDb->exists(iaDb::convertIds($name, 'name'))
                ? $this->iaDb->update($entry, iaDb::convertIds($name, 'name'))
                : $this->iaDb->insert($entry);

            self::_addPhrase('email_template_' . $entry['name'], $description, iaLanguage::CATEGORY_ADMIN);
        }

        $this->iaDb->resetTable();
    }

    /**
     * Process image types
     *
     * @param array $imageTypes
     */
    protected function _processImageTypes(array $imageTypes)
    {
        $this->iaCore->factory('field');

        $this->iaDb->setTable(iaField::getTableImageTypes());

        foreach ($imageTypes as $entry) {
            if (!trim($entry['name'])) {
                continue;
            }

            $entry['name'] = strtolower(iaSanitize::paranoid($entry['name']));

            $extensions = explode(',', $entry['extensions']);
            unset($entry['extensions']);

            if ($id = $this->iaDb->insert($entry)) {
                foreach ($extensions as $ext) {
                    if (!$ext = trim($ext)) {
                        continue;
                    }

                    $fileTypeId = $this->iaDb->one(iaDb::ID_COLUMN_SELECTION,
                        iaDb::convertIds($ext, 'extension'), iaField::getTableFileTypes());

                    if ($fileTypeId) {
                        $this->iaDb->insert(['image_type_id' => $id, 'file_type_id' => $fileTypeId],
                            null, iaField::getTableImageTypesFileTypes());
                    }
                }
            }
        }

        $this->iaDb->resetTable();
    }

    /**
     * Process template positions
     *
     * @param array $positions positions
     */
    protected function _processPositions(array $positions)
    {
        $positionsList = $positionPages = [];

        $this->iaDb->setTable('positions');
        $this->iaDb->truncate();
        foreach ($positions as $position) {
            $positionsList[] = $position['name'];

            $this->iaDb->insert(['name' => $position['name'], 'menu' => (int)$position['menu'], 'movable' => (int)$position['movable']]);

            if (null != $position['default_access']) {
                $positionPages[] = ['object_type' => 'positions', 'page_name' => '', 'object' => $position['name'], 'access' => (int)$position['default_access']];
            }

            if ($position['pages']) {
                foreach (explode(',', $position['pages']) as $pageName) {
                    $positionPages[] = ['object_type' => 'positions', 'page_name' => $pageName,
                        'object' => $position['name'], 'access' => (int)$position['access']];
                }
            }
        }
        $this->iaDb->resetTable();

        if ($positionPages) {
            $this->iaDb->delete("`object_type` = 'positions'", 'objects_pages');
            foreach ($positionPages as $positionPage) {
                $this->iaDb->insert($positionPage, null, 'objects_pages');
            }
        }
    }

    protected function _processActions(array $actions)
    {
        $this->iaDb->setTable('admin_actions');
        foreach ($actions as $action) {
            $action['name'] = strtolower(str_replace(' ', '_', $action['name']));
            if ($action['name'] && !$this->iaDb->exists('`name` = :name && `module` = :module',
                    ['name' => $action['name'], 'module' => $this->itemData['name']])) {
                $action['order'] = (empty($action['order']) || !is_numeric($action['order']))
                    ? $this->iaDb->getMaxOrder() + 1
                    : $action['order'];

                $this->iaDb->insert($action);
            }
        }
        $this->iaDb->resetTable();
    }

    protected function _runPhpCode($code)
    {
        if (iaSystem::phpSyntaxCheck($code)) {
            $iaCore = &$this->iaCore;
            $iaDb = &$this->iaDb;

            eval($code);
        }
    }

    protected function _addPhrase($key, $value, $category = iaLanguage::CATEGORY_COMMON, $forceReplacement = false)
    {
        foreach ($this->iaCore->languages as $isoCode => $language) {
            iaLanguage::addPhrase($key, $value, $isoCode, $this->itemData['name'], $category, $forceReplacement);
        }
    }

    protected function _updatePhrase($key, $title, $category)
    {
        $this->iaDb->setTable(iaLanguage::getTable());

        foreach ($this->iaCore->languages as $code => $language) {
            $row = $this->iaDb->row_bind([iaDb::ID_COLUMN_SELECTION], '`key` = :key AND `category` = :category AND `code` = :code',
                ['key' => $key, 'category' => $category, 'code' => $code]);

            $row
                ? $this->iaDb->update(['value' => $title], iaDb::convertIds($row['id']))
                : $this->iaDb->insert(['key' => $key, 'value' => $title, 'original' => $title, 'category' => $category, 'code' => $code]);
        }

        $this->iaDb->resetTable();
    }

    protected function _processChangeset(array $entries)
    {
        $result = [];

        $tablesMapping = ['block' => 'blocks', 'field' => 'fields', 'menu' => 'blocks'];

        foreach ($entries as $entry) {
            if (!isset($tablesMapping[$entry['type']])) {
                continue;
            }

            switch ($entry['type']) {
                case 'field':
                    list($fieldName, $itemName) = explode('-', $entry['name']);
                    if (empty($fieldName) || empty($itemName)) { // incorrect identity specified by template
                        continue;
                    }
                    $stmt = iaDb::printf("`name` = ':name' AND `item` = ':item'", ['name' => $fieldName, 'item' => $itemName]);
                    break;
                default:
                    $stmt = iaDb::convertIds($entry['name'], 'name');
            }

            $tableName = $tablesMapping[$entry['type']];
            $name = $entry['name'];
            $type = $entry['type'];
            $title = isset($entry['title']) ? $entry['title'] : null;
            $pages = isset($entry['pages']) ? explode(',', $entry['pages']) : [];

            unset($entry['type'], $entry['name'], $entry['title'], $entry['pages']);

            $entryData = $this->iaDb->row('`id`, `' . implode('`,`', array_keys($entry)) . '`', $stmt, $tableName);

            $this->iaDb->update($entry, $stmt, null, $tableName);

            if (0 === $this->iaDb->getErrorNumber()) {
                if ('field' != $type && isset($entry['sticky'])) {
                    $this->iaCore->factory('block')->setVisibility($entryData['id'], $entry['sticky'], $pages);
                }

                if (!is_null($title)) {
                    $entryData['title'] = $title;

                    $key = ('field' == $type)
                        ? sprintf(iaField::FIELD_TITLE_PHRASE_KEY, $itemName, $fieldName)
                        : iaBlock::LANG_PATTERN_TITLE . $entryData['id'];
                    $category = ('field' == $type)
                        ? iaLanguage::CATEGORY_COMMON
                        : iaLanguage::CATEGORY_FRONTEND;

                    $this->_updatePhrase($key, $title, $category);
                }

                unset($entryData['id']);

                $result[$tableName][$name] = $entryData;
            }
        }

        return $result;
    }

    public function rollback()
    {
        $rollbackData = $this->iaCore->get(self::CONFIG_ROLLBACK_DATA);

        if (empty($rollbackData)) {
            return;
        }

        $rollbackData = unserialize($rollbackData);

        if (!is_array($rollbackData)) {
            return;
        }

        if (isset($rollbackData['blocks'])) {
            $existPositions = [];
            if ($this->_positions) {
                foreach ($this->_positions as $entry) {
                    $existPositions[] = $entry['name'];
                }
            }
        }

        foreach ($rollbackData as $dbTable => $actions) {
            foreach ($actions as $name => $itemData) {
                switch ($dbTable) {
                    case 'fields':
                        list($fieldName, $itemName) = explode('-', $name);
                        $stmt = iaDb::printf("`name` = ':name' AND `item` = ':item'", ['name' => $fieldName, 'item' => $itemName]);
                        break;
                    case 'blocks': // menus are handled here as well
                        if (isset($itemData['position']) && !in_array($itemData['position'], $existPositions)) {
                            $itemData['position'] = '';
                            $itemData['status'] = iaCore::STATUS_INACTIVE;
                        }
                    // BREAK stmt missed intentionally
                    default:
                        $stmt = iaDb::printf("`name` = ':name'", ['name' => $name]);
                }

                $this->iaDb->update($itemData, $stmt, null, $dbTable);
            }
        }
    }
}
