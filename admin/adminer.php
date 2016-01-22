<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2016 Intelliants, LLC <http://www.intelliants.com>
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