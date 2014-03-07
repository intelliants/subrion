<?php
//##copyright##

class iaBlog extends abstractPlugin
{
	const ALIAS_SUFFIX = '.html';

	protected static $_table = 'blog_entries';

	public $dashboardStatistics = true;


	public function getDashboardStatistics()
	{
		$statuses = array(iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE);
		$rows = $this->iaDb->keyvalue('`status`, COUNT(*)', '1 GROUP BY `status`', self::getTable());
		$total = 0;

		foreach ($statuses as $status)
		{
			isset($rows[$status]) || $rows[$status] = 0;
			$total += $rows[$status];
		}

		return array(
			'icon' => 'quill',
			'item' => iaLanguage::get('blogposts'),
			'rows' => $rows,
			'total' => $total,
			'url' => 'blog/'
		);
	}

	public function titleAlias($title)
	{
		$result = iaSanitize::tags($title);

		$this->iaCore->factory('util');
		iaUtil::loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

		utf8_is_ascii($result) || $result = utf8_to_ascii($result);

		$result = preg_replace('#' . self::ALIAS_SUFFIX . '$#i', '', $result);
		$result = iaSanitize::alias($result);
		$result = substr($result, 0, 150); // the DB scheme applies this limitation
		$result .= self::ALIAS_SUFFIX;

		return $result;
	}

	public function delete($id)
	{
		$result = false;

		$this->iaDb->setTable(self::getTable());

		// if item exists, then remove it
		if ($row = $this->iaDb->row_bind(array('title', 'image'), '`id` = :id', array('id' => $id)))
		{
			$result = (bool)$this->iaDb->delete(iaDb::convertIds($id), self::getTable());

			if ($row['image'] && $result) // we have to remove the assigned image as well
			{
				$iaPicture = $this->iaCore->factory('picture');
				$iaPicture->delete($row['image']);
			}

			if ($result)
			{
				$this->iaCore->factory('log')->write(iaLog::ACTION_DELETE, array('module' => 'blog', 'item' => 'blog', 'name' => $row['title'], 'id' => (int)$id));
			}
		}

		$this->iaDb->resetTable();

		return $result;
	}

	public function getSitemapEntries()
	{
		$result = array();

		$stmt = '`status` = :status';
		$this->iaDb->bind($stmt, array('status' => iaCore::STATUS_ACTIVE));
		if ($rows = $this->iaDb->all(array('id', 'alias'), $stmt, null, null, self::getTable()))
		{
			foreach ($rows as $row)
			{
				$result[] = IA_URL . 'blog' . IA_URL_DELIMITER . $row['id'] . '-' . $row['alias'];
			}
		}

		return $result;
	}
}