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

if ($iaCore->get('cron'))
{
	$iaCron = $iaCore->factory('cron');

	$iaDb->setTable(iaCron::getTable());

	if ($job = $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, '`active` = 1 AND `nextrun` <= UNIX_TIMESTAMP() ORDER BY `nextrun`'))
	{
		$data = $iaCron->parseCron($job['data']);

		if (is_file(IA_HOME . $data[iaCron::C_CMD]))
		{
			if ($iaDb->update(array('nextrun' => $data['lastScheduled']), iaDb::convertIds($job['id'])))
			{
				ignore_user_abort(1);
				@set_time_limit(0);

				include IA_HOME . $data[iaCron::C_CMD];
			}
		}
		else
		{
			// disable cron job
			$iaDb->update(array('active' => false), iaDb::convertIds($job['id']));
		}
	}

	$iaDb->resetTable();
}

$iaView->set('nodebug', true);

header('Content-type: image/gif');
die(base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=='));