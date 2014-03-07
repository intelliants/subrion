<?php
//##copyright##

abstract class abstractPlugin extends iaGrid
{


	public function insert(array $itemData)
	{
		return $this->iaDb->insert($itemData, null, self::getTable());
	}

	public function update(array $itemData, $id)
	{
		return (bool)$this->iaDb->update($itemData, iaDb::convertIds($id));
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