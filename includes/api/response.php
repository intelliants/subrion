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

class iaApiResponse
{
    const OK = 200;
    const CREATED = 201;
    const ACCEPTED = 202;
    const NO_CONTENT = 204;

    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const NOT_ALLOWED = 405;
    const CONFLICT = 409;
    const UNPROCESSABLE_ENTITY = 422;
    const TOO_MANY_REQUESTS = 429;

    const INTERNAL_ERROR = 500;
    const SERVICE_UNAVAILABLE = 503;

    protected $_code = self::OK;
    protected $_body;

    protected $_headers = [];

    protected $_renderer;


    public function setCode($code)
    {
        $this->_code = (int)$code;
    }

    public function isRedirect()
    {
        return (3 == floor($this->_code / 100));
    }

    public function setBody($body)
    {
        $this->_body = $body;
    }

    public function setHeader($headerName, $value, $replace = false)
    {
        if ($replace || !isset($this->_headers[$headerName])) {
            $this->_headers[$headerName] = $value;
        }
    }

    public function setRedirect($url, $code = 301)
    {
        $this->setCode($code);
        $this->setHeader('location', $url);
    }

    public function setRenderer(iaApiRenderer $renderer)
    {
        $this->_renderer = $renderer;
    }

    protected function _sendHeaders()
    {
        if (headers_sent()) {
            return;
        }

        header('HTTP/1.1 ' . $this->_code);

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: *');

        foreach ($this->_headers as $name => $value) {
            header(ucfirst($name) . ': ' . $value);
        }
    }

    public function emit()
    {
        $this->_renderer->setResultCode($this->_code);
        $this->_renderer->setData($this->_body);

        $this->_sendHeaders();
        $this->_renderer->sendHeaders();

        echo $this->_renderer->render();
    }
}
