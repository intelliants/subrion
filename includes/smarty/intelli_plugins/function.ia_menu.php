<?php

function smarty_function_ia_menu($params, Smarty_Internal_Template &$smarty)
{
    if (!isset($params['menus']) || empty($params['menus'])) {
        return '';
    }

    $iaCore = iaCore::instance();

    if (isset($params['loginout']) && $params['loginout'] && $iaCore->get('members_enabled')) {
        $iaCore->factory('users');
        $menuDefaults = array('parent_id' => 0, 'el_id' => '0_000', 'menu' => 1, 'level' => 0, 'nofollow' => true);
        $currentPage = $iaCore->iaView->name();
        if (iaUsers::hasIdentity()) {
            $params['menus'][0][] = array_merge($menuDefaults, array(
                'id' => -1,
                'page_name' => 'logout',
                'new_window' => 0,
                'text' => iaLanguage::get('logout'),
                'url' => 'logout/',
                'active' => ('logout' == $currentPage)
            ));
        } else {
            $params['menus'][0][] = array_merge($menuDefaults, array(
                'id' => -1,
                'page_name' => 'login',
                'new_window' => 0,
                'text' => iaLanguage::get('page_title_login'),
                'url' => 'login/',
                'active' => ('login' == $currentPage)
            ));
            $params['menus'][0][] = array_merge($menuDefaults, array(
                'id' => 0,
                'page_name' => 'registration',
                'new_window' => 0,
                'text' => iaLanguage::get('page_title_registration'),
                'url' => 'registration/',
                'active' => ('registration' == $currentPage)
            ));
        }
    }

    $level = isset($params['level']) ? (int)$params['level'] : false;
    $tpl = isset($params['tpl']) ? $params['tpl'] : 'ul';
    $classname = isset($params['class']) ? $params['class'] : 'level';
    $textAfter = isset($params['after']) ? $params['after'] : '';
    $textBefore = isset($params['before']) ? $params['before'] : '';

    // TODO: add menus only of particular level
    if ($level !== false) {
        $alreadyShown = false;
        $list = array();
        $menus = $params['menus'];
        foreach ($menus as $pid => $children) {
            $check = false;
            foreach ($children as $child) {
                if ($child['level'] == $level) {
                    $check = true;
                    break;
                }
            }
            if ($check) {
                $hide = ($iaCore->iaView->get('id') == $pid) ? true : false;
                $list[$pid] = array('children' => $children, 'hide' => $hide);
                if ($hide === false) {
                    $alreadyShown = true;
                }
            }
        }
        unset($menus);

        echo $textBefore;
        foreach ($list as $pid => $item) {
            if ($alreadyShown === false) {
                $item['hide'] = false;
                $alreadyShown = true;
            }
            $smarty->assign('text_before', '');
            $smarty->assign('text_after', '');
            $smarty->assign('menu_children', true);
            $smarty->assign('menus', array($pid => $item['children']));
            $smarty->assign('menu_class', $classname);

            $smarty->display('menu-' . $tpl . '.tpl', $tpl . mt_rand(1000, 9999));
        }
        echo $textAfter;
    } else {
        $smarty->assign('text_before', $textBefore);
        $smarty->assign('text_after', $textAfter);
        $smarty->assign('menus', $params['menus']);
        $smarty->assign('menu_class', $classname);

        $smarty->display('menu-' . $tpl . '.tpl', $tpl . mt_rand(1000, 9999));
    }
}
