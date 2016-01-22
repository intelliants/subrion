<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2016 Intelliants, LLC <http://www.intelliants.com>
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

class iaSubscription extends abstractCore
{
	const ACTIVE = 'active';
	const PENDING = 'pending';
	const SUSPENDED = 'suspended';
	const CANCELED = 'canceled';
	const FAILED = 'failed';
	const COMPLETED = 'completed';

	protected static $_table = 'payment_subscriptions';


	public function create($planId)
	{
		$entry = array(
			'plan_id' => (int)$planId,
			'member_id' => iaUsers::hasIdentity() ? iaUsers::getIdentity()->id : 0,
			'status' => self::PENDING
		);

		if ($id = $this->iaDb->insert($entry, array('date_created' => iaDb::FUNCTION_NOW), self::getTable()))
		{
			$entry['id'] = $id;

			return $entry;
		}

		return false;
	}

	public function activate(array $subscription, $referenceId)
	{
		$values = array(
			'reference_id' => $referenceId,
			'status' => self::ACTIVE
		);

		return $this->iaDb->update($values, iaDb::convertIds($subscription['id']), null, self::getTable());
	}

	public function update(array $values, $referenceId)
	{
		return $this->iaDb->update($values, iaDb::convertIds($referenceId, 'reference_id'), null, self::getTable());
	}

	public function getByReferenceId($referenceId)
	{
		return $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($referenceId, 'reference_id'), self::getTable());
	}
}