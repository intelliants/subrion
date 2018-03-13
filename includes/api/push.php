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

class iaApiPush
{
    const PUSH_ENDPOINT_URL = 'https://android.googleapis.com/gcm/send';


    // shortcuts
    public function sendMembers($title, $message = '', array $params = [])
    {
        return $this->send($this->_fetchTokens(), $title, $message, $params);
    }

    public function sendUsergroup($usergroupId, $title, $message = '', array $params = [])
    {
        return $this->send($this->_fetchTokens(iaDb::convertIds($usergroupId, 'usergroup_id')), $title, $message, $params);
    }

    public function sendAdministrators($title, $message = '', array $params = [])
    {
        return $this->sendUsergroup(iaUsers::MEMBERSHIP_ADMINISTRATOR, $title, $message, $params);
    }
    //

    public function send(array $receivers, $title, $message = '', array $params = [])
    {
        if (!$receivers || !iaCore::instance()->get('api_push_access_key')) {
            return false;
        }

        $data = ['title' => $title, 'message' => $message];
        $data = array_merge($data, $params);

        $postData = [
            'registration_ids' => $receivers,
            'data' => $data
        ];

        $headers = [
            'Authorization: key=' . iaCore::instance()->get('api_push_access_key'),
            'Content-Type: application/json'
        ];

        return $this->_httpRequest(self::PUSH_ENDPOINT_URL, $postData, $headers);
    }

    private function _httpRequest($url, $postData, array $headers = [])
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

        $result = curl_exec($ch);

        curl_close($ch);

        // TODO: proper error handling

        return $result;
    }

    protected function _fetchTokens($condition = null)
    {
        $where = "`api_push_token` != '' AND `api_push_receive` = 'yes'";
        empty($condition) || $where.= ' AND ' . $condition;

        return iaCore::instance()->iaDb->onefield('api_push_token', $where, null, null, iaUsers::getTable());
    }
}
