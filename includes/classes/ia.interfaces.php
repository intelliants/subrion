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

abstract class abstractCore
{
    public $iaCore;
    public $iaDb;
    public $iaView;

    protected $_message;

    protected static $_table;


    public function init()
    {
        $this->iaCore = iaCore::instance();
        $this->iaDb = &$this->iaCore->iaDb;
        $this->iaView = &$this->iaCore->iaView;
    }

    public static function getTable($prefix = false)
    {
        return $prefix ? iaCore::instance()->iaDb->prefix . static::$_table : static::$_table;
    }

    public function getMessage()
    {
        return (string)$this->_message;
    }

    public function setMessage($message)
    {
        $this->_message = $message;
    }
}

abstract class abstractUtil
{
    public $iaCore;


    public function init()
    {
        $this->iaCore = iaCore::instance();
    }
}
