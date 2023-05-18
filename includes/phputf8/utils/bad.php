<?php

/**
 * Tools for locating/replacing bad bytes in UTF-8 strings.
 * The Original Code is Mozilla Communicator client code.
 * The Initial Developer of the Original Code is Netscape Communications Corporation.
 * Portions created by the Initial Developer are Copyright (C) 1998 the Initial Developer.
 * All Rights Reserved.
 *
 * Ported to PHP by Henri Sivonen (http://hsivonen.iki.fi)
 * Slight modifications to fit with phputf8 library by Harry Fuecks (hfuecks gmail com)
 *
 * @see http://lxr.mozilla.org/seamonkey/source/intl/uconv/src/nsUTF8ToUnicode.cpp
 * @see http://lxr.mozilla.org/seamonkey/source/intl/uconv/src/nsUnicodeToUTF8.cpp
 * @see http://hsivonen.iki.fi/php-utf8/
 * @see utf8_is_valid
 * @package php-utf8
 * @subpackage utils
 */

/**
 * Locates the first bad byte in a UTF-8 string returning it's byte index in the string.
 * Optionally it can locate all bad bytes in a UTF-8 string and returns a list of their
 * byte index in the string.
 *
 * PCRE Pattern to locate bad bytes in a UTF-8 string comes from W3 FAQ: Multilingual Forms
 * Note: modified to include full ASCII range including control chars
 *
 * @see http://www.w3.org/International/questions/qa-forms-utf-8
 * @param string $str
 * @param boolean $first_only
 * @return mixed integer byte index or FALSE if no bad found
 */
function utf8_bad_find($str, $first_only = true)
{
    $pos = 0;
    $bad_list = array( );

    while (preg_match('/'.PHP_UTF8_BAD_UTF_PATTERN.'/S', $str, $matches))
    {
        $bytes = strlen($matches[0]);

        if (isset($matches[2]))
        {
            if ($first_only)
                return $pos;

            $bad_list[] = $pos;
        }

        $pos += $bytes;
        $str = substr($str, $bytes);
    }

    if (!empty($bad_list))
    {
        return $bad_list;
    }

    return false;
}

/**
 * Strips out any bad bytes from a UTF-8 string and returns the rest.
 * Can optionally replace bad bytes with an alternative character.
 *
 * PCRE Pattern to locate bad bytes in a UTF-8 string comes from W3 FAQ: Multilingual Forms.
 * Note: modified to include full ASCII range including control chars
 *
 * @see http://www.w3.org/International/questions/qa-forms-utf-8
 * @param string $str
 * @return string
 */
function utf8_bad_clean($str, $replace = false)
{
    ob_start();

    while (preg_match('/'.PHP_UTF8_BAD_UTF_PATTERN.'/S', $str, $matches))
    {
        if (!isset($matches[2]))
            echo $matches[0];
        elseif ($replace !== false && is_string($replace))
            echo $replace;

        $str = substr($str, strlen($matches[0]));
    }

    return ob_get_clean();
}


/**
 * Return code from utf8_bad_identify() when a five octet sequence is detected.
 *
 * Note: 5 octets sequences are valid UTF-8 but are not supported by Unicode so do not represent a useful character
 *
 * @see utf8_bad_identify
 */
define('PHP_UTF8_BAD_5OCTET', 1);

/**
 * Return code from utf8_bad_identify() when a six octet sequence is detected.
 *
 * Note: 6 octets sequences are valid UTF-8 but are not supported by Unicode so do not represent a useful character
 *
 * @see utf8_bad_identify
 */
define('PHP_UTF8_BAD_6OCTET', 2);

/**
 * Return code from utf8_bad_identify().
 *
 * Invalid octet for use as start of multi-byte UTF-8 sequence.
 *
 * @see utf8_bad_identify
 */
define('PHP_UTF8_BAD_SEQID', 3);

/**
 * Return code from utf8_bad_identify().
 *
 * From Unicode 3.1, non-shortest form is illegal.
 *
 * @see utf8_bad_identify
 */
define('PHP_UTF8_BAD_NONSHORT', 4);

/**
 * Return code from utf8_bad_identify().
 *
 * From Unicode 3.2, surrogate characters are illegal.
 *
 * @see utf8_bad_identify
 */
define('PHP_UTF8_BAD_SURROGATE', 5);

/**
 * Return code from utf8_bad_identify().
 * Codepoints outside the Unicode range are illegal.
 * @see utf8_bad_identify
 */
define('PHP_UTF8_BAD_UNIOUTRANGE', 6);

/**
 * Return code from utf8_bad_identify().
 *
 * Incomplete multi-octet sequence
 * Note: this is kind of a "catch-all"
 *
 * @see utf8_bad_identify
 */
define('PHP_UTF8_BAD_SEQINCOMPLETE', 7);

/**
 * Reports on the type of bad byte found in a UTF-8 string.
 *
 * Returns a status code on the first bad byte found
 *
 * @author <hsivonen@iki.fi>
 * @param string $str UTF-8 encoded string
 * @return mixed $i integer constant describing problem or FALSE if valid UTF-8
 * @see utf8_bad_explain
 * @see http://hsivonen.iki.fi/php-utf8/
 */
function utf8_bad_identify($str, &$i)
{
    $mState = 0; // Cached expected number of octets after the current octet
    // until the beginning of the next UTF8 character sequence
    $mUcs4 = 0; // Cached Unicode character
    $mBytes = 1; // Cached expected number of octets in the current sequence

    $len = strlen($str);

    for ($i = 0; $i < $len; $i++)
    {
        $in = ord($str[$i]);

        if ($mState == 0)
        {
            // When mState is zero we expect either a US-ASCII character or a multi-octet sequence.
            if (0 == (0x80 & ($in)))
            {
                // US-ASCII, pass straight through.
                $mBytes = 1;
            }
            else if (0xC0 == (0xE0 & ($in)))
            {
                // First octet of 2 octet sequence
                $mUcs4 = ($in);
                $mUcs4 = ($mUcs4 & 0x1F) << 6;
                $mState = 1;
                $mBytes = 2;
            }
            else if (0xE0 == (0xF0 & ($in)))
            {
                // First octet of 3 octet sequence
                $mUcs4 = ($in);
                $mUcs4 = ($mUcs4 & 0x0F) << 12;
                $mState = 2;
                $mBytes = 3;
            }
            else if (0xF0 == (0xF8 & ($in)))
            {
                // First octet of 4 octet sequence
                $mUcs4 = ($in);
                $mUcs4 = ($mUcs4 & 0x07) << 18;
                $mState = 3;
                $mBytes = 4;
            }
            else if (0xF8 == (0xFC & ($in)))
            {
                /* First octet of 5 octet sequence.
                 *
                 * This is illegal because the encoded codepoint must be either
                 * (a) not the shortest form or
                 * (b) outside the Unicode range of 0-0x10FFFF.
                 */
                return PHP_UTF8_BAD_5OCTET;
            }
            else if (0xFC == (0xFE & ($in)))
            {
                // First octet of 6 octet sequence, see comments for 5 octet sequence.
                return PHP_UTF8_BAD_6OCTET;
            }
            else
            {
                // Current octet is neither in the US-ASCII range nor a legal first
                // octet of a multi-octet sequence.
                return PHP_UTF8_BAD_SEQID;
            }
        }
        else
        {
            // When mState is non-zero, we expect a continuation of the multi-octet sequence
            if (0x80 == (0xC0 & ($in)))
            {
                // Legal continuation.
                $shift = ($mState - 1) * 6;
                $tmp = $in;
                $tmp = ($tmp & 0x0000003F) << $shift;
                $mUcs4 |= $tmp;

                /**
                 * End of the multi-octet sequence. mUcs4 now contains the final
                 * Unicode codepoint to be output
                 */
                if (0 == --$mState)
                {
                    // From Unicode 3.1, non-shortest form is illegal
                    if (((2 == $mBytes) && ($mUcs4 < 0x0080)) || ((3 == $mBytes) && ($mUcs4 < 0x0800)) || ((4 == $mBytes) && ($mUcs4 < 0x10000)) )
                        return PHP_UTF8_BAD_NONSHORT;
                    elseif (($mUcs4 & 0xFFFFF800) == 0xD800 ) // From Unicode 3.2, surrogate characters are illegal
                        return PHP_UTF8_BAD_SURROGATE;
                    elseif ($mUcs4 > 0x10FFFF ) // Codepoints outside the Unicode range are illegal
                        return PHP_UTF8_BAD_UNIOUTRANGE;

                    // Initialize UTF8 cache
                    $mState = 0;
                    $mUcs4 = 0;
                    $mBytes = 1;
                }
            }
            else
            {
                // ((0xC0 & (*in) != 0x80) && (mState != 0))
                // Incomplete multi-octet sequence.
                $i--;
                return PHP_UTF8_BAD_SEQINCOMPLETE;
            }
        }
    }

    // Incomplete multi-octet sequence
    if ($mState != 0)
    {
        $i--;
        return PHP_UTF8_BAD_SEQINCOMPLETE;
    }

    // No bad octets found
    $i = null;
    return false;
}

/**
 * Takes a return code from utf8_bad_identify() are returns a message (in English)
 * explaining what the problem is.
 *
 * @param int $code return code from utf8_bad_identify
 * @return mixed string message or FALSE if return code unknown
 * @see utf8_bad_identify
 */
function utf8_bad_explain($code)
{
    static $errors;

    if (!$errors)
    {
        $errors = array(
            PHP_UTF8_BAD_5OCTET => 'Five octet sequences are valid UTF-8 but are not supported by Unicode',
            PHP_UTF8_BAD_6OCTET => 'Six octet sequences are valid UTF-8 but are not supported by Unicode',
            PHP_UTF8_BAD_SEQID => 'Invalid octet for use as start of multi-byte UTF-8 sequence',
            PHP_UTF8_BAD_NONSHORT => 'From Unicode 3.1, non-shortest form is illegal',
            PHP_UTF8_BAD_SURROGATE => 'From Unicode 3.2, surrogate characters are illegal',
            PHP_UTF8_BAD_UNIOUTRANGE => 'Codepoints outside the Unicode range are illegal',
            PHP_UTF8_BAD_SEQINCOMPLETE => 'Incomplete multi-octet sequence'
        );
    }

    if (isset($errors[$code]))
        trigger_error('Unknown error code: '.$errors[$code], E_USER_WARNING);

    return false;
}