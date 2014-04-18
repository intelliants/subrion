<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2014 Intelliants, LLC <http://www.intelliants.com>
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
 * @link http://www.subrion.org/
 *
 ******************************************************************************/

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