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

return [
    "base_url" => IA_URL . 'hybrid/',
    "providers" => [
    // openid providers
//        "OpenID" => [
//            "enabled" => true
//        ],
//        "Yahoo" => [
//            "enabled" => true,
//            "keys" => ["key" => "", "secret" => ""],
//        ],
//        "AOL" => [
//            "enabled" => true
//        ],
//        "Google" => [
//            "enabled" => true,
//            "keys" => ["id" => "", "secret" => ""],
//            "scope"   => "https://www.googleapis.com/auth/userinfo.profile " .
//                        "https://www.googleapis.com/auth/userinfo.email"
//        ],
//        "Facebook" => [
//            "enabled" => true,
//            'icon' => 'facebook',
//            "keys" => ["id" => "", "secret" => ""],
//            "trustForwarded" => false
//        ],
//        "Twitter" => [
//            "enabled" => true,
//            'icon' => 'facebook',
//            "keys" => ["key" => "", "secret" => ""],
//            "includeEmail" => false
//        ],
//        // windows live
//        "Live" => [
//            "enabled" => true,
//            "keys" => ["id" => "", "secret" => ""]
//        ],
//        "LinkedIn" => [
//            "enabled" => true,
//            "keys" => ["key" => "", "secret" => ""]
//        ],
//        "Foursquare" => [
//            "enabled" => true,
//            "keys" => ["id" => "", "secret" => ""]
//        ],
    ],

    // If you want to enable logging, set 'debug_mode' to true.
    // You can also set it to
    // - "error" To log only error messages. Useful in production
    // - "info" To log info and error messages (ignore debug messages)
    "debug_mode" => (bool)iaCore::instance()->get('hybrid_debug_mode'),

    // Path to file writable by the web server. Required if 'debug_mode' is not false
    "debug_file" => IA_TMP . 'hybridauth.txt',
];
