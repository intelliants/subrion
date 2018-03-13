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

class iaOutput
{
    const TEMPLATE_FILE_EXTENSION = '.tpl';

    protected $_values = [];

    protected $_layout; // object to store layout variables

    protected $_templatesPath;


    public function __construct($templatesPath)
    {
        $this->_templatesPath = $templatesPath;
        $this->_layout = new StdClass();
    }

    public function __set($key, $value)
    {
        $this->_values[$key] = $value;
    }

    public function __get($key)
    {
        return isset($this->_values[$key]) ? $this->_values[$key] : null;
    }

    public function __isset($key)
    {
        return isset($this->_values[$key]);
    }

    public function layout()
    {
        return $this->_layout;
    }

    public function render($templateName)
    {
        if (!$this->isRenderable($templateName)) {
            throw new Exception('Template file is not acceptable.');
        }

        $this->layout()->content = $this->_fetch($this->_composePath($templateName));

        return $this->_fetch($this->_composePath('layout'));
    }

    public function isRenderable($templateName)
    {
        return is_readable($this->_composePath($templateName));
    }

    protected function _fetch($filePath)
    {
        ob_start();
        require $filePath;
        $result = ob_get_contents();
        ob_end_clean();

        return $result;
    }

    protected function _composePath($templateName)
    {
        return $this->_templatesPath . $templateName . self::TEMPLATE_FILE_EXTENSION;
    }
}
