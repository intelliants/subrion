<?php
//##copyright##

class iaPage extends abstractCore
{
	protected static $_table = 'pages';


	public function getUrlByName($pageName, $appendScriptPath = true)
	{
		static $pagesToUrlMap;

		if (is_null($pagesToUrlMap))
		{
			$pagesToUrlMap = $this->iaDb->keyvalue(array('name', 'alias'), null, self::getTable());
		}

		return isset($pagesToUrlMap[$pageName])
			? ($appendScriptPath ? IA_URL : '') . $pagesToUrlMap[$pageName]
			: null;
	}

	public function getByName($name, $status = iaCore::STATUS_ACTIVE)
	{
		return $this->iaDb->row_bind(
			iaDb::ALL_COLUMNS_SELECTION,
			'`name` = :name AND `status` = :status AND `service` != 1',
			array('name' => $name, 'status' => $status),
			self::getTable()
		);
	}

	protected function _getInfoByName($name)
	{
		$pageParams = $this->getByName($name);

		$pageInfo['parent'] = $pageParams['parent'];
		$pageInfo['title'] = iaLanguage::get(sprintf('page_title_%s', $pageParams['name']));
		$pageInfo['url'] = $pageParams['alias'] ? $this->getUrlByName($pageParams['name']) : $pageParams['name'] . IA_URL_DELIMITER;

		return $pageInfo;
	}

	public function getParents($parentPageName, array &$chain)
	{
		if ($parentPageName)
		{
			$chain[] = $parent = $this->_getInfoByName($parentPageName);
			$this->getParents($parent['parent'], $chain);
		}
	}
}