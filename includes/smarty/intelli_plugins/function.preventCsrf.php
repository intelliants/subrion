<?php

function smarty_function_preventCsrf($params, &$smarty)
{
    // support several post forms in the page
    $calledTimes = 0;

    if (!isset($_SESSION['prevent_csrf']) || !is_array($_SESSION['prevent_csrf'])) {
        $_SESSION['prevent_csrf'] = array();
    }
    $count = count($_SESSION['prevent_csrf']);
    if ($count > 30) {
        $_SESSION['prevent_csrf'] = isset($_SESSION['prevent_csrf'][$count - 1]) ? array($_SESSION['prevent_csrf'][$count - 1]) : array();
    }
    $_SESSION['prevent_csrf'][] = $token = iaUtil::generateToken();
    $calledTimes++;

    return '<input type="hidden" name="prevent_csrf" value="' . $token . '" />';
}
