<?php
//##copyright##

class iaBackendController extends iaAbstractControllerBackend
{
	protected $_table = 'blocks';

	protected $_processAdd = false;
	protected $_processEdit = false;


	protected function _jsonAction()
	{
		$this->_iaCore->factory('validate');

		$output = array('result' => false, 'message' => iaLanguage::get('invalid_parameters'));

		if (isset($_POST['action']) && 'save' == $_POST['action'])
		{
			$type = $_POST['type'];
			$global = (int)$_POST['global'];
			$page = (int)$_POST['page'];
			$name = $_POST['name'];
			$pagename = $_POST['pagename'];

			if (!iaValidate::isAlphaNumericValid($name) || !iaValidate::isAlphaNumericValid($pagename))
			{
				return $output;
			}

			// convert blocks to id
			if ('blocks' == $type)
			{
				$name = $this->_iaDb->one('id', "`name` = '{$name}'");
			}

			if (in_array($type, array('positions', 'blocks')))
			{
				$this->_iaDb->setTable('objects_pages');
				if (!$global)
				{
					// get previous state
					if (!$this->_iaDb->exists("`object_type` = '{$type}' && `page_name` = '' && `object` = '{$name}' && `access` = 0"))
					{
						// delete previous settings
						$this->_iaDb->delete("`object_type` = '{$type}' && `object` = '{$name}'");

						// hide for all pages
						$this->_iaDb->insert(array(
							'object_type' => $type,
							'page_name' => '',
							'object' => $name,
							'access' => 0
						));
					}

					if ($page)
					{
						$this->_iaDb->insert(array(
							'object_type' => $type,
							'page_name' => $pagename,
							'object' => $name,
							'access' => $page
						));
					}
					else
					{
						$this->_iaDb->delete("`object_type` = '{$type}' && `page_name` = '{$pagename}' && `object` = '{$name}'");
					}
				}
				else
				{
					if ($this->_iaDb->exists("`object_type` = '{$type}' && `page_name` = '' && `object` = '{$name}' && `access` = 0"))
					{
						// delete previous settings
						$this->_iaDb->delete("`object_type` = '{$type}' && `object` = '{$name}'");
					}

					if (!$page)
					{
						$this->_iaDb->insert(array(
							'object_type' => $type,
							'page_name' => $pagename,
							'object' => $name,
							'access' => $page
						));
					}
					else
					{
						$this->_iaDb->delete("`object_type` = '{$type}' && `page_name` = '{$pagename}' && `object` = '{$name}'");
					}
				}
				$this->_iaDb->resetTable();
			}
		}

		if (isset($_GET['get']) && 'access' == $_GET['get'])
		{
			$type = $_GET['type'];
			$object = $_GET['object'];
			$page  = $_GET['page'];

			if (!iaValidate::isAlphaNumericValid($_GET['object']) || !iaValidate::isAlphaNumericValid($_GET['page']))
			{
				return $output;
			}

			// convert blocks to id
			if ('blocks' == $type)
			{
				$object = $this->_iaDb->one('id', "`name` = '{$object}'");
			}

			$sql = "SELECT IF(`page_name` = '', 'global', 'page'), `access` FROM `{$this->_iaDb->prefix}objects_pages` ";
			$sql .= "WHERE `object_type` = '{$type}' && `object` = '{$object}' && `page_name` IN ('', '{$page}')";
			if ($access = $this->_iaDb->getKeyValue($sql))
			{
				$output['result'] = array_merge(array('global' => 1, 'page' => isset($access['page']) ? $access['page'] : $access['global']), $access);
			}
			else
			{
				$output['result']['global'] = 1;
				$output['result']['page'] = 1;
			}
		}
		elseif ($_GET)
		{
			$params = $_GET;
			$positions = array_keys($this->_iaDb->assoc(array('name', 'menu', 'movable'), null, 'positions'));

			foreach ($positions as $p)
			{
				if (isset($params[$p . 'Blocks']) && is_array($params[$p . 'Blocks']) && $params[$p . 'Blocks'])
				{
					foreach ($params[$p . 'Blocks'] as $k => $v)
					{
						$blockName = str_replace('start_block_', '', 'start_' . $v);

						$this->_iaCore->startHook('phpOrderChangeBeforeUpdate', array('block' => &$blockName, 'position' => &$p));

						is_numeric($blockName)
							? $this->_iaDb->update(array('id' => $blockName, 'position' => $p, 'order' => $k + 1))
							: $this->_iaDb->update(array('position' => $p, 'order' => $k + 1), iaDb::convertIds($blockName, 'name'));
					}
				}
			}

			$output['result'] = true;
			$output['message'] = iaLanguage::get('saved');
		}

		return $output;
	}

	protected function _htmlAction(&$iaView)
	{
		$_SESSION['manageMode'] = 'mode';

		iaUtil::go_to(IA_URL);
	}
}