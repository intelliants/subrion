<?php

function smarty_function_ia_hooker($params, &$smarty)
{
    if (!isset($params['name'])) {
        return;
    }

    $name = $params['name'];

    iaDebug::debug('smarty', $name, 'hooks');
    iaSystem::renderTime('smarty', $name);

    $iaCore = iaCore::instance();
    $hooks = $iaCore->getHooks();

    if (!array_key_exists($name, $hooks) || empty($hooks[$name])) {
        return;
    }

    foreach ($hooks[$name] as $hook) {
        $hook['type'] = in_array($hook['type'], array('php', 'html', 'plain', 'smarty')) ? $hook['type'] : 'php';

        if (empty($hook['pages']) || in_array($iaCore->iaView->name(), $hook['pages'])) {
            if ($hook['filename']) {
                switch ($hook['type']) {
                    case 'php':
                        if (file_exists(IA_HOME . $hook['filename'])) {
                            include IA_HOME . $hook['filename'];
                        }
                        break;
                    case 'smarty':
                        __assign($params, $smarty);
                        echo $smarty->fetch($hook['filename']);
                }
            } else {
                switch ($hook['type']) {
                    case 'php':
                        eval($hook['code']);
                        break;
                    case 'smarty':
                        __assign($params, $smarty);
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

function __assign(array $params, &$smarty)
{
    foreach ($params as $key => $value) {
        if ('name' != $key) {
            $smarty->assign($key, $value);
        }
    }
}
