<?php
//##copyright##

class smartyResources
{


	public function intelli_get_secure($name, $smarty)
	{
	}

	public function intelli_get_trusted($name, $smarty)
	{
	}

	public function intelli_get_template($name, &$source, $smarty)
	{
		$filename = $smarty->ia_template($name);
		$source = file_get_contents($filename);

		return (bool)(false !== $source);
	}

	public function intelli_get_timestamp($name, &$timestamp, $smarty)
	{
		$filename = $smarty->ia_template($name);
		if (!is_file($filename))
		{
			return false;
		}
		$timestamp = filemtime($filename);

		return true;
	}
}