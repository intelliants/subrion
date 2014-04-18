<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2014 Intelliants, LLC <http://www.intelliants.com>
 *
 * This file is part of Subrion.
 *
 * Subrion is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Subrion is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Subrion. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @link http://www.subrion.org/
 *
 ******************************************************************************/

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