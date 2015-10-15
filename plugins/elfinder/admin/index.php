<?php
//##copyright##

$iaAcl = $iaCore->factory('acl');
if (!$iaAcl->isAdmin())
{
	die('No permissions.');
}

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	error_reporting(0); // Set E_ALL for debuging

	$pluginPath = IA_PLUGINS . IA_CURRENT_PLUGIN . IA_DS . 'includes' . IA_DS . 'elfinder' . IA_DS . 'php' . IA_DS;
	include_once $pluginPath . 'elFinderConnector.class.php';
	include_once $pluginPath . 'elFinder.class.php';
	include_once $pluginPath . 'elFinderVolumeDriver.class.php';
	include_once $pluginPath . 'elFinderVolumeLocalFileSystem.class.php';

	// Required for MySQL storage connector
	// include_once $pluginPath . 'elFinderVolumeMySQL.class.php';
	// Required for FTP connector support
	// include_once $pluginPath . 'elFinderVolumeFTP.class.php';

	/**
	 * Simple function to demonstrate how to control file access using "accessControl" callback.
	 * This method will disable accessing files/folders starting from '.' (dot)
	 *
	 * @param  string  $attr  attribute name (read|write|locked|hidden)
	 * @param  string  $path  file path relative to volume root directory started with directory separator
	 * @return bool|null
	 **/
	function access($attr, $path, $data, $volume) {
		return strpos(basename($path), '.') === 0       // if file/folder begins with '.' (dot)
			? !($attr == 'read' || $attr == 'write')    // set read+write to false, other (locked+hidden) set to true
			:  null;                                    // else elFinder decide it itself
	}

	// Documentation for connector options:
	// https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options
	$opts = array(
		// 'debug' => true,
		'roots' => array(
			array(
				'driver'        => 'LocalFileSystem',   // driver for accessing file system (REQUIRED)
				'path'          => IA_UPLOADS,         // path to files (REQUIRED)
				'URL'           => IA_CLEAR_URL . 'uploads/', // URL to files (REQUIRED)
				'accessControl' => 'access'             // disable and hide dot starting files (OPTIONAL)
			)
		)
	);

	// run elFinder
	$connector = new elFinderConnector(new elFinder($opts));
	$connector->run();
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	if (isset($_GET['mode']))
	{
		$iaView->set('nodebug', 1);
		$iaView->disableLayout();

		$iaView->display('ckeditor');
	}
	else
	{
		$iaView->display('index');
	}
}