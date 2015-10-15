<?php
//##copyright##

class iaBackendController extends iaAbstractControllerBackend
{
	protected $_name = 'actions';


	protected function _gridRead($params)
	{
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
}