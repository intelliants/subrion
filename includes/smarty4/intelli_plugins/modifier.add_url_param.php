<?php
/**
* Smarty add_url_param modifier plugin
* ----------------------------------------
*
* This plugin adds parameters to existing URLs and is useful
* when you want to append or update a parameter without
* knowing anything about the contents of the existing
* URL.
*
* If any of the parameters passed to the modifier
* are already contained in the URL, their values
* are updated in the URL, rather than appending another
* parameter to the end.
*
* Examples:
*
*  {$smarty.server.REQUEST_URI|add_url_param:'param=test&param2=test2'}
*  {$smarty.server.REQUEST_URI|add_url_param:$paramArray}
*  {$smarty.server.REQUEST_URI|add_url_param:'variable':$value}
*
*
* @author Mark Mitchenall <mark@standingwave.co.uk>
* @copyright Standingwave Ltd, 2005
*
* @param $url		 string  URL to add the parameters to
* @param $parameter   mixed   Assoc. Array with param names and
*							 values or string contain the
*							 additional parameter(s)
* @param $paramValue  string  (optional) Parameter value when
*							 $parameter contains just a
*							 parameter name.
* @return string
*/
function smarty_modifier_add_url_param($url, $parameter, $paramValue = null)
{
    if ($paramValue !== null) {

        // we were passed the parameter and value as
        // separate plug-in parameters, so just apply
        // them to the URL.
        $url = _addURLParameter($url, $parameter, $paramValue);
    } elseif (is_array($parameter)) {

        // we were passed an assoc. array containing
        // parameter names and parameter values, so
        // apply them all to the URL.
        foreach ($parameter as $paramName => $paramValue) {
            $url = _addURLParameter($url, $paramName, $paramValue);
        }
    } else {

        // was passed a string containing at least one parameter
        // so parse out those passed and apply them separately
        // to the URL.

        $numParams = preg_match_all('/([^=?&]+?)=([^&]*)/', $parameter, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $url = _addURLParameter($url, $match[1], $match[2]);
        }
    }

    return $url;
}

function _addURLParameter($url, $paramName, $paramValue)
{

    // first check whether the parameter is already
    // defined in the URL so that we can just update
    // the value if that's the case.

    if (preg_match('/[?&](' . $paramName . ')=[^&]*/', $url)) {

        // parameter is already defined in the URL, so
        // replace the parameter value, rather than
        // append it to the end.
        $url = preg_replace('/([?&]' . $paramName . ')=[^&]*/', '$1=' . $paramValue, $url);
    } else {
        // can simply append to the end of the URL, once
        // we know whether this is the only parameter in
        // there or not.
        $url .= strpos($url, '?') ? '&amp;' : '?';
        $url .= $paramName . '=' . $paramValue;
    }
    return $url;
}
