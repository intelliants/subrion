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

if (!isset($_SESSION['manageMode']) && iaCore::ACCESS_FRONT == $iaCore->getAccessType())
{
	return iaView::errorPage(iaView::ERROR_FORBIDDEN);
}

$positions = explode(',', $iaCore->get('block_positions'));

$iaDb->setTable('blocks');

foreach ($positions as $p)
{
	if (isset($_GET[$p . 'Blocks']) && is_array($_GET[$p . 'Blocks']) && $_GET[$p . 'Blocks'])
	{
		foreach ($_GET[$p . 'Blocks'] as $k => $v)
		{
			$blockName = str_replace('start_block_', '', 'start_' . $v);

			$iaCore->startHook('phpOrderChangeBeforeUpdate', array('block' => &$blockName, 'position' => &$p));

			is_numeric($blockName)
				? $iaDb->update(array('id' => $blockName, 'position' => $p, 'order' => $k + 1))
				: $iaDb->update(array('position' => $p, 'order' => $k + 1), "`name` = '" . iaSanitize::sql($blockName) . "'");
		}
	}
}

$iaDb->resetTable();