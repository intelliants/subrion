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

	public function render($template)
	{
		$templateFile = $this->_templatesPath . $template . self::TEMPLATE_FILE_EXTENSION;
		if (!file_exists($templateFile))
		{
			throw new Exception('Template file does not exist.');
		}

		$this->layout()->content = $this->_fetch($templateFile);

		return $this->_fetch($this->_templatesPath . 'layout' . self::TEMPLATE_FILE_EXTENSION);
	}

	protected function _fetch($filePath)
	{
		ob_start();
		require $filePath;
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}
}