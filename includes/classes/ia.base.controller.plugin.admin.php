<?php
//##copyright##

abstract class iaAbstractControllerPluginBackend extends iaAbstractControllerBackend
{
	protected $_pluginName;


	public function __construct()
	{
		parent::__construct();

		$this->_pluginName = IA_CURRENT_PLUGIN;
		$this->_template = 'manage';

		$this->init();
	}

	public function init()
	{

	}

	public function getPluginName()
	{
		return $this->_pluginName;
	}

	protected function _indexPage(&$iaView)
	{
		$iaView->grid('_IA_URL_plugins/' . $this->getPluginName() . '/js/admin/' . $this->getName());
	}
}