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

class iaApiAuth extends abstractCore
{
    protected static $_table = 'api_tokens';

    protected $iaUsers;


    public function __construct()
    {
        $this->init();

        if (!$this->iaCore->get('members_enabled')) {
            throw new Exception('Members disabled', iaApiResponse::SERVICE_UNAVAILABLE);
        }

        $this->iaUsers = $this->iaCore->factory('users');
    }

    protected function _coreAuth(iaApiRequest $request)
    {
        $params = $request->getContent();

        if (empty($params['login']) || empty($params['password'])) {
            throw new Exception('Empty credentials', iaApiResponse::BAD_REQUEST);
        }

        $remember = (isset($params['remember']) && 1 == $params['remember']);

        $this->iaUsers = $this->iaCore->factory('users');

        if (!$this->iaUsers->getAuth(null, $params['login'], $params['password'], $remember)) {
            throw new Exception('Invalid credentials', iaApiResponse::FORBIDDEN);
        }

        $accessToken = $this->getAccessTokenData($request);

        if ($accessToken['member_id'] != iaUsers::getIdentity()->id) {
            $this->iaDb->update(['member_id' => iaUsers::getIdentity()->id],
                iaDb::convertIds($accessToken['key'], 'key'), null, self::getTable());
        }
    }

    protected function _hybridAuth($providerName)
    {
        $this->iaUsers->hybridAuth($providerName);
    }

    public function authorize(iaApiRequest $request, iaApiResponse $response)
    {
        if (!$this->_checkRateLimiting()) {
            throw new Exception(null, iaApiResponse::TOO_MANY_REQUESTS);
        }

        $params = $request->getParams();

        if (empty($params)) {
            $this->_coreAuth($request);
        } elseif ('logout' == $params[0]) {
            $this->_logout($request);
        } elseif (1 == count($params)) {
            $this->_hybridAuth($params[0]);
        } else {
            throw new Exception(null, iaApiResponse::NOT_FOUND);
        }
    }

    public function passwordReset(iaApiRequest $request, iaApiResponse $response)
    {
        if ($request->getMethod() != iaApiRequest::METHOD_POST) {
            throw new Exception('Method not allowed', iaApiResponse::NOT_ALLOWED);
        }

        if (!is_array($request->getContent())) {
            throw new Exception('Invalid data', iaApiResponse::BAD_REQUEST);
        }

        if ($request->getPost('email') !== null || $request->getPost('username') !== null) {
            $identity = $request->getPost('email') !== null ? 'email' : 'username';
            $credential = $request->getPost('email') ?: $request->getPost('username');

            if ('email' == $identity && !iaValidate::isEmail($credential)) {
                throw new Exception(iaLanguage::get('error_email_incorrect'), iaApiResponse::BAD_REQUEST);
            }

            $member = $this->iaUsers->getInfo($credential, $identity);

            if (!$member) {
                throw new Exception(iaLanguage::get('error_no_member_email'), iaApiResponse::NOT_FOUND);
            }

            if (!$this->iaUsers->sendPasswordResetEmail($member)) {
                throw new Exception(iaLanguage::get('internal_error'), iaApiResponse::INTERNAL_ERROR);
            }

            $response->setCode(iaApiResponse::ACCEPTED);
        } elseif ($request->getPost('token') !== null) {
            if (!$request->getPost('token')) {
                throw new Exception('Empty token', iaApiResponse::BAD_REQUEST);
            }

            if ($request->getPost('password') === null) {
                throw new Exception('New password required', iaApiResponse::BAD_REQUEST);
            } elseif (!$request->getPost('password')) {
                throw new Exception('New password is empty', iaApiResponse::BAD_REQUEST);
            }

            $member = $this->iaUsers->getInfo($request->getPost('token'), 'sec_key');

            if (!$member) {
                throw new Exception('Invalid token', iaApiResponse::BAD_REQUEST);
            }

            if (!$this->iaUsers->changePassword($member, $request->getPost('password'))) {
                throw new Exception(iaLanguage::get('internal_error'), iaApiResponse::INTERNAL_ERROR);
            }

            $response->setCode(iaApiResponse::OK);
        } else {
            throw new Exception('Missing required parameters', iaApiResponse::BAD_REQUEST);
        }
    }

    protected function _logout(iaApiRequest $request)
    {
        iaUsers::clearIdentity();

        // we also have to revoke the token
        $token = $request->getServer('HTTP_X_AUTH_TOKEN');

        $this->iaDb->delete(iaDb::convertIds($token, 'key'), self::getTable());
    }

    public function handleTokenRequest(iaApiRequest $request, iaApiResponse $response)
    {
        session_regenerate_id(true);

        $entry = [
            'key' =>  $this->_generateToken($request),
            'ip' => iaUtil::getIp(),
            'session' => session_id()
        ];

        $this->iaDb->insert($entry, null, self::getTable());

        if ($this->iaDb->getErrorNumber() > 0) {
            throw new Exception('Unable to issue a token', iaApiResponse::INTERNAL_ERROR);
        }

        $response->setBody($entry['key']);
    }

    public function verifyResourceRequest(iaApiRequest $request)
    {
        return (bool)$request->getServer('HTTP_X_AUTH_TOKEN');
    }

    public function getAccessTokenData(iaApiRequest $request)
    {
        $permanentKey = $this->iaCore->get('api_token');
        if ($permanentKey && $permanentKey == $request->getServer('HTTP_X_AUTH_TOKEN')) {
            return [
                'key' => $permanentKey,
                'member_id' => 0,
                'expires' => null,
                'ip' => null,
                'session' => null
            ];
        }

        return $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION,
            iaDb::convertIds($request->getServer('HTTP_X_AUTH_TOKEN'), 'key'),
            self::getTable());
    }

    public function setSession(array $token)
    {
        session_write_close();
        session_id($token['session']);
        session_start();
    }

    private function _checkRateLimiting()
    {
        // TODO: implement

        return true;
    }

    private function _generateToken(iaApiRequest $request)
    {
        $this->iaCore->factory('util');

        $ipAddress = iaUtil::getIp();
        $userAgent = $request->getServer('HTTP_USER_AGENT');

        $token = md5(microtime() . $ipAddress . $userAgent);

        return $token;
    }
}
