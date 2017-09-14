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

abstract class iaApiEntityAbstract extends abstractCore
{
    protected $_name;

    protected $request;
    protected $response;

    protected $hiddenFields = [];
    protected $protectedFields = [];

    protected $iaField;


    public function init()
    {
        parent::init();

        $this->iaField = $this->iaCore->factory('field');
    }

    public function getName()
    {
        return $this->_name;
    }

    public function setRequest(iaApiRequest $request)
    {
        $this->_request = $request;
    }

    public function setResponse(iaApiResponse $response)
    {
        $this->_response = $response;
    }

    // actions
    public function apiList($start, $limit, $where, $order)
    {
        $rows = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $where . ' ' . $order, $start, $limit, $this->getTable());

        $this->_filterHiddenFields($rows);

        return $rows;
    }

    public function apiGet($id)
    {
        $row = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id), $this->getTable());

        $this->_filterHiddenFields($row, true);

        return $row;
    }

    public function apiDelete($id)
    {
        $resource = $this->apiGet($id);

        if (!$resource) {
            throw new Exception('Resource does not exist', iaApiResponse::NOT_FOUND);
        }

        if (!isset($resource['member_id']) || $resource['member_id'] != iaUsers::getIdentity()->id) {
            throw new Exception('Resource may be removed by owner only', iaApiResponse::FORBIDDEN);
        }

        return (bool)$this->iaDb->delete(iaDb::convertIds($id), $this->getTable());
    }

    public function apiUpdate(array $data, $id, array $params)
    {
        $resource = $this->apiGet($id);

        if (!$resource) {
            throw new Exception('Resource does not exist', iaApiResponse::NOT_FOUND);
        }

        if (!isset($resource['member_id']) || $resource['member_id'] != iaUsers::getIdentity()->id) {
            throw new Exception('Resource may be edited by owner only', iaApiResponse::FORBIDDEN);
        }

        $this->iaDb->update($data, iaDb::convertIds($id), null, $this->getTable());

        return (0 == $this->iaDb->getErrorNumber());
    }

    public function apiInsert(array $data)
    {
        if (!iaUsers::hasIdentity()) {
            throw new Exception('Guests not allowed to post data', iaApiResponse::UNAUTHORIZED);
        }

        $data['member_id'] = iaUsers::getIdentity()->id;

        return $this->iaDb->insert($data, null, $this->getTable());
    }

    protected function _filterHiddenFields(&$rows, $singleRow = false)
    {
        if (!$rows) {
            return;
        }

        $singleRow && $rows = [$rows];

        foreach ($rows as &$row) {
            if (!is_array($row)) {
                break;
            }

            foreach ($this->hiddenFields as $fieldName) {
                if (isset($row[$fieldName])) {
                    unset($row[$fieldName]);
                }
            }
        }

        $singleRow && $rows = array_shift($rows);
    }

    protected function _apiProcessFields(array &$data)
    {
        foreach ($this->protectedFields as $protectedFieldName) {
            if (isset($data[$protectedFieldName])) {
                unset($data[$protectedFieldName]);
            }
        }

        $fields = $this->iaCore->factory('field')->get($this->getName());

        foreach ($fields as $field) {
            $fieldName = $field['name'];

            if (empty($data[$fieldName])) {
                continue;
            }

            switch ($field['type']) {
                case iaField::IMAGE:
                case iaField::PICTURES:
                case iaField::STORAGE:
                    $image = base64_decode($data[$fieldName]);
                    $data[$fieldName] = serialize($this->_apiProcessUploadField($image, $field));
            }
        }
    }

    protected function _apiUpdateSingleField($fieldName, $entryId, $content)
    {
        $iaField = $this->iaCore->factory('field');

        $fieldParams = $this->iaDb->row_bind(['id', 'type', 'required', 'image_width', 'image_height', 'thumb_width',
            'thumb_height', 'resize_mode', 'file_prefix', 'folder_name', 'timepicker', 'file_types', 'imagetype_thumbnail'],
            '`name` = :field AND `item` = :item', ['field' => $fieldName, 'item' => $this->getName()], $iaField::getTable());

        if (!$fieldParams) {
            throw new Exception('No field to update', iaApiResponse::NOT_FOUND);
        }

        if ($fieldParams['required'] && !$content) {
            throw new Exception('Empty value is not accepted', iaApiResponse::UNPROCESSABLE_ENTITY);
        }

        switch ($fieldParams['type']) {
            case iaField::IMAGE:
            case iaField::PICTURES:
            case iaField::STORAGE:
                $result = $this->_apiProcessUploadField($content, $fieldParams);
                $value = serialize($result);

                $imageType = $fieldParams['timepicker'] ? $fieldParams['imagetype_thumbnail'] : iaField::IMAGE_TYPE_THUMBNAIL;

                $output = IA_CLEAR_URL . 'uploads/' . $result['path'] . $imageType . '/' . $result['file'];

                break;

            default:
                $output = '';
                $value = $content;
        }

        $this->iaDb->update([$fieldName => $value], iaDb::convertIds($entryId), null, $this->getTable());

        if (0 !== $this->iaDb->getErrorNumber()) {
            throw new Exception('DB error', iaApiResponse::INTERNAL_ERROR);
        }

        return $output;
    }

    protected function _apiProcessUploadField($content, array $field)
    {
        $tempFile = self::_getTempFile();
        file_put_contents($tempFile, $content);

        // TODO: implement previous uploads removal

        return $this->iaField->processUploadedFile($tempFile, $field,
            self::_getUniqueFileName($_SERVER['CONTENT_TYPE']), $_SERVER['CONTENT_TYPE']);
    }

    private static function _getTempFile()
    {
        return tempnam(sys_get_temp_dir(), 'api');
    }

    private static function _getUniqueFileName($contentType)
    {
        $contentTypeToExtensionMap = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/gif' => 'gif'
        ];

        $suffix = isset($contentTypeToExtensionMap[$contentType])
            ? '.' . $contentTypeToExtensionMap[$contentType]
            : '';

        return uniqid(mt_rand(), true) . $suffix;
    }
}
