<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2014 Intelliants, LLC <http://www.intelliants.com>
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
 * @link http://www.subrion.org/
 *
 ******************************************************************************/

if (iaView::REQUEST_JSON == $iaView->getRequestType() && isset($_POST['action']))
{
	$output = array('error' => true, 'message' => iaLanguage::get('invalid_parameters'));

	switch ($_POST['action'])
	{
		case 'edit-picture-title':
			$title = empty($_POST['value']) ? '' : $_POST['value'];
			$item = isset($_POST['item']) ? $_POST['item'] : null;
			$field = isset($_POST['field']) ? iaSanitize::sql($_POST['field']) : null;
			$path = isset($_POST['path']) ? $_POST['path'] : null;
			$itemId = isset($_POST['itemid']) ? (int)$_POST['itemid'] : false;

			if ($itemId && $item && $field && $path)
			{
				$tableName = $iaCore->factory('item')->getItemTable($item);

				if (iaUsers::getItemName() == $item)
				{
					$itemValue = $iaDb->one($field, iaDb::convertIds($itemId), $tableName);
					$memberId = $itemId;
				}
				else
				{
					$row = $iaDb->row($field . ', `member_id` `id`', iaDb::convertIds($itemId), $tableName);
					$itemValue = $row[$field];
					$memberId = $row['id'];
				}

				if (iaUsers::hasIdentity() && $memberId == iaUsers::getIdentity()->id && $itemValue)
				{
					$pictures = null;
					if ($itemValue[1] == ':')
					{
						$array = unserialize($itemValue);
						if (is_array($array) && $array)
						{
							$pictures = $array;
						}
					}
					else
					{
						if ($array = explode(',', $itemValue))
						{
							$pictures = $array;
						}
					}

					if (is_array($pictures))
					{
						foreach ($pictures as $i => $value)
						{
							if (is_array($value))
							{
								if ($path == $value['path'])
								{
									$pictures[$i]['title'] = $title;
								}
							}
							else
							{
								if ($path == $value)
								{
									$key = $i;
								}
							}
						}

						$newValue = is_array($value) ? serialize($pictures) : implode(',', $pictures);
						$iaDb->update(array($field => $newValue), iaDb::convertIds($itemId), null, $tableName);

						if (0 == $iaDb->getErrorNumber())
						{
							$output['error'] = false;
							unset($output['message']);
						}
						else
						{
							$output['message'] = iaLanguage::get('db_error');
						}

						if (iaUsers::getItemName() == $item)
						{
							// update current profile data
							if ($itemId == iaUsers::getIdentity()->id)
							{
								iaUsers::reloadIdentity();
							}
						}
					}
				}
			}

			break;

		case 'delete-file':
			$item = isset($_POST['item']) ? iaSanitize::sql($_POST['item']) : false;
			$field = isset($_POST['field']) ? iaSanitize::sql($_POST['field']) : false;
			$path = isset($_POST['path']) ? iaSanitize::sql($_POST['path']) : false;
			$itemId = isset($_POST['itemid']) ? (int)$_POST['itemid'] : false;

			if ($itemId && $item && $field && $path)
			{
				$tableName = $iaCore->factory('item')->getItemTable($item);

				if (iaUsers::getItemName() == $item)
				{
					$itemValue = $iaDb->one($field, iaDb::convertIds($itemId), $tableName);
					$memberId = $itemId;
				}
				else
				{
					$row = $iaDb->row($field . ', `member_id` `id`', iaDb::convertIds($itemId), $tableName);
					$itemValue = $row[$field];
					$memberId = $row['id'];
				}

				if (iaUsers::hasIdentity() && $memberId == iaUsers::getIdentity()->id && $itemValue)
				{
					$pictures = null;
					if ($itemValue[1] == ':')
					{
						$array = unserialize($itemValue);
						if (is_array($array) && $array)
						{
							$pictures = $array;
						}
					}
					else
					{
						if ($array = explode(',', $itemValue))
						{
							$pictures = $array;
						}
					}

					$key = false;
					if (is_array($pictures))
					{
						foreach ($pictures as $i => $value)
						{
							if (is_array($value))
							{
								if ($path == $value['path'])
								{
									$key = $i;
									break;
								}
							}
							else
							{
								if ($path == $value)
								{
									$key = $i;
									break;
								}
							}
						}
						if (false !== $key)
						{
							unset($pictures[$key]);
						}
						$newValue = is_array($value) ? serialize($pictures) : implode(',', $pictures);
					}
					else
					{
						// single image
						$newValue = '';
						if ($pictures == $path)
						{
							$key = true;
						}
					}

					if ($key !== false)
					{
						$iaDb->update(array($field => $newValue), iaDb::convertIds($itemId), null, $tableName);

						$iaPicture = $iaCore->factory('picture');
						$iaPicture->delete($path);

						$output = array('error' => false, 'message' => iaLanguage::get('deleted'));

						if (iaUsers::getItemName() == $item)
						{
							// update current profile data
							if ($itemId == iaUsers::getIdentity()->id)
							{
								iaUsers::reloadIdentity();
							}
						}
					}
				}
			}

			break;

		case 'send_email':
			$output['message'] = array();
			$memberInfo = $iaCore->factory('users')->getInfo((int)$_POST['author_id']);

			if (empty($memberInfo) || $memberInfo['status'] != iaCore::STATUS_ACTIVE)
			{
				$output['message'][] = iaLanguage::get('member_doesnt_exist');
			}

			if (empty($_POST['from_name']))
			{
				$output['message'][] = iaLanguage::get('incorrect_fullname');
			}

			if (empty($_POST['from_email']) || !iaValidate::isEmail($_POST['from_email']))
			{
				$output['message'][] = iaLanguage::get('error_email_incorrect');
			}

			if (empty($_POST['email_body']))
			{
				$output['message'][] = iaLanguage::get('err_message');
			}

			if ($captchaName = $iaCore->get('captcha_name'))
			{
				$iaCaptcha = $iaCore->factoryPlugin($captchaName, iaCore::FRONT, 'captcha');
				if (!$iaCaptcha->validate())
				{
					$output['message'][] = iaLanguage::get('confirmation_code_incorrect');
				}
			}

			if (empty($output['message']))
			{
				$iaMailer = $iaCore->factory('mailer');

				$subject = iaLanguage::getf('author_contact_request', array('title' => $_POST['regarding']));

				$iaMailer->FromName = $_POST['from_name'];
				$iaMailer->From = $_POST['from_email'];
				$iaMailer->AddAddress($memberInfo['email']);
				$iaMailer->Subject = $subject;
				$iaMailer->Body = strip_tags($_POST['email_body']);

				$output['error'] = !$iaMailer->Send();
				$output['message'][] = iaLanguage::get($output['error'] ? 'unable_to_send_email' : 'mail_sent');
			}
	}

	$iaView->assign($output);
}

if (isset($_GET) && isset($_GET['action']))
{
	switch ($_GET['action'])
	{
		case 'ckeditor_upload':
			$iaView->disableLayout();
			$iaView->set('nodebug', 1);

			$err = 0;

			if (isset($_GET['Type']) && 'Image' == $_GET['Type'] && isset($_FILES['upload']))
			{
				$oFile = $_FILES['upload'];
				$sErrorNumber = '0';

				$imgTypes = array(
					'image/gif' => 'gif',
					'image/jpeg' => 'jpg',
					'image/pjpeg' => 'jpg',
					'image/png' => 'png'
				);
				$_user = iaUsers::hasIdentity() ? iaUsers::getIdentity()->username : false;
				$sFileUrl = 'uploads/' . iaUtil::getAccountDir($_user);

				$ext = array_key_exists($oFile['type'], $imgTypes) ? $imgTypes[$oFile['type']] : false;

				if (!$ext)
				{
					$err = '202 error';
				}

				$tok = iaUtil::generateToken();
				$fname = $tok . '.' . $ext;

				if (!$err)
				{
					move_uploaded_file($oFile['tmp_name'], IA_HOME . $sFileUrl . $fname);
					chmod(IA_HOME . $sFileUrl . $fname, 0777);
				}

				// fix windows URLs
				$fileUrl = $sFileUrl . $fname;
				$fileUrl = str_replace('\\', '/', $fileUrl);

				$callback = (int)$_GET['CKEditorFuncNum'];

				$output = '<html><body><script type="text/javascript">';
				$output .= "window.parent.CKEDITOR.tools.callFunction('$callback', '" . IA_CLEAR_URL . $fileUrl . "', '');";
				$output .= '</script></body></html>';

				die($output);
			}
	}
}