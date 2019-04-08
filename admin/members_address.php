<?php

class iaBackendController extends iaAbstractControllerBackend
{

    protected $_gridColumns = ['id', 'member_name', 'ip_address', 'user_agent', 'entry_date'];
    protected $_name = 'members_address';
    protected $_table = 'members_addresses';
    protected $_gridFilters = ['ip_address' => self::LIKE,'member_name' => self::LIKE];
}