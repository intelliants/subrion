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

class iaBackendController extends iaAbstractControllerBackend
{
    protected $_name = 'email-templates';

    protected $_processAdd = false;
    protected $_processEdit = false;


    public function __construct()
    {
        parent::__construct();

        $this->setTable(iaCore::getConfigTable());
    }

    protected function _indexPage(&$iaView)
    {
        $iaView->display($this->getName());

        $templates = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION,
            "`config_group` = 'email_templates' AND `type` IN ('radio', 'divider') ORDER BY `order`");
        $iaView->assign('templates', $templates);
    }

    protected function _gridRead($params)
    {
        $template = $params['id'];
        $options = json_decode($this->_iaDb->one('`options`', iaDb::convertIds($template, 'name')));
        $signature = empty($options->signature) ? false : true;

        $result = [
            'config' => (bool)$this->_iaCore->get($template, null, false, true),
            'signature' => $signature,
            'subject' => $this->_iaCore->get($template . '_subject', null, false, true),
            'body' => $this->_iaCore->get($template . '_body', null, false, true)
        ];

        // composing the patterns description
        if ($array = $this->_iaDb->one_bind('multiple_values', '`name` = :name', ['name' => $template . '_body'])) {
            $array = array_filter(explode(',', $array));
            $patterns = [];

            foreach ($array as $entry) {
                list($key, $value) = explode('|', $entry);
                $patterns[$key] = $value;
            }

            $result['patterns'] = $patterns;
        }

        return $result;
    }

    protected function _gridUpdate($params)
    {
        $template = $params['id'];

        $this->_iaCore->set($template . '_subject', $params['subject'], true);
        $this->_iaCore->set($template . '_body', $params['body'], true);
        $this->_iaCore->set($template, (int)$params['enable_template'], true);

        $options = json_decode($this->_iaDb->one('`options`', iaDb::convertIds($template, 'name')));
        $options->signature = $params['enable_signature'] ? true : false;
        $this->_iaDb->update(['options' => json_encode($options)], iaDb::convertIds($template, 'name'));

        return ['result' => (0 == $this->_iaDb->getErrorNumber())];
    }
}
