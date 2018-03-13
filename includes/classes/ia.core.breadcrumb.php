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

class iaBreadcrumb extends abstractUtil
{
    const POSITION_ROOT = -1000;
    const POSITION_FIRST = -100;
    const POSITION_LAST = 100;

    const BEHAVE_REPLACE = true;
    const BEHAVE_INSERT_AFTER = false;
    const BEHAVE_APPEND = 2;

    protected static $_list = [];

    /**
     * Set root breadcrumb
     * @static
     * @param string $caption
     * @param string $url
     * @return void
     */
    public static function root($caption = '', $url = '')
    {
        self::_set($caption, $url, self::POSITION_ROOT, self::BEHAVE_REPLACE);
    }

    public static function first($caption = '', $url = '')
    {
        self::replace($caption, $url, self::POSITION_FIRST);
    }

    public static function toEnd($caption = '', $url = '')
    {
        self::insert($caption, $url, self::POSITION_LAST);
    }

    public static function preEnd($caption = '', $url = '')
    {
        self::insert($caption, $url, self::total() - 1);
    }

    public static function replaceEnd($caption = '', $url = '')
    {
        self::replace($caption, $url, self::POSITION_LAST);
    }

    public static function add($caption = '', $url = '')
    {
        self::_set($caption, $url, false);
    }

    public static function addChain(array $chain)
    {
        foreach ($chain as $item) {
            self::_set($item['title'], $item['url'], false);
        }
    }

    public static function insert($caption = '', $url = '', $index = 0)
    {
        self::_set($caption, $url, $index, self::BEHAVE_INSERT_AFTER);
    }

    public static function replace($caption = '', $url = '', $index = 0)
    {
        self::_set($caption, $url, $index, self::BEHAVE_REPLACE);
    }

    /**
     * Removes breadcrumb elements from the chain
     *
     * @param integer $index chain array index
     *
     * @return bool
     */
    public static function remove($index)
    {
        if ($index < 0) {
            $index = self::total() + $index + 1;
        }

        if (isset(self::$_list[$index])) {
            unset(self::$_list[$index]);

            return true;
        }

        return false;
    }

    /**
     * Resets breadcrumb chain array
     *
     * @return void
     */
    public static function clear()
    {
        self::$_list = [];
    }

    /**
     * Returns total number of elements in breadcrumb chain
     *
     * @return array
     */
    public static function total()
    {
        return count(self::$_list);
    }

    public static function render()
    {
        $items = self::$_list;
        ksort($items);
        $list = [];
        foreach ($items as $val) {
            if (!isset($val['caption'])) {
                foreach ($val as $v) {
                    $list[] = $v;
                }
            } else {
                $list[] = $val;
            }
        }

        return $list;
    }

    /**
     * Add element to breadcrumb
     * @static
     * @param string $caption
     * @param string $url
     * @param bool $index
     * @param int $replace - 2: in the end of list if exists, true: replace, false: insert between(after this element)
     * @return void
     */
    private static function _set($caption = '', $url = '', $index = false, $replace = self::BEHAVE_APPEND)
    {
        if ($index === false) {
            $index = count(self::$_list) + 1;
        }
        $item = ['caption' => $caption, 'url' => $url];

        if ($replace === self::BEHAVE_REPLACE) {
            self::$_list[$index] = $item;
        } elseif ($replace === self::BEHAVE_INSERT_AFTER && isset(self::$_list[$index])) {
            $next = 1;
            if (isset(self::$_list[$index]['caption'])) {
                self::$_list[$index] = [self::$_list[$index]];
            }
            while (isset(self::$_list[$index][$next])) {
                $next++;
            }
            self::$_list[$index][$next] = $item;
        } else {
            if ($replace == self::BEHAVE_APPEND) {
                while (isset(self::$_list[$index])) {
                    $index++;
                }
            }
            self::$_list[$index] = $item;
        }
    }
}
