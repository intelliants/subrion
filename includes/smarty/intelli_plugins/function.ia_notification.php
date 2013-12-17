<?php
/**
 * Prints notification box
 * 
 * @param array $params parameters to generate title: $params['type'] - notification type, $params['msg'] - message
 */
function smarty_function_ia_notification($params, &$smarty)
{
	if (!empty($params['msg']))
	{
		if (is_bool($params['type']))
		{
			$error = (true == $params['type']) ? 'error' : 'notification';
		}
		$smarty->assign('ordered', (bool)$params['ordered']);
		$smarty->assign('msg', $params['msg']);

		$smarty->display('notification.tpl');
	}
}