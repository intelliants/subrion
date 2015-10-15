<?php
//##copyright##

if ($iaCore->get('cron'))
{
	$id = (isset($_GET['_t']) && isset($_GET['t']) && is_numeric($_GET['t'])) ? (int)$_GET['t'] : null;
	$iaCore->factory('cron')->run($id);
}

$iaView->set('nodebug', true);

header('Content-type: image/gif');
die(base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=='));