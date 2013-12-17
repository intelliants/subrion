<?php
//##copyright##

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
			$iaDb->update(array('active' => 0), iaDb::convertIds($job['id']));
		}
	}

	$iaDb->resetTable();
}

$iaView->set('nodebug', true);

header('Content-type: image/gif');
die(base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=='));