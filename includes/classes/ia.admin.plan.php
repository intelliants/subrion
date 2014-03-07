<?php
//##copyright##

class iaPlan extends abstractCore
{
	protected static $_table = 'plans';

	protected $_moduleUrl = 'plans/';


	public function getModuleUrl()
	{
		return $this->_moduleUrl;
	}

	public function getById($planId)
	{
		return $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($planId), self::getTable());
	}

	public function updatePlanLanguage($planId, array $language)
	{
		foreach ($this->iaCore->languages as $code => $title)
		{
			iaLanguage::addPhrase('plan_title_' . $planId, $language['title'][$code], $code);
			iaLanguage::addPhrase('plan_description_' . $planId, $language['description'][$code], $code);
		}

		return true;
	}

	/**
	 * Updates plan
	 *
	 * @param array $plan
	 * @param array|int $ids
	 *
	 * @return bool
	 */
	public function update($plan, $ids)
	{
		if (empty($plan))
		{
			$this->setMessage(iaLanguage::getf('key_parameter_is_empty', array('key' => 'Plan')));

			return false;
		}

		if (empty($ids))
		{
			$this->setMessage(iaLanguage::getf('key_parameter_is_empty', array('key' => 'ID')));

			return false;
		}

		unset($plan['title'], $plan['description']);

		$this->iaDb->update($plan, $this->iaDb->convertIds($ids), null, self::getTable());

		return true;
	}

	/**
	 * Adds plan to database
	 *
	 * @param array $aPlan
	 *
	 * @return int|bool
	 */
	public function insert(array $plan)
	{
		if (empty($plan))
		{
			$this->setMessage(iaLanguage::getf('key_parameter_is_empty', array('key' => 'Plan')));

			return false;
		}

		unset($plan['title'], $plan['description']);

		$order = $this->iaDb->getMaxOrder(self::getTable()) + 1;
		$plan['order'] = $order ? $order : 1;

		$this->iaDb->insert($plan, null, self::getTable());

		return $this->iaDb->getInsertId();
	}

	/**
	 * Deletes plan from database
	 *
	 * @param int|array $ids plan id or array of ids
	 *
	 * @return bool
	 */
	public function delete($ids)
	{
		if (empty($ids))
		{
			$this->setMessage(iaLanguage::getf('key_parameter_is_empty', array('key' => 'ID')));

			return false;
		}

		is_array($ids) || $ids = array($ids);

		$this->iaCore->startHook('phpAdminBeforePlanDelete');

		$this->iaDb->delete($this->iaDb->convertIds($ids), self::getTable());

		// here we should drop the "for_plan" column of fields
		// if there are no plans exist
		if (0 === (int)$this->iaDb->one(iaDb::STMT_COUNT_ROWS, null, self::getTable()))
		{
			$this->iaCore->factory('field');
			$this->iaDb->update(array('for_plan' => 0), '`for_plan` = 1', null, iaField::getTable());
		}
		//

		return true;
	}
}