<?php
//##copyright##

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$iaView->disableLayout();

	$filename = IA_PLUGINS . $iaCore->get('captcha_name') . IA_DS . 'index.php';

	if (file_exists($filename))
	{
		$iaView->set('nodebug', true);

		include $filename;
	}
}

exit;