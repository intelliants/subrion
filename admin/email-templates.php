<?php
//##copyright##

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	switch ($pageAction)
	{
		case iaCore::ACTION_READ:
			$template = $_GET['id'];
			$result = array(
				'config' => (bool)$iaCore->get($template, null, false, true),
				'signature' => (bool)$iaCore->iaDb->one_bind('`show`', '`name` = :template', array('template' => $template), iaCore::getConfigTable()),
				'subject' => $iaCore->get($template . '_subject', null, false, true),
				'body' => $iaCore->get($template . '_body', null, false, true)
			);
			$iaView->assign($result);
			break;

		case iaCore::ACTION_EDIT:
			$template = $_POST['id'];

			$iaCore->set($template . '_subject', $_POST['subject'], true);
			$iaCore->set($template . '_body', $_POST['body'], true);

			$iaCore->set($template, (int)$_POST['enable_template'], true);

			$signature = $_POST['enable_signature'] ? '1' : '';
			$iaDb->update(array('show' => $signature), "`name` = '{$template}'", null, iaCore::getConfigTable());

			die('ok');
	}
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$templates = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`config_group` = 'email_templates' AND `type` IN ('radio', 'divider') ORDER BY `order`", null, null, iaCore::getConfigTable());
	$iaView->assign('templates', $templates);

	$iaView->display('email-templates');
}