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
    protected $_name = 'fieldgroups';

    protected $_gridColumns = ['name', 'module', 'item', 'collapsible', 'order', 'tabview'];
    protected $_gridFilters = ['id' => 'equal', 'item' => 'equal'];
    protected $_gridSorting = ['title' => ['value', 'pt'], 'item' => ['value', 'pi']];
    protected $_gridQueryMainTableAlias = 'fg';

    protected $_phraseAddSuccess = 'fieldgroup_added';
    protected $_phraseGridEntryDeleted = 'fieldgroup_deleted';

    protected $_systemFieldsEnabled = false;
    protected $_permissionsEdit = true;

    private $_itemsList;


    public function __construct()
    {
        parent::__construct();

        $iaField = $this->_iaCore->factory('field');
        $this->setHelper($iaField);

        $this->setTable(iaField::getTableGroups());

        $this->_itemsList = $this->_iaCore->factory('item')->getItems();
    }

    protected function _gridRead($params)
    {
        if (1 == count($this->_iaCore->requestPath) && 'tabs' == $this->_iaCore->requestPath[0]) {
            $where = '`item` = :item AND `name` != :name AND `tabview` = 1';
            $this->_iaDb->bind($where, $params);

            return $this->_iaDb->onefield('name', $where);
        }

        return parent::_gridRead($params);
    }

    protected function _gridQuery($columns, $where, $order, $start, $limit)
    {
        $sql = <<<SQL
SELECT :columns, pt.`value` `title`, pi.`value` `item` 
  FROM `:prefix:table_groups` fg 
LEFT JOIN `:prefix:table_phrases` pt ON (pt.`key` = CONCAT("fieldgroup_", fg.`item`, "_", fg.`name`) AND pt.`code` = ":lang") 
LEFT JOIN `:prefix:table_phrases` pi ON (pi.`key` = fg.`item` AND pi.`code` = ":lang") 
WHERE :conditions 
GROUP BY fg.`id` :order 
LIMIT :start, :limit
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $this->_iaDb->prefix,
            'table_groups' => self::getTable(),
            'table_phrases' => iaLanguage::getTable(),
            'lang' => $this->_iaCore->iaView->language,
            'columns' => $columns,
            'conditions' => $where,
            'order' => $order,
            'start' => $start,
            'limit' => $limit
        ]);

        return $this->_iaDb->getAll($sql);
    }

    protected function _gridModifyOutput(array &$entries)
    {
        foreach ($entries as &$entry) {
            // processing in case if there are no appropriate phrases
            $entry['title'] || $entry['title'] = iaLanguage::get("fieldgroup_{$entry['item']}_{$entry['name']}");
            $entry['item'] || $entry['item'] = iaLanguage::get($entry['item']);
        }
    }

    protected function _entryUpdate(array $entryData, $entryId)
    {
        // first, check if administrator changed the title
        if (count($entryData) == 1 && isset($entryData['title'])) {
            if ($name = $this->_iaDb->one(['name'], iaDb::convertIds($entryId))) {
                $phraseKey = "fieldgroup_{$entryData['item']}_{$name}";

                return iaLanguage::addPhrase($phraseKey, iaSanitize::html($entryData['title']), null, '',
                    iaLanguage::CATEGORY_COMMON, true);
            }

            return false;
        } else {
            return parent::_entryUpdate($entryData, $entryId);
        }
    }

    protected function _entryDelete($entryId)
    {
        $row = $this->_iaDb->row(['name', 'item'], iaDb::convertIds($entryId));
        $result = parent::_entryDelete($entryId);

        if ($result && $row) {
            $stmt = iaDb::printf("`key` = 'fieldgroup_:item_:name' OR `key` = 'fieldgroup_description_:item_:name'",
                $row);

            $this->_iaDb->delete($stmt, iaLanguage::getTable());
        }

        return $result;
    }

    protected function _setDefaultValues(array &$entry)
    {
        $entry['name'] = iaUtil::checkPostParam('name');
        $entry['tabcontainer'] = iaUtil::checkPostParam('tabcontainer');
    }

    protected function _assignValues(&$iaView, array &$entryData)
    {
        if (!empty($entryData['name'])) { // generate title & description for all available languages
            $this->_iaDb->setTable(iaLanguage::getTable());

            $entryData['titles'] = $this->_iaDb->keyvalue(['code', 'value'],
                "`key` = 'fieldgroup_{$entryData['item']}_{$entryData['name']}'");
            $entryData['description'] = $this->_iaDb->keyvalue(['code', 'value'],
                "`key` = 'fieldgroup_description_{$entryData['item']}_{$entryData['name']}'");

            $this->_iaDb->resetTable();
        }

        $iaView->assign('items', $this->_itemsList);
    }

    protected function _preSaveEntry(array &$entry, array $data, $action)
    {
        $entry = [
            'name' => iaUtil::checkPostParam('name'),
            'item' => iaUtil::checkPostParam('item'),
            'collapsible' => iaUtil::checkPostParam('collapsible'),
            'collapsed' => iaUtil::checkPostParam('collapsed'),
            'tabview' => iaUtil::checkPostParam('tabview'),
            'tabcontainer' => iaUtil::checkPostParam('tabcontainer'),
        ];

        iaUtil::loadUTF8Functions('ascii', 'bad', 'validation');

        if (iaCore::ACTION_ADD == $action) {
            if (!utf8_is_ascii($entry['name'])) {
                $this->addMessage('ascii_required');
            } else {
                $entry['name'] = strtolower($entry['name']);
            }

            if (!$this->getMessages() && !preg_match('/^[a-z0-9\-_]{2,50}$/', $entry['name'])) {
                $this->addMessage('name_is_incorrect');
            }

            if (empty($data['item'])) {
                $this->addMessage('at_least_one_item_should_be_checked');
            } else {
                $stmt = '`name` = :name && `item` = :item';
                $this->_iaDb->bind($stmt, ['name' => $entry['name'], 'item' => $entry['item']]);
                if ($this->_iaDb->exists($stmt)) {
                    $this->addMessage('error_fieldgroup_duplicate');
                }
            }

            $entry['module'] = $this->_iaCore->factory('item')->getModuleByItem($data['item']);
            $entry['order'] = $this->_iaDb->getMaxOrder(iaField::getTableGroups()) + 1;
        }

        foreach ($this->_iaCore->languages as $code => $language) {
            if ($data['titles'][$code]) {
                if (!utf8_is_valid($data['titles'][$code])) {
                    $data['titles'][$code] = utf8_bad_replace($data['titles'][$code]);
                }
            } else {
                $this->addMessage($language['title'] . ': ' . iaLanguage::get('title_incorrect'), false);
            }

            if ($data['description'][$code]) {
                if (!utf8_is_valid($data['description'][$code])) {
                    $data['description'][$code] = utf8_bad_replace($data['description'][$code]);
                }
            }
        }

        return !$this->getMessages();
    }

    protected function _postSaveEntry(array &$entry, array $data, $action)
    {
        $this->_savePhrases($data, $entry['name'], $entry['item']);

        $this->_iaCore->iaCache->clearAll();
    }

    private function _savePhrases(array &$data, $name, $item)
    {
        $this->_iaDb->setTable(iaLanguage::getTable());

        $phraseKeyTitle = "fieldgroup_{$item}_{$name}";
        $phraseKeyDescription = "fieldgroup_description_{$item}_{$name}";

        foreach ($this->_iaCore->languages as $code => $language) {
            $stmt = '`key` = :phrase AND `code` = :language';
            $this->_iaDb->bind($stmt, ['phrase' => $phraseKeyTitle, 'language' => $code]);

            $this->_iaDb->exists($stmt)
                ? $this->_iaDb->update(['value' => iaSanitize::html($data['titles'][$code])], $stmt)
                : iaLanguage::addPhrase($phraseKeyTitle, iaSanitize::html($data['titles'][$code]), $code);

            $stmt = '`key` = :phrase && `code` = :language';
            $this->_iaDb->bind($stmt, ['phrase' => $phraseKeyDescription, 'language' => $code]);

            $this->_iaDb->exists($stmt)
                ? $this->_iaDb->update(['value' => iaSanitize::html($data['description'][$code])], $stmt)
                : iaLanguage::addPhrase($phraseKeyDescription, iaSanitize::html($data['description'][$code]), $code);
        }

        $this->_iaDb->resetTable();
    }
}
