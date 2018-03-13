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

$basePath = IA_INCLUDES . 'OAuth2/';

require_once $basePath . 'Storage/AccessTokenInterface.php';
require_once $basePath . 'Storage/ClientInterface.php';
require_once $basePath . 'Storage/ClientCredentialsInterface.php';
require_once $basePath . 'Storage/AuthorizationCodeInterface.php';
require_once $basePath . 'Storage/RefreshTokenInterface.php';


class iaApiStorage implements
    OAuth2\Storage\AccessTokenInterface,
    OAuth2\Storage\ClientCredentialsInterface,
    OAuth2\Storage\AuthorizationCodeInterface,
    OAuth2\Storage\RefreshTokenInterface
{
    const ACCESS_TOKEN = 'access_token';
    const AUTHORIZATION_CODE = 'authorization_code';
    const REFRESH_TOKEN = 'refresh_token';

    protected $_table = 'oauth';

    protected $_iaCore;
    protected $_iaDb;


    public function __construct()
    {
        $this->_iaCore = iaCore::instance();
        $this->_iaDb = &$this->_iaCore->iaDb;
    }

    protected function _fetch($type, $key)
    {
        $where = '`key` = :key AND `type` = :type';
        $this->_iaDb->bind($where, ['key' => $key, 'type' => $type]);

        $row = $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, $where, $this->_table);
        empty($row) || $row['expires'] = strtotime($row['expires']);

        return $row ? $row : null;
    }

    protected function _save($type, $key, $clientId, $userId, $expires, $data = null)
    {
        $entry = [
            'key' => $key,
            'type' => $type,
            'client_id' => $clientId,
            'user_id' => $userId,
            'expires' => date(iaDb::DATETIME_FORMAT, $expires),
            'data' => $data
        ];

        return (bool)$this->_iaDb->insert($entry, null, $this->_table);
    }

    protected function _delete($type, $key)
    {
        return (bool)$this->_iaDb->delete('`key` = :key AND `type` = :type', $this->_table, ['key' => $key, 'type' => $type]);
    }

    public function getAccessToken($oauth_token)
    {
        return $this->_fetch(self::ACCESS_TOKEN, $oauth_token);
    }

    public function setAccessToken($oauth_token, $client_id, $user_id, $expires, $scope = null)
    {
        return $this->_save(self::ACCESS_TOKEN, $oauth_token, $client_id, $user_id, $expires);
    }

    public function checkClientCredentials($client_id, $client_secret = null)
    {
        $iaUsers = $this->_iaCore->factory('users');

        $member = $this->_iaDb->row(['password'], iaDb::convertIds($client_id, 'username'), $iaUsers::getTable());

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
        var_dump('isPublicClient', $client_id);
        die;
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

        if ($member) {
            $redirectUrl = empty($_SESSION['oauth_referrer']) ? IA_URL : $_SESSION['oauth_referrer'];

            return [
                'redirect_uri' => $redirectUrl,
                'user_id' => $member['id']
            ];
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

    public function getAuthorizationCode($code)
    {
        return $this->_fetch(self::AUTHORIZATION_CODE, $code);
    }

    public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null)
    {
        return $this->_save(self::AUTHORIZATION_CODE, $code, $client_id, $user_id, $expires, $redirect_uri);
    }

    public function expireAuthorizationCode($code)
    {
        return $this->_delete(self::AUTHORIZATION_CODE, $code);
    }

    public function getRefreshToken($refresh_token)
    {
        return $this->_fetch(self::REFRESH_TOKEN, $refresh_token);
    }

    public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null)
    {
        return $this->_save(self::REFRESH_TOKEN, $refresh_token, $client_id, $user_id, $expires);
    }

    public function unsetRefreshToken($refresh_token)
    {
        return $this->_delete(self::REFRESH_TOKEN, $refresh_token);
    }
}
