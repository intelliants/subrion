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

class iaBackendController extends iaAbstractControllerBackend
{
	const TYPE_DIVIDER = 'divider';

	protected $_name = 'configuration';

	private $_imageTypes = array(
		'image/gif' => 'gif',
		'image/jpeg' => 'jpg',
		'image/pjpeg' => 'jpg',
		'image/png' => 'png'
	);


	protected function _indexPage(&$iaView)
	{
		$iaAcl = $this->_iaCore->factory('acl');

		$pageAction = $iaView->getParams('action');

		if (isset($_GET['group']))
		{
			$type = 'group';
			$customGroup = (int)$_GET['group'];
			$customUser = false;
			$pageAction = 'custom';

			iaBreadcrumb::preEnd(iaLanguage::get('usergroups'), IA_ADMIN_URL . 'usergroups/');
		}
		elseif (isset($_GET['user']))
		{
			$type = 'user';
			$customUser = (int)$_GET['user'];
			$customGroup = false;
			$pageAction = 'custom';

			iaBreadcrumb::preEnd(iaLanguage::get('members'), IA_ADMIN_URL . 'members/');
		}

		if (isset($_POST['save']))
		{
			if (!$iaAcl->checkAccess($iaView->name() . iaAcl::SEPARATOR . iaCore::ACTION_EDIT))
			{
				return iaView::accessDenied();
			}

			iaUtil::loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

			$messages = array();
			$error = false;

			if ($_POST['param'] && is_array($_POST['param']))
			{
				$values = $_POST['param'];

				$this->_iaDb->setTable(iaCore::getConfigTable());

				$this->_iaCore->startHook('phpConfigurationChange', array('configurationValues' => &$values));

				foreach ($values as $key => $value)
				{
					$s = strpos($key, '_items_enabled');
					if ($s !== false)
					{
						$p = $this->_iaCore->get($key, '', (bool)('custom' == $pageAction));
						$array = $p ? explode(',', $p) : array();

						$data = array();

						array_shift($value);

						if ($diff = array_diff($value, $array))
						{
							foreach ($diff as $item)
							{
								array_push($data, array('action' => '+', 'item' => $item));
							}
						}

						if ($diff = array_diff($array, $value))
						{
							foreach ($diff as $item)
							{
								array_push($data, array('action' => '-', 'item' => $item));
							}
						}

						$extra = substr($key, 0, $s);

						$this->_iaCore->startHook('phpPackageItemChangedForPlugin', array('data' => $data), $extra);
					}

					if (is_array($value))
					{
						$value = implode(',', $value);
					}

					if (!utf8_is_valid($value))
					{
						$value = utf8_bad_replace($value);
						trigger_error('Bad UTF-8 detected (replacing with "?") in configuration', E_USER_NOTICE);
					}
					if (('site_logo' == $key || 'site_watermark' == $key))
					{
						if (isset($_POST['delete'][$key]) && $_POST['delete'][$key] == 'on')
						{
							$value = '';
						}
						elseif (!empty($_FILES[$key]['name']))
						{
							if ((bool)$_FILES[$key]['error'])
							{
								$error = true;
								$messages[] = iaLanguage::get('site_logo_image_error');
							}
							else
							{
								if (@is_uploaded_file($_FILES[$key]['tmp_name']))
								{
									$ext = strtolower(utf8_substr($_FILES[$key]['name'], -3));

									// if jpeg
									if ($ext == 'peg')
									{
										$ext = 'jpg';
									}

									if (!array_key_exists(strtolower($_FILES[$key]['type']), $this->_imageTypes) || !in_array($ext, $this->_imageTypes, true) || !getimagesize($_FILES[$key]['tmp_name']))
									{
										$error = true;
										$messages[] = iaLanguage::get('wrong_site_logo_image_type');
									}
									else
									{
										if ($this->_iaCore->get($key) && file_exists(IA_UPLOADS . $this->_iaCore->get($key)))
										{
											iaUtil::deleteFile(IA_UPLOADS . $this->_iaCore->get($key));
										}

										$token = iaUtil::generateToken();
										$fileName = $key . '_' . $token . '.' . $ext;
										$fname = IA_UPLOADS . $fileName;

										$value = $fileName;

										@move_uploaded_file($_FILES[$key]['tmp_name'], $fname);

										@chmod($fname, 0777);
									}
								}
							}
						}
						else
						{
							$value = $this->_iaCore->get($key, '', ($pageAction == 'custom' ? true : false));
						}
					}

					if ('custom' == $pageAction)
					{
						$typeId = ($type == 'user') ? $customUser : $customGroup;
						$where = "`name` = '{$key}' AND `type` = '{$type}' AND `type_id` = '{$typeId}'";

						$this->_iaDb->setTable('config_custom');
						if ($_POST['chck'][$key] == 1)
						{
							$values = array('name' => $key, 'value' => $value, 'type' => $type, 'type_id' => $typeId);

							if ($this->_iaDb->exists($where))
							{
								unset($values['value']);
								$this->_iaDb->bind($where, $values);
								$this->_iaDb->update(array('value' => $value), $where);
							}
							else
							{
								$this->_iaDb->insert($values);
							}
						}
						else
						{
							$this->_iaDb->delete($where);
						}
						$this->_iaDb->resetTable();
					}
					else
					{
						$stmt = '`name` = :key';
						$this->_iaDb->bind($stmt, array('key' => $key));

						$this->_iaDb->update(array('value' => $value), $stmt);
					}
				}

				$this->_iaDb->resetTable();

				$this->_iaCore->factory('cache')->clearAll();
			}

			if (!$error)
			{
				$iaView->setMessages(iaLanguage::get('saved'), iaView::SUCCESS);
			}
			elseif ($messages)
			{
				$iaView->setMessages($messages);
			}
		}

		$iaItem = $this->_iaCore->factory('item');

		$groupName = isset($this->_iaCore->requestPath[0]) ? $this->_iaCore->requestPath[0] : 'general';
		$groupData = $this->_iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`name` = :name', array('name' => $groupName), iaCore::getConfigGroupsTable());

		if (empty($groupData))
		{
			return iaView::errorPage(iaView::ERROR_NOT_FOUND);
		}

		$this->_setGroup($iaView, $iaItem, $groupData);

		$where = "`config_group` = '{$groupName}' AND `type` != 'hidden' " . ('custom' == $pageAction ? 'AND `custom` = 1' : '') . ' ORDER BY `order`';
		$params = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $where, null, null, iaCore::getConfigTable());
		if ('custom' == $pageAction)
		{
			$custom = $this->_iaCore->getCustomConfig($customUser, $customGroup);
			$custom2 = array();
			if (false === $customGroup)
			{
				$custom2 = $this->_iaDb->getKeyValue('SELECT d.`name`, d.`value` '
					. "FROM `{$this->_iaCore->iaDb->prefix}config_custom` d, `{$this->_iaCore->iaDb->prefix}members` a "
					. "WHERE d.`type` = 'group' AND d.`type_id` = a.`usergroup_id` AND a.`id` = '{$customUser}'");
			}
		}

		$itemsList = $iaItem->getItems();

		foreach ($params as $index => $item)
		{
			$className = 'default';

			if ('custom' == $pageAction)
			{
				$className = 'custom';

				if (self::TYPE_DIVIDER != $item['type'])
				{
					if (isset($custom2[$item['name']]))
					{
						$params[$index]['dtype'] = 'usergroup';
						$params[$index]['default'] = $custom2[$item['name']];
						$params[$index]['value'] = $custom2[$item['name']];
					}
					else
					{
						$params[$index]['dtype'] = 'core';
						$params[$index]['default'] = $this->_iaCore->get($item['name']);
					}

					if (isset($custom[$item['name']]))
					{
						$className = 'common';
						$params[$index]['value'] = $custom[$item['name']];
					}
				}
			}

			if ('itemscheckbox' == $item['type'])
			{
				$array = $this->_iaCore->get($item['extras'] . '_items_implemented');
				$array = $array ? explode(',', $array) : array();
				$array = array_values(array_intersect($array, $itemsList));

				if ($array)
				{
					$enabledItems = $iaItem->getEnabledItemsForPlugin($item['extras']);

					for ($i = 0; $i < count($array); $i++)
					{
						$array[$i] = trim($array[$i]);
						$params[$index]['items'][] = array(
							'name' => $array[$i],
							'title' => iaLanguage::get($array[$i]),
							'checked' => (int)in_array($array[$i], $enabledItems)
						);
					}
				}
			}

			if ('select' == $item['type'])
			{
				switch ($item['name'])
				{
					case 'timezone':
						$params[$index]['values'] = iaUtil::getFormattedTimezones();
						break;
					case 'lang':
						$params[$index]['values'] = $this->_iaCore->languages;
						break;
					default:
						$params[$index]['values'] = explode(',', $item['multiple_values']);
				}
			}

			$params[$index]['classname'] = $className;
		}

		$customUrl = '';
		if ('custom' == $pageAction)
		{
			$customUrl = isset($_GET['user'])
				? '?user=' . $_GET['user']
				: '?group=' . $_GET['group'];
			$customUrl = iaSanitize::html($customUrl);
		}

		$iaView->assign('group', $groupData);
		$iaView->assign('params', $params);
		$iaView->assign('tooltips', iaLanguage::getTooltips());
		$iaView->assign('url_custom', $customUrl);
	}

	protected function _gridRead($params)
	{
		$output = array();

		switch ($params['action'])
		{
			case 'update':
				$output = array(
					'result' => false,
					'message' => iaLanguage::get('invalid_parameters')
				);

				if ($this->_iaCore->set($_POST['name'], $_POST['value'], true))
				{
					$output['result'] = true;
					$output['message'] = iaLanguage::get('saved');
				}

				break;

			case 'remove_site_logo':
				if (file_exists(IA_UPLOADS . $this->_iaCore->get('site_logo')))
				{
					iaUtil::deleteFile(IA_UPLOADS . $this->_iaCore->get('site_logo'));
				}
				$this->_iaCore->set('site_logo', '', true);

				break;

			case 'get_site_logo':
				echo file_exists(IA_UPLOADS . $this->_iaCore->get('site_logo'))
					? '<img src="' . IA_URL . 'uploads/' . $this->_iaCore->get('site_logo') . '" alt="">'
					: '<div style="padding: 15px; margin: 0; background: #ffe269 none repeat scroll 0 0;">' . iaLanguage::get('logo_image_not_found') . '</div>';

				exit;

			case 'upload':
				if ((bool)$_FILES['site_logo']['error'])
				{
					$output = array('error' => true, 'msg' => iaLanguage::get('site_logo_image_error'));
				}
				else
				{
					if (is_uploaded_file($_FILES['site_logo']['tmp_name']))
					{
						$ext = substr($_FILES['site_logo']['name'], -3);

						// if 'jpeg'
						if ($ext == 'peg')
						{
							$ext = 'jpg';
						}

						if (!array_key_exists($_FILES['site_logo']['type'], $this->_imageTypes) || !in_array($ext, $this->_imageTypes))
						{
							$output['error'] = true;
							$output['msg'] = iaLanguage::get('wrong_site_logo_image_type');
						}
						else
						{
							if ($this->_iaCore->get('site_logo') && file_exists(IA_UPLOADS . $this->_iaCore->get('site_logo')))
							{
								iaUtil::deleteFile(IA_UPLOADS . $this->_iaCore->get('site_logo'));
							}

							$fileName = 'site_logo_' . iaUtil::generateToken() . '.' . $ext;
							$fname = IA_UPLOADS . $fileName;

							if (@move_uploaded_file($_FILES['site_logo']['tmp_name'], $fname))
							{
								$output['error'] = false;
								$output['msg'] = iaLanguage::getf('image_uploaded', array('name' => $_FILES['site_logo']['name']));
								$output['file_name'] = $fileName;

								$this->_iaCore->set('site_logo', $fileName, true);

								@chmod($fname, 0777);
							}
						}
					}
				}
		}

		return $output;
	}

	private function _setGroup(&$iaView, &$iaItem, array $groupData)
	{
		$iaView->title($groupData['title']);

		if ($groupData['extras']) // special cases
		{
			$iaPage = $this->_iaCore->factory('page', iaCore::ADMIN);

			$activeMenu = $groupData['name'];

			if ($groupData['extras'] == $this->_iaCore->get('tmpl'))
			{
				// template configuration options
				$page = $iaPage->getByName('templates');

				$iaView->set('group', $page['group']);

				iaBreadcrumb::add($page['title'], IA_ADMIN_URL . $page['alias']);
			}
			elseif ($pluginPage = $this->_iaDb->row(array('alias', 'group'), iaDb::printf("`name` = ':name' OR `name` = ':name_stats'", array('name' => $groupData['extras'])), iaPage::getAdminTable()))
			{
				// it is a package
				$iaView->set('group', $pluginPage['group']);

				iaBreadcrumb::insert($groupData['title'], IA_ADMIN_URL . $pluginPage['alias'], iaBreadcrumb::POSITION_FIRST);
			}
			elseif ($iaItem->isExtrasExist($groupData['extras'], iaItem::TYPE_PLUGIN))
			{
				// plugin with no admin pages
				$iaView->set('group', 5);
			}
		}
		else
		{
			$activeMenu = 'configuration_' . $groupData['name'];

			iaBreadcrumb::toEnd($groupData['title'], IA_SELF);
		}

		$iaView->set('active_menu', $activeMenu);
	}
}