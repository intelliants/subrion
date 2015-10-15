<?php
//##copyright##

if (isset($_GET['file']))
{
	switch ($_GET['file'])
	{
		case 'default.css':
			header('Content-Type: text/css');
			echo file_get_contents(IA_INCLUDES . 'adminer' . IA_DS . 'adminer.css');
			break;
		case 'functions.js':
			header('Content-Type: text/javascript; charset=utf-8');
			echo file_get_contents(IA_INCLUDES . 'adminer' . IA_DS . 'adminer.js');
			break;
	}
}

if (iaCore::ACCESS_ADMIN == $iaCore->getAccessType())
{
	define('SID', true);

	$_GET['username'] = INTELLI_DBUSER;
	$_GET['server'] = INTELLI_DBHOST;
	$_GET['driver'] = INTELLI_CONNECT;
	$_GET['db'] = INTELLI_DBNAME;
	$_SESSION['pwds']['server'][INTELLI_DBHOST][INTELLI_DBUSER] = INTELLI_DBPASS;

	$iaView->set('nodebug', 1);
	$iaView->disableLayout();

	$iaView->display(iaView::NONE);

	include IA_INCLUDES . 'adminer' . IA_DS . 'adminer.script' . iaSystem::EXECUTABLE_FILE_EXT;
}