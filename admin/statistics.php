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
    const GETTER_METHOD_NAME = 'getDashboardStatistics';

    protected $_name = 'statistics';

    protected $_processAdd = false;
    protected $_processEdit = false;


    protected function _indexPage(&$iaView)
    {
        $moduleName = explode('_stats', $iaView->name());
        $moduleName = array_shift($moduleName);
        $iaView->assign('package', $moduleName);

        $this->_iaCore->startHook('phpAdminPackageStatistics', ['package' => $moduleName]);

        $iaItem = $this->_iaCore->factory('item');

        $statistics = [];
        if ($packageItems = $iaItem->getItemsByModule($moduleName)) {
            foreach ($packageItems as $itemName) {
                $itemClass = $this->_iaCore->factoryModule($itemName, $moduleName, iaCore::ADMIN);
                if (method_exists($itemClass, self::GETTER_METHOD_NAME)) {
                    if ($itemClass->dashboardStatistics) {
                        if ($data = call_user_func([$itemClass, self::GETTER_METHOD_NAME], [false])) {
                            $statistics[$itemName] = $data;
                        }
                    }
                }
            }
        }
        $iaView->assign('statistics', $statistics);

        $iaView->assign('timeline', $this->_iaCore->factory('log')->get($moduleName));

        $iaView->display($this->getName());
    }
}
