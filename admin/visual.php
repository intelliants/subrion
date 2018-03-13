<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2018 Intelliants, LLC <https://intelliants.com>
 *
 * This file is part of Subrion.
 *
 * Subrion is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Subrion is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Subrion. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @link https://subrion.org/
 *
 ******************************************************************************/

class iaBackendController extends iaAbstractControllerBackend
{
    protected $_table = 'blocks';

    protected $_processAdd = false;
    protected $_processEdit = false;


    protected function _jsonAction()
    {
        $this->_iaCore->factory('validate');

        $output = ['result' => false, 'message' => iaLanguage::get('invalid_parameters')];

        if (isset($_POST['action']) && 'save' == $_POST['action']) {
            $type = $_POST['type'];
            $global = (int)$_POST['global'];
            $page = (int)$_POST['page'];
            $name = $_POST['name'];
            $pagename = $_POST['pagename'];

            if (!iaValidate::isAlphaNumericValid($name) || !iaValidate::isAlphaNumericValid($pagename)) {
                return $output;
            }

            // convert blocks to id
            if ('blocks' == $type) {
                $name = $this->_iaDb->one('id', "`name` = '{$name}'");
            }

            if (in_array($type, ['positions', 'blocks'])) {
                $this->_iaDb->setTable('objects_pages');
                if (!$global) {
                    // get previous state
                    if (!$this->_iaDb->exists("`object_type` = '{$type}' && `page_name` = '' && `object` = '{$name}' && `access` = 0")) {
                        // delete previous settings
                        $this->_iaDb->delete("`object_type` = '{$type}' && `object` = '{$name}'");

                        // hide for all pages
                        $this->_iaDb->insert([
                            'object_type' => $type,
                            'page_name' => '',
                            'object' => $name,
                            'access' => 0
                        ]);
                    }

                    if ($page) {
                        $this->_iaDb->insert([
                            'object_type' => $type,
                            'page_name' => $pagename,
                            'object' => $name,
                            'access' => $page
                        ]);
                    } else {
                        $this->_iaDb->delete("`object_type` = '{$type}' && `page_name` = '{$pagename}' && `object` = '{$name}'");
                    }
                } else {
                    if ($this->_iaDb->exists("`object_type` = '{$type}' && `page_name` = '' && `object` = '{$name}' && `access` = 0")) {
                        // delete previous settings
                        $this->_iaDb->delete("`object_type` = '{$type}' && `object` = '{$name}'");
                    }

                    if (!$page) {
                        $this->_iaDb->insert([
                            'object_type' => $type,
                            'page_name' => $pagename,
                            'object' => $name,
                            'access' => $page
                        ]);
                    } else {
                        $this->_iaDb->delete("`object_type` = '{$type}' && `page_name` = '{$pagename}' && `object` = '{$name}'");
                    }
                }
                $this->_iaDb->resetTable();
            }
        }

        if (isset($_GET['get']) && 'access' == $_GET['get']) {
            $type = $_GET['type'];
            $object = $_GET['object'];
            $page = $_GET['page'];

            if (!iaValidate::isAlphaNumericValid($_GET['object']) || !iaValidate::isAlphaNumericValid($_GET['page'])) {
                return $output;
            }

            // convert blocks to id
            if ('blocks' == $type) {
                $object = $this->_iaDb->one('id', "`name` = '{$object}'");
            }

            $sql = "SELECT IF(`page_name` = '', 'global', 'page'), `access` FROM `{$this->_iaDb->prefix}objects_pages` ";
            $sql .= "WHERE `object_type` = '{$type}' && `object` = '{$object}' && `page_name` IN ('', '{$page}')";
            if ($access = $this->_iaDb->getKeyValue($sql)) {
                $output['result'] = array_merge([
                    'global' => 1,
                    'page' => isset($access['page']) ? $access['page'] : $access['global']
                ], $access);
            } else {
                $output['result']['global'] = 1;
                $output['result']['page'] = 1;
            }
        } elseif ($_GET) {
            $params = $_GET;
            $positions = array_keys($this->_iaDb->assoc(['name', 'menu', 'movable'], null, 'positions'));

            foreach ($positions as $p) {
                if (isset($params[$p . 'Blocks']) && is_array($params[$p . 'Blocks']) && $params[$p . 'Blocks']) {
                    foreach ($params[$p . 'Blocks'] as $k => $v) {
                        $blockName = str_replace('start_block_', '', 'start_' . $v);

                        $this->_iaCore->startHook('phpOrderChangeBeforeUpdate',
                            ['block' => &$blockName, 'position' => &$p]);

                        is_numeric($blockName)
                            ? $this->_iaDb->update(['id' => $blockName, 'position' => $p, 'order' => $k + 1])
                            : $this->_iaDb->update(['position' => $p, 'order' => $k + 1],
                            iaDb::convertIds($blockName, 'name'));
                    }
                }
            }

            $output['result'] = true;
            $output['message'] = iaLanguage::get('saved');
        }

        return $output;
    }

    protected function _htmlAction(&$iaView)
    {
        $_SESSION['manageMode'] = 'mode';

        iaUtil::go_to(IA_URL);
    }
}
