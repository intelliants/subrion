<?php
//##copyright##

class iaTransaction extends abstractCore
{
	const CANCELED = 'canceled';
	const FAILED = 'failed';
	const REFUNDED = 'refunded';
	const PASSED = 'passed';
	const PENDING = 'pending';

	protected static $_table = 'transactions';
	protected $_tableGateways = 'payment_gateways';

	protected $_gateways;

	public $dashboardStatistics = true;


	public function getTableGateways()
	{
		return $this->_tableGateways;
	}

	/**
	 * Returns installed payment gateways
	 *
	 * @return array
	 */
	public function getPaymentGateways()
	{
		if (is_null($this->_gateways))
		{
			$this->_gateways = $this->iaDb->keyvalue(array('name', 'gateway'), null, $this->getTableGateways());
		}

		return $this->_gateways;
	}

	/**
	 * Checks if input string is a valid payment status
	 *
	 * @param string $status text to be processed
	 *
	 * @return bool
	 */
	public static function isPaymentStatus($status)
	{
		return (bool)in_array($status, array(self::FAILED, self::REFUNDED, self::PASSED, self::PENDING));
	}

	/**
	 * Checks if payment gateway installed
	 *
	 * @param string $aGateway payment gateway name
	 *
	 * @return boolean
	 */
	public function isPaymentGateway($gatewayName)
	{
		return $this->iaDb->exists('`name` = :gateway', array('gateway' => $gatewayName), $this->getTableGateways());
	}

	/**
	 * Return payment gateways
	 *
	 * @param string $aGateway payment gateway name
	 *
	 * @return string
	 */
	public function getPaymentGatewayByName($gatewayName)
	{
		return $this->one_bind('gateway', '`name` = :gateway', array('gateway' => $gatewayName), $this->getTableGateways());
	}

	public function update(array $transactionData)
	{
		return (bool)$this->iaDb->update($transactionData, null, array('date' => iaDb::FUNCTION_NOW), self::getTable());
	}

	public function delete($aId)
	{
		$res = false;
		if ($aId)
		{
			$res = $this->iaDb->delete("`id` = '{$aId}' AND `status` != 'passed'", self::getTable());
		}

		return $res;
	}

	public function getById($transactionId)
	{
		return $this->iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`sec_key` = :key', array('key' => $transactionId), self::getTable());
	}

	public function createIpn($transaction)
	{
		unset($transaction['id']);
		unset($transaction['date']);
		$this->iaDb->insert($transaction, array('date' => iaDb::FUNCTION_NOW), self::getTable());

		return $this->iaDb->getInsertId();
	}

	/*
	 * Generates invoice for an item
	 *
	 * @param string $title     plan title
	 * @param double $cost      plan cost
	 * @param string $aItem     item name
	 * @param array $aItemInfo  item details
	 * @param string $returnUrl return URL
	 * @param integer $aPlan    plan id
	 * @param boolean $return   true redirects to invoice payment URL
	 *
	 * @return string invoice unique ID
	 */
	public function createInvoice($title, $cost, $itemName = 'members', $itemData = array(), $returnUrl = '', $planId = 0, $return = false)
	{
		if (!isset($itemData['id']))
		{
			$itemData['id'] = 0;
		}
		$title = empty($title) ? iaLanguage::get('plan_title_' . $planId) : $title;
		$transactionId = uniqid('t');
		$pay = array(
			'member_id' => (int)(isset($itemData['member_id']) && $itemData['member_id'] ? $itemData['member_id'] : iaUsers::getIdentity()->id),
			'item' => $itemName,
			'item_id' => $itemData['id'],
			'total' => $cost,
			'currency' => $this->iaCore->get('currency'),
			'sec_key' => $transactionId,
			'status' => self::PENDING,
			'plan_id' => $planId,
			'return_url' => $returnUrl,
			'operation_name' => $title
		);
		$this->iaDb->insert($pay, array('date' => iaDb::FUNCTION_NOW), self::getTable());

		if (!$return)
		{
			$this->iaCore->util()->go_to(IA_URL . 'pay' . IA_URL_DELIMITER . $transactionId . IA_URL_DELIMITER);
		}

		return $transactionId;
	}


	public function getDashboardStatistics()
	{
		$this->iaDb->setTable(self::getTable());

		$currenciesToSymbolMap = array('USD' => '$', 'EUR' => '€', 'RMB' => '¥', 'CNY' => '¥');

		$currency = strtoupper($this->iaCore->get('currency'));
		$currency = isset($currenciesToSymbolMap[$currency]) ? $currenciesToSymbolMap[$currency] : '';


		$data = array();
		$weekDay = getdate();
		$weekDay = $weekDay['wday'];
		$stmt = '`status` = :status AND DATE(`date`) BETWEEN DATE(DATE_SUB(NOW(), INTERVAL ' . $weekDay . ' DAY)) AND DATE(NOW()) GROUP BY DATE(`date`)';
		$this->iaDb->bind($stmt, array('status' => self::PASSED));
		$rows = $this->iaDb->keyvalue('DAYOFWEEK(DATE(`date`)), SUM(`total`)', $stmt);
		for ($i = 0; $i < 7; $i++)
		{
			$data[$i] = isset($rows[$i]) ? $rows[$i] : 0;
		}


		$statuses = array(self::PASSED, self::PENDING, self::REFUNDED, self::FAILED);
		$rows = $this->iaDb->keyvalue('`status`, COUNT(*)', '1 GROUP BY `status`');

		foreach ($statuses as $status)
		{
			isset($rows[$status]) || $rows[$status] = 0;
		}

		$total = $this->iaDb->one_bind('ROUND(SUM(`total`)) `total`', '`status` = :status', array('status' => self::PASSED));
		$total || $total = 0;

		$this->iaDb->resetTable();


		return array(
			'_format' => 'medium',
			'data' => array('array' => implode(',', $data)),
			'icon' => 'banknote',
			'item' => iaLanguage::get('total_income'),
			'rows' => $rows,
			'total' => $currency . $total,
			'url' => 'transactions/'
		);
	}
}