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
    protected $_name = 'image-types';

    protected $_table = 'image_types';

    protected $_gridColumns = ['name', 'width', 'height', 'resize_mode'];
    protected $_gridFilters = ['name' => self::LIKE, 'id' => self::EQUAL];


    public function __construct()
    {
        parent::__construct();

        $this->_iaCore->factory('picture');

        $this->setHelper($this->_iaCore->factory('field'));
    }

    protected function _setDefaultValues(array &$entry)
    {
        $entry['name'] = '';
        $entry['width'] = 900;
        $entry['height'] = 600;
        $entry['resize_mode'] = iaPicture::CROP;
    }

    protected function _preSaveEntry(array &$entry, array $data, $action)
    {
        $entry['width'] = (int)$data['width'];
        $entry['height'] = (int)$data['height'];
        $entry['resize_mode'] = $data['resize_mode'];
        //$entry['cropper'] = (int)$data['cropper'];

        if (iaCore::ACTION_ADD == $action) {
            $entry['name'] = trim(strtolower(iaSanitize::paranoid($data['name'])));

            if (!$entry['name']) {
                $this->addMessage('field_name_invalid');
            }
        }

        if (empty($data['fileTypes'])) {
            $this->addMessage('error_file_type');
        }

        if (!$entry['width'] || !$entry['height']) {
            $this->addMessage('error_incorrect_dimensions');
        }

        return !$this->getMessages();
    }

    protected function _postSaveEntry(array &$entry, array $data, $action)
    {
        $this->_saveFileTypes(isset($data['fileTypes']) ? $data['fileTypes'] : []);

        if (iaCore::ACTION_ADD == $action) {
            $this->_iaCore->factory('log')->write(iaLog::ACTION_CREATE, [
                'item' => 'image-type',
                'name' => $entry['name'],
                'id' => $this->getEntryId()
            ]);
        }
    }

    protected function _assignValues(&$iaView, array &$entryData)
    {
        switch (true) {
            case isset($_POST['fileTypes']):
                $types = $_POST['fileTypes'];
                break;
            case $this->getEntryId():
                $types = $this->getHelper()->getFileTypesByImageTypeId($this->getEntryId());
                break;
            default:
                $types = [];
                foreach ($this->getHelper()->getFileTypes(true) as $fileType) {
                    $types[] = $fileType['id'];
                }
        }

        $iaView->assign('assignedFileTypes', $types);
        $iaView->assign('fileTypes', $this->getHelper()->getFileTypes(true));
    }

    private function _saveFileTypes(array $fileTypes)
    {
        $this->_iaDb->setTable(iaField::getTableImageTypesFileTypes());

        $this->_iaDb->delete(iaDb::convertIds($this->getEntryId(), 'image_type_id'));

        foreach ($fileTypes as $typeId) {
            $this->_iaDb->insert(['image_type_id' => $this->getEntryId(), 'file_type_id' => (int)$typeId]);
        }

        $this->_iaDb->resetTable();
    }

    protected function _entryDelete($entryId)
    {
        if ($result = parent::_entryDelete($entryId)) {
            $where = iaDb::convertIds($entryId, 'image_type_id');

            $this->_iaDb->delete($where, iaField::getTableImageTypesFileTypes());
            $this->_iaDb->delete($where, iaField::getTableFieldsImageTypes());
        }

        return $result;
    }
}
