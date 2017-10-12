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
    protected $_name = 'phrases';

    protected $_table = 'language';

    protected $_gridColumns = "`id`, `key`, `original`, `value`, `code`, `category`, IF(`original` != `value`, 1, 0) `modified`, 1 `update`, 1 `delete`";
    protected $_gridFilters = ['key' => 'like', 'value' => 'like', 'category' => 'equal', 'module' => 'equal'];

    protected $_phraseAddSuccess = 'phrase_added';


    public function __construct()
    {
        parent::__construct();

        $this->setHelper($this->_iaCore->iaCache);
    }

    protected function _gridRead($params)
    {
        $params['lang'] = (isset($_GET['lang']) && array_key_exists($_GET['lang'], $this->_iaCore->languages))
            ? $_GET['lang']
            : $this->_iaCore->iaView->language;

        return parent::_gridRead($params);
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
        }

        if (isset($stmt)) {
            $output['result'] = isset($insertion) ? $insertion : (bool)$this->_iaDb->update($params, $stmt);
            $output['message'] = iaLanguage::get($output['result'] ? $this->_phraseEditSuccess : $this->_phraseSaveError);

            empty($output['result']) || $this->getHelper()->createJsCache(true);
        }

        return $output;
    }

    protected function _preSaveEntry(array &$entry, array $data, $action)
    {
        if (empty($data['key'])) {
            $error = true;
            $output['message'] = iaLanguage::get('incorrect_key');
        }

        if (empty($data['value'])) {
            $error = true;
            $output['message'] = iaLanguage::get('incorrect_value');
        }

        if (!$error) {
            $key = iaSanitize::paranoid($data['key']);
            $value = $data['value'];

            $category = iaSanitize::paranoid($data['category']);

            if (empty($key)) {
                $error = true;
                $output['message'] = iaLanguage::get('key_not_valid');
            }

            if (empty($value)) {
                $error = true;
                $output['message'] = iaLanguage::get('incorrect_value');
            }
        }
        _v($error);

        if (!$error) {
            foreach ($this->_iaCore->languages as $code => $language) {
                $exist = $this->_iaDb->exists('`key` = :key AND `code` = :language AND `category` = :category',
                    ['key' => $key, 'language' => $code, 'category' => $category]);

                if (isset($data['force_replacement']) || !$exist) {
                    iaLanguage::addPhrase($key, $value[$code], $code, '', $category);
                }
            }

            $output['result'] = true;
            $output['message'] = iaLanguage::get($this->_phraseAddSuccess);

            $this->getHelper()->createJsCache(true);
        }

        return !$this->getMessages();
    }

    protected function _postSaveEntry(array &$entry, array $data, $action)
    {
        $this->getHelper()->clearAll();
    }

    public function getById($id)
    {
        if ($phrase = parent::getById($id)) {
            $phrase['value'] = $this->_iaDb->keyvalue(['code', 'value'], iaDb::convertIds($phrase['key'], 'key'));
        }

        return $phrase;
    }

    protected function _assignValues(&$iaView, array &$entryData)
    {
        // phrase categories
        $categories = ['admin' => 'Administration Board', 'frontend' => 'User Frontend', 'common' => 'Common', 'tooltip' => 'Tooltip'];
        $iaView->assign('categories', $categories);

        // phrase modules
        $iaView->assign('modules', array_merge(['core' => 'Core'],
            $this->_iaDb->keyvalue(['name', 'title'], null, 'modules')));
    }

    protected function _setDefaultValues(array &$entry)
    {
        $entry = [
            'key' => '',
            'title' => '',
            'category' => 'common',
            'module' => 'core',
            'force_replacement' => false,
        ];
    }

    protected function _entryAdd(array $entryData)
    {
        return iaLanguage::addPhrase($entryData['key'], $entryData['value'], null, null, $entryData['category'], $entryData['force_replacement']);
    }

    protected function _entryUpdate(array $entryData, $entryId)
    {
        return $this->_iaDb->update($entryData, iaDb::convertIds($entryId));
    }

    protected function _entryDelete($entryId)
    {
        if ($entry = $this->getById($entryId)) {
            return iaLanguage::delete($entry['key']);
        }

        return true;
    }
}
