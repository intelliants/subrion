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
    protected $_name = 'actions';


    protected function _gridRead($params)
    {
        if (2 == count($this->_iaCore->requestPath) && 'options' == $this->_iaCore->requestPath[0]) {
            return $this->_fetchOptions($this->_iaCore->requestPath[1]);
        }

        switch ($_POST['action']) {
            case 'delete-file':
                return $this->_deleteUploadedFile($_POST);

            case 'remove-installer':
                $result = iaUtil::deleteFile(IA_HOME . 'install/modules/module.install.php');

                return [
                    'error' => !$result,
                    'message' => iaLanguage::get($result ? 'deleted' : 'error')
                ];

            case 'send-test-email':
                return $this->_sendTestEmail();

            case 'dropzone-upload-file':
                return $this->_dropzoneUpload();

            case 'dropzone-delete-file':
                return $this->_dropzoneDelete();

            default:
                $result = [];
                $this->_iaCore->startHook('phpAdminActionsJsonHandle',
                    ['action' => $_POST['action'], 'output' => &$result]);

                return $result;
        }
    }

    protected function _indexPage(&$iaView)
    {
        return iaView::errorPage(iaView::ERROR_NOT_FOUND);
    }

    protected function _dropzoneUpload()
    {
        $result = ['error' => true, 'message' => 'invalid_parameters'];

        is_writable(IA_UPLOADS) || $result['message'] = 'error_directory_readonly';

        if (empty($_POST['field']) || empty($_POST['item']) || empty($_FILES) || !is_writable(IA_UPLOADS)) {
            return $result;
        }

        $iaField = $this->_iaCore->factory('field');

        if ($field = $iaField->getField($_POST['field'], $_POST['item'])) {
            $fieldName = 'file';

            if (in_array($field['type'], [iaField::IMAGE, iaField::PICTURES])
                && iaCore::STATUS_ACTIVE == $field['status']
                && isset($_FILES[$fieldName]['error']) && !$_FILES[$fieldName]['error']
            ) {
                try {
                    $result = $iaField->processUploadedFile($_FILES[$fieldName]['tmp_name'],
                        $field, $_FILES[$fieldName]['name'], $_FILES[$fieldName]['type']);

                    $result['error'] = false;
                    $result['size'] = $_FILES[$fieldName]['size'];
                    $result['imagetype'] = $field['imagetype_primary'];
                } catch (Exception $e) {
                    $result['message'] = $e->getMessage();
                }
            }
        }

        return $result;
    }

    protected function _dropzoneDelete()
    {
        $result = ['error' => true, 'message' => iaLanguage::get('invalid_parameters')];

        if (empty($_POST['field']) || empty($_POST['item']) || empty($_POST['path']) || empty($_POST['file'])) {
            return $result;
        }

        $iaField = $this->_iaCore->factory('field');

        if ($field = $iaField->getField($_POST['field'], $_POST['item'])) {
            $iaField->deleteFileByPath($_POST['path'], $_POST['file'],
                $iaField->getImageTypeNamesByField($field));

            $result['error'] = false;
            $result['message'] = iaLanguage::get('deleted');
        } else {
            $result['message'] = iaLanguage::get('error');
        }

        return $result;
    }

    protected function _deleteUploadedFile($params)
    {
        $result = ['error' => true, 'message' => iaLanguage::get('invalid_parameters')];

        $item = isset($params['item']) ? $params['item'] : null;
        $itemId = isset($params['itemid']) ? (int)$params['itemid'] : null;
        $field = isset($params['field']) ? $params['field'] : null;
        $file = isset($params['file']) ? $params['file'] : null;

        if ($itemId && $item && $field) {
            $result = $this->_iaCore->factory('field')->deleteUploadedFile($field, $item, $itemId, $file)
                ? ['error' => false, 'message' => iaLanguage::get('deleted')]
                : ['error' => true, 'message' => iaLanguage::get('error')];
        }

        return $result;
    }

    protected function _sendTestEmail()
    {
        $iaMailer = $this->_iaCore->factory('mailer');

        $iaMailer->setSubject('Subrion CMS Mailing test');
        $iaMailer->setBody('THIS IS A TEST EMAIL MESSAGE FROM ADMIN DASHBOARD.');

        $iaMailer->addAddressByMember(iaUsers::getIdentity(true));

        $result = $iaMailer->send();

        $output = [
            'result' => $result,
            'message' => $result
                ? iaLanguage::getf('test_email_sent', ['email' => iaUsers::getIdentity()->email])
                : iaLanguage::get('error') . ': ' . $iaMailer->getError()
        ];

        return $output;
    }

    protected function _fetchOptions($entityName)
    {
        switch ($entityName) {
            case 'module':
                $this->_iaCore->factory('item');
                $this->_iaCore->factory('page', iaCore::ADMIN);

                $sql = <<<SQL
SELECT IF(p.`module` = '', 'core', p.`module`) `value`, IF(p.`module` = '', 'Core', g.`title`) `title` 
  FROM `:prefix:table_pages` p 
LEFT JOIN `:prefix:table_modules` g ON (g.`name` = p.`module`) 
GROUP BY p.`module`
SQL;
                $sql = iaDb::printf($sql, [
                    'prefix' => $this->_iaDb->prefix,
                    'table_pages' => iaPage::getTable(),
                    'table_modules' => iaItem::getModulesTable()
                ]);

                return ['data' => $this->_iaDb->getAll($sql)];
        }

        return [];
    }
}
