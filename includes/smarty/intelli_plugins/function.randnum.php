<?php

/**
* returns a random whole number
* -------------------------------------------------------------
* @param int floor optional lower range limit (defaults to 0)
* @param int ceiling optional upper range limit (defaults to 1000)
* @return int
*/
function smarty_function_randnum($params, &$smarty)
{
    $floor = (array_key_exists('floor', $params)) ? $params['floor']:0;
    $ceiling = (array_key_exists('ceiling', $params)) ? $params['ceiling']:1000;

    $result = rand($floor, $ceiling);

    $smarty->assign('randnum', $result);

    return $result;
}
