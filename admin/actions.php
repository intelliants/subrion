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
	protected $_name = 'actions';


	protected function _gridRead($params)
	{
		if (2 == count($this->_iaCore->requestPath) && 'options' == $this->_iaCore->requestPath[0])
		{
			return $this->_fetchOptions($this->_iaCore->requestPath[1]);
		}

		switch ($_POST['action'])
		{
			case 'delete-file':
				return $this->_deleteFile($_POST);

			case 'remove-installer':
				$result = iaUtil::deleteFile(IA_HOME . 'install/modules/module.install.php');

				return array(
					'error' => !$result,
					'message' => iaLanguage::get($result ? 'deleted' : 'error')
				);

			case 'send-test-email':
				return $this->_sendTestEmail();

			case 'dropzone-upload-file':
				if (!is_writable(IA_UPLOADS))
				{
					$output['error'] = true;
					$output['message'] = 'error_directory_readonly';
				}
				else
				{
					$iaPicture = $this->_iaCore->factory('picture');
					$this->_iaCore->factory('field');

					$folderName = iaUtil::getAccountDir();
					$fieldName = 'file';
					if (!is_dir(IA_UPLOADS . $folderName))
					{
						mkdir(IA_UPLOADS . $folderName);
					}
					$path = $folderName;

					$this->_iaCore->factory('field');
					$galleryField = $this->_iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`name` = :name AND `item` = :item',
						array('name' => $_POST['field_name'], 'item' => $_POST['item_name']), iaField::getTable());
					list($fileName,) = iaField::generateFileName($_FILES[$fieldName]['name'], $galleryField['file_prefix'], false);

					if ($filePath = $iaPicture->processImage($_FILES[$fieldName], $path, $fileName, $galleryField))
					{
						$output['path'] = $filePath;
						$output['name'] = $fileName;
						$output['size'] = $_FILES[$fieldName]['size'];
						$output['error'] = false;
						$output['message'] = '';
					}
				}

				$this->_iaCore->iaView->assign($output);
				break;

			case 'dropzone-delete-file':
				$path = isset($_POST['path']) && $_POST['path'] ? $_POST['path'] : null;
				$output['error'] = false;
				$output['message'] = iaLanguage::get('deleted');
				if ($path)
				{
					if (!$this->_iaCore->factory('picture')->delete($path))
					{
						$output['error'] = true;
						$output['message'] = iaLanguage::get('error');
					}
				}

				$this->_iaCore->iaView->assign($output);

				break;

			default:
				$result = array();
				$this->_iaCore->startHook('phpAdminActionsJsonHandle', array('action' => $_POST['action'], 'output' => &$result));

				return $result;
		}
	}

	protected function _indexPage(&$iaView)
	{
		return iaView::errorPage(iaView::ERROR_NOT_FOUND);
	}

	private function _deleteFile($params)
	{
		$result = array('error' => true, 'message' => iaLanguage::get('invalid_parameters'));

		$item = isset($params['item']) ? iaSanitize::sql($params['item']) : null;
		$field = isset($params['field']) ? iaSanitize::sql($params['field']) : null;
		$path = isset($params['path']) ? iaSanitize::sql($params['path']) : null;
		$itemId = isset($params['itemid']) ? (int)$params['itemid'] : null;

		if ($itemId && $item && $field && $path)
		{
			$tableName = $this->_iaCore->factory('item')->getItemTable($item);
			$itemValue = $this->_iaDb->one($field, iaDb::convertIds($itemId), $tableName);

			$iaAcl = $this->_iaCore->factory('acl');
			if ($iaAcl->isAdmin() && $itemValue)
			{
				$pictures = ($itemValue[1] == ':') ? unserialize($itemValue) : $itemValue;
				$key = null;

				if (is_array($pictures)) // picture gallery
				{
					if ($primitive = !is_array($pictures[key($pictures)]))// used to correctly handle the Image type fields (holds the single image)
					{
						$pictures = array($pictures);
					}
					foreach ($pictures as $k => $v)
					{
						if ($path == $v['path'])
						{
							$key = $k;
							break;
						}
					}
					if (!is_null($key))
					{
						unset($pictures[$key]);
					}

					$newItemValue = $primitive ? '' : serialize($pictures);
				}
				else
				{
					// single image
					$newItemValue = '';
					if ($pictures == $path)
					{
						$key = true;
					}
				}

				if (!is_null($key))
				{
					if ($this->_iaCore->factory('picture')->delete($path))
					{
						if ($this->_iaDb->update(array($field => $newItemValue), iaDb::convertIds($itemId), null, $tableName))
						{
							if (iaUsers::getItemName() == $item)
							{
								// update current profile data
								if ($itemId == iaUsers::getIdentity()->id)
								{
									iaUsers::reloadIdentity();
								}
							}
						}

						$result['error'] = false;
						$result['message'] = iaLanguage::get('deleted');
					}
					else
					{
						$result['message'] = iaLanguage::get('error');
					}
				}
			}
		}

		return $result;
	}

	protected function _sendTestEmail()
	{
		$iaMailer = $this->_iaCore->factory('mailer');

		$iaMailer->Subject = 'Subrion CMS Mailing test';
		$iaMailer->Body = 'THIS IS A TEST EMAIL MESSAGE FROM ADMIN DASHBOARD.';

		$iaMailer->addAddress(iaUsers::getIdentity()->email);

		$result = $iaMailer->send();

		$output = array(
			'result' => $result,
			'message' => $result
				? iaLanguage::getf('test_email_sent', array('email' => iaUsers::getIdentity()->email))
				: iaLanguage::get('error') . ': ' . $iaMailer->getError()
		);

		return $output;
	}

	protected function _fetchOptions($entityName)
	{
		switch ($entityName)
		{
			case 'extras':
				$this->_iaCore->factory('item');
				$this->_iaCore->factory('page', iaCore::ADMIN);

				$sql = <<<SQL
SELECT IF(p.`extras` = '', 'core', p.`extras`) `value`, 
	IF(p.`extras` = '', 'Core', g.`title`) `title` 
	FROM `:prefix:table_pages` p 
LEFT JOIN `:prefix:table_extras` g ON (g.`name` = p.`extras`) 
GROUP BY p.`extras`
SQL;
				$sql = iaDb::printf($sql, array(
					'prefix' => $this->_iaDb->prefix,
					'table_pages' => iaPage::getTable(),
					'table_extras' => iaItem::getExtrasTable()
				));

				return array('data' => $this->_iaDb->getAll($sql));
		}

		return array();
	}
}