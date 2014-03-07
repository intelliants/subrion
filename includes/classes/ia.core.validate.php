<?php
//##copyright##

/**
 * Validator class for Subrion CMS
 */
class iaValidate extends abstractUtil
{
	const USERNAME_PATTERN = '/^[a-zA-Z0-9.@_-]+$/';
	const PATH_PATTERN = '/^[a-z\/0-9_-]*$/i';
	const URL_PATTERN = '/^[a-zA-Z]+[:\/\/]+[A-Za-z0-9\-_]+\\.+[A-Za-z0-9\.\/%&=\?\-_]+$/i';
	const INT_PATTERN = '/^[-+]?[0-9]+$/';
	const FLOAT_PATTERN = '/^[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?$/';
	const ALPHA_NUMERIC_PATTERN = '/^[A-Za-z0-9_]+$/';

	/*
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
	 *
	 * @return bool
	 */
	public static function isUrl($value)
	{
		return (1 === preg_match(self::URL_PATTERN, $value));
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
		if ($iaCore->get('captcha', false))
		{
			if ($pluginName = $iaCore->get('captcha_name'))
			{
				$iaCaptcha = $iaCore->factoryPlugin($pluginName, iaCore::FRONT, 'captcha');

				$result = (bool)$iaCaptcha->validate();
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