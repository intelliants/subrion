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

/**
 * Validator class for Subrion CMS
 */
class iaValidate extends abstractUtil
{
    const USERNAME_PATTERN = '/^[a-zA-Z0-9.@_-]+$/';
    const PATH_PATTERN = '/^[a-z\/0-9_-]*$/i';
    const URL_PATTERN = '/^[a-zA-Z]+[:\/\/]+[A-Za-z0-9\-_]+\\.+[A-Za-z0-9\.\/%&=\?\-_]+$/i';
    const URL_SOFT_PATTERN = '/^[A-Za-z0-9\-_]+\\.+[A-Za-z0-9\.\/%&=\?\-_]+$/i';
    const INT_PATTERN = '/^[-+]?[0-9]+$/';
    const FLOAT_PATTERN = '/^[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?$/';
    const ALPHA_NUMERIC_PATTERN = '/^[A-Za-z0-9_]+$/';

    /**
     * Checks if input string is a valid username
     *
     * @param string $value text to be processed
     *
     * @return bool
     */
    public static function isUsername($value)
    {
        return (1 === preg_match(self::USERNAME_PATTERN, $value));
    }

    /**
     * Checks if input string is a valid linux path
     *
     * @param string $value text to be processed
     *
     * @return bool
     */
    public static function isPath($value)
    {
        return (1 === preg_match(self::PATH_PATTERN, $value));
    }

    /**
     * Checks if input string is a valid URL
     *
     * @param string $value text to be processed
     * @param bool $checkProtocol makes possible to perform softer check (protocol absence ignored)
     *
     * @return bool
     */
    public static function isUrl($value, $checkProtocol = true)
    {
        return (1 === preg_match($checkProtocol ? self::URL_PATTERN : self::URL_SOFT_PATTERN, $value));
    }

    /**
     * Checks if input string is a valid email address
     *
     * @param string $value text to be processed
     *
     * @return bool
     */
    public static function isEmail($value)
    {
        return (bool)filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Checks if input string is a valid integer value
     *
     * @param string $value text to be processed
     *
     * @return bool
     */
    public static function isIntValid($value)
    {
        return (1 === preg_match(self::INT_PATTERN, $value));
    }

    /**
     * Checks if input string is a valid float value
     *
     * @param string $value text to be processed
     *
     * @return bool
     */
    public static function isFloatValid($value)
    {
        return (1 === preg_match(self::FLOAT_PATTERN, $value));
    }

    /**
     * Checks if input string is a valid alpha-numeric value
     *
     * @param string $value text to be processed
     *
     * @return bool
     */
    public static function isAlphaNumericValid($value)
    {
        return (1 === preg_match(self::ALPHA_NUMERIC_PATTERN, trim($value)));
    }

    /**
     * Checks if input values stand for correct date
     *
     * @param string $month month
     * @param int|string $day day of month
     * @param int $year year
     *
     * @return bool
     */
    public static function isDateValid($month, $day, $year)
    {
        return checkdate($month, $day, $year);
    }

    /**
     * Checks if captcha is correct
     *
     * @return bool
     */
    public static function isCaptchaValid()
    {
        $iaCore = iaCore::instance();

        $result = true;
        if ($iaCore->get('captcha')) {
            if ($moduleName = $iaCore->get('captcha_name')) {
                $result = (bool)$iaCore->factoryModule('captcha', $moduleName)->validate();
            }
        }

        return $result;
    }

    /**
     * Checks if input string is a valid IP address
     *
     * @param string $ip string to be processed
     *
     * @return bool
     */
    public static function isIPAddress($ip)
    {
        return (bool)filter_var($ip, FILTER_VALIDATE_IP);
    }
}
