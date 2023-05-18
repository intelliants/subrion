<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

function smarty_function_ia_print_img($params, &$smarty)
{
    $out = IA_CLEAR_URL;
    $folder = isset($params['folder']) ? $params['folder'] : '';

    if (isset($params['ups']) && !empty($params['ups'])) {
        $out .= 'uploads/' . $folder . $params['fl'];
    } elseif (isset($params['pl']) && !empty($params['pl'])) {
        $admin = (isset($params['admin']) && $params['admin']) ? 'admin/' : 'front/';
        $out .= 'plugins/' . $params['pl'] . '/' . $admin . 'templates/img/' . $folder . $params['fl'];
    } elseif (isset($params['package']) && $params['package']) {
        $iaCore = iaCore::instance();
        $packages = $iaCore->modulesData;
        $template = $iaCore->iaView->theme;
        $admin = (isset($params['admin']) && $params['admin']);
        $design = ($admin ? 'admin/' : $template . '/');
        if (isset($packages[$params['package']])) {
            $out = $packages[$params['package']]['tpl_url'] . $design . 'images/' . $folder . $params['fl'];
        } else {
            $out = ($admin ? 'admin/' : '') . 'templates/' . $template . '/img/' . $folder . $params['fl'];
        }
    } else {
        $admin = (isset($params['admin']) && $params['admin']) ? 'admin/templates/' : '';
        if ($admin) {
            $out .= $admin . 'default/img/' . $folder . $params['fl'];
        } else {
            $out .= 'templates/' . $smarty->tmpl . '/img/' . $folder . $params['fl'];
        }
    }

    // prints including image tag
    if (isset($params['full'])) {
        $attrs = array('id', 'title', 'width', 'height', 'border', 'style', 'class', 'alt');
        $params['alt'] = isset($params['alt']) ? $params['alt'] : '';

        $atrs = '';
        foreach ($params as $key => $attr) {
            $atrs .= (in_array($key, $attrs) && isset($attr)) ? $key . '="' . $attr . '" ' : '';
        }
        $out = '<img src="' . $out . '" ' . $atrs . '/>';
    }

    echo $out;
}
