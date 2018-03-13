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

class iaXml extends abstractUtil
{
    private $_foreign = false;

    public function init()
    {
        parent::init();

        if (!function_exists('simplexml_load_string')) {
            require_once IA_INCLUDES . 'utils/simplexml.class.php';

            $this->_foreign = new simplexml();
        }
    }

    private function _parse_string($string)
    {
        return $this->_foreign ? $this->_foreign->xml_load_string($string) : simplexml_load_string($string);
    }

    public function parse_file($file)
    {
        return $this->_parse_string(file_get_contents($file));
    }
}
