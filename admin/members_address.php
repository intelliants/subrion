<?php

class iaBackendController extends iaAbstractControllerBackend
{

    protected $_gridColumns = ['id', 'member_name', 'ip_address', 'user_agent', 'entry_date'];
    protected $_name = 'members_address';
    protected $_table = 'members_addresses';
    protected $_gridFilters = ['ip_address' => self::LIKE,'member_name' => self::LIKE];

//    protected function _gridRead($params)
//    {
//        if (1 == count($this->_iaCore->requestPath) && 'registration-email' == $this->_iaCore->requestPath[0]) {
//            return $this->_resendRegistrationEmail();
//        }
//
//        return parent::_gridRead($params);
//    }

//    protected function _gridQuery($columns, $where, $order, $start, $limit)
//    {
////        _v($order);
//        $sql =
//            'SELECT SQL_CALC_FOUND_ROWS '
//            . ':columns from :prefix:table_address '
//            . 'WHERE :where :order '
//            . 'LIMIT :start, :limit';
//
//        $sql = iaDb::printf($sql, [
//            'prefix' => $this->_iaDb->prefix,
//            'table_address' => $this->getTable(),
//            'where' => $where ? $where : iaDb::EMPTY_CONDITION,
//            'columns' => $columns,
//            'order' => $order,
//            'start' => $start,
//            'limit' => $limit
//        ]);
//        return $this->_iaDb->getAll($sql);
//    }
}