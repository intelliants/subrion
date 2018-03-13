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
    protected $_name = 'subscriptions';

    protected $_processAdd = false;
    protected $_processEdit = false;


    public function __construct()
    {
        parent::__construct();

        $iaSubscription = $this->_iaCore->factory('subscription');
        $this->setHelper($iaSubscription);

        $this->setTable(iaSubscription::getTable());
    }

    protected function _gridQuery($columns, $where, $order, $start, $limit)
    {
        $sql = 'SELECT s.`id`, s.`reference_id`, s.`status`, s.`plan_id`, '
            . 's.`date_created`, s.`date_next_payment`, m.`fullname` `user` '
            . 'FROM `:prefix:table_subscriptions` s '
            . 'LEFT JOIN `:prefix:table_members` m ON (s.`member_id` = m.`id`) '
            . ($where ? 'WHERE ' . $where . ' ' : '') . $order . ' '
            . 'LIMIT :start, :limit';
        $sql = iaDb::printf($sql, [
            'prefix' => $this->_iaDb->prefix,
            'table_subscriptions' => $this->getTable(),
            'table_members' => iaUsers::getTable(),
            'start' => $start,
            'limit' => $limit
        ]);

        return $this->_iaDb->getAll($sql);
    }

    protected function _gridModifyParams(&$conditions, &$values, array $params)
    {
        if (!empty($params['reference_id'])) {
            $conditions[] = 's.`reference_id` LIKE :reference';
            $values['reference'] = '%' . $params['reference_id'] . '%';
        }
        if (!empty($params['status'])) {
            $conditions[] = 's.`status` = :status';
            $values['status'] = $params['status'];
        }
    }

    protected function _gridModifyOutput(array &$entries)
    {
        foreach ($entries as &$entry) {
            $entry['plan'] = iaLanguage::get('plan_title_' . $entry['plan_id']);
        }
    }
}
