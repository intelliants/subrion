<?php
//##copyright##

$ia_version = true;
include IA_HOME . 'index.php';

$iaOutput->layout()->title = 'Upgrade Wizard';

$iaOutput->steps = array(
	'check' => 'Pre-Upgrade Check',
	'download' => 'Start Upgrade',
	'finish' => 'Finish'
);

// check if a user performing an upgrade is administrator
$iaUsers = iaHelper::loadCoreClass('users', 'core');

$proceed = false;
if (iaUsers::hasIdentity())
{
	if (iaUsers::MEMBERSHIP_ADMINISTRATOR == iaUsers::getIdentity()->usergroup_id)
	{
		$proceed = true;
	}
}
if (!$proceed)
{
	$iaOutput->errorCode = 'authorization';

	return false;
}

switch ($step)
{
	case 'check':
		$patchVersion = trim($_SERVER['REQUEST_URI'], '/');
		$patchVersion = explode('/', $patchVersion);
		$patchVersion = end($patchVersion);

		if (!preg_match('#\d{1}\.\d{1}\.\d{1}#', $patchVersion))
		{
			if (!isset($_SESSION['upgrade_to']) && empty($_SESSION['upgrade_to']))
			{
				$iaOutput->errorCode = 'version';
			}
		}
		else
		{
			$_SESSION['upgrade_to'] = $patchVersion;
		}

		if (!iaHelper::hasAccessToRemote())
		{
			$iaOutput->errorCode = 'remote';
		}

		if (isset($_SESSION['upgrade_to']))
		{
			$iaOutput->version = $_SESSION['upgrade_to'];
		}

		break;

	case 'download':
		$patchUrl = 'http://tools.subrion.com/download/patch/%s/%s/';
		$patchUrl = sprintf($patchUrl, IA_VERSION, $_SESSION['upgrade_to']);

		$patchFileContent = iaHelper::getRemoteContent($patchUrl);

		if ($patchFileContent !== false)
		{
			$file = fopen(IA_HOME . 'tmp' . IA_DS . 'patch.iap', 'wb');
			fwrite($file, $patchFileContent);
			fclose($file);

			$iaOutput->size = strlen($patchFileContent);
		}
		else {
			$iaOutput->error = true;
		}

		break;

	case 'finish':
		require_once IA_INSTALL . 'classes/ia.patch.parser.php';
		require_once IA_INSTALL . 'classes/ia.patch.applier.php';

		$iaOutput->adminPath = iaCore::instance()->iaDb->one_bind('value', '`name` = :name', array('name' => 'admin_page'), iaCore::getConfigTable());

		try
		{
			$patchFileContent = @file_get_contents(IA_HOME . 'tmp' . IA_DS . 'patch.iap');
			if (false === $patchFileContent)
			{
				throw new Exception('Could not get downloaded patch file. Please download it again.');
			}

			$patchParser = new iaPatchParser($patchFileContent);

			$patch = $patchParser->patch;

			if ($patch['info']['version_from'] != str_replace('.', '', IA_VERSION))
			{
				throw new Exception('Patch is not applicable to your version of Subrion CMS.');
			}

			$forceMode = (bool)(isset($_GET['mode']) && 'force' == $_GET['mode']);

			$patchApplier = new iaPatchApplier(IA_HOME, array(
				'host' => INTELLI_DBHOST . ':' . INTELLI_DBPORT,
				'database' => INTELLI_DBNAME,
				'user' => INTELLI_DBUSER,
				'password' => INTELLI_DBPASS,
				'prefix' => INTELLI_DBPREFIX
			), $forceMode);
			$patchApplier->process($patch, $_SESSION['upgrade_to']);

			$textLog = $patchApplier->getLog();

			$logFile = 'upgrade-log-' . $patch['info']['version_to'] . '_' . date('d-m-y-Hi') . '.txt';
			if ($fh = fopen(IA_HOME . 'uploads' . IA_DS . $logFile, 'wt'))
			{
				fwrite($fh, $textLog);
				fclose($fh);
			}

			// log this event
			$iaLog = iaHelper::loadCoreClass('log', 'core');
			$iaLog->write(iaLog::ACTION_UPGRADE, array(
				'type' => 'app',
				'from' => IA_VERSION,
				'to' => $_SESSION['upgrade_to'],
				'file' => $logFile
			));
			//

			// processing the upgrade log to show nicely
			$textLog = htmlspecialchars($textLog);
			$textLog = str_replace(
				array(PHP_EOL, 'SUCCESS', 'ERROR', 'ALERT'),
				array('',
					'<p><span class="label label-success">SUCCESS</span>',
					'<p><span class="label label-danger">ERROR</span>',
					'<p><span class="label label-warning">ALERT</span>'
				), $textLog
			);
			//

			$iaOutput->log = $textLog;

			// clean up cache files
			$tempFolder = IA_HOME . 'tmp' . IA_DS;
			iaHelper::cleanUpDirectoryContents($tempFolder);
		}
		catch (Exception $e)
		{
			@unlink(IA_HOME . 'tmp' . IA_DS . 'patch.iap');

			$iaOutput->message = $e->getMessage();
		}

		unset($_SESSION['upgrade_to']);
}