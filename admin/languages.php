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
    protected $_name = 'languages';

    protected $_tooltipsEnabled = true;

    protected $_gridColumns = "`id`, `key`, `original`, `value`, `code`, `category`, IF(`original` != `value`, 1, 0) `modified`, 1 `update`1 `delete`";
    protected $_gridFilters = ['key' => 'like', 'value' => 'like', 'category' => 'equal', 'module' => 'equal'];

    protected $_phraseAddSuccess = 'phrase_added';


    public function __construct()
    {
        parent::__construct();

        $this->setTable(iaLanguage::getTable());

        $this->setHelper($this->_iaCore->iaCache);
    }

    protected function _gridRead($params)
    {
        $output = [];
        $iaDb = &$this->_iaDb;

        if (empty($params['get'])) {
            $params['lang'] = (isset($_GET['lang']) && array_key_exists($_GET['lang'], $this->_iaCore->languages))
                ? $_GET['lang']
                : $this->_iaCore->iaView->language;

            return parent::_gridRead($params);
        }

        switch ($params['get']) {
            case 'plugins':
                if ($plugins = $this->_iaDb->onefield('name', null, null, null, 'module')) {
                    $output['data'][] = ['value' => iaCore::CORE, 'title' => iaLanguage::get('core', 'Core')];

                    foreach ($plugins as $plugin) {
                        $output['data'][] = ['value' => $plugin, 'title' => iaLanguage::get($plugin, ucfirst($plugin))];
                    }
                }

                break;

            case 'comparison':
                if (isset($params['lang1']) && isset($params['lang2']) && $params['lang1'] != $params['lang2']
                    && array_key_exists($params['lang1'],
                        $this->_iaCore->languages) && array_key_exists($params['lang2'], $this->_iaCore->languages)
                ) {
                    $start = isset($params['start']) ? (int)$params['start'] : 0;
                    $limit = isset($params['limit']) ? (int)$params['limit'] : 15;

                    $values = [];

                    if (!empty($params['key'])) {
                        $conditions[] = '`key` LIKE :key OR `value` LIKE :key';
                        $values['key'] = '%' . $params['key'] . '%';
                    }

                    if (!empty($params['category'])) {
                        $conditions[] = '`category` = :category';
                        $values['category'] = $params['category'];
                    }

                    if (!empty($params['plugin'])) {
                        if (iaCore::CORE == $params['plugin']) {
                            $params['plugin'] = '';
                        }
                        $conditions[] = '`module` = :plugin';
                        $values['plugin'] = $params['plugin'];
                    }

                    $where = empty($conditions) ? iaDb::EMPTY_CONDITION : implode(' AND ', $conditions);
                    $iaDb->bind($where, $values);

                    $rows = $iaDb->all('SQL_CALC_FOUND_ROWS DISTINCT `key`, `category`', $where, $start, $limit);
                    $output = ['data' => [], 'total' => $iaDb->foundRows()];

                    $keys = [];
                    foreach ($rows as $row) {
                        $keys[] = $row['key'];
                    }

                    $stmt = "`code` = ':lang' AND `key` IN('" . implode("','", $keys) . "')";

                    $lang1 = $iaDb->keyvalue(['key', 'value'], iaDb::printf($stmt, ['lang' => $params['lang1']]));
                    $lang2 = $iaDb->keyvalue(['key', 'value'], iaDb::printf($stmt, ['lang' => $params['lang2']]));

                    foreach ($rows as $row) {
                        $key = $row['key'];
                        $output['data'][] = [
                            'key' => $key,
                            'lang1' => isset($lang1[$key]) ? $lang1[$key] : null,
                            'lang2' => isset($lang2[$key]) ? $lang2[$key] : null,
                            'category' => $row['category']
                        ];
                    }
                }
        }

        return $output;
    }

    protected function _gridModifyParams(&$conditions, &$values, array $params)
    {
        if (!empty($params['lang']) && array_key_exists($params['lang'], $this->_iaCore->languages)) {
            $conditions[] = '`code` = :language';
            $values['language'] = $params['lang'];
        }

        if (isset($values['module']) && iaCore::CORE == $values['module']) {
            $values['module'] = '';
        }
    }

    protected function _jsonAction(&$iaView)
    {
        $error = false;
        $output = ['result' => false, 'message' => iaLanguage::get('invalid_parameters')];

        if (isset($_POST['sorting']) && 'save' == $_POST['sorting']) {
            if (count($_POST['langs']) > 1) {
                $i = 0;
                foreach ($_POST['langs'] as $code) {
                    $this->_iaDb->update(['order' => ++$i], iaDb::convertIds($code, 'code'), null,
                        iaLanguage::getLanguagesTable());
                }

                $output = ['result' => true, 'message' => iaLanguage::get('saved')];
            }
        } else {
            if (empty($_POST['key'])) {
                $error = true;
                $output['message'] = iaLanguage::get('incorrect_key');
            }

            if (empty($_POST['value'])) {
                $error = true;
                $output['message'] = iaLanguage::get('incorrect_value');
            }

            if (!$error) {
                $key = iaSanitize::paranoid($_POST['key']);
                $value = $_POST['value'];
                $category = iaSanitize::paranoid($_POST['category']);

                if (empty($key)) {
                    $error = true;
                    $output['message'] = iaLanguage::get('key_not_valid');
                }

                if (empty($value)) {
                    $error = true;
                    $output['message'] = iaLanguage::get('incorrect_value');
                }
            }

            if (!$error) {
                foreach ($this->_iaCore->languages as $code => $language) {
                    $exist = $this->_iaDb->exists('`key` = :key AND `code` = :language AND `category` = :category',
                        ['key' => $key, 'language' => $code, 'category' => $category]);
                    if (isset($_POST['force_replacement']) || !$exist) {
                        iaLanguage::addPhrase($key, $value[$code], $code, '', $category);
                    }
                }

                $output['result'] = true;
                $output['message'] = iaLanguage::get($this->_phraseAddSuccess);

                $this->getHelper()->createJsCache(true);
            }
        }

        return $output;
    }

    protected function _gridUpdate($params)
    {
        $output = [
            'result' => false,
            'message' => iaLanguage::get('invalid_parameters')
        ];

        $params = $_POST;

        if (isset($params['id']) && $params['id']) {
            $stmt = '`id` IN (' . implode($params['id']) . ')';

            unset($params['id']);
        } elseif (isset($params['key'])) { // request from the page 'Comparison'
            $stmt = '`key` = :key';
            empty($params['lang']) || $stmt .= ' AND `code` = :lang';
            $this->_iaDb->bind($stmt, $params);

            if (!$this->_iaDb->exists($stmt)
                && ($row = $this->_iaDb->row_bind(['module', 'category'], '`key` = :key AND `code` != :lang', $params))
            ) {
                $insertion = iaLanguage::addPhrase($params['key'], $params['value'], $params['lang'], $row['module'],
                    $row['category'], true);
            }

            unset($params['key'], $params['lang']);
        }

        if (isset($stmt)) {
            $output['result'] = isset($insertion)
                ? $insertion
                : (bool)$this->_iaDb->update($params, $stmt);
            $output['message'] = iaLanguage::get($output['result'] ? $this->_phraseEditSuccess : $this->_phraseSaveError);

            empty($output['result']) || $this->getHelper()->createJsCache(true);
        }

        return $output;
    }

    public function getById($id)
    {
        return $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id), iaLanguage::getLanguagesTable());
    }

    protected function _setPageTitle(&$iaView, array $entryData, $action)
    {
        $iaView->title(iaLanguage::get(iaCore::ACTION_EDIT == $iaView->get('action') ? 'edit_language' : 'copy_language'));
    }

    protected function _preSaveEntry(array &$entry, array $data, $action)
    {
        if (empty($data['title']) || !trim($data['title'])) {
            $this->addMessage('title_incorrect');
        }

        // iso code SHOULD NOT BE changed
        if (iaCore::ACTION_ADD == $action) {
            if (preg_match('/^[a-z]{2}$/i', $data['code'])) {
                if (iaCore::ACTION_ADD == $action && array_key_exists($data['code'], $this->_iaCore->languages)) {
                    $this->addMessage('language_already_exists');
                }
            } else {
                $this->addMessage('bad_iso_code');
            }

            $entry['code'] = strtolower($data['code']);
        }

        if (empty($data['locale']) || !trim($data['locale'])) {
            $this->addMessage('language_locale_incorrect');
        }

        if (empty($data['date_format']) || !trim($data['date_format'])) {
            $this->addMessage('language_date_format_incorrect');
        }

        $entry['title'] = $data['title'];
        $entry['locale'] = $data['locale'];
        $entry['date_format'] = $data['date_format'];
        $entry['time_format'] = $data['time_format'];
        $entry['direction'] = $data['direction'];
        $entry['status'] = $data['status'];

        return !$this->getMessages();
    }

    protected function _postSaveEntry(array &$entry, array $data, $action)
    {
        if (iaCore::ACTION_ADD == $action) {
            $this->_iaCore->factory('field')->syncMultilingualFields();
            $this->_iaCore->factory('config')->copyMultilingualKeys($entry['code']);
            $this->_syncMultilingualEntities($entry['code'], $action);

            $this->_iaCore->factory('log')->write(iaLog::ACTION_CREATE, [
                'item' => 'language',
                'name' => $entry['title'],
                'id' => $this->getEntryId()
            ]);

            $this->_phraseAddSuccess = null;

            $this->getHelper()->clearAll();
        }
    }

    protected function _setDefaultValues(array &$entry)
    {
        $entry = [
            'code' => '',
            'title' => '',
            'locale' => 'en_US',
            'date_format' => '%e %B, %Y',
            'time_format' => '%H:%M',
            'direction' => 'ltr',
            'status' => iaCore::STATUS_INACTIVE
        ];
    }

    protected function _entryAdd(array $entryData)
    {
        // create language
        $entryData['order'] = $this->_iaDb->getMaxOrder('languages') + 1;

        $result = $this->_iaDb->insert($entryData, null, iaLanguage::getLanguagesTable());

        // copy phrases
        $counter = 0;
        $languageCode = strtolower($entryData['code']);
        $rows = $this->_iaDb->all(['key', 'value', 'category', 'module'],
            "`code` = '" . $this->_iaCore->iaView->language . "'");
        foreach ($rows as $value) {
            $row = [
                'key' => $value['key'],
                'value' => $value['value'],
                'module' => $value['module'],
                'code' => $languageCode,
                'category' => $value['category']
            ];

            if ($this->_iaDb->insert($row)) {
                $counter++;
            }
        }
        $this->_iaDb->update(null, iaDb::convertIds($languageCode, 'code'), ['original' => '`value`']);

        $this->_iaCore->iaView->setMessages(iaLanguage::getf('language_copied', ['count' => $counter]),
            iaView::SUCCESS);

        return $result;
    }

    protected function _entryUpdate(array $entryData, $entryId)
    {
        $this->_iaDb->update($entryData, iaDb::convertIds($entryId), null, iaLanguage::getLanguagesTable());

        return (0 === $this->_iaDb->getErrorNumber());
    }

    protected function _entryDelete($entryId)
    {
        $where = iaDb::convertIds($entryId, 'code');

        if ($result = $this->_iaDb->delete($where, iaLanguage::getLanguagesTable())) {
            $this->_iaDb->delete($where);

            $this->_syncMultilingualEntities($entryId, iaCore::ACTION_DELETE);

            $this->getHelper()->clearAll();
        }

        return $result;
    }

    protected function _indexPage(&$iaView)
    {
        if ('phrases' == $iaView->get('name')) {
            iaBreadcrumb::preEnd(iaLanguage::get('languages'), IA_ADMIN_URL . 'languages/');

            $iaView->assign('action', 'phrases');
            $iaView->display('languages');

            return true;
        }

        $action = isset($this->_iaCore->requestPath[0]) ? $this->_iaCore->requestPath[0] : 'list';
        $iaView->assign('action', $action);

        switch ($action) {
            case 'search':
                $pageCaption = iaLanguage::get('search_in_phrases');
                break;

            case 'download':
                $pageCaption = iaLanguage::get('export_language');

                if ((isset($_POST['lang']) && $_POST['lang'])
                    || (isset($this->_iaCore->requestPath[1])
                        && array_key_exists($this->_iaCore->requestPath[1], $this->_iaCore->languages))
                ) {
                    $this->_downloadLanguage($iaView);
                }

                break;

            case 'comparison':
                $pageCaption = iaLanguage::get('languages_comparison');

                $this->_compareLanguages($iaView);

                break;

            case 'rm':
                $defaultLanguage = $this->_iaDb->row(['code'], iaDb::convertIds(1, 'default'), iaLanguage::getLanguagesTable());
                $url = IA_CLEAR_URL . $defaultLanguage['code'] . IA_URL_DELIMITER . $this->_iaCore->get('admin_page') . IA_URL_DELIMITER . $this->_name . IA_URL_DELIMITER;
                // TODO: set checkAccess
                $this->_removeLanguage($iaView);
                iaUtil::go_to($url);

                break;

            case 'default':
                $this->_setLanguageAsDefault($iaView);
                iaUtil::go_to($this->getPath());

                break;

            case 'import':
                $result = $this->_importLanguage($iaView);
                iaUtil::go_to($this->getPath() . ($result ? '' : 'download/'));

                break;
        }

        if (isset($pageCaption)) {
            iaBreadcrumb::toEnd($pageCaption, IA_SELF);
            $iaView->title($pageCaption);
        }
    }

    private function _importLanguage(&$iaView)
    {
        if (!isset($_POST['form-import'])) {
            return false;
        }

        list($result, $messages, $languageCode) = self::_importDump($this->_iaDb);
        if ($result) {
            $this->_iaCore->languages[$languageCode] = $_POST['title'];
            $this->_iaCore->set('languages', serialize($this->_iaCore->languages), true);

            $this->getHelper()->clearAll();
        }

        $iaView->setMessages($messages, $result ? iaView::SUCCESS : iaView::ERROR);

        return $result;
    }

    private function _setLanguageAsDefault(&$iaView)
    {
        if (isset($this->_iaCore->requestPath[1]) && array_key_exists($this->_iaCore->requestPath[1],
                $this->_iaCore->languages)
        ) {
            $this->_iaCore->set('lang', $this->_iaCore->requestPath[1], true);
            $this->getHelper()->clearAll();

            $iaView->setMessages(iaLanguage::get('saved'), iaView::SUCCESS);
        } else {
            $iaView->setMessages(iaLanguage::get('invalid_parameters'));
        }
    }

    private function _removeLanguage(&$iaView)
    {
        if (!isset($this->_iaCore->requestPath[1]) || $this->_iaCore->get('lang') == $this->_iaCore->requestPath[1]) {
            return;
        }

        $this->_entryDelete($this->_iaCore->requestPath[1]);

        $iaView->setMessages(iaLanguage::get($this->_phraseGridEntryDeleted), iaView::SUCCESS);
    }

    private function _downloadLanguage(&$iaView)
    {
        $language = isset($_POST['lang']) ? iaSanitize::paranoid($_POST['lang']) : $this->_iaCore->requestPath[1];
        $format = isset($_POST['file_format']) && in_array($_POST['file_format'],
            ['csv', 'sql']) ? $_POST['file_format'] : 'sql';

        $phrases = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`code` = '" . $language . "'");
        $fileName = urlencode(isset($_POST['filename']) ? $_POST['filename'] . '.' . $format : 'subrion_' . IA_VERSION . '_' . $this->_iaCore->requestPath[1] . '.' . $format);

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        $stream = fopen('php://output', 'w');

        if ('sql' == $format) {
            fwrite($stream,
                'INSERT INTO `{prefix}language` (`id`, `key`, `original`, `value`, `category`, `code`, `module`) VALUES' . PHP_EOL);
        }

        foreach ($phrases as $i => $entry) {
            switch ($format) {
                case 'sql':
                    $data = '(';
                    foreach ($entry as $key => $value) {
                        $data .= $value
                            ? ('id' == $key) ? 'NULL' : "'" . iaSanitize::sql($value) . "'"
                            : "''";
                        $data .= ', ';
                    }
                    $data = substr($data, 0, -2);
                    $data .= isset($phrases[$i + 1])
                        ? '),' . PHP_EOL
                        : ');';

                    fwrite($stream, $data);

                    break;

                default:
                    unset($entry['id']);

                    $entry['value'] = str_replace(["\r\n", "\r", "\n"], '\n', $entry['value']);
                    $entry['original'] = str_replace(["\r\n", "\r", "\n"], '\n', $entry['original']);

                    fputcsv($stream, $entry, '|', '"');
            }
        }

        fclose($stream);

        $iaView->set('nodebug', true);

        exit;
    }

    private function _compareLanguages(&$iaView)
    {
        if (count($this->_iaCore->languages) > 1) {
            $languages = array_keys($this->_iaCore->languages);

            $lang1 = isset($_GET['l1']) && in_array($_GET['l1'], $languages)
                ? $_GET['l1']
                : $languages[0];
            $lang2 = isset($_GET['l2']) && in_array($_GET['l2'], $languages)
                ? $_GET['l2']
                : $languages[1];

            $iaView->assign('lang1', $lang1);
            $iaView->assign('lang2', $lang2);
        } else {
            $iaView->setMessages(iaLanguage::get('impossible_to_compare_single_language'));
        }
    }

    private static function _importDump(&$iaDb)
    {
        $filename = $_FILES ? $_FILES['language_file']['tmp_name'] : $_POST['language_file2'];
        $format = isset($_POST['format']) && in_array($_POST['format'], ['csv', 'sql']) ? $_POST['format'] : 'sql';

        $error = false;
        $messages = [];

        if (empty($filename)) {
            $error = true;
            $messages[] = iaLanguage::get('choose_import_file');
        } elseif (!$fh = fopen($filename, 'r')) {
            $error = true;
            $messages[] = iaLanguage::getf('cant_open_sql', ['filename' => $filename]);
        }

        if ($format == 'csv' && isset($_POST['title']) && trim($_POST['title']) == '') {
            $error = true;
            $messages[] = iaLanguage::get('title_is_empty');
        }

        if (!$error) {
            $error = true;
            $languageCode = '';

            switch ($format) {
                case 'sql':
                    $sql = '';

                    while ($s = fgets($fh, 10240)) {
                        $s = trim($s);
                        if ($s[0] == '#' || $s[0] == '') {
                            continue;
                        }
                        $sql .= $s;
                        if ($s[strlen($s) - 1] != ';') {
                            continue;
                        }
                        $sql = str_replace('{prefix}', $iaDb->prefix, $sql);
                        $iaDb->query($sql);
                        if (empty($languageCode)) {
                            $matches = [];
                            if (preg_match('#, \'([a-z]{2})\', \'#', $sql,
                                    $matches) || preg_match('#,\'([a-z]{2})\',\'#', $sql, $matches)
                            ) {
                                $languageCode = $matches[1];
                            }
                        }
                        $sql = '';
                    }

                    $error = false;

                    break;

                case 'csv':
                    while ($line = fgetcsv($fh, null, '|')) {
                        if (6 == count($line)) {
                            list($key, , $value, $category, $iso, $module) = $line;
                            if (empty($key) || 2 != strlen($iso)) {
                                continue;
                            }
                            iaLanguage::addPhrase($key, $value, $iso, $module, $category);
                        }
                    }

                    $error = false;
                    isset($iso) && $languageCode = $iso;
            }

            fclose($fh);

            $messages[] = iaLanguage::get($error ? 'incorrect_file_format' : 'saved');
        }

        return [!$error, $messages, isset($languageCode) ? $languageCode : null];
    }

    protected function _syncMultilingualEntities($langCode, $action)
    {
        $systemEntities = [
            'email_templates' => [
                'subject' => 'varchar(191) NOT NULL',
                'body' => 'text NOT NULL'
            ]
        ];

        foreach ($systemEntities as $entity => $columns) {
            foreach ($columns as $columnName => $scheme) {
                $columnNameIso = $columnName . '_' . $langCode;

                $sql = iaCore::ACTION_ADD == $action
                    ? 'ALTER TABLE `:table` ADD `:column` :scheme'
                    : 'ALTER TABLE `:table` DROP `:column`';

                $sql = iaDb::printf($sql, [
                    'table' => $this->_iaDb->prefix . $entity,
                    'column' => $columnNameIso,
                    'scheme' => $scheme
                ]);

                $this->_iaDb->query($sql);

                if (iaCore::ACTION_ADD == $action) {
                    $this->_iaDb->update(null, iaDb::EMPTY_CONDITION,
                        [$columnNameIso => '`' . $columnName . '_' . iaLanguage::getMasterLanguage()->iso . '`'], $entity);
                }
            }
        }
    }
}
