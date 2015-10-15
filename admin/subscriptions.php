<?php
//##copyright##

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
		$sql = iaDb::printf($sql, array(
			'prefix' => $this->_iaDb->prefix,
			'table_subscriptions' => $this->getTable(),
			'table_members' => iaUsers::getTable(),
			'start' => $start,
			'limit' => $limit
		));

		return $this->_iaDb->getAll($sql);
	}

	protected function _modifyGridParams(&$conditions, &$values)
	{
		if (!empty($_GET['reference_id']))
		{
			$conditions[] = 's.`reference_id` LIKE :reference';
			$values['reference'] = '%' . $_GET['reference_id'] . '%';
		}
		if (!empty($_GET['status']))
		{
			$conditions[] = 's.`status` = :status';
			$values['status'] = $_GET['status'];
		}
	}

	protected function _modifyGridResult(array &$entries)
	{
		foreach ($entries as &$entry)
		{
			$entry['plan'] = iaLanguage::get('plan_title_' . $entry['plan_id']);
		}
	}
}