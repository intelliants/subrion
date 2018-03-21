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

class iaBackendController extends iaAbstractControllerBackend
{
    protected $_name = 'menus';

    protected $_gridColumns = ['name', 'status', 'order', 'position', 'delete' => 'removable'];
    protected $_gridFilters = ['position' => 'equal', 'status' => 'equal'];
    protected $_gridSorting = ['title' => ['value', 'p']];
    protected $_gridQueryMainTableAlias = 'm';

    protected $_phraseGridEntryDeleted = 'menu_deleted';
    protected $_phraseGridEntriesDeleted = 'menus_deleted';

    protected $_permissionsEdit = true;


    public function __construct()
    {
        parent::__construct();

        $this->setHelper($this->_iaCore->factory('block', iaCore::ADMIN));
        $this->setTable(iaBlock::getTable());
    }

    protected function _gridRead($params)
    {
        if (empty($params['action'])) {
            return parent::_gridRead($params);
        }

        $output = [];

        $iaPage = $this->_iaCore->factory('page', iaCore::ADMIN);

        switch ($params['action']) {
            case 'pages':
                foreach ($iaPage->getGroups() as $groupId => $group) {
                    $children = [];
                    foreach ($group['children'] as $pageId => $pageTitle) {
                        $children[] = ['text' => $pageTitle, 'leaf' => true, 'id' => $pageId];
                    }

                    $output[] = [
                        'text' => $group['title'],
                        'id' => 'group_' . $groupId,
                        'cls' => 'folder',
                        'draggable' => false,
                        'children' => $children
                    ];
                }

                $output[0]['expanded'] = true;

                break;

            case 'menus':
                function recursiveRead($list, $pid, array $titles)
                {
                    $result = [];

                    if (isset($list[$pid])) {
                        foreach ($list[$pid] as $child) {
                            $title = isset($titles[$child['el_id']]) ? $titles[$child['el_id']] : 'none';

                            if ('none' == $title) {
                                $title = ('node' == $child['page_name'] || !isset($titles[$child['page_name']]))
                                    ? iaLanguage::get('_page_removed_')
                                    : $titles[$child['page_name']];
                            } else {
                                $title .= ((int)$child['el_id'] > 0)
                                    ? ' (custom)'
                                    : ' (no link)';
                            }

                            $result[] = [
                                'text' => $title,
                                'id' => $child['el_id'],
                                'expanded' => true,
                                'children' => recursiveRead($list, $child['el_id'], $titles)
                            ];
                        }
                    }

                    return $result;
                }

                $output = [];

                if ($id = (int)$params['id']) {
                    $rows = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, '`menu_id` = ' . $id . ' ORDER BY `id`',
                        null, null, 'menus');
                    foreach ($rows as $row) {
                        $output[$row['parent_id']][] = $row;
                    }

                    $output = recursiveRead($output, 0, $iaPage->getTitles());
                }

                break;

            case 'titles':
                $output['languages'] = [];

                $languagesList = $this->_iaCore->languages;
                $node = isset($params['id']) ? iaSanitize::sql($params['id']) : false;
                $entry = isset($params['menu']) ? iaSanitize::sql($params['menu']) : false;

                if (isset($params['new']) && $params['new']) {
                    ksort($languagesList);
                    foreach ($languagesList as $code => $language) {
                        $output['languages'][] = ['fieldLabel' => $language['title'], 'name' => $code, 'value' => ''];
                    }
                } elseif ($node && $entry) {
                    $key = false;
                    $title = $iaPage->getPageTitle($node, 'none');
                    if ($title != 'none') {
                        $key = 'page_title_' . $node;
                    } else {
                        if ($pageId = (int)$node) {
                            $page = $this->_iaDb->one('`name`', iaDb::convertIds($pageId), 'pages');
                            $key = 'page_title_' . $page;
                        } else {
                            $current = isset($params['current']) ? $params['current'] : '';
                            ksort($languagesList);
                            foreach ($languagesList as $code => $language) {
                                $output['languages'][] = [
                                    'fieldLabel' => $language['title'],
                                    'name' => $code,
                                    'value' => $current
                                ];
                            }
                        }
                    }

                    if ($key) {
                        $where = '`key` = :key AND `category` = :category';
                        $this->_iaDb->bind($where, ['key' => $key, 'category' => 'page']);

                        $titles = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $where, null,
                            null, iaLanguage::getTable());

                        foreach ($titles as $row) {
                            if (isset($languagesList[$row['code']])) {
                                $output['languages'][] = [
                                    'fieldLabel' => $languagesList[$row['code']]['title'],
                                    'name' => $row['code'],
                                    'value' => $row['value']
                                ];
                            }
                        }
                    }

                    $output['key'] = $key;
                }

                break;

            case 'save':
                $output['message'] = iaLanguage::get('invalid_parameters');

                $menu = isset($params['menu']) ? $params['menu'] : null;
                $node = isset($params['node']) ? $params['node'] : null;

                if ($menu && $node) {
                    $rows = [];
                    foreach ($_POST as $code => $value) {
                        $rows[] = [
                            'code' => $code,
                            'value' => $value,
                            'module' => $menu,
                            'key' => 'page_title_' . $node,
                            'category' => iaLanguage::CATEGORY_PAGE
                        ];
                    }

                    $this->_iaDb->setTable(iaLanguage::getTable());
                    $this->_iaDb->delete('`key` = :key', null, ['key' => 'page_title_' . $node]);
                    $this->_iaDb->insert($rows);
                    $this->_iaDb->resetTable();

                    $output['message'] = iaLanguage::get('saved');
                    $output['success'] = true;

                    $this->_iaCore->iaCache->remove('menu_' . $menu);
                }
        }

        return $output;
    }

    protected function _entryAdd(array $entryData)
    {
        return $this->getHelper()->insert($entryData);
    }

    protected function _entryUpdate(array $entryData, $entryId)
    {
        return $this->getHelper()->update($entryData, $entryId);
    }

    protected function _gridModifyParams(&$conditions, &$values, array $params)
    {
        if (!empty($params['name'])) {
            $conditions[] = '(m.`name` LIKE :name OR p.`value` LIKE :name)';
            $values['name'] = '%' . $params['name'] . '%';
        }

        $conditions[] = "m.`type` = 'menu'";
    }

    protected function _gridQuery($columns, $where, $order, $start, $limit)
    {
        $sql = <<<SQL
SELECT :columns, p.`value` `title`
  FROM `:prefix:table_menus` m
LEFT JOIN `:prefix:table_phrases` p ON (p.`key` = CONCAT('block_title_', m.`id`) AND p.`code` = ':lang')
WHERE :where :order
LIMIT :start, :limit
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $this->_iaDb->prefix,
            'table_menus' => $this->getTable(),
            'table_phrases' => iaLanguage::getTable(),
            'lang' => $this->_iaCore->language['iso'],
            'columns' => $columns,
            'where' => $where,
            'order' => $order,
            'start' => (int)$start,
            'limit' => (int)$limit
        ]);

        return $this->_iaDb->getAll($sql);
    }

    protected function _setDefaultValues(array &$entry)
    {
        $entry = [
            'name' => 'menu_' . iaUtil::generateToken(5),
            'position' => '',
            'classname' => '',
            'status' => iaCore::STATUS_ACTIVE,
            'sticky' => false,
            'tpl' => iaBlock::DEFAULT_MENU_TEMPLATE,
            'type' => iaBlock::TYPE_MENU
        ];

        $entry['header'] = $entry['collapsible'] = $entry['collapsed'] = false;
    }

    protected function _preSaveEntry(array &$entry, array $data, $action)
    {
        $entry['position'] = empty($data['position']) ? 'left' : $data['position'];
        $entry['classname'] = $data['classname'];
        $entry['sticky'] = (int)$data['sticky'];
        $entry['header'] = (int)$data['header'];
        $entry['collapsible'] = (int)$data['collapsible'];
        $entry['collapsed'] = (int)$data['collapsed'];
        $entry['status'] = $data['status'];

        // bundled data
        $entry['title'] = $data['title'];
        $entry['pages'] = isset($data['pages']) ? $data['pages'] : [];

        if ($data['name']) {
            if ($name = iaSanitize::paranoid(iaSanitize::tags($data['name']))) {
                $entry['name'] = $name;
            } else {
                $this->addMessage('incorrect_menu_name');

                return false;
            }
        }

        $menuExists = $this->_iaDb->exists(iaDb::convertIds($entry['name'], 'name'));

        if (iaCore::ACTION_EDIT == $action) {
            $menuExists || $this->addMessage('menu_doesnot_exists');
        } else {
            $menuExists && $this->addMessage('menu_exists');
        }

        return !$this->getMessages();
    }

    protected function _postSaveEntry(array &$entry, array $data, $action)
    {
        function recursive_read_menu($menus, $pages, &$list, $menuId)
        {
            foreach ($menus as $menu) {
                $pageId = explode('_', $menu['id']);
                $pageId = reset($pageId);

                $list[] = [
                    'parent_id' => ('root' == $menu['parentId']) ? 0 : $menu['parentId'],
                    'menu_id' => $menuId,
                    'el_id' => $menu['id'],
                    'level' => $menu['depth'] - 1,
                    'page_name' => ($pageId > 0 && isset($pages[$pageId])) ? $pages[$pageId] : 'node',
                ];
            }
        }

        $menus = isset($data['menus']) && $data['menus'] ? $data['menus'] : '';
        $menus = json_decode($menus, true);
        array_shift($menus);

        $rows = [];
        $pages = $this->_iaDb->keyvalue(['id', 'name'], null, 'pages');
        recursive_read_menu($menus, $pages, $rows, $this->getEntryId());

        $this->_iaDb->setTable(iaBlock::getMenusTable());
        $this->_iaDb->delete(iaDb::convertIds($this->getEntryId(), 'menu_id'));
        empty($rows) || $this->_iaDb->insert($rows);
        $this->_iaDb->resetTable();

        if (iaCore::ACTION_EDIT == $action) {
            $this->_iaCore->iaCache->remove('menu_' . $this->getEntryId());
        }
    }

    protected function _assignValues(&$iaView, array &$entryData)
    {
        $pageGroups = [];
        $visibleOn = [];

        // get groups
        $groups = $this->_iaDb->onefield('`group`', '1 GROUP BY `group`', null, null, 'pages');
        $rows = $this->_iaDb->all(['id', 'name'], null, null, null, 'admin_pages_groups');
        foreach ($rows as $row) {
            if (in_array($row['id'], $groups)) {
                $row['title'] = iaLanguage::get('pages_group_' . $row['name']);
                $pageGroups[$row['id']] = $row;
            }
        }

        if (iaCore::ACTION_EDIT == $iaView->get('action')) {
            if ($array = $this->_iaDb->onefield('page_name',
                "`object_type` = 'blocks' && " . iaDb::convertIds($this->getEntryId(), 'object'), null, null,
                'objects_pages')
            ) {
                $visibleOn = $array;
            }

            $entryData['title'] = $this->_iaDb->keyvalue(['code', 'value'],
                iaDb::convertIds(iaBlock::LANG_PATTERN_TITLE . $this->getEntryId(), 'key'), iaLanguage::getTable());
        } elseif (!empty($_POST['pages'])) {
            $visibleOn = $_POST['pages'];
        }

        if (!empty($_POST['menus'])) {
            $iaView->assign('treeData', iaSanitize::html(json_encode($_POST['menus'])));
        }

        $iaView->assign('pageGroups', $pageGroups);
        $iaView->assign('visibleOn', $visibleOn);
        $iaView->assign('pages', $this->_getPages());
        $iaView->assign('pagesGroup', $pageGroups);
        $iaView->assign('positions', $this->getHelper()->getPositions());
    }

    private function _getPages()
    {
        $sql = <<<SQL
SELECT DISTINCTROW p.*, IF(t.`value` is null, p.`name`, t.`value`) `title` 
  FROM `:prefixpages` p 
LEFT JOIN `:prefix:table_language` t ON (`key` = CONCAT('page_title_', p.`name`) AND t.`code` = ':language') 
WHERE p.`status` = ':status' AND p.`service` = 0 
ORDER BY t.`value`
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $this->_iaDb->prefix,
            'table_language' => iaLanguage::getTable(),
            'language' => $this->_iaCore->iaView->language,
            'status' => iaCore::STATUS_ACTIVE
        ]);

        return $this->_iaDb->getAll($sql);
    }
}
