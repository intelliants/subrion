<?php
//##copyright##

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	return iaView::errorPage(iaView::ERROR_NOT_FOUND);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	iaBreadcrumb::add(iaLanguage::get('main_page'), IA_URL);

	$redirectUrl = IA_URL;
	if (isset($_SESSION['redir']))
	{
		$iaView->assign('redir', $_SESSION['redir']);
		$redirectUrl = $_SESSION['redir']['url'];
		unset($_SESSION['redir']);
	}

	$iaView->disableLayout();
	$iaView->assign('redirect_url', $redirectUrl);
	$iaView->title($iaCore->get('site'));
}