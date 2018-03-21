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

function _v($val = '<br />', $title = '', $type = 0)
{
    iaDebug::dump($val, $title, $type);
}

function _vc()
{
    echo '<!-- DEBUG OUTPUT STARTED' . PHP_EOL;
    if ($count = func_num_args()) {
        $count--;
        foreach (func_get_args() as $i => $argument) {
            echo PHP_EOL . 'Arg #' . ($i + 1) . ':' . PHP_EOL;
            var_dump($argument);
            echo PHP_EOL . ($i == $count ? '' : '==========');
        }
    }
    echo '-->';
}

function _d($value, $key = null)
{
    if (func_num_args() > 1 && $key != 'debug' && !is_null($key) && !is_string($key)) { // treat it as a multiple variables display
        foreach (func_get_args() as $argument) {
            iaDebug::debug($argument);
        }
    } else {
        iaDebug::debug($value, $key);
    }
}

function _t($key = '', $default = null)
{
    if (!class_exists('iaLanguage') || empty($key)) {
        return false;
    }

    iaDebug::debug($key, 'Deprecated language phrase obtaining');

    return iaLanguage::get($key, $default);
}
