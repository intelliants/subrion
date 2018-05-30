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

class iaSearch extends abstractCore
{
    const ITEM_SEARCH_PROPERTY_ENABLED = 'coreSearchEnabled';
    const ITEM_SEARCH_PROPERTY_OPTIONS = 'coreSearchOptions';

    const ITEM_SEARCH_METHOD = 'coreSearch';
    const ITEM_COLUMN_TRANSLATION_METHOD = 'coreSearchTranslateColumn';

    const GET_PARAM_PAGE = '__p';
    const GET_PARAM_SORTING_FIELD = '__s';
    const GET_PARAM_SORTING_ORDER = '__so';

    protected static $_table = 'search';

    protected $_query = '';

    protected $_start;
    protected $_limit;
    protected $_sorting = '';

    protected $_itemName;
    protected $_type;
    protected $_params;

    protected $_caption = '';

    protected $_module;
    protected $_options = [];

    protected $_itemInstance;

    private $_fields = [];
    private $_smartyVarsAssigned = false;


    public function init()
    {
        parent::init();
        $this->iaCore->factory(['field', 'item']);
    }

    public function doRegularSearch($query, $limit)
    {
        $this->_query = $query;

        $this->_start = 0;
        $this->_limit = $limit;

        $results = ['pages' => $this->_searchByPages()];
        $results = array_merge($results, $this->_searchByItems());
        $results[iaUsers::getItemName()] = $this->_searchByMembers();

        return $results;
    }

    public function doItemSearch($itemName, $params, $start, $limit)
    {
        if (!$this->_loadItemInstance($itemName)) {
            return false;
        }

        $this->_start = (int)$start;
        $this->_limit = (int)$limit;

        if (is_string($params)) {
            $fieldsSearch = false;
            $this->_query = $params;
        } else {
            $fieldsSearch = true;
            $this->_processParams($params, true);
        }

        if ($search = $this->_callInstanceMethod($fieldsSearch)) {
            return [$search[0], $this->_renderResults($search[1])];
        }

        return false;
    }

    public function doAjaxItemSearch($itemName, array $params)
    {
        $page = isset($params[self::GET_PARAM_PAGE]) ? max((int)$params[self::GET_PARAM_PAGE], 1) : 1;
        $sorting = [
            isset($params[self::GET_PARAM_SORTING_FIELD]) ? $params[self::GET_PARAM_SORTING_FIELD] : null,
            isset($params[self::GET_PARAM_SORTING_ORDER]) ? $params[self::GET_PARAM_SORTING_ORDER] : null
        ];

        $result = [
            'hash' => $this->httpBuildQuery($params)
        ];

        unset($params[self::GET_PARAM_PAGE], $params[self::GET_PARAM_SORTING_FIELD], $params[self::GET_PARAM_SORTING_ORDER]);

        if ($this->_loadItemInstance($itemName)) {
            $this->_limit = $this->_getLimitByItemName($itemName);
            $this->_start = ($page - 1) * $this->_limit;

            $this->_processSorting($sorting);
            $this->_processParams($params);

            if ($search = $this->_callInstanceMethod()) {
                $p = empty($_GET['page']) ? null : $_GET['page'];
                $_GET['page'] = $page; // dirty hack to make this work correctly
                $result['pagination'] = iaSmarty::pagination(['aTotal' => $search[0], 'aItemsPerPage' => $this->_limit, 'aTemplate' => '#'], $this->iaView->iaSmarty);
                is_null($p) || $_GET['page'] = $p;

                $result['total'] = $search[0];
                $result['html'] = $this->_renderResults($search[1]);
            }
        }

        return $result;
    }

    public function getFilters($itemName)
    {
        $result = [
            'fields' => $this->getItemFields($itemName),
            'params' => $this->iaView->get('filtersParams') ? $this->iaView->get('filtersParams') : [],
            'item' => $itemName
        ];

        return $result;
    }

    public function save($item, $params, $name)
    {
        if (is_string($item) && is_string($params)) {
            $entry = [
                'member_id' => (int)iaUsers::getIdentity()->id,
                'date' => date(iaDb::DATETIME_FORMAT),
                'item' => trim($item),
                'params' => ltrim($params, IA_URL_DELIMITER),
                'title' => iaSanitize::tags((string)$name)
            ];

            return (bool)$this->iaDb->insert($entry, null, self::getTable());
        }

        return false;
    }

    public function get()
    {
        if (iaUsers::hasIdentity()) {
            $stmt = '`member_id` = :member ORDER BY `date` DESC';
            $this->iaDb->bind($stmt, ['member' => (int)iaUsers::getIdentity()->id]);

            return $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $stmt, null, null, self::getTable());
        }

        return false;
    }

    public function delete($id)
    {
        return $this->iaDb->delete(iaDb::convertIds($id), self::getTable());
    }

    // getters
    public function getOption($name)
    {
        $result = isset($this->_options[$name]) ? $this->_options[$name] : null;

        return is_array($result) ? (object)$result : $result;
    }

    public function getParams()
    {
        return $this->_params;
    }

    public function getCaption()
    {
        return $this->_caption;
    }
    //

    protected function _renderResults($rows)
    {
        $iaView = &$this->iaView;
        $iaSmarty = &$iaView->iaSmarty;

        if (!$this->_smartyVarsAssigned) {
            $core = [
                'config' => $this->iaCore->getConfig(),
                'customConfig' => $this->iaCore->getCustomConfig(),
                'language' => $this->iaCore->languages[$iaView->language],
                'languages' => $this->iaCore->languages,
                'packages' => $this->iaCore->modulesData,
                'page' => [
                    'info' => $iaView->getParams(),
                    'name' => $iaView->name(),
                    'nonProtocolUrl' => $iaView->assetsUrl,
                    'title' => $iaView->get('caption', $iaView->get('title')),
                ]
            ];

            $iaSmarty->assign('core', $core);
            $iaSmarty->assign('img', IA_TPL_URL . 'img/');
            $iaSmarty->assign('member', iaUsers::getIdentity(true));

            $this->_smartyVarsAssigned = true;
        }

        if ($this->_itemName != iaUsers::getItemName()) {
            $result = $this->_render(sprintf('module:%s/search.%s.tpl', $this->_module,
                iaItem::toPlural($this->_itemName)), ['listings' => $rows]);
        } else {
            $array = [];
            $fields = $this->iaCore->factory('field')->filter($this->_itemName, $array, iaUsers::getTable());

            $result = $this->_render('search.members.tpl',
                ['fields' => $fields, 'listings' => $rows]);
        }

        return $result;
    }

    public function getItemFields($itemName, $unpackValues = true)
    {
        $this->iaCore->factory('field');

        $stmt = '`status` = :status AND `item` = :item AND `adminonly` = 0 AND `searchable` = 1 ORDER BY `order`';
        $this->iaDb->bind($stmt, ['status' => iaCore::STATUS_ACTIVE, 'item' => $itemName]);

        $rows = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $stmt, null, null, iaField::getTable());

        $result = [];

        if ($rows && $unpackValues) {
            $numberFields = [];

            foreach ($rows as &$row) {
                switch ($row['type']) {
                    case iaField::CHECKBOX:
                    case iaField::COMBO:
                    case iaField::RADIO:
                        if (iaField::CHECKBOX == $row['type']) {
                            $row['default'] = explode(',', $row['default']);
                        }

                        $array = explode(',', $row['values']);
                        $row['values'] = [];
                        foreach ($array as $value) {
                            $row['values'][$value] = iaField::getLanguageValue($row['item'], $row['name'], $value);
                        }

                        $row['type'] = $row['show_as'];

                        break;

                    case iaField::NUMBER:
                    case iaField::CURRENCY:
                        $numberFields[] = $row['name'];
//                        $phraseKey = sprintf('field_%s_range_', $row['name']);
//
//                        $stmt = '`category` = :category AND `key` LIKE :key AND `code` = :code ORDER BY `value`';
//                        $this->iaDb->bind($stmt, array('category' => iaLanguage::CATEGORY_FRONTEND, 'key' => $phraseKey . '%', 'code' => $this->iaView->language));
//
//                        $row['range'] = $this->iaDb->keyvalue(array('key', 'value'), $stmt, iaLanguage::getTable());

                        break;

                    case iaField::TREE:
                        $row['values'] = $this->_getTreeNodes($itemName, $row['name'], $row['values']);
                }

                $result[$row['name']] = $row;
            }

            if ($numberFields) {
                $stmt = '';

                foreach ($numberFields as $fieldName) {
                    $stmt.= iaDb::printf('FLOOR(MIN(`:field`)) `:field_min`, CEIL(MAX(`:field`)) `:field_max`,', ['field' => $fieldName]);
                }
                $stmt = substr($stmt, 0, -1);

                $ranges = $this->iaDb->row($stmt, iaDb::convertIds(iaCore::STATUS_ACTIVE, 'status'), $this->iaCore->factory('item')->getItemTable($itemName));

                foreach ($numberFields as $fieldName) {
                    $result[$fieldName]['range'] = [$ranges[$fieldName . '_min'], $ranges[$fieldName . '_max']];
                }
            }
        }

        return $result;
    }

    protected function _searchByPages()
    {
        $iaCore = &$this->iaCore;
        $iaDb = &$this->iaDb;
        $iaPage = $iaCore->factory('page', iaCore::FRONT);

        $stmt = '`value` LIKE :query AND `category` = :category AND `code` = :language ORDER BY `key`';
        $iaDb->bind($stmt, [
            'query' => '%' . iaSanitize::sql($this->_query) . '%',
            'category' => iaLanguage::CATEGORY_PAGE,
            'language' => $iaCore->iaView->language
        ]);

        $result = [];

        if ($rows = $iaDb->all(['key', 'value'], $stmt, null, null, iaLanguage::getTable())) {
            foreach ($rows as $row) {
                $pageName = str_replace(['page_title_', 'page_content_'], '', $row['key']);

                if ($iaPage->getByName($pageName)) {
                    $key = (false === stripos($row['key'], 'page_content_')) ? 'title' : 'content';
                    $value = iaSanitize::tags($row['value']);

                    isset($result[$pageName]) || $result[$pageName] = [];

                    if ('content' == $key) {
                        $value = $this->_extractSnippet($value);
                        if (empty($result[$pageName]['title'])) {
                            $result[$pageName]['title'] = iaLanguage::get('page_title_' . $pageName);
                        }
                    }

                    $result[$pageName]['url'] = $iaPage->getUrlByName($pageName, false);
                    $result[$pageName][$key] = $value;
                }
            }
        }

        // blocks content will be printed out as a pages content
        if ($blocks = $this->_searchByBlocks()) {
            foreach ($blocks as $pageName => $blocksData) {
                if (isset($result[$pageName])) {
                    $result[$pageName]['extraItems'] = $blocksData;
                } else {
                    $result[$pageName] = [
                        'url' => $iaPage->getUrlByName($pageName),
                        'title' => iaLanguage::get('page_title_' . $pageName),
                        'content' => '',
                        'extraItems' => $blocksData
                    ];
                }
            }
        }

        $count = count($result);
        $html = $this->_render('search-list-pages' . iaView::TEMPLATE_FILENAME_EXT, ['pages' => $result]);

        return [$count, $html];
    }

    protected function _searchByItems()
    {
        $this->iaCore->factory('item');

        $modules = $this->iaDb->all(['name', 'type', 'items'], "`status` = 'active' AND `items` != '' AND `name` != 'core'", null, null, iaItem::getModulesTable());

        $results = [];
        foreach ($modules as $module) {
            if ($module['items']) {
                $items = unserialize($module['items']);
                foreach ($items as $entry) {
                    if ($this->_loadItemInstance($entry['item'])) {
                        if ($search = $this->_callInstanceMethod(false)) {
                            $search[1] = $this->_renderResults($search[1]);
                            $results[$this->_itemName] = $search;
                        }
                    }
                }
            }
        }

        return $results;
    }

    protected function _searchByMembers()
    {
        if ($this->_loadItemInstance(iaUsers::getItemName())) {
            if ($search = $this->_callInstanceMethod(false)) {
                return [$search[0], $this->_renderResults($search[1])];
            }
        }

        return false;
    }

    /**
     * @return array
     */
    protected function _searchByBlocks()
    {
        $iaCore = &$this->iaCore;
        $iaDb = &$this->iaDb;

        $sql = <<<SQL
SELECT 
  b.`name`, b.`external`, b.`filename`, b.`module`, b.`sticky`, b.`type`, b.`header`, 
  p1.`value` `title`,
  IF(b.`external` = 1, '', p2.`value`) `contents`,
  o.`page_name` `page` 
  FROM `:prefix:table_blocks` b 
LEFT JOIN `:prefix:table_objects` o ON (o.`object` = b.`id` AND o.`object_type` = 'blocks' AND o.`access` = 1) 
LEFT JOIN `:prefix:table_phrases` p1 ON (p1.`key` = CONCAT('block_title_', b.`id`) AND p1.`code` = ':lang')
LEFT JOIN `:prefix:table_phrases` p2 ON (p2.`key` = CONCAT('block_content_', b.`id`) AND p2.`code` = ':lang')
WHERE b.`type` IN ('plain','smarty','html') 
  AND b.`status` = ':status' 
  AND b.`module` IN (':module') 
  AND (b.`external` = 1 OR p2.`value` LIKE ':query' OR p1.`value` LIKE ':query')
  AND o.`page_name` IS NOT NULL 
GROUP BY b.`id`
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $iaDb->prefix,
            'table_blocks' => 'blocks',
            'table_objects' => 'objects_pages',
            'table_phrases' => iaLanguage::getTable(),
            'status' => iaCore::STATUS_ACTIVE,
            'lang' => $this->iaView->language,
            'query' => '%' . iaSanitize::sql($this->_query) . '%',
            'module' => implode("','", $iaCore->get('module'))
        ]);

        $blocks = [];

        if ($rows = $iaDb->getAll($sql)) {
            $modules = $iaDb->keyvalue(['name', 'type'], iaDb::convertIds(iaCore::STATUS_ACTIVE, 'status'), 'modules');

            foreach ($rows as $row) {
                $pageName = empty($row['page']) ? $iaCore->get('home_page') : $row['page'];

                if (empty($pageName)) {
                    continue;
                }

                if ($row['external']) {
                    switch ($modules[$row['module']]) {
                        case 'package':
                        case 'plugin':
                            $fileName = explode('/', $row['filename'])[1];

                            $tpl = IA_HOME . sprintf('templates/%s/modules/%s/%s',
                                    iaCore::instance()->get('tmpl'), $row['module'], $fileName);
                            is_file($tpl) || $tpl = IA_HOME . sprintf('modules/%s/templates/front/%s',
                                    $row['module'], $fileName);

                            break;

                        default:
                            $tpl = IA_HOME . 'templates/' . $row['module'] . IA_DS;
                    }

                    $content = @file_get_contents($tpl);

                    if (false === $content) {
                        continue;
                    }

                    $content = self::_stripSmartyTags(iaSanitize::tags($content));

                    if (false === stripos($content, $this->_query)) {
                        continue;
                    }
                } else {
                    switch ($row['type']) {
                        case 'smarty':
                            $content = self::_stripSmartyTags(iaSanitize::tags($row['contents']));
                            break;
                        case 'html':
                            $content = iaSanitize::tags($row['contents']);
                            break;
                        default:
                            $content = $row['contents'];
                    }
                }

                isset($blocks[$pageName]) || $blocks[$pageName] = [];

                $blocks[$pageName][] = [
                    'title' => $row['header'] ? $row['title'] : null,
                    'content' => $this->_extractSnippet($content)
                ];
            }
        }

        return $blocks;
    }

    protected function _getQueryStmtByParams()
    {
        $this->iaCore->factory('field');

        $statements = [];

        foreach ($this->_params as $fieldName => $value) {
            if ($this->getOption('customColumns') && in_array($fieldName, $this->_options['customColumns'])) {
                $statements[] = $this->_performCustomColumnTranslation($fieldName, $value);
                continue;
            }

            $column = ':column';
            $condition = '=';
            $val = is_string($value) ? "'" . iaSanitize::sql($value) . "'" : '';

            switch ($this->_fields[$fieldName]['type']) {
                case iaField::CHECKBOX:
                    is_string($value) && $value = [$value];

                    foreach ($value as $v) {
                        $expr = sprintf("FIND_IN_SET('%s', :column)", iaSanitize::sql($v));
                        $statements[] = ['col' => $expr, 'cond' => '>', 'val' => 0, 'field' => $fieldName];
                    }

                    continue 2;

                case iaField::NUMBER:
                case iaField::CURRENCY:
                    $d = 1;

                    if (iaField::CURRENCY == $this->_fields[$fieldName]['type']) {
                        $currency = $this->iaCore->factory('currency')->get();
                        if (!$currency['default']) {
                            $d = $currency['rate'];
                        }
                    }

                    if (!empty($value['f']) && is_numeric($value['f'])) {
                        $value['f'] = round($value['f'] / $d, 2);
                        $statements[] = ['col' => $column, 'cond' => '>=', 'val' => $value['f'], 'field' => $fieldName];
                    }

                    if (!empty($value['t']) && is_numeric($value['t'])) {
                        $value['t'] = round($value['t'] / $d, 2);
                        $statements[] = ['col' => $column, 'cond' => '<=', 'val' => $value['t'], 'field' => $fieldName];
                    }

                    continue 2;

                case iaField::RADIO:
                case iaField::COMBO:
                case iaField::TREE:
                    $array = [];
                    $value = is_array($value) ? $value : [$value];

                    foreach ($value as $v) {
                        if (trim($v)) {
                            $v = "'" . iaSanitize::sql($v) . "'";
                            $array[] = ['col' => $column, 'cond' => $condition, 'val' => $v, 'field' => $fieldName];
                        }
                    }

                    empty($array) || $statements[] = $array;

                    continue 2;

                case iaField::TEXT:
                case iaField::TEXTAREA:
                    if ($this->_fields[$fieldName]['multilingual']) {
                        $fieldName .= '_' . $this->iaCore->language['iso'];
                    }

                    // BREAK stmt missing intentionally

                case iaField::URL:
                    $condition = 'LIKE';
                    $val = "'%" . iaSanitize::sql($value) . "%'";

                    break;

                case iaField::PICTURES:
                case iaField::IMAGE:
                case iaField::STORAGE:
                    $condition = '!=';
                    $val = "''";

                    break;

                case iaField::DATE:

            }

            $statements[] = [
                'col' => $column,
                'cond' => $condition,
                'val' => $val,
                'field' => $fieldName
            ];
        }

        if (!$statements) {
            return iaDb::EMPTY_CONDITION;
        }

        $tableAlias = $this->getOption('tableAlias') ? $this->getOption('tableAlias') . '.' : '';

        foreach ($statements as &$stmt) {
            if (isset($stmt['field'])) {
                $stmt = iaDb::printf(':column :condition :value', [
                    'column' => str_replace(':column', sprintf('%s`%s`', $tableAlias, $stmt['field']), $stmt['col']),
                    'condition' => $stmt['cond'],
                    'value' => $stmt['val']
                ]);
            } else {
                $s = [];
                foreach ($stmt as $innerStmt) {
                    $s[] = iaDb::printf(':column :condition :value', [
                        'column' => str_replace(':column', sprintf('%s`%s`', $tableAlias, $innerStmt['field']), $innerStmt['col']),
                        'condition' => $innerStmt['cond'],
                        'value' => $innerStmt['val']
                    ]);
                }

                $stmt = '(' . implode(' OR ', $s) . ')';
            }
        }

        return '(' . implode(' AND ', $statements) . ')';
    }

    protected function _getQueryStmtByString()
    {
        $statements = [];

        $tableAlias = $this->getOption('tableAlias') ? $this->getOption('tableAlias') . '.' : '';
        $escapedQuery = iaSanitize::sql($this->_query);

        foreach ($this->_fields as $fieldName => $field) {
            switch ($field['type']) {
                case iaField::TEXT:
                case iaField::TEXTAREA:
                    $statements[] = sprintf("%s LIKE '%s'", $tableAlias . $fieldName, '%' . $escapedQuery . '%');
                    break;
                case iaField::NUMBER:
                    if (is_numeric($this->_query)) {
                        $statements[] = sprintf('%s = %s', $tableAlias . $fieldName, (int)$this->_query);
                    }
                    break;
                case iaField::CURRENCY:
                    if (is_numeric($this->_query)) {
                        // TODO: implement currency rates conversion
                        $statements[] = sprintf('%s = %s', $tableAlias . $fieldName, (int)$this->_query);
                    }
                    break;
                default:
                    $statements[] = sprintf("%s LIKE '%s'", $tableAlias . $fieldName, '%' . $escapedQuery . '%');
            }
        }

        // multilingual fields support
        $fieldsToSearchBy = $this->getOption('regularSearchFields');
        $fieldsToSearchBy || $fieldsToSearchBy = [];

        $multilingualFields = $this->iaCore->factory('field')->getMultilingualFields($this->_itemName);

        foreach ($fieldsToSearchBy as $item) {
            $table = $tableAlias;
            $column = $item;

            is_array($item) && list($table, $column) = $item;

            $table = rtrim($table, '.');
            $table && $table.= '.';

            in_array($column, $multilingualFields) && $column.= '_' . $this->iaView->language;

            $statements[] = sprintf("%s`%s` LIKE '%s'", $table, $column, '%' . $escapedQuery . '%');
        }

        return '(' . implode(' OR ', $statements) . ')';
    }

    protected function _render($template, array $params = [])
    {
        $iaSmarty = &$this->iaView->iaSmarty;

        try {
            foreach ($params as $key => $value) {
                $iaSmarty->assign($key, $value);
            }

            return $iaSmarty->fetch($template);
        } catch (Exception $e) {
            iaDebug::debug($template, 'Error rendering TPL file used to search results output');
            return '';
        }
    }

    private function _extractSnippet($text)
    {
        $result = $text;

        if (strlen($text) > 500) {
            $start = stripos($result, $this->_query);
            $result = '…' . substr($result, -30 + $start, 250);
            if (strlen($text) > strlen($result)) {
                $result.= '…';
            }
        }

        return $result;
    }

    private function _processSorting(array $sorting)
    {
        if ($sorting[0]) {
            $field = isset($this->getOption('columnAlias')->{$sorting[0]})
                ? $this->getOption('columnAlias')->{$sorting[0]}
                : iaSanitize::sql($sorting[0]);
            $order = (empty($sorting[1]) || !in_array($sorting[1], ['asc', 'desc']))
                ? iaDb::ORDER_ASC
                : strtoupper($sorting[1]);

            $this->_sorting = ($this->getOption('tableAlias') ? $this->getOption('tableAlias') . '.' : '')
                . sprintf('`%s` %s', $field, $order);
        } else {
            $this->_sorting = '';
        }
    }

    private function _processParams($params, $processRequestUri = false)
    {
        $data = [];

        $stmt = '`item` = :item AND `searchable` = 1';
        $this->iaDb->bind($stmt, ['item' => $this->_itemName]);

        $fields = $this->iaDb->all(['name', 'type', 'multilingual'], $stmt, null, null, iaField::getTable());
        foreach ($fields as $field) {
            $this->_fields[$field['name']] = $field;
        }

        if ($params && is_array($params)) {
            foreach ($params as $fieldName => $value) {
                $fieldName = empty($this->getOption('columnAlias')->$fieldName)
                    ? iaSanitize::paranoid($fieldName)
                    : $this->getOption('columnAlias')->$fieldName;

                if (empty($value) ||
                    (!isset($this->_fields[$fieldName]) && ($this->getOption('customColumns') && !in_array($fieldName, $this->_options['customColumns'])))) {
                    continue;
                }

                $data[$fieldName] = $value;
            }
        }

        // support for custom parameters field:value within request URL
        if ($processRequestUri) {
            $captions = [];

            foreach ($this->iaCore->requestPath as $chunk) {
                if (false === strstr($chunk, ':')) {
                    continue;
                }

                $value = explode(':', $chunk);

                $key = array_shift($value);
                empty($this->getOption('columnAlias')->$key) || $key = $this->getOption('columnAlias')->$key;

                if ($value && isset($this->_fields[$key])) {
                    switch ($this->_fields[$key]['type']) {
                        case iaField::NUMBER:
                        case iaField::CURRENCY:
                            if (count($value) > 1) {
                                $data[$key] = ['f' => (int)$value[0], 't' => (int)$value[1]];
                                $captions[] = sprintf('%d-%d', $value[0], $value[1]);
                            } else {
                                $data[$key] = ['f' => (int)$value[0], 't' => (int)$value[0]];
                                $captions[] = $value[0];
                            }
                            break;
                        case iaField::COMBO:
                            foreach ($value as $v) {
                                if ($title = iaField::getLanguageValue($this->_itemName, $key, $v, false)) {
                                    $captions[] = $title;
                                }
                            }
                            $data[$key] = $value;
                            break;
                        case iaField::TREE:
                            $nodeId = array_shift($value);
                            $data[$key] = $nodeId;
                            if ($title = iaField::getLanguageValue($this->_itemName, $key, $nodeId, false)) {
                                $captions[] = $title;
                            }
                            break;
                        default:
                            $value = array_shift($value);

                            $data[$key] = $value;
                            $captions[] = $value;
                    }
                }
            }

            $this->_caption = implode(' ', $captions);
        }

        $this->_params = $data;
    }

    private static function _stripSmartyTags($content)
    {
        return preg_replace('#\{.+\}#sm', '', $content);
    }

    public static function httpBuildQuery(array $params)
    {
        return preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', http_build_query($params));
    }

    protected function _loadItemInstance($itemName)
    {
        $this->_itemName = $itemName;

        if (iaUsers::getItemName() == $this->_itemName) {
            $this->_itemInstance = $this->iaCore->factory('users');
            $this->_module = null;
            $this->_options = $this->_itemInstance->{self::ITEM_SEARCH_PROPERTY_OPTIONS};

            return true;
        }

        $instance = $this->iaCore->factoryItem($this->_itemName);

        if ($instance && $instance->isSearchable()) {
            $this->_itemInstance = &$instance;
            $this->_module = $instance->getModuleName();
            $this->_options = isset($instance->{self::ITEM_SEARCH_PROPERTY_OPTIONS}) ? $instance->{self::ITEM_SEARCH_PROPERTY_OPTIONS} : [];

            return true;
        }

        return false;
    }

    protected function _callInstanceMethod($fieldsSearch = true)
    {
        return call_user_func_array([$this->_itemInstance, self::ITEM_SEARCH_METHOD], [
            $fieldsSearch ? $this->_getQueryStmtByParams() : $this->_getQueryStmtByString(),
            $this->_start,
            $this->_limit,
            $this->_sorting
        ]);
    }

    protected function _performCustomColumnTranslation($column, $value)
    {
        return call_user_func_array([$this->_itemInstance, self::ITEM_COLUMN_TRANSLATION_METHOD], [
            $column,
            $value
        ]);
    }

    private function _getTreeNodes($itemName, $fieldName, $packedNodes)
    {
        if (!$packedNodes) {
            return [];
        }

        $key = 'filter_tree_' . md5($packedNodes) . '_' . $this->iaView->language;

        if ($result = $this->iaCore->iaCache->get($key, 25920000, true)) { // 30 days
            return $result;
        } else {
            $result = $this->_parseTreeNodes($itemName, $fieldName, $packedNodes, $this->iaView->language);
            $this->iaCore->iaCache->write($key, $result);

            return $result;
        }
    }

    protected function _parseTreeNodes($itemName, $fieldName, $packedNodes, $isoCode)
    {
        $result = [];
        $nodes = json_decode($packedNodes, true);

        $indent = [];
        foreach ($nodes as $node) {
            $id = $node['id'];
            $parent = $node['parent'];

            $indent[$id] = 0;
            ('#' != $parent) && (++$indent[$id]) && (isset($indent[$parent]) ?
                ($indent[$id]+= $indent[$parent]) : ($indent[$parent] = 0));
        }

        $phraseKey = 'field_' . $itemName . '_' . $fieldName;
        $where = '`key` LIKE :key AND `code` = :code';
        $this->iaDb->bind($where, ['key' => $phraseKey . '+%', 'code' => $isoCode]);

        $phrases = $this->iaDb->keyvalue(['key', 'value'], $where, iaLanguage::getTable());

        foreach ($nodes as $node) {
            $title = isset($phrases[$phraseKey . '+' . $node['id']])
                ? $phrases[$phraseKey . '+' . $node['id']]
                : $node['text'];

            $result[$node['id']] = str_repeat('&nbsp;&nbsp;&nbsp;', $indent[$node['id']]) . ' &mdash; ' . $title;
        }

        return $result;
    }

    protected function _getLimitByItemName($itemName)
    {
        $defaultLimit = 10;

        $itemsMap = [
            'auto' => 'autos_number_perpage',
            'boat' => 'boats_number_perpage',
            'product' => 'commerce_products_per_page',
            'coupon' => 'coupons_per_page',
            'listing' => 'directory_listings_perpage',
            'article' > 'art_perpage',
            'estate' => 'realestate_num_per_page',
            'venue' => 'yp_listings_perpage'
        ];

        return isset($itemsMap[$itemName])
            ? (int)$this->iaCore->get($itemsMap[$itemName], $defaultLimit)
            : $defaultLimit;
    }
}
