<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {sbr_datepicker} function plugin
 *
 * Type:     function<br>
 * Name:     sbr_datepicker<br>
 * Purpose:  calls registered hooks (work only as part of Subrion installation starting from Subrion 1.0 version<br>
 * @author Ruslan Adigamov adigamov.ruslan@gmail.com
 * @param array
 * @param Smarty
 */
function smarty_function_ia_datepicker($params, &$smarty)
{
	$return = '';

	if (isset($params['date']))
	{
		$date_ar = explode('-', $params['date']);
		$return = $date_ar[2] . '/' . $date_ar[1] . '/' . $date_ar[0];
		$return = $params['date'];
	}
	return $return;
}