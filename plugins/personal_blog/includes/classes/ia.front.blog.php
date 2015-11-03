<?php
//##copyright##

class iaBlog extends abstractPlugin
{
	const ALIAS_SUFFIX = '.html';

	const PAGE_NAME = 'blog';

	protected static $_table = 'blog_entries';
	protected $_tableBlogTags = 'blog_tags';
	protected $_tableBlogEntriesTags = 'blog_entries_tags';


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
			$result[] = (bool)$this->iaDb->delete(iaDb::convertIds($id), self::getTable());

			if ($row['image'] && $result) // we have to remove the assigned image as well
			{
				$iaPicture = $this->iaCore->factory('picture');
				$iaPicture->delete($row['image']);
			}

			$result[] = (bool)$this->iaDb->delete(iaDb::convertIds($id, 'blog_id'), $this->_tableBlogEntriesTags);

			$sql =
				'DELETE ' .
				'FROM `:prefix:table_blog_tags` ' .
				'WHERE `id` NOT IN (' .
					'SELECT DISTINCT `tag_id` ' .
					'FROM `:prefix:table_blog_entries_tags`)';

			$sql = iaDb::printf($sql, array(
				'prefix' => $this->_iaDb->prefix,
				'table_blog_entries_tags' => 'blog_entries_tags',
				'table_blog_tags' => 'blog_tags'
			));
			$result[] = (bool)$this->iaDb->query($sql);

			if ($result)
			{
				$this->iaCore->factory('log')->write(iaLog::ACTION_DELETE, array('module' => 'blog', 'item' => 'blog', 'name' => $row['title'], 'id' => (int)$id));
			}
		}

		$this->iaDb->resetTable();

		return $result;
	}

	public function getTags($id)
	{
		$sql =
			'SELECT GROUP_CONCAT(`title`) ' .
			'FROM `:prefix:table_blog_tags` bt ' .
			'WHERE `id` IN (' .
			'SELECT `tag_id` ' .
			'FROM `:prefix:table_blog_entries_tags` ' .
			'WHERE `blog_id` = :id)';

		$sql = iaDb::printf($sql, array(
			'prefix' => $this->_iaDb->prefix,
			'table_blog_tags' => $this->_tableBlogTags,
			'table_blog_entries_tags' => $this->_tableBlogEntriesTags,
			'id' => $id
		));

		return $this->_iaDb->getOne($sql);
	}

	public function saveTags($id, $tags)
	{
		$tags = array_filter(explode(',', $tags));

		$this->_iaDb->setTable($this->_tableBlogEntriesTags);

		$sql =
			'DELETE ' .
			'FROM `:prefix:table_blog_tags` ' .
			'WHERE `id` IN (' .
			'SELECT DISTINCT `tag_id` ' .
			'FROM `:prefix:table_blog_entries_tags` ' .
			'WHERE `tag_id` IN (' .
			'SELECT DISTINCT `tag_id` FROM `:prefix:table_blog_entries_tags` ' .
			'WHERE `blog_id` = :id) ' .
			'GROUP BY 1 ' .
			'HAVING COUNT(*) = 1)';

		$sql = iaDb::printf($sql, array(
			'prefix' => $this->_iaDb->prefix,
			'table_blog_tags' => $this->_tableBlogTags,
			'table_blog_entries_tags' => $this->_tableBlogEntriesTags,
			'id' => $id
		));

		$this->_iaDb->query($sql);
		$sql =
			'DELETE ' .
			'FROM :prefix:table_blog_entries_tags ' .
			'WHERE `blog_id` = :id';
		$sql = iaDb::printf($sql, array(
			'prefix' => $this->_iaDb->prefix,
			'table_blog_entries_tags' => $this->_tableBlogEntriesTags,
			'id' => $id
		));

		$this->_iaDb->query($sql);

		$allTagTitles = $this->_iaDb->keyvalue(array('title','id'), null,$this->_tableBlogTags);

		foreach ($tags as $tag)
		{
			$tagAlias = iaSanitize::alias(strtolower($tag));
			$tagEntry = array(
				'title' => $tag,
				'alias' => $tagAlias
			);
			$tagId = isset($allTagTitles[$tag])
				? $allTagTitles[$tag]
				: $this->_iaDb->insert($tagEntry, null, $this->_tableBlogTags);

			$tagBlogIds = array(
				'blog_id' => $id,
				'tag_id' => $tagId
			);

			$this->_iaDb->insert($tagBlogIds);
		}
	}
}