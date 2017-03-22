<?php

/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2017 Intelliants, LLC <https://intelliants.com>
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
class iaCaptcha extends abstractUtil
{
    public function getImage()
    {
        $html = <<<HTML
<p class="field-captcha">
    <img src=":url" onclick="$(this).attr('src', ':url?'+Math.random())" title=":title" alt="captcha" style="cursor:pointer; margin-right: 10px;" align="left">:text<br />
    <input type="text" class="span1" name="security_code" size=":length" maxlength=":length">
</p>
<div class="clearfix"></div>
HTML;
        $html = iaDb::printf($html, [
            'length' => (int)$this->iaCore->get('kcaptcha_num_chars'),
            'url' => IA_URL . 'captcha/',
            'text' => iaLanguage::get('captcha_tooltip'),
            'title' => iaLanguage::get('click_to_redraw')
        ]);

        return $html;
    }

    public function validate()
    {
        if (iaUsers::hasIdentity()) {
            return true;
        }

        $sc1 = isset($_POST['security_code']) ? $_POST['security_code'] : (isset($_GET['security_code']) ? $_GET['security_code'] : '');
        $sc2 = $_SESSION['pass'];

        $functionName = $this->iaCore->get('kcaptcha_case_sensitive') ? 'strcmp' : 'strcasecmp';

        if (empty($_SESSION['pass']) || $functionName($sc1, $sc2) !== 0) {
            return false;
        }

        $_SESSION['pass'] = '';

        return true;
    }

    public function getPreview()
    {
        $html = '<img src=":url" onclick="$(this).attr(\'src\', \':url?\'+Math.random())" alt="captcha" style="cursor:pointer; margin-right: 10px;" align="left" />';
        $html = iaDb::printf($html, [
            'url' => IA_URL . 'captcha/'
        ]);

        return $html;
    }
}
