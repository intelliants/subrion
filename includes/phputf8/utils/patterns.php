<?php

/**
 * PCRE Regular expressions for UTF-8.
 *
 * Note this file is not actually used by the rest of the library but these
 * regular expressions can be useful to have available. The regular expressions
 * are modified to include full ASCII range including control chars.
 *
 * @see http://www.w3.org/International/questions/qa-forms-utf-8
 * @package php-utf8
 * @subpackage utils
 */

/**
 * PCRE Pattern to check a UTF-8 string is valid.
 */
define('PHP_UTF8_VALID_UTF_PATTERN',
	'[\x00-\x7F]'.							# ASCII (including control chars)
	'|[\xC2-\xDF][\x80-\xBF]'.				# Non-overlong 2-byte
	'|\xE0[\xA0-\xBF][\x80-\xBF]'.			# Excluding overlongs
	'|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}'.	# Straight 3-byte
	'|\xED[\x80-\x9F][\x80-\xBF]'.			# Excluding surrogates
	'|\xF0[\x90-\xBF][\x80-\xBF]{2}'.		# Planes 1-3
	'|[\xF1-\xF3][\x80-\xBF]{3}'.			# Planes 4-15
	'|\xF4[\x80-\x8F][\x80-\xBF]{2}'.		# Plane 16
	')*$'
);

/**
 * PCRE Pattern to match single UTF-8 characters.
 */
define('PHP_UTF8_SINGLE_CHAR_UTF_PATTERN',
	'([\x00-\x7F])'.						# ASCII (including control chars)
	'|([\xC2-\xDF][\x80-\xBF])'.			# Non-overlong 2-byte
	'|(\xE0[\xA0-\xBF][\x80-\xBF])'.		# Excluding overlongs
	'|([\xE1-\xEC\xEE\xEF][\x80-\xBF]{2})'.	# Straight 3-byte
	'|(\xED[\x80-\x9F][\x80-\xBF])'.		# Excluding surrogates
	'|(\xF0[\x90-\xBF][\x80-\xBF]{2})'.		# Planes 1-3
	'|([\xF1-\xF3][\x80-\xBF]{3})'.			# Planes 4-15
	'|(\xF4[\x80-\x8F][\x80-\xBF]{2})'		# Plane 16
);

/**
 * PCRE Pattern to locate bad bytes in a UTF-8 string.
 */
define('PHP_UTF8_BAD_UTF_PATTERN',
	'([\x00-\x7F]'.							# ASCII (including control chars)
	'|[\xC2-\xDF][\x80-\xBF]'.				# Non-overlong 2-byte
	'|\xE0[\xA0-\xBF][\x80-\xBF]'.			# Excluding overlongs
	'|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}'.	# Straight 3-byte
	'|\xED[\x80-\x9F][\x80-\xBF]'.			# Excluding surrogates
	'|\xF0[\x90-\xBF][\x80-\xBF]{2}'.		# Planes 1-3
	'|[\xF1-\xF3][\x80-\xBF]{3}'.			# Planes 4-15
	'|\xF4[\x80-\x8F][\x80-\xBF]{2}'.		# Plane 16
	'|(.{1}))'								# Invalid byte
);
