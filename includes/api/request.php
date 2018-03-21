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

class iaApiRequest
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_OPTIONS = 'OPTIONS';

    const FORMAT_RAW = 'raw';
    const FORMAT_JSON = 'json';

    protected $_version;
    protected $_method;
    protected $_format = self::FORMAT_RAW;
    protected $_endpoint;

    protected $_content;

    protected $_params = [];


    public function __construct(array $requestPath)
    {
        if (iaView::REQUEST_JSON == iaCore::instance()->iaView->getRequestType()) {
            $this->_format = self::FORMAT_JSON;
        }

        $this->_method = $this->_fetchMethod();
        $this->_contentType = $this->_fetchContentType();

        $this->_version = $this->_fetchVersion(array_shift($requestPath));

        $this->_endpoint = array_shift($requestPath);
        $this->_params = $requestPath;

        $this->_content = $this->_fetchContent();
    }

    private function _fetchMethod()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) && self::METHOD_POST == $method) {
            if (self::METHOD_DELETE == $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] || self::METHOD_PUT == $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) {
                $method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
            }
        }

        return $method;
    }

    private function _fetchContentType()
    {
        $contentType = $this->getServer('CONTENT_TYPE');

        if (false !== ($pos = stripos($contentType, ';'))) {
            $contentType = substr($contentType, 0, $pos);
        }

        return $contentType;
    }

    private function _fetchVersion($input)
    {
        $version = substr($input, 1);
        if (is_numeric($version) && strlen($version) < 3 && 'v' == $input[0]) {
            return $version;
        }

        return iaApi::VERSION;
    }

    private function _fetchContent()
    {
        $content = file_get_contents('php://input');

        switch ($this->getFormat()) {
            case self::FORMAT_RAW:
                switch ($this->getContentType()) {
                    case 'multipart/form-data':
                    case 'application/x-www-form-urlencoded':
                        $array = [];
                        parse_str($content, $array);
                        $content = $array;

                        break;
                }

                break;

            case self::FORMAT_JSON:
                $content = json_decode($content, true);
        }

        return $content;
    }

    // getters
    public function getMethod()
    {
        return $this->_method;
    }

    public function getVersion()
    {
        return $this->_version;
    }

    public function getEndpoint()
    {
        return $this->_endpoint;
    }

    public function getFormat()
    {
        return $this->_format;
    }

    public function getContentType()
    {
        return $this->_contentType;
    }

    public function getContent()
    {
        return $this->_content;
    }

    public function getParams()
    {
        return $this->_params;
    }

    public function getQuery($name)
    {
        return isset($_GET[$name]) ? $_GET[$name] : null;
    }

    public function getPost($name)
    {
        return isset($this->_content[$name]) ? $this->_content[$name] : null;
    }

    public function getServer($name)
    {
        return isset($_SERVER[$name]) ? $_SERVER[$name] : null;
    }
}
