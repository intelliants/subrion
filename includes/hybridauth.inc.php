<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2016 Intelliants, LLC <http://www.intelliants.com>
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
 * @link http://www.subrion.org/
 *
 ******************************************************************************/

return array(
	"base_url" => IA_URL . 'hybrid/',
	"providers" => array(
	// openid providers
//		"OpenID" => array(
//			"enabled" => true
//		),
//		"Yahoo" => array(
//			"enabled" => true,
//			"keys" => array("key" => "", "secret" => ""),
//		),
//		"AOL" => array(
//			"enabled" => true
//		),
//		"Google" => array(
//			"enabled" => true,
//			"keys" => array("id" => "", "secret" => ""),
//			"scope"   => "https://www.googleapis.com/auth/userinfo.profile " .
//						"https://www.googleapis.com/auth/userinfo.email"
//		),
//		"Facebook" => array(
//			"enabled" => true,
//			'icon' => 'facebook',
//			"keys" => array("id" => "", "secret" => ""),
//			"trustForwarded" => false
//		),
//		"Twitter" => array(
//			"enabled" => true,
//			'icon' => 'facebook',
//			"keys" => array("key" => "", "secret" => ""),
//			"includeEmail" => false
//		),
//		// windows live
//		"Live" => array(
//			"enabled" => true,
//			"keys" => array("id" => "", "secret" => "")
//		),
//		"LinkedIn" => array(
//			"enabled" => true,
//			"keys" => array("key" => "", "secret" => "")
//		),
//		"Foursquare" => array(
//			"enabled" => true,
//			"keys" => array("id" => "", "secret" => "")
//		),
	),

	// If you want to enable logging, set 'debug_mode' to true.
	// You can also set it to
	// - "error" To log only error messages. Useful in production
	// - "info" To log info and error messages (ignore debug messages)
	"debug_mode" => (bool)iaCore::instance()->get('hybrid_debug_mode'),

	// Path to file writable by the web server. Required if 'debug_mode' is not false
	"debug_file" => IA_TMP . 'hybridauth.txt',
);