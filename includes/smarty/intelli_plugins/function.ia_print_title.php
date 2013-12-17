<?php
/**
 * Prints page title
 * 
 * @param array $params parameters to generate title: $params['title'] - page title
 */
function smarty_function_ia_print_title($params, &$smarty)
{
	$iaCore = iaCore::instance();
	if (isset($params['title']))
	{
		echo $params['title'] . ' ' . $iaCore->get('suffix');
		return false;
	}

	$pageParams = $smarty->iaView->getParams();
	echo $pageParams['title'] . ' ' . $iaCore->get('suffix');
}