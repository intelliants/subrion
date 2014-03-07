<?php
//##copyright##

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	$output = array('error' => true, 'message' => iaLanguage::get('invalid_parameters'));

	switch ($_POST['action'])
	{
		case 'edit_picture_title':
			$title = isset($_POST['value']) ? iaSanitize::sql($_POST['value']) : '';
			$item = isset($_POST['item']) ? iaSanitize::sql($_POST['item']) : false;
			$field = isset($_POST['field']) ? iaSanitize::sql($_POST['field']) : false;
			$path = isset($_POST['path']) ? iaSanitize::sql($_POST['path']) : false;
			$itemId = isset($_POST['itemid']) ? (int)$_POST['itemid'] : false;

			if ($itemId && $item && $field && $path)
			{
				$tableName = $iaCore->factory('item')->getItemTable($item);

				if ($item == iaUsers::getItemName())
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
						$iaDb->update(array($field => $newValue), iaDb::convertIds($itemId), false, $tableName);

						if ($item == iaUsers::getItemName())
						{
							// update current profile data
							if ($itemId == iaUsers::getIdentity()->id)
							{
								$iaUsers->getAuth($itemId);
							}
						}

						$output['error'] = false;
						$output['message'] = iaLanguage::get('saved');
					}
				}
			}

			break;

		case 'delete-file':
			$item = isset($_POST['item']) ? iaSanitize::sql($_POST['item']) : null;
			$field = isset($_POST['field']) ? iaSanitize::sql($_POST['field']) : null;
			$path = isset($_POST['path']) ? iaSanitize::sql($_POST['path']) : null;
			$itemId = isset($_POST['itemid']) ? (int)$_POST['itemid'] : null;

			if ($itemId && $item && $field && $path)
			{
				$tableName = $iaCore->factory('item')->getItemTable($item);
				$itemValue = $iaDb->one($field, iaDb::convertIds($itemId), $tableName);

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
						$newItemValue = serialize($primitive ? '' : $pictures);
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
						if ($iaCore->factory('picture')->delete($path))
						{
							if ($iaDb->update(array($field => $newItemValue), iaDb::convertIds($itemId), null, $tableName))
							{
								if (iaUsers::getItemName() == $item)
								{
									// update current profile data
									if ($itemId == iaUsers::getIdentity()->id)
									{
										$iaUsers->getAuth($itemId);
									}
								}
							}

							$output['error'] = false;
							$output['message'] = iaLanguage::get('deleted');
						}
						else
						{
							$output['message'] = iaLanguage::get('error');
						}
					}
				}
			}

			break;

		case 'remove-installer':
			$iaCore->factory('util');

			$output['error'] = !iaUtil::deleteFile(IA_HOME . 'install/modules/module.install.php');
			$output['message'] = iaLanguage::get($output['error'] ? 'error' : 'deleted');

			break;

		default:
			$iaCore->startHook('phpAdminActionsJsonHandle', array('action' => $_POST['action'], 'output' => &$output));
	}

	$iaView->assign($output);
}