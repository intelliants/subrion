<?php

function smarty_function_ia_hooker($params, &$smarty)
{
	if (!isset($params['name']))
	{
		return;
	}

	iaSystem::renderTime('<b>smarty</b> - ' . $params['name']);

	$iaCore = iaCore::instance();
	$hooks = $iaCore->getHooks();

	if (!array_key_exists($params['name'], $hooks) || empty($hooks[$params['name']]))
	{
		return false;
	}

	foreach ($hooks[$params['name']] as $hook)
	{
		if (empty($hook))
		{
			continue;
		}

		$hook['type'] = (in_array($hook['type'], array('php', 'html', 'plain', 'smarty'))) ? $hook['type'] : 'php';
		if (empty($hook['pages']) || in_array($iaCore->iaView->name(), $hook['pages']))
		{
			if ($hook['filename'])
			{
				switch ($hook['type'])
				{
					case 'php':
						if (file_exists(IA_HOME . $hook['filename']))
						{
							include IA_HOME . $hook['filename'];
						}
						break;
					case 'smarty':
						echo $smarty->fetch(IA_HOME . $hook['filename']);
				}
			}
			else
			{
				switch ($hook['type'])
				{
					case 'php':
						eval($hook['code']);
						break;
					case 'smarty':
						echo $smarty->fetch('eval:' . $hook['code']);
						break;
					case 'html':
						echo $hook['code'];
						break;
					case 'plain':
						echo iaSanitize::html($hook['code']);
				}
			}
		}
	}
}