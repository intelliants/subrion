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

abstract class abstractModuleFrontApiResponder extends abstractModuleFront
{
    protected $apiRequest;
    protected $apiResponse;

    protected $apiHiddenFields = [];
    protected $apiProtectedFields = [];

    protected $_timestampColumn;

    protected $iaField;


    public function init()
    {
        parent::init();

        $this->iaField = $this->iaCore->factory('field');
    }

    public function setRequest(iaApiRequest $request)
    {
        $this->apiRequest = $request;
    }

    public function setResponse(iaApiResponse $response)
    {
        $this->apiResponse = $response;
    }

    // actions
    public function apiList($start, $limit, $where, $order)
    {
        $rows = $this->getAll($where . ' ' . $order, null, $start, $limit);

        $this->_apiFilterHiddenFields($rows);

        return $rows;
    }

    public function apiGet($id)
    {
        $row = $this->getById($id);

        $this->_apiFilterHiddenFields($row, true);

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

        return $this->delete($id);
    }

    public function apiUpdate($data, $id, array $params)
    {
        $resource = $this->apiGet($id);

        if (!$resource) {
            throw new Exception('Resource does not exist', iaApiResponse::NOT_FOUND);
        }

        if (!isset($resource['member_id']) || $resource['member_id'] != iaUsers::getIdentity()->id) {
            throw new Exception('Resource may be edited by owner only', iaApiResponse::FORBIDDEN);
        }

        if (1 == count($params)) {
            return $this->_apiUpdateSingleField($params[0], $id, $data);
        }

        $this->_apiProcessFields($data);

        $this->iaDb->update($data, iaDb::convertIds($id), null, $this->getTable());

        return (0 == $this->iaDb->getErrorNumber());
    }

    public function apiInsert(array $data)
    {
        if (!iaUsers::hasIdentity()) {
            throw new Exception('Guests not allowed to post data', iaApiResponse::UNAUTHORIZED);
        }

        $data['member_id'] = iaUsers::getIdentity()->id;

        if ($this->_timestampColumn && is_string($this->_timestampColumn)) {
            $data[$this->_timestampColumn] = date(iaDb::DATETIME_FORMAT);
        }

        $this->_apiProcessFields($data);

        return $this->iaDb->insert($data, null, $this->getTable());
    }

    protected function _apiFilterHiddenFields(&$rows, $singleRow = false)
    {
        if (!$rows) {
            return;
        }

        $singleRow && $rows = [$rows];

        foreach ($rows as &$row) {
            if (!is_array($row)) {
                break;
            }

            foreach ($this->apiHiddenFields as $fieldName) {
                unset($row[$fieldName]);
            }
        }

        $singleRow && $rows = array_shift($rows);
    }

    protected function _apiProcessFields(&$data)
    {
        if (!is_array($data)) {
            throw new Exception('Invalid data (array expected)', iaApiResponse::BAD_REQUEST);
        }

        foreach ($this->apiProtectedFields as $protectedFieldName) {
            if (isset($data[$protectedFieldName])) {
                unset($data[$protectedFieldName]);
            }
        }

        $fields = $this->iaField->get($this->getName());

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
                    $data[$fieldName] = $this->_apiProcessUploadField($image, $field);
            }
        }
    }

    protected function _apiUpdateSingleField($fieldName, $entryId, $content)
    {
        $iaField = $this->iaCore->factory('field');

        $fieldParams = $this->iaDb->row_bind(['id', 'type', 'required', 'image_width', 'image_height', 'thumb_width',
            'thumb_height', 'resize_mode', 'file_prefix', 'folder_name', 'timepicker', 'file_types', 'imagetype_thumbnail'],
            '`name` = :field AND `item` = :item', ['field' => $fieldName, 'item' => $this->getItemName()], $iaField::getTable());

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
                if (!is_string($content)) {
                    throw new Exception('Invalid image', iaApiResponse::BAD_REQUEST);
                }

                $upload = $this->_apiProcessUploadField($content, $fieldParams);

                $initialValue = $this->iaDb->one($fieldName, iaDb::convertIds($entryId), self::getTable());
                $initialValue = empty($initialValue)
                    ? []
                    : unserialize($initialValue);

                $value = $fieldParams['type'] == iaField::IMAGE
                    ? $upload
                    : array_merge($initialValue, [$upload]);
                $value = serialize($value);

                $imageType = $fieldParams['timepicker'] ? $fieldParams['imagetype_thumbnail'] : iaField::IMAGE_TYPE_THUMBNAIL;

                $output = IA_CLEAR_URL . 'uploads/' . $upload['path'] . $imageType . '/' . $upload['file'];

                break;

            default:
                $output = '';
                $value = $content;
        }

        $this->iaDb->update([$fieldName => $value], iaDb::convertIds($entryId), null, self::getTable());

        if (0 !== $this->iaDb->getErrorNumber()) {
            throw new Exception('DB error', iaApiResponse::INTERNAL_ERROR);
        }

        // remove previously assigned resource for 'image' field
        if (iaField::IMAGE == $fieldParams['type'] && !empty($initialValue)) {
            // remove previously assigned resource
            $this->iaField->deleteUploadedFile($fieldName, $this->getItemName(), $entryId,
                $initialValue['file']);
        }

        return $output;
    }

    protected function _apiProcessUploadField($content, array $field)
    {
        $tempFile = self::_getTempFile();
        file_put_contents($tempFile, $content);

        $result = $this->iaField->processUploadedFile($tempFile, $field,
            self::_getUniqueFileName($_SERVER['CONTENT_TYPE']), $_SERVER['CONTENT_TYPE']);

        if ($message = $this->iaField->getMessage()) {
            throw new Exception($message, iaApiResponse::INTERNAL_ERROR);
        }

        return $result;
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