<?php
//##copyright##

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	if (iaUsers::hasIdentity())
	{
		$iaPage = $iaCore->factory('page', iaCore::FRONT);

		$iaCore->factory('util')->go_to($iaPage->getUrlByName('profile'));
	}

	if (isset($_SERVER['HTTP_REFERER'])) // used by login redirecting mech
	{
		$_SESSION['referrer'] = $_SERVER['HTTP_REFERER'];
	}
}