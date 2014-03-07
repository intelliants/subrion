<?php
//##copyright##

if (isset($_GET['file']) && 'default.css' == $_GET['file']) // bug workaround
{
	header('Content-Type: text/css');
	echo file_get_contents(IA_INCLUDES . 'adminer' . IA_DS . 'adminer.css');
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
	$iaView->add_css('adminer');

	$iaView->display(iaView::NONE);

	include IA_INCLUDES . 'adminer' . IA_DS . 'adminer.script' . iaSystem::EXECUTABLE_FILE_EXT;
}