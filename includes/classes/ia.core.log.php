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

class iaLog extends abstractCore
{
    const ACTION_CREATE = 1;
    const ACTION_READ = 2;
    const ACTION_UPDATE = 3;
    const ACTION_DELETE = 4;

    const ACTION_LOGIN = 10;
    const ACTION_LOGOUT = 11;

    const ACTION_INSTALL = 20;
    const ACTION_UPGRADE = 21;
    const ACTION_UNINSTALL = 22;

    const ACTION_ENABLE = 30;
    const ACTION_DISABLE = 31;

    const LINK_PATTERN = '<a href="%s" target="_blank">%s</a>';

    protected static $_table = 'logs';

    protected $_validActions = [
        self::ACTION_CREATE, self::ACTION_READ, self::ACTION_UPDATE, self::ACTION_DELETE,
        self::ACTION_LOGIN, self::ACTION_LOGOUT,
        self::ACTION_INSTALL, self::ACTION_UPGRADE, self::ACTION_UNINSTALL,
        self::ACTION_ENABLE, self::ACTION_DISABLE
    ];


    public function write($actionCode, $params = null, $moduleName = null)
    {
        if (!in_array($actionCode, $this->_validActions)) {
            return false;
        }

        if (iaUsers::hasIdentity()) {
            $params['user'] = iaUsers::getIdentity()->fullname;
        }

        empty($params['title']) || $params['title'] = iaSanitize::html($params['title']);

        $row = [
            'date' => date(iaDb::DATETIME_FORMAT),
            'action' => $actionCode,
            'user_id' => iaUsers::hasIdentity() ? iaUsers::getIdentity()->id : null,
            'params' => serialize($params)
        ];

        if ($moduleName) {
            $row['module'] = $moduleName;
        } elseif ($module = iaCore::instance()->iaView->get('module')) {
            $row['module'] = $module;
        }

        return (bool)$this->iaDb->insert($row, null, self::getTable());
    }

    public function get($module = null)
    {
        $result = [];

        $stmt = iaDb::EMPTY_CONDITION;
        $stmt.= $module ? ' AND `module` = :module' : '';
        $this->iaDb->bind($stmt, ['module' => $module]);

        if ($rows = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $stmt . ' ORDER BY `date` DESC', 0, 20, self::getTable())) {
            foreach ($rows as $row) {
                if ($array = $this->_humanize($row)) {
                    $result[] = [
                        'description' => $array[0],
                        'icon' => $array[1],
                        'style' => $array[2],
                        'date' => self::_humanFriendlyDate($row['date'])
                    ];
                }
            }
        }

        return $result;
    }

    // TODO: use DB language strings
    private function _humanize(array $logEntry)
    {
        $params = unserialize($logEntry['params']);

        if (isset($params['user'])) {
            $params['user'] = sprintf('<a href="%s" target="_blank">%s</a>', IA_ADMIN_URL . 'members/edit/' . $logEntry['user_id'] . '/', $params['user']);
        }
        if (isset($params['name'])) {
            $params['name'] = iaSanitize::html($params['name']);
        }

        $style = 'added';

        switch ($logEntry['action']) {
            case self::ACTION_CREATE:
            case self::ACTION_UPDATE:
            case self::ACTION_DELETE:
                $actionsMap = [
                    self::ACTION_CREATE => 'create',
                    self::ACTION_UPDATE => 'update',
                    self::ACTION_DELETE => 'remove'
                ];
                $iconsMap = [
                    'block' => 'grid',
                    'page' => 'copy',
                    'member' => 'members',
                    'blog' => 'quill',
                    'listing' => 'link',
                    'menu' => 'menu'
                ];

                if (isset($params['item']) && isset($params['id']) && isset($params['name']) && self::ACTION_DELETE != $logEntry['action']) {
                    $urlPart = isset($params['path']) ? $params['path'] : $params['item'] . 's';
                    $params['name'] = sprintf(self::LINK_PATTERN, IA_ADMIN_URL . $urlPart . '/edit/' . $params['id'] . '/', $params['name']);
                }

                if (self::ACTION_DELETE == $logEntry['action']) {
                    $params['name'] = '"' . $params['name'] . '"';
                    $style = 'removed';
                }

                // special case
                if ('member' == $params['item']) {
                    switch (true) {
                        case self::ACTION_CREATE == $logEntry['action'] && isset($params['type']) && iaCore::FRONT == $params['type']:
                            return [
                                'New member signed up: ' . sprintf(self::LINK_PATTERN, IA_ADMIN_URL . 'members/edit/' . $params['id'] . '/', $params['name']) . '.',
                                $iconsMap[$params['item']],
                                'default'
                            ];
                        case self::ACTION_UPDATE == $logEntry['action'] && iaUsers::getIdentity()->id == $params['id']:
                            return [
                                sprintf('You updated ' . self::LINK_PATTERN . '.', IA_ADMIN_URL . 'members/edit/' . iaUsers::getIdentity()->id . '/', 'profile of yourself'),
                                $iconsMap[$params['item']],
                                $style
                            ];
                    }
                }

                $action = iaDb::printf(':item :name :actiond by :user.', array_merge($params,
                    ['action' => $actionsMap[$logEntry['action']], 'item' => ucfirst(iaLanguage::get($params['item'], $params['item']))]));

                $icon = 'copy';
                if (isset($iconsMap[$params['item']])) {
                    $icon = $iconsMap[$params['item']];
                } elseif (isset($params['icon'])) {
                    $icon = $params['icon'];
                }

                return [$action, $icon, $style];

            case self::ACTION_LOGIN:
                $text = ':user logged in <small class="text-muted"><em>from :ip.</em></small>';
                $text.= ($logEntry['user_id'] == iaUsers::getIdentity()->id) ? ' — you' : '';
                $text.= '.';

                return [
                    iaDb::printf($text, $params),
                    'user',
                    $style
                ];

            case self::ACTION_INSTALL:
                switch ($params['type']) {
                    case 'app':
                        return ['Subrion version ' . IA_VERSION . ' installed. Cheers!', 'subrion', 'default'];
                    case 'template':
                        $text = iaDb::printf(':user activated the ":name" template.', $params);
                        return [$text, 'eye', 'default'];
                }

                $params['name'] = ucfirst($params['name']);

                return [
                    iaDb::printf(':user installed ":name" :type.', $params),
                    'extensions',
                    $style
                ];

            case self::ACTION_UNINSTALL:
                $params['name'] = ucfirst($params['name']);

                return [
                    iaDb::printf(':user uninstalled ":name" :type.', $params),
                    'extensions',
                    'removed'
                ];

            case self::ACTION_ENABLE:
            case self::ACTION_DISABLE:
                $params['name'] = ucfirst($params['name']);

                if (self::ACTION_DISABLE == $logEntry['action']) {
                    $style = 'removed';
                }

                $actionsMap = [
                    self::ACTION_ENABLE => 'activated',
                    self::ACTION_DISABLE => 'deactivated'
                ];

                return [
                    iaDb::printf('The ":name" :type :action by :user.', array_merge($params, ['action' => $actionsMap[$logEntry['action']]])),
                    'extensions',
                    $style
                ];

            case self::ACTION_UPGRADE:
                $icon = 'extensions';

                switch ($params['type']) {
                    case 'package':
                    case 'plugin':
                        $message = '":name" :type upgraded to :to version.';
                        $params['name'] = ucfirst($params['name']);
                        break;
                    case 'app':
                    case 'app-forced':
                        $icon = 'subrion';
                        $message = ('app' == $params['type'])
                            ? 'Subrion version upgraded from :from to :to. The :log is available.'
                            : 'Automated Subrion upgrade from :from to :to. View the :log.';

                        $link = sprintf(self::LINK_PATTERN, IA_CLEAR_URL . 'uploads' . IA_URL_DELIMITER . $params['file'], 'log');
                        $params['log'] = $link;
                }

                $message = iaDb::printf($message, array_merge($params));

                return [$message, $icon, 'default'];
        }
    }

    protected static function _humanFriendlyDate($date)
    {
        $minutes = ceil((time() - strtotime($date)) / 60); // get the time difference in minutes

        switch (true) {
            case (1 == $minutes): return iaLanguage::get('just_now');
            case (60 > $minutes): return iaLanguage::getf('minutes_ago', ['minutes' => $minutes]);
            case (59 < $minutes && $minutes < 121): return iaLanguage::get('one_hour_ago');
            case ((60 * 24) > $minutes): return iaLanguage::getf('hours_ago', ['hours' => floor($minutes / 60)]);
            case ($minutes > 1439 && $minutes < 2881): return iaLanguage::get('one_day_ago');
            default: return iaLanguage::getf('days_ago', ['days' => floor($minutes / (60 * 24))]);
        }
    }

    public function cleanup()
    {
        $this->iaDb->setTable(self::getTable());

        if ($startingRowTimestamp = $this->iaDb->one('date', '1 ORDER BY `date` DESC', null, 20)) {
            $this->iaDb->delete('`date` < :date', null, ['date' => $startingRowTimestamp]);
        }

        $this->iaDb->resetTable();
    }
}
