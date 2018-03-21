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
    protected $_name = 'blocks';

    protected $_gridColumns = ['contents', 'position', 'module', 'type', 'status', 'order', 'delete' => 'removable'];
    protected $_gridFilters = [
        'status' => self::EQUAL,
        'type' => self::EQUAL,
        'position' => self::EQUAL,
        'module' => self::EQUAL
    ];
    protected $_gridSorting = ['title' => ['value', 'p']];
    protected $_gridQueryMainTableAlias = 'b';

    protected $_phraseAddSuccess = 'block_created';

    protected $_permissionsEdit = true;


    public function __construct()
    {
        parent::__construct();

        $this->setHelper($this->_iaCore->factory('block', iaCore::ADMIN));

        if (iaView::REQUEST_HTML == $this->_iaCore->iaView->getRequestType()
            && isset($this->_iaCore->requestPath[0]) && 'create' == $this->_iaCore->requestPath[0]
        ) {
            $this->_iaCore->iaView->set('action', iaCore::ACTION_ADD);
        }
    }

    protected function _entryAdd(array $entryData)
    {
        return $this->getHelper()->insert($entryData);
    }

    protected function _entryDelete($entryId)
    {
        return $this->getHelper()->delete($entryId);
    }

    protected function _entryUpdate(array $entryData, $entryId)
    {
        if (isset($entryData['type'])) {
            if (iaBlock::TYPE_MENU == $entryData['type']
                || iaBlock::TYPE_MENU == $this->_iaDb->one('`type`', iaDb::convertIds($entryId))
            ) {
                return false;
            }
        }

        return $this->getHelper()->update($entryData, $entryId);
    }

    protected function _gridModifyParams(&$conditions, &$values, array $params)
    {
        if (!empty($params['pos'])) {
            $conditions[] = 'b.`position` = :position';
            $values['position'] = $params['pos'];
        }

        if (!empty($params['title'])) {
            $conditions[] = 'p.`value` LIKE :title';
            $values['title'] = '%' . iaSanitize::sql($params['title']) . '%';
        }

        if (isset($values['module']) && iaCore::CORE == strtolower($values['module'])) {
            $values['module'] = '';
        }

        $conditions[] = "b.`type` != 'menu'";
    }

    protected function _gridQuery($columns, $where, $order, $start, $limit)
    {
        $sql = <<<SQL
SELECT :columns, p.`value` `title`, IF(b.`type` = 'php' OR b.`type` = 'smarty', b.`contents`,
    (SELECT `value` FROM `:prefix:table_phrases` WHERE `key` = CONCAT('block_content_', b.`id`) AND `code` = ':lang')
  ) `contents`
  FROM `:prefix:table_blocks` b
LEFT JOIN `:prefix:table_phrases` p ON (p.`key` = CONCAT('block_title_', b.`id`) AND p.`code` = ':lang')
WHERE :where :order
LIMIT :start, :limit
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $this->_iaDb->prefix,
            'table_blocks' => $this->getTable(),
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

    protected function _gridUpdate($params)
    {
        // custom permission should be checked
        if (isset($params['order'])
            && !$this->_iaCore->factory('acl')->isAccessible($this->getName(), 'order')
        ) {
            return iaView::accessDenied();
        }

        return parent::_gridUpdate($params);
    }

    protected function _setDefaultValues(array &$entry)
    {
        $entry = [
            'name' => 'block_' . mt_rand(1000, 9999),
            'type' => iaBlock::TYPE_HTML,
            'contents' => '',
            'collapsible' => true,
            'collapsed' => false,
            'header' => true,
            'sticky' => true,
            'external' => false,
            'filename' => '',
            'status' => iaCore::STATUS_ACTIVE,
            // bundled info
            'title' => [],
            'content' => [],
            'pages' => []
        ];
    }

    protected function _preSaveEntry(array &$entry, array $data, $action)
    {
        $this->_iaCore->startHook('adminAddBlockValidation');

        if (empty($data['type'])) {
            $this->addMessage(iaLanguage::getf('field_is_not_selected', ['field' => iaLanguage::get('type')]), false);
        }

        foreach ($this->_iaCore->languages as $iso => $language) { // checking multilingual values
            if (empty($data['title'][$iso])) {
                $this->addMessage(iaLanguage::getf('multilingual_field_is_empty',
                    ['lang' => $language['title'], 'field' => iaLanguage::get('title')]), false);
            }
        }

        // validate block name
        if (iaCore::ACTION_ADD == $action) {
            if (!$this->_iaCore->factory('acl')->isAccessible($this->getName(), $entry['type'])) {
                $this->addMessage(iaView::ERROR_FORBIDDEN);
                return false;
            }

            if (empty($data['name'])) {
                $this->addMessage(iaLanguage::getf('field_is_empty', ['field' => iaLanguage::get('name')]), false);
            } else {
                $entry['name'] = strtolower(iaSanitize::paranoid($data['name']));

                if (!iaValidate::isAlphaNumericValid($entry['name'])) {
                    $this->addMessage('error_block_name');
                } elseif ($this->_iaDb->exists('`name` = :name', ['name' => $entry['name']])) {
                    $this->addMessage('error_block_name_duplicate');
                }
            }
        }

        $entry['type'] = $data['type'];
        $entry['classname'] = $data['classname'];
        $entry['position'] = $data['position'];
        $entry['status'] = isset($data['status']) && in_array($data['status'],
            [iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE]) ? $data['status'] : iaCore::STATUS_ACTIVE;
        $entry['header'] = (int)$data['header'];
        $entry['collapsible'] = (int)$data['collapsible'];
        $entry['collapsed'] = (int)$data['collapsed'];
        $entry['sticky'] = (int)$data['sticky'];
        $entry['external'] = (int)$data['external'];
        $entry['filename'] = $data['filename'];

        // bundled data
        $entry['pages'] = isset($data['pages']) ? $data['pages'] : [];
        $entry['title'] = $data['title'];
        $entry['content'] = (iaBlock::TYPE_PHP == $entry['type'] || iaBlock::TYPE_SMARTY == $entry['type'])
            ? $data['contents'] // single value
            : $data['content']; // multilingual values

        if ($entry['external'] && !$entry['filename']) {
            $this->addMessage('error_filename');
        }

        if (in_array($entry['type'], [iaBlock::TYPE_PHP, iaBlock::TYPE_SMARTY])
            && !$data['external'] && !$data['contents']
        ) {
            $this->addMessage('error_contents');
        }

        $this->_iaCore->startHook('phpAdminBlocksEdit', ['block' => &$entry]);

        return !$this->getMessages();
    }

    protected function _postSaveEntry(array &$entry, array $data, $action)
    {
        if (iaCore::ACTION_ADD == $action) {
            $this->_iaCore->factory('log')->write(iaLog::ACTION_CREATE, [
                'item' => 'block',
                'name' => $entry['title'][$this->_iaCore->language['iso']],
                'id' => $this->getEntryId()
            ]);
        }
    }

    protected function _assignValues(&$iaView, array &$entryData)
    {
        $groupList = $this->_iaDb->onefield('`group`', '1 = 1 GROUP BY `group`', null, null, 'pages');

        $array = $this->_iaDb->all(['id', 'name'], null, null, null, 'admin_pages_groups');
        $pagesGroups = [];
        foreach ($array as $row) {
            $row['title'] = iaLanguage::get('pages_group_' . $row['name']);
            in_array($row['id'], $groupList) && $pagesGroups[$row['id']] = $row;
        }

        $menuPages = [];

        if (empty($entryData['title']) && iaCore::ACTION_EDIT == $iaView->get('action')) {
            $entryData['title'] = $this->_iaDb->keyvalue(['code', 'value'],
                iaDb::convertIds(iaBlock::LANG_PATTERN_TITLE . $this->getEntryId(), 'key'), iaLanguage::getTable());
            $entryData['content'] = $this->_iaDb->keyvalue(['code', 'value'],
                iaDb::convertIds(iaBlock::LANG_PATTERN_CONTENT . $this->getEntryId(), 'key'), iaLanguage::getTable());

            $menuPages = $this->_iaDb->onefield('`name`', "FIND_IN_SET('{$entryData['name']}', `menus`)", null, null,
                'pages');
        }

        empty($entryData['subpages']) || $entryData['subpages'] = unserialize($entryData['subpages']);
        isset($entryData['pages']) || $entryData['pages'] = $this->_iaDb->onefield('page_name',
            "`object_type` = 'blocks' && " . iaDb::convertIds($this->getEntryId(), 'object'), 0, null,
            iaBlock::getPagesTable());

        $iaView->assign('menuPages', $menuPages);
        $iaView->assign('pagesGroup', $pagesGroups);
        $iaView->assign('pages', $this->_getPagesList($iaView->language));
        $iaView->assign('positions', $this->getHelper()->getPositions());
        $iaView->assign('types', $this->getHelper()->getTypes());
    }

    protected function _gridRead($params)
    {
        return (count($this->_iaCore->requestPath) == 1 && 'positions' == $this->_iaCore->requestPath[0])
            ? $this->_getPositions()
            : parent::_gridRead($params);
    }

    private function _getPositions()
    {
        $output = [];
        foreach ($this->getHelper()->getPositions() as $entry) {
            $output[] = ['value' => $entry['name'], 'title' => $entry['name']];
        }

        return $output;
    }

    private function _getPagesList($languageCode)
    {
        $iaPage = $this->_iaCore->factory('page', iaCore::ADMIN);

        $sql = <<<SQL
SELECT DISTINCTROW p.*, IF(l.`value` IS NULL, p.`name`, l.`value`) `title` 
  FROM `:prefix:table_pages` p 
LEFT JOIN `:prefix:table_phrases` l 
  ON (`key` = CONCAT('page_title_', p.`name`) AND l.`code` = ':lang' AND l.`category` = ':category') 
WHERE p.`status` = ':status' AND p.`service` = 0 
GROUP BY p.`name` 
ORDER BY l.`value`
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $this->_iaDb->prefix,
            'table_pages' => $iaPage::getTable(),
            'table_phrases' => iaLanguage::getTable(),
            'status' => iaCore::STATUS_ACTIVE,
            'lang' => $languageCode,
            'category' => iaLanguage::CATEGORY_PAGE
        ]);

        return $this->_iaDb->getAll($sql);
    }

    // we should prevent editing menus via this controller
    public function getById($id)
    {
        $stmt = '`type` != :type AND `id` = :id';
        $this->_iaDb->bind($stmt, ['type' => iaBlock::TYPE_MENU, 'id' => $id]);

        return $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, $stmt);
    }
}
