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

abstract class abstractPlugin extends iaGrid
{


	public function insert(array $itemData)
	{
		return $this->iaDb->insert($itemData, null, self::getTable());
	}

	public function update(array $itemData, $id)
	{
		$this->iaDb->update($itemData, iaDb::convertIds($id), null, self::getTable());

		return (0 == $this->iaDb->getErrorNumber());
	}

	public function delete($id)
	{
		return (bool)$this->iaDb->delete(iaDb::convertIds($id), self::getTable());
	}

	public function gridUpdate($params)
	{
		$result = array(
			'result' => false,
			'message' => iaLanguage::get('invalid_parameters')
		);

		$params || $params = array();

		if (isset($params['id']) && is_array($params['id']) && count($params) > 1)
		{
			$ids = $params['id'];
			unset($params['id']);

			$total = count($ids);
			$affected = 0;

			foreach ($ids as $id)
			{
				if ($this->update($params, $id))
				{
					$affected++;
				}
			}

			if ($affected)
			{
				$result['result'] = true;
				$result['message'] = ($affected == $total)
					? iaLanguage::get('saved')
					: iaLanguage::getf('items_updated_of', array('num' => $affected, 'total' => $total));
			}
			else
			{
				$result['message'] = iaLanguage::get('db_error');
			}
		}

		return $result;
	}

	public function gridDelete($params, $languagePhraseKey = 'deleted')
	{
		$result = array(
			'result' => false,
			'message' => iaLanguage::get('invalid_parameters')
		);

		if (isset($params['id']) && is_array($params['id']) && $params['id'])
		{
			$total = count($params['id']);
			$affected = 0;

			foreach ($params['id'] as $id)
			{
				if ($this->delete($id))
				{
					$affected++;
				}
			}

			if ($affected)
			{
				$result['result'] = true;
				if (1 == $total)
				{
					$result['message'] = iaLanguage::get($languagePhraseKey);
				}
				else
				{
					$result['message'] = ($affected == $total)
						? iaLanguage::getf('items_deleted', array('num' => $affected))
						: iaLanguage::getf('items_deleted_of', array('num' => $affected, 'total' => $total));
				}
			}
			else
			{
				$result['message'] = iaLanguage::get('db_error');
			}
		}

		return $result;
	}
}