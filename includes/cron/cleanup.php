<?php
//##copyright##

$iaDb->delete('`time` < (UNIX_TIMESTAMP() - 86400)', 'search'); // 24 hours
$iaDb->delete('`date` < (UNIX_TIMESTAMP() - 172800)', 'views_log'); // 48 hours

$iaCore->factory('log')->cleanup();


$iaDb->setTable('online');

$iaDb->delete('`date` < NOW() - INTERVAL 1 DAY');
$iaDb->update(array('status' => 'expired'), '`date` < NOW() - INTERVAL 20 MINUTE');

$iaDb->resetTable();