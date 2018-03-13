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

class Smarty_Resource_Extra extends Smarty_Resource_Custom
{


    protected function fetch($name, &$source, &$modifiedTime)
    {
        $path = $this->_translateName($name);

        $source = @file_get_contents($path);
        $modifiedTime = @filemtime($path);
    }

    protected function fetchTimestamp($name)
    {
        return filemtime($this->_translateName($name));
    }


    private function _translateName($name)
    {
        iaDebug::debug($name, "Usage of obsolete smarty resource 'Extra'");

        $array = explode('/', $name);

        $moduleName = array_shift($array);
        $templateName = implode('.', $array) . iaView::TEMPLATE_FILENAME_EXT;

        if (iaCore::ACCESS_ADMIN == iaCore::instance()->getAccessType()) {
            $filePath = sprintf('modules/%s/templates/admin/%s', $moduleName, $templateName);
            return IA_HOME . $filePath;
        }

        $filePath = sprintf('templates/%s/modules/%s/%s', iaCore::instance()->get('tmpl'), $moduleName, $templateName);
        is_file($filePath) || $filePath = sprintf('modules/%s/templates/front/%s', $moduleName, $templateName);

        return IA_HOME . $filePath;
    }
}