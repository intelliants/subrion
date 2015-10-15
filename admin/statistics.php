<?php
//##copyright##

class iaBackendController extends iaAbstractControllerBackend
{
	const GETTER_METHOD_NAME = 'getDashboardStatistics';

	protected $_name = 'statistics';

	protected $_processAdd = false;
	protected $_processEdit = false;


	protected function _indexPage(&$iaView)
	{
		$packageName = explode('_stats', $iaView->name());
		$packageName = array_shift($packageName);

		$this->_iaCore->startHook('phpAdminPackageStatistics', array('package' => $packageName));

		$statistics = array();

		$iaItem = $this->_iaCore->factory('item');
		if ($packageItems = $iaItem->getItemsByPackage($packageName))
		{
			foreach ($packageItems as $itemName)
			{
				$itemName = substr($itemName, 0, -1);
				$itemClass = $this->_iaCore->factoryPackage($itemName, $packageName, iaCore::ADMIN);
				if (method_exists($itemClass, self::GETTER_METHOD_NAME))
				{
					if ($itemClass->dashboardStatistics)
					{
						if ($data = $itemClass->{self::GETTER_METHOD_NAME}(false))
						{
							$statistics[$itemName] = $data;
						}
					}
				}
			}
		}

		$timeline = $this->_iaCore->factory('log')->get($packageName);

		$iaView->assign('package', $packageName);
		$iaView->assign('statistics', $statistics);
		$iaView->assign('timeline', $timeline);

		$iaView->display($this->getName());
	}
}