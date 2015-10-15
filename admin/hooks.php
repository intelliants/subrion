<?php
//##copyright##

class iaBackendController extends iaAbstractControllerBackend
{
	protected $_name = 'hooks';

	protected $_processAdd = false;
	protected $_processEdit = false;

	protected $_gridColumns = "`id`, `name`, `extras`, `order`, `type`, `status`, `filename`, 1 `delete`, IF(`filename` = '', 1, 0) `open`";
	protected $_gridFilters = array('name' => 'like', 'type' => 'equal');


	protected function _gridRead($params)
	{
		$output = array();

		switch ($this->_iaCore->requestPath[0])
		{
			case 'get':
				$output['code'] = $this->_iaDb->one_bind('`code`', iaDb::convertIds((int)$_GET['id']));
				break;

			case 'set':
				$this->_iaDb->update(array('code' => $_POST['code']), iaDb::convertIds($_POST['id']));

				$output['result'] = (0 == $this->_iaDb->getErrorNumber());
				$output['message'] = iaLanguage::get($output['result'] ? 'saved' : 'db_error');
				break;

			default:
				$output = parent::_gridRead($params);
		}

		return $output;
	}

	protected function _modifyGridParams(&$conditions, &$values)
	{
		if (isset($_GET['item']) && $_GET['item'])
		{
			$value = ('core' == strtolower($_GET['item']) ? '' : iaSanitize::sql($_GET['item']));

			$conditions[] = '`extras` = :extras';
			$values['extras'] = $value;
		}
	}

	protected function _indexPage(&$iaView)
	{
		parent::_indexPage($iaView);
		$iaView->display($this->getName());
	}
}