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

$basePath = IA_INCLUDES . 'oauth2' . IA_DS;

require_once $basePath . 'storage/AccessTokenInterface.php';
require_once $basePath . 'storage/ClientInterface.php';
require_once $basePath . 'storage/ClientCredentialsInterface.php';
require_once $basePath . 'storage/AuthorizationCodeInterface.php';
require_once $basePath . 'storage/UserCredentialsInterface.php';
require_once $basePath . 'storage/RefreshTokenInterface.php';

class iaApiStorage implements OAuth2\Storage\AccessTokenInterface,
	OAuth2\Storage\ClientCredentialsInterface, OAuth2\Storage\AuthorizationCodeInterface,
	OAuth2\Storage\UserCredentialsInterface, OAuth2\Storage\RefreshTokenInterface
{
	protected $_iaCore;
	protected $_iaDb;


	public function __construct()
	{
		$this->_iaCore = iaCore::instance();
		$this->_iaDb = &$this->_iaCore->iaDb;
	}

	/**
	 * Look up the supplied oauth_token from storage.
	 *
	 * We need to retrieve access token data as we create and verify tokens.
	 *
	 * @param $oauth_token
	 * oauth_token to be check with.
	 *
	 * @return
	 * An associative array as below, and return NULL if the supplied oauth_token
	 * is invalid:
	 * - expires: Stored expiration in unix timestamp.
	 * - client_id: (optional) Stored client identifier.
	 * - user_id: (optional) Stored user identifier.
	 * - scope: (optional) Stored scope values in space-separated string.
	 * - id_token: (optional) Stored id_token (if "use_openid_connect" is true).
	 *
	 * @ingroup oauth2_section_7
	 */
	public function getAccessToken($oauth_token)
	{
		$row = $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($oauth_token, 'access_token'), 'oauth_access_tokens');
var_dump($oauth_token,$row);die;
		return $row ? $row : null;
	}

	/**
	 * Store the supplied access token values to storage.
	 *
	 * We need to store access token data as we create and verify tokens.
	 *
	 * @param $oauth_token    oauth_token to be stored.
	 * @param $client_id      client identifier to be stored.
	 * @param $user_id        user identifier to be stored.
	 * @param int    $expires expiration to be stored as a Unix timestamp.
	 * @param string $scope   OPTIONAL Scopes to be stored in space-separated string.
	 *
	 * @ingroup oauth2_section_4
	 */
	public function setAccessToken($oauth_token, $client_id, $user_id, $expires, $scope = null)
	{
		$entry = array(
			'access_token' => $oauth_token,
			'client_id' => $client_id,
			'user_id' => $user_id,
			'expires' => date(iaDb::DATETIME_FORMAT, $expires)
		);

		return (bool)$this->_iaDb->insert($entry, null, 'oauth_access_tokens');
	}

	/**
	 * Make sure that the client credentials is valid.
	 *
	 * @param $client_id
	 * Client identifier to be check with.
	 * @param $client_secret
	 * (optional) If a secret is required, check that they've given the right one.
	 *
	 * @return
	 * TRUE if the client credentials are valid, and MUST return FALSE if it isn't.
	 * @endcode
	 *
	 * @see http://tools.ietf.org/html/rfc6749#section-3.1
	 *
	 * @ingroup oauth2_section_3
	 */
	public function checkClientCredentials($client_id, $client_secret = null)
	{
		$iaUsers = $this->_iaCore->factory('users');

		$member = $this->_iaDb->row(array('password'), iaDb::convertIds($client_id, 'username'), $iaUsers::getTable());

		return ($member && $member['password'] == $iaUsers->encodePassword($client_secret));
	}

	/**
	 * Determine if the client is a "public" client, and therefore
	 * does not require passing credentials for certain grant types
	 *
	 * @param $client_id
	 * Client identifier to be check with.
	 *
	 * @return
	 * TRUE if the client is public, and FALSE if it isn't.
	 * @endcode
	 *
	 * @see http://tools.ietf.org/html/rfc6749#section-2.3
	 * @see https://github.com/bshaffer/oauth2-server-php/issues/257
	 *
	 * @ingroup oauth2_section_2
	 */
	public function isPublicClient($client_id)
	{
		var_dump('isPublicClient', $client_id);die;
	}

	/**
	 * Get client details corresponding client_id.
	 *
	 * OAuth says we should store request URIs for each registered client.
	 * Implement this function to grab the stored URI for a given client id.
	 *
	 * @param $client_id
	 * Client identifier to be check with.
	 *
	 * @return array
	 *               Client details. The only mandatory key in the array is "redirect_uri".
	 *               This function MUST return FALSE if the given client does not exist or is
	 *               invalid. "redirect_uri" can be space-delimited to allow for multiple valid uris.
	 *               <code>
	 *               return array(
	 *               "redirect_uri" => REDIRECT_URI,      // REQUIRED redirect_uri registered for the client
	 *               "client_id"    => CLIENT_ID,         // OPTIONAL the client id
	 *               "grant_types"  => GRANT_TYPES,       // OPTIONAL an array of restricted grant types
	 *               "user_id"      => USER_ID,           // OPTIONAL the user identifier associated with this client
	 *               "scope"        => SCOPE,             // OPTIONAL the scopes allowed for this client
	 *               );
	 *               </code>
	 *
	 * @ingroup oauth2_section_4
	 */
	public function getClientDetails($clientId)
	{
		$member = $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($clientId, 'username'), iaUsers::getTable());

		if ($member)
		{
			return array(
				'redirect_uri' => 'http://subrion-dev.my/api/v1/members.json',
				'user_id' => $member['id']
			);
		}

		return false;
	}

	/**
	 * Get the scope associated with this client
	 *
	 * @return
	 * STRING the space-delineated scope list for the specified client_id
	 */
	public function getClientScope($client_id)
	{
		return '';
	}

	/**
	 * Check restricted grant types of corresponding client identifier.
	 *
	 * If you want to restrict clients to certain grant types, override this
	 * function.
	 *
	 * @param $client_id
	 * Client identifier to be check with.
	 * @param $grant_type
	 * Grant type to be check with
	 *
	 * @return
	 * TRUE if the grant type is supported by this client identifier, and
	 * FALSE if it isn't.
	 *
	 * @ingroup oauth2_section_4
	 */
	public function checkRestrictedGrantType($client_id, $grant_type)
	{
		return true;
	}

	/**
	 * Fetch authorization code data (probably the most common grant type).
	 *
	 * Retrieve the stored data for the given authorization code.
	 *
	 * Required for OAuth2::GRANT_TYPE_AUTH_CODE.
	 *
	 * @param $code
	 * Authorization code to be check with.
	 *
	 * @return
	 * An associative array as below, and NULL if the code is invalid
	 * @code
	 * return array(
	 *     "client_id"    => CLIENT_ID,      // REQUIRED Stored client identifier
	 *     "user_id"      => USER_ID,        // REQUIRED Stored user identifier
	 *     "expires"      => EXPIRES,        // REQUIRED Stored expiration in unix timestamp
	 *     "redirect_uri" => REDIRECT_URI,   // REQUIRED Stored redirect URI
	 *     "scope"        => SCOPE,          // OPTIONAL Stored scope values in space-separated string
	 * );
	 * @endcode
	 *
	 * @see http://tools.ietf.org/html/rfc6749#section-4.1
	 *
	 * @ingroup oauth2_section_4
	 */
	public function getAuthorizationCode($code)
	{
		$row = $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($code, 'authorization_code'), 'oauth_authorization_codes');
$row['expires'] = strtotime($row['expires']);
		return $row ? $row : null;
	}

	/**
	 * Take the provided authorization code values and store them somewhere.
	 *
	 * This function should be the storage counterpart to getAuthCode().
	 *
	 * If storage fails for some reason, we're not currently checking for
	 * any sort of success/failure, so you should bail out of the script
	 * and provide a descriptive fail message.
	 *
	 * Required for OAuth2::GRANT_TYPE_AUTH_CODE.
	 *
	 * @param string $code         Authorization code to be stored.
	 * @param mixed  $client_id    Client identifier to be stored.
	 * @param mixed  $user_id      User identifier to be stored.
	 * @param string $redirect_uri Redirect URI(s) to be stored in a space-separated string.
	 * @param int    $expires      Expiration to be stored as a Unix timestamp.
	 * @param string $scope        OPTIONAL Scopes to be stored in space-separated string.
	 *
	 * @ingroup oauth2_section_4
	 */
	public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null)
	{
		$entry = array(
			'authorization_code' => $code,
			'client_id' => $client_id,
			'user_id' => $user_id,
			'redirect_uri' => $redirect_uri,
			'expires' => date(iaDb::DATETIME_FORMAT, $expires + 6400)
		);

		return (bool)$this->_iaDb->insert($entry, null, 'oauth_authorization_codes');
	}

	/**
	 * once an Authorization Code is used, it must be exipired
	 *
	 * @see http://tools.ietf.org/html/rfc6749#section-4.1.2
	 *
	 *    The client MUST NOT use the authorization code
	 *    more than once.  If an authorization code is used more than
	 *    once, the authorization server MUST deny the request and SHOULD
	 *    revoke (when possible) all tokens previously issued based on
	 *    that authorization code
	 *
	 */
	public function expireAuthorizationCode($code)
	{
		$this->_iaDb->delete(iaDb::convertIds($code, 'authorization_code'), 'oauth_authorization_codes');
	}

	/**
	 * Grant access tokens for basic user credentials.
	 *
	 * Check the supplied username and password for validity.
	 *
	 * You can also use the $client_id param to do any checks required based
	 * on a client, if you need that.
	 *
	 * Required for OAuth2::GRANT_TYPE_USER_CREDENTIALS.
	 *
	 * @param $username
	 * Username to be check with.
	 * @param $password
	 * Password to be check with.
	 *
	 * @return
	 * TRUE if the username and password are valid, and FALSE if it isn't.
	 * Moreover, if the username and password are valid, and you want to
	 *
	 * @see http://tools.ietf.org/html/rfc6749#section-4.3
	 *
	 * @ingroup oauth2_section_4
	 */
	public function checkUserCredentials($username, $password)
	{
var_dump('checkUserCredentials', $username, $password);die;
	}

	/**
	 * @return
	 * ARRAY the associated "user_id" and optional "scope" values
	 * This function MUST return FALSE if the requested user does not exist or is
	 * invalid. "scope" is a space-separated list of restricted scopes.
	 * @code
	 * return array(
	 *     "user_id"  => USER_ID,    // REQUIRED user_id to be stored with the authorization code or access token
	 *     "scope"    => SCOPE       // OPTIONAL space-separated list of restricted scopes
	 * );
	 * @endcode
	 */
	public function getUserDetails($username)
	{
var_dump('getUserDetails', $username);die;
	}

	/**
	 * Grant refresh access tokens.
	 *
	 * Retrieve the stored data for the given refresh token.
	 *
	 * Required for OAuth2::GRANT_TYPE_REFRESH_TOKEN.
	 *
	 * @param $refresh_token
	 * Refresh token to be check with.
	 *
	 * @return
	 * An associative array as below, and NULL if the refresh_token is
	 * invalid:
	 * - refresh_token: Refresh token identifier.
	 * - client_id: Client identifier.
	 * - user_id: User identifier.
	 * - expires: Expiration unix timestamp, or 0 if the token doesn't expire.
	 * - scope: (optional) Scope values in space-separated string.
	 *
	 * @see http://tools.ietf.org/html/rfc6749#section-6
	 *
	 * @ingroup oauth2_section_6
	 */
	public function getRefreshToken($refresh_token)
	{
		var_dump('getRefreshToken', $refresh_token);die;
	}

	/**
	 * Take the provided refresh token values and store them somewhere.
	 *
	 * This function should be the storage counterpart to getRefreshToken().
	 *
	 * If storage fails for some reason, we're not currently checking for
	 * any sort of success/failure, so you should bail out of the script
	 * and provide a descriptive fail message.
	 *
	 * Required for OAuth2::GRANT_TYPE_REFRESH_TOKEN.
	 *
	 * @param $refresh_token
	 * Refresh token to be stored.
	 * @param $client_id
	 * Client identifier to be stored.
	 * @param $user_id
	 * User identifier to be stored.
	 * @param $expires
	 * Expiration timestamp to be stored. 0 if the token doesn't expire.
	 * @param $scope
	 * (optional) Scopes to be stored in space-separated string.
	 *
	 * @ingroup oauth2_section_6
	 */
	public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null)
	{
		$entry = array(
			'refresh_token' => $refresh_token,
			'client_id' => $client_id,
			'user_id' => $user_id,
			'expires' => date(iaDb::DATETIME_FORMAT, $expires + 6400)
		);

		return (bool)$this->_iaDb->insert($entry, null, 'oauth_refresh_tokens');
	}

	/**
	 * Expire a used refresh token.
	 *
	 * This is not explicitly required in the spec, but is almost implied.
	 * After granting a new refresh token, the old one is no longer useful and
	 * so should be forcibly expired in the data store so it can't be used again.
	 *
	 * If storage fails for some reason, we're not currently checking for
	 * any sort of success/failure, so you should bail out of the script
	 * and provide a descriptive fail message.
	 *
	 * @param $refresh_token
	 * Refresh token to be expirse.
	 *
	 * @ingroup oauth2_section_6
	 */
	public function unsetRefreshToken($refresh_token)
	{
		var_dump('unsetRefreshToken', $refresh_token);die;
	}
}