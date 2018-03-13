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
    protected $_name = 'hooks';

    protected $_processAdd = false;
    protected $_processEdit = false;

    protected $_gridColumns = "`id`, `name`, `module`, `order`, `type`, `status`, `filename`, 1 `delete`, IF(`filename` = '', 1, 0) `open`";
    protected $_gridFilters = ['name' => 'like', 'type' => 'equal'];


    protected function _gridRead($params)
    {
        $output = [];

        switch ($this->_iaCore->requestPath[0]) {
            case 'get':
                $output['code'] = $this->_iaDb->one_bind('`code`', iaDb::convertIds((int)$_GET['id']), []);
                break;

            case 'set':
                $this->_iaDb->update(['code' => $_POST['code']], iaDb::convertIds($_POST['id']));

                $output['result'] = (0 == $this->_iaDb->getErrorNumber());
                $output['message'] = iaLanguage::get($output['result'] ? 'saved' : 'db_error');
                break;

            default:
                $output = parent::_gridRead($params);
        }

        return $output;
    }

    protected function _gridModifyParams(&$conditions, &$values, array $params)
    {
        if (isset($_GET['item']) && $_GET['item']) {
            $value = ('core' == strtolower($_GET['item']) ? '' : iaSanitize::sql($_GET['item']));

            $conditions[] = '`module` = :module';
            $values['module'] = $value;
        }
    }

    protected function _indexPage(&$iaView)
    {
        parent::_indexPage($iaView);
        $iaView->display($this->getName());
    }
}
