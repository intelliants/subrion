<?php
//##copyright##

class iaOutput
{
	const TEMPLATE_FILE_EXTENSION = '.tpl';

	protected $_values = array();

	protected $_layout; // object to store layout variables

	protected $_templatesPath;


	public function __construct($templatesPath)
	{
		$this->_templatesPath = $templatesPath;
		$this->_layout = new StdClass();
	}

	public function __set($key, $value)
	{
		$this->_values[$key] = $value;
	}

	public function __get($key)
	{
		return isset($this->_values[$key]) ? $this->_values[$key] : null;
	}

	public function __isset($key)
	{
		return isset($this->_values[$key]);
	}

	public function layout()
	{
		return $this->_layout;
	}

	public function render($templateName)
	{
		if (!$this->isRenderable($templateName))
		{
			throw new Exception('Template file is not acceptable.');
		}

		$this->layout()->content = $this->_fetch($this->_composePath($templateName));

		return $this->_fetch($this->_composePath('layout'));
	}

	public function isRenderable($templateName)
	{
		return is_readable($this->_composePath($templateName));
	}

	protected function _fetch($filePath)
	{
		ob_start();
		require $filePath;
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	protected function _composePath($templateName)
	{
		return $this->_templatesPath . $templateName . self::TEMPLATE_FILE_EXTENSION;
	}
}