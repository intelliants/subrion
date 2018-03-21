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

class iaBackendController extends iaAbstractControllerBackend
{
    protected $_name = 'email-templates';

    protected $_table = 'email_templates';

    protected $_processAdd = false;
    protected $_processEdit = false;


    protected function _indexPage(&$iaView)
    {
        $iaView->display($this->getName());

        $templates = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, '1 ORDER BY `order`');

        $iaView->assign('templates', $templates);
    }

    protected function _gridRead($params)
    {
        $templateName = $params['name'];

        $template = $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($templateName, 'name'));

        $result = [
            'active' => (bool)$template['active'],
            'subject' => [],
            'body' => []
        ];

        foreach ($this->_iaCore->languages as $iso => $language) {
            $result['subject'][$iso] = $template['subject_' . $iso];
            $result['body'][$iso] = $template['body_' . $iso];
        }

        // composing the patterns description
        if ($template['variables']) {
            $array = array_filter(explode(',', $template['variables']));
            $variables = [];

            foreach ($array as $entry) {
                list($key, $value) = explode('|', $entry);
                $variables[$key] = $value;
            }

            $result['variables'] = $variables;
        }

        return $result;
    }

    protected function _gridUpdate($params)
    {
        $this->_iaDb->update($params, iaDb::convertIds($params['name'], 'name'));

        return ['result' => (0 == $this->_iaDb->getErrorNumber())];
    }
}