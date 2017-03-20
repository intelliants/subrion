<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2017 Intelliants, LLC <https://intelliants.com>
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

class iaBackendController extends iaAbstractControllerBackend
{
    const TYPE_DIVIDER = 'divider';
    const TYPE_IMAGE = 'image';
    const TYPE_SELECT = 'select';
    const TYPE_TEXT = 'text';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_ITEMSCHECKBOX = 'itemscheckbox';

    protected $_name = 'configuration';

    protected $_customConfigParams = ['admin_page', 'https'];

    protected $_redirectUrl;

    private $_imageTypes = [
        'image/gif' => 'gif',
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/x-icon' => 'ico'
    ];

    private $_type;
    private $_typeId;


    protected function _indexPage(&$iaView)
    {
        if (!empty($_GET['group'])) {
            $this->_type = 'group';
            $this->_typeId = (int)$_GET['group'];

            iaBreadcrumb::preEnd(iaLanguage::get('usergroups'), IA_ADMIN_URL . 'usergroups/');
        } elseif (!empty($_GET['user'])) {
            $this->_type = 'user';
            $this->_typeId = (int)$_GET['user'];

            iaBreadcrumb::preEnd(iaLanguage::get('members'), IA_ADMIN_URL . 'members/');
        }

        $groupName = isset($this->_iaCore->requestPath[0]) ? $this->_iaCore->requestPath[0] : 'general';
        $groupData = $this->_getGroupByName($groupName);

        if (empty($groupData)) {
            return iaView::errorPage(iaView::ERROR_NOT_FOUND);
        }

        $this->_setGroup($iaView, $groupData);

        if (isset($_POST['save'])) {
            $this->_save($iaView);
        }

        $iaView->assign('custom', (bool)$this->_type);
        $iaView->assign('group', $groupData);
        $iaView->assign('params', $this->_getParams($groupName));
        $iaView->assign('tooltips', iaLanguage::getTooltips());
    }

    protected function _gridRead($params)
    {
        $output = [];

        switch ($params['action']) {
            case 'update':
                $output = [
                    'result' => false,
                    'message' => iaLanguage::get('invalid_parameters')
                ];

                if ($this->_iaCore->set($_POST['name'], $_POST['value'], true)) {
                    $output['result'] = true;
                    $output['message'] = iaLanguage::get('saved');
                }

                break;

            case 'remove_image':
                iaUtil::deleteFile(IA_UPLOADS . $this->_iaCore->get($_POST['name']));
                $this->_iaCore->set($_POST['name'], '', true);

                break;

            case 'upload_image':
                $paramName = $_POST['name'];
                if (!(bool)$_FILES[$paramName]['error']) {
                    if (is_uploaded_file($_FILES[$paramName]['tmp_name'])) {
                        $ext = substr($_FILES[$paramName]['name'], -3);

                        // if 'jpeg'
                        if ($ext == 'peg') {
                            $ext = 'jpg';
                        }

                        if (!array_key_exists($_FILES[$paramName]['type'], $this->_imageTypes) || !in_array($ext,
                                $this->_imageTypes)
                        ) {
                            $output['error'] = true;
                            $output['msg'] = iaLanguage::getf('file_type_error',
                                ['extension' => implode(', ', array_unique($this->_imageTypes))]);
                        } else {
                            if ($this->_iaCore->get($paramName) && file_exists(IA_UPLOADS . $this->_iaCore->get($paramName))) {
                                iaUtil::deleteFile(IA_UPLOADS . $this->_iaCore->get($paramName));
                            }

                            $fileName = $paramName . '.' . $ext;
                            $fname = IA_UPLOADS . $fileName;

                            if (@move_uploaded_file($_FILES[$paramName]['tmp_name'], $fname)) {
                                $output['error'] = false;
                                $output['msg'] = iaLanguage::getf('image_uploaded',
                                    ['name' => $_FILES[$paramName]['name']]);
                                $output['file_name'] = $fileName;

                                $this->_iaCore->set($paramName, $fileName, true);

                                @chmod($fname, 0777);
                            }
                        }
                    }
                }
        }

        return $output;
    }

    private function _setGroup(&$iaView, array $groupData)
    {
        $iaItem = $this->_iaCore->factory('item');

        if ($this->_type) {
            $entity = ('user' == $this->_type)
                ? $this->_iaCore->factory('users')->getInfo($this->_typeId)
                : $this->_iaDb->row(['name'], iaDb::convertIds($this->_typeId), iaUsers::getUsergroupsTable());

            if (!$entity) {
                return iaView::errorPage(iaView::ERROR_NOT_FOUND);
            }

            $title = ('user' == $this->_type)
                ? $entity['fullname']
                : iaLanguage::get('usergroup_' . $entity['name']);

            $title = iaLanguage::getf('custom_configuration_title', [
                'settings' => $groupData['title'],
                'title' => $title,
                'type' => strtolower(iaLanguage::get('user' == $this->_type ? 'member' : 'usergroup'))
            ]);
        } else {
            $title = $groupData['title'];
        }

        $iaView->title($title);

        if ($groupData['module']) { // special cases
            $iaPage = $this->_iaCore->factory('page', iaCore::ADMIN);

            $activeMenu = $groupData['name'];

            if ($groupData['module'] == $this->_iaCore->get('tmpl')) {
                // template configuration options
                $page = $iaPage->getByName('templates');

                $iaView->set('group', $page['group']);
                $iaView->set('active_config', $groupData['name']);

                iaBreadcrumb::add($page['title'], IA_ADMIN_URL . $page['alias']);
            } elseif ($pluginPage = $this->_iaDb->row(['alias', 'group'],
                iaDb::printf("`name` = ':name' || `name` = ':name_stats'", ['name' => $groupData['module']]),
                iaPage::getAdminTable())
            ) {
                // it is a package
                $iaView->set('group', $pluginPage['group']);
                $iaView->set('active_config', $groupData['name']);

                $activeMenu = null;

                iaBreadcrumb::insert(iaLanguage::get('config_group_' . $groupData['name']), IA_ADMIN_URL
                    . $pluginPage['alias'], iaBreadcrumb::POSITION_FIRST);
            } elseif ($iaItem->isModuleExist($groupData['module'], iaItem::TYPE_PLUGIN)) {
                // plugin with no admin pages
                $iaView->set('group', 5);
                $iaView->set('active_config', $groupData['module']);
            }
        } else {
            $activeMenu = 'configuration_' . $groupData['name'];

            iaBreadcrumb::toEnd($groupData['title'], IA_SELF);
        }

        $iaView->set('active_menu', $activeMenu);
    }

    private function _save(&$iaView)
    {
        if (!$this->_iaCore->factory('acl')->isAccessible($iaView->name(), iaCore::ACTION_EDIT)) {
            return iaView::accessDenied();
        }

        $where = "`type` != 'hidden' " . ($this->_type ? '&& `custom` = 1' : '');
        $rows = $this->_iaDb->all(['name', 'type', 'options'], $where, null, null, iaCore::getConfigTable());

        $params = [];
        foreach ($rows as $row) {
            $params[$row['name']] = $row;
        }

        iaUtil::loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

        $messages = [];
        $error = false;

        if ($_POST['v'] && is_array($_POST['v'])) {
            $values = $_POST['v'];

            $this->_iaCore->startHook('phpConfigurationChange', ['configurationValues' => &$values]);

            $this->_iaDb->setTable(iaCore::getConfigTable());

            foreach ($values as $key => $value) {
                $options = $params[$key]['options'] ? json_decode($params[$key]['options'], true) : [];

                if (false !== ($s = strpos($key, '_items_enabled'))) {
                    $p = $this->_iaCore->get($key, '', !is_null($this->_type));
                    $array = $p ? explode(',', $p) : [];

                    $data = [];

                    array_shift($value);

                    if ($diff = array_diff($value, $array)) {
                        foreach ($diff as $item) {
                            array_push($data, ['action' => '+', 'item' => $item]);
                        }
                    }

                    if ($diff = array_diff($array, $value)) {
                        foreach ($diff as $item) {
                            array_push($data, ['action' => '-', 'item' => $item]);
                        }
                    }

                    $extra = substr($key, 0, $s);

                    $this->_iaCore->startHook('phpPackageItemChangedForPlugin', ['data' => $data], $extra);
                }

                if (is_array($value)) {
                    $value = empty($options['multilingual'])
                        ? implode(',', $value)
                        : self::_packMultilingualValue($value);
                }

                if (!utf8_is_valid($value)) {
                    $value = utf8_bad_replace($value);
                    trigger_error('Bad UTF-8 detected (replacing with "?") in configuration', E_USER_NOTICE);
                }

                if (self::TYPE_IMAGE == $params[$key]['type']) {
                    if (isset($_POST['delete'][$key])) {
                        $value = '';
                    } elseif (!empty($_FILES[$key]['name'])) {
                        if (!(bool)$_FILES[$key]['error']) {
                            if (@is_uploaded_file($_FILES[$key]['tmp_name'])) {
                                $ext = strtolower(utf8_substr($_FILES[$key]['name'], -3));

                                // if jpeg
                                if ($ext == 'peg') {
                                    $ext = 'jpg';
                                }

                                if (!array_key_exists(strtolower($_FILES[$key]['type']),
                                        $this->_imageTypes) || !in_array($ext, $this->_imageTypes,
                                        true) || !getimagesize($_FILES[$key]['tmp_name'])
                                ) {
                                    $error = true;
                                    $messages[] = iaLanguage::getf('file_type_error',
                                        ['extension' => implode(', ', array_unique($this->_imageTypes))]);
                                } else {
                                    if ($this->_iaCore->get($key) && file_exists(IA_UPLOADS . $this->_iaCore->get($key))) {
                                        iaUtil::deleteFile(IA_UPLOADS . $this->_iaCore->get($key));
                                    }

                                    $value = $fileName = $key . '.' . $ext;
                                    @move_uploaded_file($_FILES[$key]['tmp_name'], IA_UPLOADS . $fileName);
                                    @chmod(IA_UPLOADS . $fileName, 0777);
                                }
                            }
                        }
                    } else {
                        $value = $this->_iaCore->get($key, '', !is_null($this->_type));
                    }
                }

                if ($this->_type) {
                    $where = sprintf("`name` = '%s' && `type` = '%s' && `type_id` = %d", $key, $this->_type,
                        $this->_typeId);

                    $this->_iaDb->setTable(iaCore::getCustomConfigTable());

                    if ($_POST['c'][$key]) {
                        $array = [
                            'name' => $key,
                            'value' => $value,
                            'type' => $this->_type,
                            'type_id' => $this->_typeId
                        ];

                        if ($this->_iaDb->exists($where)) {
                            unset($array['value']);
                            $this->_iaDb->bind($where, $array);
                            $this->_iaDb->update(['value' => $value], $where);
                        } else {
                            $this->_iaDb->insert($array);
                        }
                    } else {
                        $this->_iaDb->delete($where);
                    }

                    $this->_iaDb->resetTable();
                } else {
                    $this->_updateParam($key, $value);
                }
            }

            $this->_iaDb->resetTable();

            $this->_iaCore->iaCache->clearAll();
        }

        if (!$error) {
            $iaView->setMessages(iaLanguage::get('saved'), iaView::SUCCESS);

            empty($this->_redirectUrl) || iaUtil::go_to(IA_URL . $this->_redirectUrl);
        } elseif ($messages) {
            $iaView->setMessages($messages);
        }
    }

    private function _getUsersSpecificConfig()
    {
        $sql = <<<SQL
SELECT c.`name`, c.`value` 
	FROM `:prefix:table_custom_config` c, `:prefix:table_members` m 
WHERE c.`type` = ':type' && c.`type_id` = m.`usergroup_id` && m.`id` = :id
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $this->_iaDb->prefix,
            'table_custom_config' => iaCore::getCustomConfigTable(),
            'table_members' => iaUsers::getTable(),
            'id' => $this->_typeId
        ]);

        return ($rows = $this->_iaDb->getKeyValue($sql)) ? $rows : [];
    }

    protected function _getGroupByName($groupName)
    {
        $result = $this->_iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`name` = :name', ['name' => $groupName],
            iaCore::getConfigGroupsTable());
        empty($result) || $result['title'] = iaLanguage::get('config_group_' . $result['name']);

        return $result;
    }

    protected function _updateParam($key, $value)
    {
        if (in_array($key, $this->_customConfigParams)) {
            if (!$this->_updateCustomParam($key, $value)) {
                return;
            }
        }

        $this->_iaDb->update(['value' => $value], iaDb::convertIds($key, 'name'));
    }

    protected function _updateCustomParam($key, $value)
    {
        switch ($key) { // exit with false in case if config should not be updated
            case 'https':
                $baseUrl = $this->_iaCore->get('baseurl');
                $newBaseUrl = 'http' . ($value ? 's' : '') . substr($baseUrl, strpos($baseUrl, '://'));
                $this->_iaCore->set('baseurl', $newBaseUrl, true);

                break;

            case 'admin_page':
                $this->_redirectUrl = iaSanitize::htmlInjectionFilter($value) . '/configuration/system/';
        }

        return true;
    }

    private function _getParams($groupName)
    {
        $where = "`config_group` = '{$groupName}' && `type` != 'hidden' " . ($this->_type ? '&& `custom` = 1' : '') . ' ORDER BY `order`';
        $params = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $where, null, null, iaCore::getConfigTable());

        if ($this->_type) {
            $custom = ('user' == $this->_type)
                ? $this->_iaCore->getCustomConfig($this->_typeId)
                : $this->_iaCore->getCustomConfig(null, $this->_typeId);
            $custom2 = ('user' == $this->_type) ? $this->_getUsersSpecificConfig() : [];
        }

        $iaItem = $this->_iaCore->factory('item');
        $itemsList = $iaItem->getItems();

        foreach ($params as &$entry) {
            $entry['options'] = $entry['options'] ? json_decode($entry['options'], true) : [];
            $entry['description'] = iaLanguage::get('config_' . $entry['name']);

            $className = 'default';

            if ($this->_type) {
                $className = 'custom';

                if (self::TYPE_DIVIDER != $entry['type']) {
                    if (isset($custom2[$entry['name']])) {
                        $entry['default'] = $custom2[$entry['name']];
                        $entry['value'] = $custom2[$entry['name']];
                    } else {
                        $entry['default'] = $this->_iaCore->get($entry['name']);
                    }

                    if (isset($custom[$entry['name']])) {
                        $className = 'common';
                        $entry['value'] = $custom[$entry['name']];
                    }
                }
            }

            switch ($entry['type']) {
                case self::TYPE_TEXT:
                case self::TYPE_TEXTAREA: // 'multilingual' option enabled types
                    if (isset($entry['options']['multilingual']) && $entry['options']['multilingual']) {
                        $value = [];
                        foreach ($this->_iaCore->languages as $iso => $language) {
                            $value[$iso] = preg_match('#\{\:' . $iso . '\:\}(.*?)(?:$|\{\:[a-z]{2}\:\})#s',
                                $entry['value'], $matches)
                                ? $matches[1]
                                : '';
                        }

                        $entry['value'] = $value;
                    }

                    break;

                case self::TYPE_ITEMSCHECKBOX:
                    $array = $this->_iaCore->get($entry['module'] . '_items_implemented');
                    $array = $array ? explode(',', $array) : [];
                    $array = array_values(array_intersect($array, $itemsList));

                    if ($array) {
                        $enabledItems = $iaItem->getEnabledItemsForPlugin($entry['module']);

                        for ($i = 0; $i < count($array); $i++) {
                            $array[$i] = trim($array[$i]);
                            $entry['items'][] = [
                                'name' => $array[$i],
                                'title' => iaLanguage::get($array[$i]),
                                'checked' => (int)in_array($array[$i], $enabledItems)
                            ];
                        }
                    }
            }

            if (self::TYPE_SELECT == $entry['type']) {
                switch ($entry['name']) {
                    case 'timezone':
                        $entry['values'] = iaUtil::getFormattedTimezones();
                        break;
                    case 'lang':
                        $entry['values'] = $this->_iaCore->languages;
                        break;
                    default:
                        $array = explode(',', trim($entry['multiple_values'], ','));
                        $values = [];

                        foreach ($array as $a) {
                            $a = explode('||', $a);
                            if (count($a) > 1) {
                                $values[$a[0]] = $a[1];
                            } else {
                                $v = trim($a[0], "'");
                                $values[$v] = $v;
                            }
                        }

                        $entry['values'] = $values;
                }
            }

            $entry['class'] = $className;
        }

        return $params;
    }

    protected static function _packMultilingualValue($value)
    {
        $result = '';

        foreach ($value as $k => $v) {
            $result .= '{:' . $k . ':}' . $v;
        }

        return $result;
    }
}
