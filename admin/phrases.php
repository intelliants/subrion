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
    protected $_name = 'phrases';

    protected $_table = 'language';

    protected $_gridColumns = ['key', 'original', 'value', 'code', 'category'];
    protected $_gridFilters = ['key' => self::LIKE, 'value' => self::LIKE, 'category' => self::EQUAL, 'module' => self::EQUAL];

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

    protected function _gridModifyOutput(array &$entries)
    {
        foreach ($entries as &$entry) {
            $entry['modified'] = $entry['original'] != $entry['value'];
        }
    }

    protected function _gridUpdate($params)
    {
        $output = parent::_gridUpdate($params);

        if ($output['result']) {
            $this->getHelper()->createJsCache(true);
        }

        return $output;
    }

    protected function _setDefaultValues(array &$entry)
    {
        $entry = [
            'key' => '',
            'category' => 'common',
            'module' => 'core'
        ];
    }

    protected function _preSaveEntry(array &$entry, array $data, $action)
    {
        $entry['category'] = $data['category'];
        $entry['value'] = $data['value'];

        if (iaCore::ACTION_ADD == $action) {
            $entry['module'] = $data['module'];

            if (empty($data['key'])) {
                $this->addMessage('incorrect_key');
            } else {
                $entry['key'] = iaSanitize::paranoid($data['key']);

                if (!$entry['key']) {
                    $this->addMessage('key_not_valid');
                } elseif ($this->_phraseExists($entry)) {
                    $this->addMessage('phrase_exists');
                }
            }
        } else {
            if (($this->_phrasesCount($entry) + 1) > count($this->_iaCore->languages)) {
                $this->addMessage('phrase_exists');
            }
        }

        return !$this->getMessages();
    }

    protected function _entryAdd(array $entryData)
    {
        $entryData['code'] = iaLanguage::getMasterLanguage()->iso;
        $entryData['value'] = $entryData['value'][iaLanguage::getMasterLanguage()->iso];
        $entryData['original'] = $entryData['value'];

        return parent::_entryAdd($entryData);
    }

    protected function _entryUpdate(array $entryData, $entryId)
    {
        if (isset($entryData['value']) && is_array($entryData['value'])) {
            $entryData['value'] = $entryData['value'][iaLanguage::getMasterLanguage()->iso];
        }

        return parent::_entryUpdate($entryData, $entryId);
    }

    protected function _entryDelete($entryId)
    {
        $entry = parent::getById($entryId);

        return (bool)iaLanguage::delete($entry['key']);
    }

    protected function _postSaveEntry(array &$entry, array $data, $action)
    {
        $this->_saveSecondaryLanguagesPhrases($data['value']);
        $this->getHelper()->createJsCache(true);
    }

    private function _saveSecondaryLanguagesPhrases(array $values)
    {
        $entry = parent::getById($this->getEntryId());

        foreach ($this->_iaCore->languages as $code => $language) {
            if ($code != iaLanguage::getMasterLanguage()->iso) {
                iaLanguage::addPhrase($entry['key'], $values[$code], $code,
                    $entry['module'], $entry['category']);
            }
        }
    }

    public function getById($id)
    {
        if ($entry = parent::getById($id)) {
            $where = '`key` = :key AND `category` = :category AND `module` = :module';
            $this->_iaDb->bind($where, $entry);

            $entry['value'] = $this->_iaDb->keyvalue(['code', 'value'], $where);
        }

        return $entry;
    }

    protected function _assignValues(&$iaView, array &$entryData)
    {
        $categories = [
            'admin' => 'Administration Board',
            'frontend' => 'User Frontend',
            'common' => 'Common',
            'tooltip' => 'Tooltip'
        ];

        $modules = array_merge(['' => 'Core'],
            $this->_iaDb->keyvalue(['name', 'title'], null, 'modules'));

        $iaView->assign('categories', $categories);
        $iaView->assign('modules', $modules);
    }

    private function _phrasesCount(array $entry)
    {
        unset($entry['value']);

        return (int)$this->_iaDb->one_bind(iaDb::STMT_COUNT_ROWS,
            '`id` != :id AND `key` = :key AND `category` = :category AND `module` = :module',
            array_merge($entry, ['id' => $this->getEntryId()]));
    }

    private function _phraseExists(array $entry)
    {
        unset($entry['value']);

        return (int)$this->_iaDb->exists('`key` = :key AND `category` = :category AND `module` = :module', $entry);
    }
}
