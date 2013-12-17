<?php
//##copyright##

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