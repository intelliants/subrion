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

class iaField extends abstractCore
{
    const CHECKBOX = 'checkbox';
    const COMBO = 'combo';
    const CURRENCY = 'currency';
    const DATE = 'date';
    const ICONPICKER = 'iconpicker';
    const IMAGE = 'image';
    const NUMBER = 'number';
    const PICTURES = 'pictures';
    const RADIO = 'radio';
    const STORAGE = 'storage';
    const TEXT = 'text';
    const TEXTAREA = 'textarea';
    const TREE = 'tree';
    const URL = 'url';

    const RELATION_DEPENDENT = 'dependent';
    const RELATION_PARENT = 'parent';
    const RELATION_REGULAR = 'regular';

    const DEFAULT_LENGTH = 100;

    const UPLOAD_FOLDER_ORIGINAL = 'original';

    const IMAGE_TYPE_LARGE = 'large';
    const IMAGE_TYPE_THUMBNAIL = 'thumbnail';

    const FIELD_TITLE_PHRASE_KEY = 'field_%s_%s';
    const FIELD_VALUE_PHRASE_KEY = 'field_%s_%s+%s';
    const FIELD_TOOLTIP_PHRASE_KEY = 'field_tooltip_%s_%s';

    const FIELDGROUP_TITLE_PHRASE_KEY = 'fieldgroup_%s_%s';
    const FIELDGROUP_DESCRIPTION_PHRASE_KEY = 'fieldgroup_description_%s_%s';

    protected static $_table = 'fields';
    protected static $_tableGroups = 'fields_groups';
    protected static $_tablePages = 'fields_pages';
    protected static $_tableRelations = 'fields_relations';
    protected static $_tableFileTypes = 'file_types';
    protected static $_tableImageTypes = 'image_types';
    protected static $_tableFieldsImageTypes = 'fields_image_types';
    protected static $_tableImageTypesFileTypes = 'image_types_filetypes';

    protected $_restrictedNames = ['link'];


    public static function getTableGroups()
    {
        return self::$_tableGroups;
    }

    public static function getTablePages()
    {
        return self::$_tablePages;
    }

    public static function getTableRelations()
    {
        return self::$_tableRelations;
    }

    public static function getTableImageTypesFileTypes()
    {
        return self::$_tableImageTypesFileTypes;
    }

    public static function getTableFieldsImageTypes()
    {
        return self::$_tableFieldsImageTypes;
    }

    public static function getTableFileTypes()
    {
        return self::$_tableFileTypes;
    }

    public static function getTableImageTypes()
    {
        return self::$_tableImageTypes;
    }


    public static function getLanguageValue($itemName, $fieldName, $value, $default = null)
    {
        return iaLanguage::get(sprintf(self::FIELD_VALUE_PHRASE_KEY, $itemName, $fieldName, $value), $default);
    }

    public static function getFieldTitle($itemName, $fieldName)
    {
        return iaLanguage::get(sprintf(self::FIELD_TITLE_PHRASE_KEY, $itemName, $fieldName));
    }

    public static function getFieldTooltip($itemName, $fieldName)
    {
        return iaLanguage::get(sprintf(self::FIELD_TOOLTIP_PHRASE_KEY, $itemName, $fieldName));
    }

    public static function getFieldValue($itemName, $fieldName, $key)
    {
        return iaLanguage::get(sprintf(self::FIELD_VALUE_PHRASE_KEY, $itemName, $fieldName, $key), $key);
    }

    public static function getFieldgroupTitle($itemName, $fieldName)
    {
        return iaLanguage::get(sprintf(self::FIELDGROUP_TITLE_PHRASE_KEY, $itemName, $fieldName));
    }

    public function fetch($where = null, $order = null, $start = null, $limit = null)
    {
        $rows = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $where . $order, $start, $limit, self::getTable());

        $result = [];
        foreach ($rows as $row) {
            $result[$row['id']] = $row;
        }

        self::_unpackValues($result);

        return $result;
    }

    /**
     * Returns fields by item name
     *
     * @param $itemName string Item name
     *
     * @return array
     */
    public function get($itemName)
    {
        $where = '`status` = :status && `item` = :item' . (!$this->iaCore->get('api_enabled') ? " && `fieldgroup_id` != 3 " : '') . ' ORDER BY `order`';
        $this->iaDb->bind($where, ['status' => iaCore::STATUS_ACTIVE, 'item' => $itemName]);

        return $this->fetch($where);
    }

    protected function _fetchVisibleFieldsForPage($pageName, $itemName, $where)
    {
        $sql = <<<SQL
SELECT f.* 
	FROM `:prefix:table_fields` f 
LEFT JOIN `:prefix:table_pages` fp ON (fp.field_id = f.id) 
WHERE fp.page_name = ':page' 
	AND f.item = ':item' 
	AND f.adminonly = 0 
	AND :where 
GROUP BY f.id 
ORDER BY f.order
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $this->iaDb->prefix,
            'table_fields' => self::getTable(),
            'table_pages' => self::getTablePages(),
            'page' => $pageName,
            'item' => $itemName,
            'where' => $where
        ]);

        return $this->iaDb->getAll($sql);
    }

    public function filter($itemName, array &$itemData, $pageName = null, $where = null)
    {
        static $cache = [];

        is_null($pageName) && $pageName = $this->iaView->name();
        is_null($where) && $where = iaDb::EMPTY_CONDITION;

        $where.= iaDb::printf(" && f.status = ':status'", ['status' => iaCore::STATUS_ACTIVE]);
        $where.= !empty($itemData['sponsored_plan_id']) && !empty($itemData['sponsored'])
            ? " && (f.plans = '' || FIND_IN_SET('{$itemData['sponsored_plan_id']}', f.plans))"
            : " && f.plans = ''";

        if (isset($cache[$pageName][$itemName][$where])) {
            $result = $cache[$pageName][$itemName][$where];
        } else {
            $result = [];
            $rows = $this->_fetchVisibleFieldsForPage($pageName, $itemName, $where);

            $iaAcl = $this->iaCore->factory('acl');

            foreach ($rows as $row) {
                $iaAcl->checkAccess('field', $itemName . '_' . $row['name'])
                    && $result[$row['id']] = $row;
            }

            self::_unpackValues($result);

            $cache[$pageName][$itemName][$where] = $result;
        }

        if ($itemData) {
            if (!empty($itemData['sponsored_plan_id'])) {
                $plans = $this->iaCore->factory('plan')->getPlans($itemName);
                if (isset($plans[$itemData['sponsored_plan_id']]['data']['fields'])) {
                    $planFields = $plans[$itemData[iaPlan::SPONSORED_PLAN_ID]]['data']['fields'];
                }
            }

            foreach ($result as $field) {
                $fieldName = $field['name'];

                if (iaCore::STATUS_ACTIVE != $field['status'] && isset($itemData[$fieldName])) {
                    unset($itemData[$fieldName]);
                    continue;
                }

                // assign a default value if field is assigned to plan and item has no active 'sponsored' flag
                if ($field['for_plan'] &&
                    (!isset($planFields) || (isset($planFields) && !in_array($fieldName, $planFields)))) {
                    isset($itemData[$fieldName]) && $itemData[$fieldName] = $field['empty_field'];
                    continue;
                }

                if ($field['multilingual']) {
                    $key = $fieldName . '_' . $this->iaCore->language['iso'];
                    isset($itemData[$key]) && $itemData[$fieldName] = $itemData[$key];
                }

                if (self::RELATION_PARENT == $field['relation'] && isset($itemData[$fieldName])) {
                    $value = $itemData[$fieldName];
                    foreach ($field['children'] as $dependentFieldName => $values) {
                        if (!in_array($value, $values)) {
                            unset($itemData[$dependentFieldName]);
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Manages internal structure of fields: unpacks values, validates parent/dependent structure
     *
     * @param $fields array Array of fields
     *
     * @return void
     */
    protected static function _unpackValues(array &$fields)
    {
        if (!$fields) {
            return;
        }

        $relations = iaCore::instance()->iaDb->all(['field_id', 'element', 'child'],
            '`field_id` IN (' . implode(',', array_keys($fields)) . ')', null, null, self::getTableRelations());

        $relationsMap = [];
        foreach ($relations as $entry) {
            $relationsMap[$entry['field_id']][$entry['child']][] = $entry['element'];
        }

        foreach ($fields as $id => &$field) {
            switch ($field['type']) {
                case self::CHECKBOX:
                case self::COMBO:
                case self::RADIO:
                    if (self::CHECKBOX == $field['type']) {
                        $field['default'] = explode(',', $field['default']);
                    }

                    $values = [];
                    foreach (explode(',', $field['values']) as $v) {
                        $values[$v] = self::getLanguageValue($field['item'], $field['name'], $v);
                    }

                    $field['values'] = $values;

                    break;

                case self::PICTURES:
                case self::IMAGE:
                    empty($field['timepicker']) && $field['imagetype_primary'] = self::IMAGE_TYPE_LARGE;
                    empty($field['timepicker']) && $field['imagetype_thumbnail'] = self::IMAGE_TYPE_THUMBNAIL;
                    //$field['imagetype_primary'] = $field['timepicker'] ? $field['imagetype_primary'] : self::IMAGE_TYPE_LARGE;
                    //$field['imagetype_thumbnail'] = $field['timepicker'] ? $field['imagetype_thumbnail'] : self::IMAGE_TYPE_THUMBNAIL;

                    break;

                case self::TREE:
                    if ($values = json_decode($field['values'], true)) {
                        foreach ($values as &$v) {
                            $v['text'] = self::getLanguageValue($field['item'], $field['name'], $v['id']);
                        }
                    }

                    $field['values'] = json_encode($values);
            }

            $field['class'] = 'fieldzone';
            if ($field['plans']) {
                foreach (explode(',', $field['plans']) as $p) {
                    $field['class'].= sprintf(' plan_%d ', $p);
                }
            }

            $field['title'] = self::getFieldTitle($field['item'], $field['name']);
            $field['children'] = isset($relationsMap[$id]) ? $relationsMap[$id] : null;
        }
    }

    protected function _getGroups($itemName, array $fields)
    {
        $where = '`item` = :item ORDER BY `order`';
        $this->iaDb->bind($where, ['item' => $itemName]);

        $groups = [];
        $rows = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $where, null, null, self::getTableGroups());

        foreach ($rows as $row) {
            $row['title'] = iaLanguage::get(sprintf(self::FIELDGROUP_TITLE_PHRASE_KEY, $row['item'], $row['name']), '');
            $row['description'] = iaLanguage::get(sprintf(self::FIELDGROUP_DESCRIPTION_PHRASE_KEY, $row['item'], $row['name']), '');

            $groups[$row['id']] = $row;
        }

        if (!$fields) {
            return $groups;
        }

        foreach ($fields as $value) {
            $fieldGroupId = (int)$value['fieldgroup_id'];

            if (!isset($groups[$fieldGroupId])) { // emulate tab to make TPL code compact
                $groups[$fieldGroupId] = ['name' => '___empty___', 'title' => iaLanguage::get('other'),
                    'tabview' => '', 'tabcontainer' => '', 'description' => null, 'collapsible' => false, 'collapsed' => false];
            }

            $groups[$fieldGroupId]['fields'][] = $value;
        }

        return $groups;
    }

    public function getGroups($itemName)
    {
        return $this->_getGroups($itemName, $this->get($itemName));
    }

    public function getGroupsFiltered($itemName, array &$itemData)
    {
        return $this->_getGroups($itemName, $this->filter($itemName, $itemData));
    }

    public function getTabs($itemName, array &$itemData, $defaultTab = 'common')
    {
        $fieldGroups = $this->getGroupsFiltered($itemName, $itemData);

        $tabs = [];
        foreach ($fieldGroups as $key => $group) {
            if ($group['tabview']) {
                $tabs['fieldgroup_' . $group['item'] . '_' . $group['name']][$key] = $group;
            } elseif ($group['tabcontainer']) {
                $tabs['fieldgroup_' . $group['item'] . '_' . $group['tabcontainer']][$key] = $group;
            } else {
                $tabs[$defaultTab][$key] = $group;
            }
        }

        return $tabs;
    }

    public function parsePost($itemName, array &$itemData)
    {
        $errors = [];

        $item = [];
        $data = &$_POST; // access to the data source by link

        if (iaCore::ACCESS_ADMIN == $this->iaCore->getAccessType()) {
            if (isset($data['sponsored'])) {
                $item['sponsored'] = (int)$data['sponsored'];
                $item['sponsored_plan_id'] = $item['sponsored'] ? (int)$data['plan_id'] : 0;
                if ($item['sponsored']) {
                    if (!(isset($itemData['sponsored_start']) && $itemData['sponsored_start'])) {
                        $item['sponsored_start'] = date(iaDb::DATETIME_SHORT_FORMAT);
                    }
                } else {
                    $item['sponsored_start'] = null;
                }

                $item['sponsored_end'] = null;
                if ($item['sponsored'] && !empty($data['sponsored_end'])) {
                    $item['sponsored_end'] = $data['sponsored_end'];
                }
            }

            if (isset($data['featured'])) {
                $item['featured'] = (int)$data['featured'];
                if ($item['featured']) {
                    if (isset($data['featured_end']) && $data['featured_end']) {
                        $item['featured_start'] = date(iaDb::DATETIME_SHORT_FORMAT);
                        $item['featured_end'] = iaSanitize::html($data['featured_end']);
                    } else {
                        $errors['featured_end'] = iaLanguage::get('featured_status_finished_date_is_empty');
                    }
                } else {
                    $item['featured_start'] = null;
                    $item['featured_end'] = null;
                }
            }

            if (isset($data['status'])) {
                $item['status'] = iaSanitize::html($data['status']);
            }

            if (isset($data['date_added'])) {
                $time = strtotime($data['date_added']);
                if (!$time) {
                    $errors['added_date'] = iaLanguage::get('added_date_is_incorrect');
                } elseif ($time > time()) {
                    $errors['added_date'] = iaLanguage::get('future_date_specified_for_added_date');
                } else {
                    $item['date_added'] = date(iaDb::DATETIME_SHORT_FORMAT, $time);
                }
            }

            if (isset($data['owner'])) {
                if (trim($data['owner']) && isset($data['member_id']) && $data['member_id'] &&
                    $memberId = $this->iaDb->one('id', iaDb::convertIds((int)$data['member_id']), iaUsers::getTable())) {
                    $item['member_id'] = $memberId;
                } else {
                    $item['member_id'] = 0;
                }
            }

            if (isset($data['locked'])) {
                $item['locked'] = (int)$data['locked'];
            }
        }

        $fields = (iaCore::ACCESS_FRONT == $this->iaCore->getAccessType())
            ? $this->filter($itemName, $itemData)
            : $this->get($itemName);

        // the code block below filters fields based on parent/dependent structure
        $activeFields = [];
        $parentFields = [];

        foreach ($fields as $field) {
            $activeFields[$field['name']] = $field;
            if (iaField::RELATION_PARENT == $field['relation']) {
                $parentFields[$field['name']] = $field['children'];
            }
        }

        foreach ($parentFields as $fieldName => $dependencies) {
            if (isset($data[$fieldName])) {
                $value = $data[$fieldName];
                foreach ($dependencies as $dependentFieldName => $values) {
                    if (!in_array($value, $values)) {
                        unset($activeFields[$dependentFieldName]);
                    }
                }
            }
        }
        //

        $this->iaCore->factory('util');
        iaUtil::loadUTF8Functions('validation', 'bad');

        foreach ($activeFields as $fieldName => $field) {
            $value = $field['allow_null'] ? null : '';
            isset($data[$fieldName]) && $value = $data[$fieldName];

            if ($field['extra_actions']) {
                if (false === eval($field['extra_actions'])) {
                    continue; // make possible to stop further processing of this field by returning FALSE
                }
            }

            if ($field['required']) {
                if ($field['required_checks']) {
                    eval($field['required_checks']);
                }

                if (!$value && !$field['multilingual']
                    && in_array($field['type'], [self::TEXT, self::TEXTAREA, self::NUMBER, self::RADIO, self::CHECKBOX, self::COMBO, self::DATE])) {
                    $errors[$fieldName] = in_array($field['type'], [self::RADIO, self::CHECKBOX, self::COMBO])
                        ? iaLanguage::getf('field_is_not_selected', ['field' => self::getFieldTitle($field['item'], $fieldName)])
                        : iaLanguage::getf('field_is_empty', ['field' => self::getFieldTitle($field['item'], $fieldName)]);
                }
            }

            switch ($field['type']) {
                case self::TEXT:
                case self::TEXTAREA:
                    if ($field['multilingual']) {
                        $langCode = (iaCore::ACCESS_FRONT == $this->iaCore->getAccessType())
                            ? $this->iaView->language
                            : iaLanguage::getMasterLanguage()->code;

                        $value = isset($data[$fieldName][$langCode])
                            ? $data[$fieldName][$langCode]
                            : null;

                        if ($field['required'] && !$value) {
                            $errors[$fieldName] = iaLanguage::getf('field_is_empty', ['field' => self::getFieldTitle($field['item'], $fieldName)]);
                        } else {
                            foreach ($this->iaCore->languages as $code => $language) {
                                if (iaCore::ACCESS_FRONT == $this->iaCore->getAccessType()) {
                                    $string = $value;
                                } else {
                                    $string = empty($data[$fieldName][$code]) // copy the master language value if empty
                                        ? $value
                                        : $data[$fieldName][$code];
                                }

                                utf8_is_valid($string) || $string = utf8_bad_replace($string);
                                $string = (self::TEXT == $field['type'])
                                    ? iaSanitize::tags($string)
                                    : ($field['use_editor'] ? iaUtil::safeHTML($string) : iaSanitize::tags($string));

                                $item[$fieldName . '_' . $code] = $string;
                            }
                        }
                    } else {
                        // Check the UTF-8 is well formed
                        utf8_is_valid($value) || $value = utf8_bad_replace($value);

                        $item[$fieldName] = (self::TEXT == $field['type'])
                            ? iaSanitize::tags($value)
                            : ($field['use_editor'] ? iaUtil::safeHTML($value) : iaSanitize::tags($value));
                    }

                    break;

                case self::ICONPICKER:
                    $item[$fieldName] = $value;

                    break;

                case self::NUMBER:
                case self::CURRENCY:
                    $item[$fieldName] = (float)str_replace(' ', '', $value);

                    break;

                case self::CHECKBOX:
                    is_array($value) && $value = implode(',', $value);

                    // BREAK stmt omitted intentionally

                case self::COMBO:
                case self::RADIO:
                    $item[$fieldName] = $value;

                    break;

                case self::IMAGE:
                case self::STORAGE:
                case self::PICTURES:
                    $item[$fieldName] = isset($data[$fieldName]) ? $value : [];

                    if (!is_writable(IA_UPLOADS)) {
                        $errors[$fieldName] = iaLanguage::get('error_directory_readonly');
                    } else {
                        if ((self::PICTURES == $field['type'] || self::IMAGE == $field['type'])
                            && iaCore::ACCESS_ADMIN == $this->iaCore->getAccessType()) {
                            $paths = $fieldName . '_dropzone_paths';
                            //$titles = $fieldName . '_dropzone_titles';
                            $files = $fieldName . '_dropzone_files';
                            $sizes = $fieldName . '_dropzone_sizes';

                            $pictures = empty($data[$paths]) ? [] : $data[$paths];

                            // run required field checks
                            if ($field['required'] && $field['required_checks']) {
                                eval($field['required_checks']);
                            } elseif ($field['required'] && 1 > count($pictures) + count($item[$fieldName])) {
                                $errors[$fieldName] = iaLanguage::getf('field_is_empty', ['field' => self::getFieldTitle($field['item'], $fieldName)]);
                                $invalidFields[] = $fieldName;
                            }
                            if (count($pictures) + count($item[$fieldName]) > $field['length']) {
                                $errors[$fieldName] = iaLanguage::get('no_more_files');
                            } elseif ($pictures) {
                                if (self::PICTURES == $field['type']) {
                                    $newPictures = [];
                                    foreach ($pictures as $i => $picture) {
                                        $fileName = empty($data[$files][$i]) ? '' : $data[$files][$i];
                                        iaSanitize::filenameEscape($fileName);
                                        $newPictures[] = [
                                            //'title' => $data[$titles][$i],
                                            'path' => $picture,
                                            'file' => $fileName,
                                            'size' => empty($data[$sizes][$i]) ? '' : (int)$data[$sizes][$i],
                                        ];
                                    }

                                    $item[$fieldName] = (iaCore::ACTION_EDIT == $this->iaView->get('action'))
                                        ? array_merge($item[$fieldName], $newPictures)
                                        : $newPictures;
                                } else {
                                    $item[$fieldName] = [
                                        'path' => $data[$paths][0],
                                        'file' => $data[$files][0],
                                        'size' => empty($data[$sizes][0]) ? '' : (int)$data[$sizes][0]
                                    ];
                                }
                            }
                        } else {
                            if ($field['required'] && !in_array(UPLOAD_ERR_OK, $_FILES[$fieldName]['error'])) {
                                $existImages = empty($itemData[$fieldName]) ? null : $itemData[$fieldName];
                                $existImages = is_string($existImages) ? unserialize($existImages) : $existImages;

                                $existImages || $errors[$fieldName] = iaLanguage::getf('field_is_empty', ['field' => self::getFieldTitle($field['item'], $fieldName)]);
                            }

                            foreach ($_FILES[$fieldName]['tmp_name'] as $i => $tmpName) {
                                if ($_FILES[$fieldName]['error'][$i]) {
                                    continue;
                                }

                                // files limit exceeded or rewrite image value
                                if (self::IMAGE != $field['type']
                                    && count($item[$fieldName]) >= $field['length']) {
                                    break;
                                }

                                try {
                                    $fileEntry = $this->processUploadedFile($tmpName, $field, $_FILES[$fieldName]['name'][$i], $_FILES[$fieldName]['type'][$i]);

                                    $fieldValue = [
                                        'title' => (isset($data[$fieldName . '_title'][$i]) ? substr(trim($data[$fieldName . '_title'][$i]), 0, 100) : ''),
                                        'path' => $fileEntry['path'],
                                        'file' => $fileEntry['file']
                                    ];

                                    self::STORAGE != $field['type'] && $fieldValue['size'] = $_FILES[$fieldName]['size'][$i];

                                    if (self::IMAGE == $field['type']) {
                                        $item[$fieldName] = $fieldValue;
                                    } else {
                                        $item[$fieldName][] = $fieldValue;
                                    }
                                } catch (Exception $e) {
                                    $errors[$fieldName] = $e->getMessage();
                                }
                            }
                        }
                    }

                    // If already has images, append them.
                    $item[$fieldName] = empty($item[$fieldName]) ? '' : serialize(array_merge($item[$fieldName])); // array_merge is used to reset numeric keys

                    break;

                case self::DATE:
                    if ($value = trim($value)) {
                        $value = strtotime($value);
                    }

                    if (!$value && $field['allow_null']) {
                        $value = null;
                    } else {
                        $value = date($field['timepicker'] ? iaDb::DATETIME_FORMAT : iaDb::DATE_FORMAT, $value);
                    }

                    $item[$fieldName] = $value;

                    break;

                case self::TREE:
                    $value && $value = str_replace(' ', '', iaSanitize::tags($value));
                    $item[$fieldName] = $value;

                    break;

                case self::URL:
                    $validProtocols = ['http://', 'https://'];
                    $item[$fieldName] = '';

                    if ($field['required'] && (empty($value['url']) || in_array($value['url'], $validProtocols))) {
                        $errors[$fieldName] = iaLanguage::getf('field_is_empty', ['field' => self::getFieldTitle($field['item'], $fieldName)]);
                    } else {
                        if (false === stripos($value['url'], 'http://') && false === stripos($value['url'], 'https://')) {
                            $value['url'] = 'http://' . $value['url'];
                        }

                        if (in_array($value['url'], $validProtocols) || iaValidate::isUrl($value['url'])) {
                            $url = iaSanitize::tags($value['url']);
                            $title = empty($value['title'])
                                ? str_replace($validProtocols, '', $value['url'])
                                : $value['title'];

                            $item[$fieldName] = $url . '|' . $title;
                        } else {
                            $errors[$fieldName] = iaLanguage::get('error_url') . ': ' . self::getFieldTitle($field['item'], $fieldName);
                        }
                    }
            }

            if (isset($item[$fieldName])) {
                // process hook if field value exists
                $this->iaCore->startHook('phpParsePostAfterCheckField', [
                    'field' => $field,
                    'value' => $item[$fieldName],
                    'errors' => &$errors
                ]);
            }
        }

        $error = !empty($errors);
        $fields = array_keys($errors);
        $messages = array_values($errors);

        return [$item, $error, $messages, $fields];
    }

    /**
     *
     * Upload images for non fields assigned data
     *
     * @param array $file $_FILES superglobal item
     * @param int $width width of primary image
     * @param int $height height of primary image
     * @param int $thumbWidth width of thumbnail
     * @param int $thumbHeight height of thumbnail
     */
    public function uploadImage(array $file, $width, $height, $thumbWidth, $thumbHeight, $resizeMode, $folder = null, $prefix = null)
    {
        $field = [
            'type' => self::IMAGE,
            'thumb_width' => $thumbWidth,
            'thumb_height' => $thumbHeight,
            'image_width' => $width,
            'image_height' => $height,
            'resize_mode' => $resizeMode,
            'folder_name' => $folder,
            'file_prefix' => $prefix
        ];

        $imageEntry = $this->processUploadedFile($file['tmp_name'], $field, $file['name']);

        return $imageEntry['path'] . '|' . $imageEntry['file'];
    }

    /**
     *
     * Method processed uploaded files assigned to fields
     *
     * @param string $tmpFile path to the temporary uploaded file
     * @param array $field field data to fetch settings from
     * @param string $fileName file name for destination file
     * @param string/null $mimeType mime type (if available)
     *
     * @return array array to be serialized and then written to DB
     */
    public function processUploadedFile($tmpFile, array $field, $fileName, $mimeType = null)
    {
        $uploadPath = empty($field['folder_name']) ? iaUtil::getAccountDir() : $field['folder_name'] . IA_DS;
        $absUploadPath = IA_UPLOADS . $uploadPath;

        self::_processFileName($fileName);

        if (!empty($field['file_prefix'])) {
            $fileName = $field['file_prefix'] . $fileName;
        }

        $fileExists = in_array($field['type'], [self::IMAGE, self::PICTURES])
            ? is_file($absUploadPath . self::UPLOAD_FOLDER_ORIGINAL . IA_DS . $fileName)
            : is_file($absUploadPath . $fileName);

        if ($fileExists) { // first, try to upload under original name
            // if exists, then add unique tail
            $pathInfo = pathinfo($fileName);
            $fileName = $pathInfo['filename'] . '_' . iaUtil::generateToken(5) . '.' . $pathInfo['extension'];
        }

        switch ($field['type']) {
            case self::IMAGE:
            case self::PICTURES:
                $this->_processImageField($field, $tmpFile, $absUploadPath, $fileName, $mimeType);
                $this->iaCore->startHook('phpImageUploaded',
                    ['field' => $field, 'path' => $uploadPath, 'image' => &$fileName]);
                break;
            case self::STORAGE:
                $this->_processStorageField($field, $tmpFile, $absUploadPath, $fileName);
        }

        return ['path' => $uploadPath, 'file' => $fileName];
    }

    protected function _processStorageField(array $field, $tmpFile, $path, $fileName)
    {
        $allowedExtensions = empty($field['file_types'])
            ? []
            : explode(',', strtolower($field['file_types'])); // no need to replace spaces, it's done when setting up a field

        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        if (!$extension || !in_array(strtolower($extension), $allowedExtensions)) {
            throw new Exception(iaLanguage::getf('file_type_error', ['extension' => $field['file_types']]));
        }

        iaUtil::makeDirCascade($path);

        if (!move_uploaded_file($tmpFile, $path . $fileName)) {
            throw new Exception(iaLanguage::get('upload_correct_permission'));
        }

        chmod($path . $fileName, 0644);
    }

    protected function _processImageField(array $field, $tmpFile, $path, $fileName, $mimeType)
    {
        $this->_message = null;

        $iaPicture = $this->iaCore->factory('picture');

        if (isset($field['timepicker']) && $field['timepicker']) { // image types enabled field
            $imageTypeIds = $this->getImageTypeIdsByFieldId($field['id']);
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);

            $imageTypes = [];
            $allowedFileExtensions = [];
            foreach ($this->getImageTypes() as $imageType) {
                in_array($imageType['id'], $imageTypeIds) // check if image type assigned to this field
                && in_array($ext, $imageType['extensions']) // check if uploaded file's extension is enabled in settings
                && ($imageTypes[] = $imageType) // include image type to processing
                && ($allowedFileExtensions = array_merge($allowedFileExtensions, $imageType['extensions']));
            }
        } else { // standard processing (original, thumbnail & large image)
            $imageTypes = [
                ['name' => self::IMAGE_TYPE_THUMBNAIL, 'width' => $field['thumb_width'], 'height' => $field['thumb_height'], 'resize_mode' => $field['resize_mode'], 'watermark' => false],
                ['name' => self::IMAGE_TYPE_LARGE, 'width' => $field['image_width'], 'height' => $field['image_height'], 'resize_mode' => $field['resize_mode'], 'watermark' => true]
            ];

            $allowedFileExtensions = $iaPicture->getSupportedImageTypes();
        }

        if (!$imageTypes) {
            $this->iaView->setMessages(iaLanguage::get('no_uploaded_files_processed'), iaView::ALERT);
            return;
        }

        $allowedFileExtensions = array_unique($allowedFileExtensions);

        $originalFilePath = $path . 'original/';

        // first, put original file into appropriate folder
        // here we should manipulate the first argument so the file extension check performed correctly
        $this->_processStorageField(array_merge($field, ['file_types' => implode(',', $allowedFileExtensions)]),
            $tmpFile, $originalFilePath, $fileName);

        $originalFile = $originalFilePath . $fileName;

        // process images according to assigned image types rules
        foreach ($imageTypes as $imageType) {
            if (!$imageType['width'] && !$imageType['height']) {
                continue;
            }

            $imageTypeFolder = $path . $imageType['name'] . IA_DS;
            iaUtil::makeDirCascade($imageTypeFolder);

            $destinationFile = $imageTypeFolder . $fileName;

            $result = $iaPicture->process($originalFile, $destinationFile, $mimeType, $imageType['width'],
                $imageType['height'], $imageType['resize_mode'], (isset($imageType['watermark']) ? $imageType['watermark'] : true));

            if (!$result) {
                $this->setMessage($iaPicture->getMessage());
            }
        }
    }

    public function getValues($fieldName, $itemName)
    {
        $values = $this->iaDb->one_bind(['values'], '`name` = :field AND `item` = :item',
            ['field' => $fieldName, 'item' => $itemName], self::getTable());

        if ($values) {
            $result = [];
            foreach (explode(',', $values) as $key) {
                $result[$key] = self::getLanguageValue($itemName, $fieldName, $key);
            }

            return $result;
        }

        return false;
    }

    public function getUploadFields($itemName)
    {
        $where = '`type` IN (:image, :pictures, :storage)';
        $this->iaDb->bind($where, ['image' => self::IMAGE, 'pictures' => self::PICTURES, 'storage' => self::STORAGE]);

        return $this->_getFieldNames($itemName, $where);
    }

    public function getSerializedFields($itemName)
    {
        return $this->_getFieldNames($itemName, "`type` IN ('image', 'pictures', 'storage')");
    }

    public function getMultilingualFields($itemName)
    {
        return $this->_getFieldNames($itemName, iaDb::convertIds(1, 'multilingual'));
    }

    public function getFieldsByType($itemName, $fieldType)
    {
        return $this->_getFieldNames($itemName, iaDb::convertIds($fieldType, 'type'));
    }

    protected function _getFieldNames($itemName, $condition)
    {
        if (!$itemName) {
            return [];
        }

        static $cache = [];

        $conditions = ["`status` = 'active'", iaDb::convertIds($itemName, 'item'), $condition];
        $conditions = implode(' AND ', $conditions);

        if (!isset($cache[$conditions])) {
            $result = $this->iaDb->onefield('name', $conditions, null, null, self::getTable());
            $cache[$conditions] = $result ? $result : [];
        }

        return $cache[$conditions];
    }

    public function getTreeNodes($condition = '')
    {
        $rows = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $condition, null, null, 'fields_tree_nodes');

        if ($rows) {
            foreach ($rows as &$node) {
                $node['title'] = self::getFieldValue($node['item'], $node['field'], $node['node_id']);
            }
        }

        return $rows;
    }

    public function getTreeNode($condition)
    {
        $row = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, $condition, 'fields_tree_nodes');
        $row && $row['title'] = self::getFieldValue($row['item'], $row['field'], $row['node_id']);

        return $row;
    }


    public function alterTable(array $fieldData)
    {
        $dbTable = $this->iaCore->factory('item')->getItemTable($fieldData['item']);

        if ($fieldData['multilingual']) {
            $this->alterMultilingualColumns($dbTable, $fieldData['name'], $fieldData);
        } else {
            $this->alterColumnScheme($dbTable, $fieldData);

            if (in_array($fieldData['type'], [self::TEXT, self::TEXTAREA])) {
                $this->alterColumnIndex($dbTable, $fieldData['name'], $fieldData['searchable']);
            }
        }
    }

    // DB mgmt utility methods
    public function alterMultilingualColumns($dbTable, $fieldName, array $fieldData)
    {
        $defaultLanguageCode = null;

        foreach ($this->iaCore->languages as $language) {
            if ($language['default']) {
                $defaultLanguageCode = $language['iso'];
                break;
            }
        }

        if ($fieldData['multilingual']) {
            $fieldData['name'] = $fieldName;
            $this->alterColumnScheme($dbTable, $fieldData, $fieldName . '_' . $defaultLanguageCode);

            foreach ($this->iaCore->languages as $language) {
                if ($language['iso'] != $defaultLanguageCode) {
                    $fieldData['name'] = $fieldName . '_' . $language['iso'];
                    $this->alterColumnScheme($dbTable, $fieldData);
                }
            }

            $this->_copyContent($dbTable, $fieldName, $defaultLanguageCode);
        } else {
            $fieldData['name'] = $fieldName . '_' . $defaultLanguageCode;
            $this->alterColumnScheme($dbTable, $fieldData, $fieldName);

            foreach ($this->iaCore->languages as $language) {
                if ($language['iso'] != $defaultLanguageCode) {
                    $this->alterDropColumn($dbTable, $fieldName . '_' . $language['iso']);
                }
            }
        }
    }

    public function alterColumnScheme($dbTable, array $fieldData, $newName = null)
    {
        is_null($newName) && $newName = $fieldData['name'];

        $sql = $this->isDbColumnExist($dbTable, $fieldData['name'])
            ? 'ALTER TABLE `:prefix:table` CHANGE `:column1` `:column2` :scheme'
            : 'ALTER TABLE `:prefix:table` ADD `:column2` :scheme';

        $sql = iaDb::printf($sql, [
            'prefix' => $this->iaDb->prefix,
            'table' => $dbTable,
            'column1' => $fieldData['name'],
            'column2' => $newName,
            'scheme' => $this->_alterCmdBody($fieldData)
        ]);

        $this->iaDb->query($sql);
    }

    public function alterColumnIndex($dbTable, $fieldName, $enabled)
    {
        $sql = sprintf('SHOW INDEX FROM `%s%s`', $this->iaDb->prefix, $dbTable);

        $exists = false;
        if ($indexes = $this->iaDb->getAll($sql)) {
            foreach ($indexes as $i) {
                if ($i['Key_name'] == $fieldName && $i['Index_type'] == 'FULLTEXT') {
                    $exists = true;
                    break;
                }
            }
        }

        if ($enabled && !$exists) {
            $sql = sprintf('ALTER TABLE `%s%s` ADD FULLTEXT(`%s`)', $this->iaDb->prefix, $dbTable, $fieldName);
        } elseif (!$enabled && $exists) {
            $sql = sprintf('ALTER TABLE `%s%s` DROP INDEX `%s`', $this->iaDb->prefix, $dbTable, $fieldName);
        }

        isset($sql) && $this->iaDb->query($sql);
    }

    public function alterDropColumn($dbTable, $columnName)
    {
        $sql = sprintf('ALTER TABLE `%s%s` DROP `%s`', $this->iaDb->prefix, $dbTable, $columnName);

        $this->iaDb->query($sql);
    }

    public function isDbColumnExist($dbTable, $columnName)
    {
        $sql = sprintf("SHOW COLUMNS FROM `%s%s` WHERE `Field` LIKE '%s'",
            $this->iaDb->prefix, $dbTable, $columnName);

        return (bool)$this->iaDb->getRow($sql);
    }

    public function isRestrictedName($columnName)
    {
        return in_array($columnName, $this->_restrictedNames);
    }

    protected function _copyContent($dbTable, $columnName, $defaultLanguageCode)
    {
        $primaryLangCol = $columnName . '_' . $defaultLanguageCode;

        $rawUpdateStmt = [];
        foreach ($this->iaCore->languages as $code => $language)
            $rawUpdateStmt[$columnName . '_' . $code] = '`' . $primaryLangCol . '`';

        $this->iaDb->update(null, iaDb::EMPTY_CONDITION, $rawUpdateStmt, $dbTable);
    }

    private function _alterCmdBody(array $fieldData)
    {
        $result = '';

        switch ($fieldData['type']) {
            case self::DATE:
                $result.= 'DATETIME ';
                break;
            case self::NUMBER:
                $result.= 'DOUBLE ';
                break;
            case self::TEXT:
                $result.= 'VARCHAR(' . $fieldData['length'] . ') '
                    . ($fieldData['default'] ? "DEFAULT '{$fieldData['default']}' " : '');
                break;
            case self::ICONPICKER:
            case self::URL:
                $result.= 'TINYTEXT ';
                break;
            case self::TREE:
            case self::IMAGE:
            case self::STORAGE:
            case self::PICTURES:
                $result.= 'TEXT ';
                break;
            case self::TEXTAREA:
                $result.= 'MEDIUMTEXT ';
                break;
            case self::CURRENCY:
                $result.= 'DECIMAL(' . ($fieldData['length'] + 2) . ',2) unsigned ';
                break;
            default:
                if (isset($fieldData['values'])) {
                    $values = explode(',', $fieldData['values']);

                    $result.= ($fieldData['type'] == self::CHECKBOX) ? 'SET' : 'ENUM';
                    $result.= "('" . implode("','", $values) . "')";

                    if (!empty($fieldData['default'])) {
                        $result.= " DEFAULT '{$fieldData['default']}' ";
                    }
                }
        }

        $result.= $fieldData['allow_null'] ? 'NULL' : 'NOT NULL';

        return $result;
    }

    public function syncMultilingualFields()
    {
        $iaItem = $this->iaCore->factory('item');

        $multilingualFields = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds(1, 'multilingual'),
            null, null, self::getTable());

        $this->iaCore->languages = $this->iaDb->assoc(
            ['code', 'id', 'title', 'locale', 'date_format', 'direction', 'master', 'default', 'flagicon', 'iso' => 'code', 'status'],
            iaDb::EMPTY_CONDITION . ' ORDER BY `order` ASC',
            iaLanguage::getLanguagesTable()
        );

        foreach ($multilingualFields as $field) {
            $dbTable = $iaItem->getItemTable($field['item']);
            $this->alterMultilingualColumns($dbTable, $field['name'], $field);
        }
    }

    // image types
    public function getImageTypes()
    {
        static $cache;

        if (is_null($cache)) {
            $sql = <<<SQL
	SELECT it.*, GROUP_CONCAT(ft.`extension`) `extensions`
	FROM `:prefix:table_image_types` it
	LEFT JOIN `:prefix:table_image_type_file_types` itft ON (itft.`image_type_id` = it.`id`)
	LEFT JOIN `:prefix:table_file_types` ft ON (ft.`id` IN (itft.`file_type_id`))
	GROUP BY it.`id`
SQL;
            $sql = iaDb::printf($sql, [
                'prefix' => $this->iaDb->prefix,
                'table_image_types' => self::$_tableImageTypes,
                'table_image_type_file_types' => self::$_tableImageTypesFileTypes,
                'table_file_types' => self::$_tableFileTypes
            ]);

            if ($cache = $this->iaDb->getAll($sql)) {
                foreach ($cache as &$entry) {
                    $entry['extensions'] = empty($entry['extensions']) ? [] : explode(',', $entry['extensions']);
                }
            }
        }

        return $cache;
    }

    public function getFileTypes($imagesOnly = false)
    {
        $where = $imagesOnly ? iaDb::convertIds(1, 'image') : iaDb::EMPTY_CONDITION;

        return $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $where, null, null, self::$_tableFileTypes);
    }

    public function getFileTypesByImageTypeId($id)
    {
        return $this->iaDb->onefield('file_type_id', iaDb::convertIds($id, 'image_type_id'), null, null, self::getTableImageTypesFileTypes());
    }

    public function getImageTypeNamesByField(array $field)
    {
        if ($field['type'] != self::IMAGE && $field['type'] != self::PICTURES) {
            return [];
        }

        $result = [self::IMAGE_TYPE_THUMBNAIL, self::IMAGE_TYPE_LARGE];

        if ($field['timepicker']) {
            $result = [];

            if ($ids = $this->getImageTypeIdsByFieldId($field['id'])) {
                foreach ($this->getImageTypes() as $imageType) {
                    in_array($imageType['id'], $ids) && $result[] = $imageType['name'];
                }
            }
        }

        return $result;
    }

    public function getImageTypeIdsByFieldId($fieldId)
    {
        return $this->iaDb->onefield('image_type_id', iaDb::convertIds($fieldId, 'field_id'), null, null, self::$_tableFieldsImageTypes);
    }

    public function saveImageTypesByFieldId($id, array $imageTypeIds)
    {
        $this->iaDb->setTable(self::getTableFieldsImageTypes());

        $this->iaDb->delete(iaDb::convertIds($id, 'field_id'));

        foreach ($imageTypeIds as $imageTypeId) {
            $this->iaDb->insert(['field_id' => (int)$id, 'image_type_id' => (int)$imageTypeId]);
        }

        $this->iaDb->resetTable();
    }

    public function deleteFileByPath($path, $file, $imageTypes = null)
    {
        iaSanitize::filenameEscape($file);

        $this->iaCore->factory('util');

        if (is_null($imageTypes)) {
            return iaUtil::deleteFile(IA_UPLOADS . $path . $file);
        } elseif (is_array($imageTypes)) {
            $imageTypes[] = self::UPLOAD_FOLDER_ORIGINAL;

            $files = [];
            foreach ($imageTypes as $imageTypeName) {
                iaUtil::deleteFile($files[] = IA_UPLOADS . $path . $imageTypeName . IA_DS . $file);
            }

            // returning valid result
            $result = true;
            foreach ($files as $file) {
                if (is_file($file)) {
                    $result = false;
                    break;
                }
            }

            return $result;
        }

        return false;
    }

    public function cleanUpItemFiles($itemName, array $itemData)
    {
        if ($uploadFields = $this->getUploadFields($itemName)) {
            foreach ($uploadFields as $fieldName) {
                empty($itemData[$fieldName]) || $this->deleteFilesByFieldName($fieldName, $itemName, $itemData[$fieldName]);
            }
        }
    }

    public function deleteFilesByFieldName($fieldName, $itemName, $files)
    {
        if (!$files || !is_string($files)) {
            return false;
        }

        if (!$field = $this->getField($fieldName, $itemName)) {
            return false;
        }

        $filesArray = unserialize($files);

        switch ($field['type']) {
            case self::IMAGE:
                return $this->deleteFileByPath($filesArray['path'], $filesArray['file'], $this->getImageTypeNamesByField($field));
            case self::PICTURES:
            case self::STORAGE:
                $result = [];
                $imageTypeNames = ($field['type'] == self::PICTURES) ? $this->getImageTypeNamesByField($field) : null;
                foreach ($filesArray as $fileEntry) {
                    $result[] = $this->deleteFileByPath($fileEntry['path'], $fileEntry['file'], $imageTypeNames);
                }
                return !in_array(false, $result, true);
            default:
                throw new Exception('File removal request on unsupported type of field');
        }
    }

    public function deleteUploadedFile($fieldName, $itemName, $itemId, $fileName = null, $checkOwnership = false)
    {
        $fieldName = iaSanitize::paranoid($fieldName);
        $tableName = $this->iaCore->factory('item')->getItemTable($itemName);

        if ($checkOwnership) {
            if (!iaUsers::hasIdentity()) {
                return false;
            }

            $memberIdColumn = (iaUsers::getItemName() == $itemName) ? iaDb::ID_COLUMN_SELECTION : 'member_id';
            $row = $this->iaDb->row([$memberIdColumn, $fieldName], iaDb::convertIds($itemId), $tableName);

            if (!$row || iaUsers::getIdentity()->id != $row[$memberIdColumn]) {
                return false;
            }

            $itemValue = $row[$fieldName];
        } else {
            $itemValue = $this->iaDb->one([$fieldName], iaDb::convertIds($itemId), $tableName);
        }

        if ($itemValue) {
            $newValue = $itemValue;

            if ($field = $this->getField($fieldName, $itemName)) {
                $files = unserialize($itemValue);

                switch ($field['type']) {
                    case self::PICTURES:
                    case self::STORAGE:
                        foreach ($files as $i => $fileEntry) {
                            if ($fileEntry['file'] == $fileName) {
                                $path = $fileEntry['path'];
                                $file = $fileEntry['file'];
                                unset($files[$i]);
                                break;
                            }
                        }
                        break;
                    case self::IMAGE:
                        if ($files['file'] == $fileName) {
                            $path = $files['path'];
                            $file = $files['file'];
                            $files = [];
                            break;
                        }
                }

                $newValue = $files ? serialize(array_merge($files)) : '';

                if ($newValue != $itemValue) {
                    in_array($field['type'], [self::IMAGE, self::PICTURES])
                        ? $this->deleteFileByPath($path, $file, $this->getImageTypeNamesByField($field))
                        : $this->deleteFileByPath($path, $file);
                }
            } else {
                if ($fileName == $itemValue) {
                    $newValue = '';

                    list($path, $file) = explode('|', $itemValue);
                    $this->deleteFileByPath($path, $file, [self::IMAGE_TYPE_THUMBNAIL, self::IMAGE_TYPE_LARGE]);
                }
            }

            if ($itemValue != $newValue) {
                if ($this->iaDb->update([$fieldName => $newValue], iaDb::convertIds($itemId), null, $tableName)) {
                    // check if image removed from the entry of currently logged in user
                    // and reload his identity if so
                    iaUsers::getItemName() == $itemName && iaUsers::hasIdentity()
                        && $itemId == iaUsers::getIdentity()->id && iaUsers::reloadIdentity();

                    return true;
                }
            }
        }

        return false;
    }

    public function getField($fieldName, $itemName)
    {
        return $this->iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`name` = :name AND `item` = :item',
            ['name' => $fieldName, 'item' => $itemName], self::getTable());
    }

    /**
     * @param $itemName
     * @param array $itemData
     *
     * Different field types require different data transformations in order to be printed out
     * This method applies field type specific conversions to array elements
     */
    public function unwrapItemValues($itemName, array &$itemData)
    {
        foreach ($this->get($itemName) as $field) {
            $fieldName = $field['name'];

            if (!isset($itemData[$fieldName])) {
                $itemData[$fieldName] = $field['default'];
                continue;
            }

            switch ($field['type']) {
                case self::CHECKBOX:
                    $itemData[$fieldName] = explode(',', $itemData[$fieldName]);
                    break;
                case self::IMAGE:
                case self::PICTURES:
                case self::STORAGE:
                    $itemData[$fieldName] = $itemData[$fieldName] ? unserialize($itemData[$fieldName]) : [];
                    break;
                default:
                    if (!$itemData[$fieldName] && !in_array($field['type'], [self::NUMBER, self::CURRENCY])) {
                        $itemData[$fieldName] = $field['default'];
                    }
            }
        }
    }

    protected static function _processFileName(&$fileName)
    {
        // first, sanitize filename
        iaSanitize::filenameEscape($fileName);

        // then convert utf filenames
        iaCore::instance()->factory('util')
            ->loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

        if (!utf8_is_ascii($fileName)) {
            $fileName = utf8_to_ascii($fileName);
        }

        // last, replace spaces
        $fileName = str_replace(' ', '_', $fileName);
    }
}
