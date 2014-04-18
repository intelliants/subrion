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

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	if (iaUsers::hasIdentity())
	{
		$iaPage = $iaCore->factory('page', iaCore::FRONT);

		$iaCore->factory('util')->go_to($iaPage->getUrlByName('profile'));
	}

	if (isset($_SERVER['HTTP_REFERER']) && IA_SELF != $_SERVER['HTTP_REFERER']) // used by login redirecting mech
	{
		$_SESSION['referrer'] = $_SERVER['HTTP_REFERER'];
	}
}