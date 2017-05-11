<?php

/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2017 Intelliants, LLC <https://intelliants.com>
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
 * @link https://subrion.org/
 *
 ******************************************************************************/
class iaBlog extends abstractModuleFront
{
    const ALIAS_SUFFIX = '.html';

    const PAGE_NAME = 'blog';

    protected static $_table = 'blog_entries';
    protected $_tableBlogTags = 'blog_tags';
    protected $_tableBlogEntriesTags = 'blog_entries_tags';

    public $coreSearchEnabled = true;


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

    public function coreSearch($query, $start, $limit, $order)
    {
        $where = '(b.`title` LIKE :query OR b.`body` LIKE :query)';
        $this->iaDb->bind($where, ['query' => '%' . iaSanitize::sql($query) . '%']);

        $rows = $this->get(0, $limit, $where);

        return [$this->iaDb->foundRows(), $rows];
    }

    public function get($start, $limit, $conditions = null)
    {
        $order = 'date' == $this->iaCore->get('blog_order') ? '`date_added` DESC' : '`title` ASC';

        $where = 'b.`status` = :status AND b.`lang` = :language';
        empty($conditions) || $where .= ' AND ' . $conditions;
        $this->iaDb->bind($where, ['status' => iaCore::STATUS_ACTIVE, 'language' => $this->iaView->language]);

        $sql = <<<SQL
SELECT SQL_CALC_FOUND_ROWS b.`id`, b.`title`, b.`date_added`, b.`body`, b.`alias`, b.`image`, m.`fullname` 
	FROM `:prefix:table_blog_entries` b 
LEFT JOIN `:prefix:table_members` m ON (b.`member_id` = m.`id`) 
WHERE :where 
GROUP BY b.`id` 
ORDER BY :order 
LIMIT :start, :limit
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $this->iaDb->prefix,
            'table_blog_entries' => self::getTable(),
            'table_members' => iaUsers::getTable(),
            'where' => $where,
            'order' => $order,
            'start' => (int)$start,
            'limit' => (int)$limit
        ]);

        return $this->iaDb->getAll($sql);
    }

    public function getById($id, $decorate = true)
    {
        $sql = <<<SQL
SELECT b.`id`, b.`title`, b.`date_added`, b.`body`, b.`alias`, b.`image`, m.`fullname`, b.`member_id` 
	FROM `:prefix:table_blog_entries` b 
LEFT JOIN `:prefix:table_members` m ON (b.`member_id` = m.`id`) 
WHERE b.`id` = :id AND b.`status` = ':status'
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $this->iaDb->prefix,
            'table_blog_entries' => self::getTable(),
            'table_members' => iaUsers::getTable(),
            'id' => (int)$id,
            'status' => iaCore::STATUS_ACTIVE
        ]);

        return $this->iaDb->getRow($sql);
    }

    public function delete($id)
    {
        $result = false;

        $this->iaDb->setTable(self::getTable());

        // if item exists, then remove it
        if ($row = $this->iaDb->row_bind(['title', 'image'], '`id` = :id', ['id' => $id])) {
            $result[] = (bool)$this->iaDb->delete(iaDb::convertIds($id), self::getTable());

            if ($row['image'] && $result) { // we have to remove the assigned image as well
                $iaPicture = $this->iaCore->factory('picture');
                $iaPicture->delete($row['image']);
            }

            $result[] = (bool)$this->iaDb->delete(iaDb::convertIds($id, 'blog_id'), $this->_tableBlogEntriesTags);

            $sql = <<<SQL
DELETE FROM `:prefix:table_blog_tags` 
WHERE `id` NOT IN (SELECT DISTINCT `tag_id`	FROM `:prefix:table_blog_entries_tags`)
SQL;
            $sql = iaDb::printf($sql, [
                'prefix' => $this->iaDb->prefix,
                'table_blog_entries_tags' => 'blog_entries_tags',
                'table_blog_tags' => 'blog_tags'
            ]);
            $result[] = (bool)$this->iaDb->query($sql);

            if ($result) {
                $this->iaCore->factory('log')->write(iaLog::ACTION_DELETE,
                    ['module' => 'blog', 'item' => 'blog', 'name' => $row['title'], 'id' => (int)$id]);
            }
        }

        $this->iaDb->resetTable();

        return $result;
    }

    public function getTags($blogEntryId)
    {
        $sql = <<<SQL
SELECT DISTINCT bt.`title`, bt.`alias`
	FROM `:prefix:table_blog_tags` bt 
LEFT JOIN `:prefix:table_blog_entries_tags` bet ON (bt.`id` = bet.`tag_id`) 
WHERE bet.`blog_id` = :id
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $this->iaDb->prefix,
            'table_blog_entries_tags' => $this->_tableBlogEntriesTags,
            'table_blog_tags' => $this->_tableBlogTags,
            'id' => (int)$blogEntryId
        ]);

        return $this->iaDb->getAll($sql);
    }

    public function getTagsString($blogEntryId)
    {
        $sql = <<<SQL
SELECT GROUP_CONCAT(`title`) 
	FROM `:prefix:table_blog_tags` bt 
WHERE `id` IN (SELECT `tag_id` FROM `:prefix:table_blog_entries_tags` WHERE `blog_id` = :id)
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $this->iaDb->prefix,
            'table_blog_tags' => $this->_tableBlogTags,
            'table_blog_entries_tags' => $this->_tableBlogEntriesTags,
            'id' => $blogEntryId
        ]);

        return $this->iaDb->getOne($sql);
    }

    public function getAllTags()
    {
        $sql = <<<SQL
SELECT bt.`title`, bt.`alias`, bet.`blog_id` 
	FROM `:prefix:table_blog_tags` bt 
LEFT JOIN `:prefix:table_blog_entries_tags` bet ON (bt.`id` = bet.`tag_id`) 
ORDER BY bt.`title`
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $this->iaDb->prefix,
            'table_blog_entries_tags' => $this->_tableBlogEntriesTags,
            'table_blog_tags' => $this->_tableBlogTags
        ]);

        return $this->iaDb->getAll($sql);
    }

    public function saveTags($id, $tags)
    {
        $tags = array_filter(explode(',', $tags));

        $this->iaDb->setTable($this->_tableBlogEntriesTags);

        $sql = <<<SQL
DELETE FROM `:prefix:table_blog_tags` 
WHERE `id` IN (
	SELECT DISTINCT `tag_id` FROM `:prefix:table_blog_entries_tags` 
	WHERE `tag_id` IN (SELECT DISTINCT `tag_id` FROM `:prefix:table_blog_entries_tags` WHERE `blog_id` = :id) 
	GROUP BY 1 
	HAVING COUNT(*) = 1)
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $this->iaDb->prefix,
            'table_blog_tags' => $this->_tableBlogTags,
            'table_blog_entries_tags' => $this->_tableBlogEntriesTags,
            'id' => $id
        ]);
        $this->iaDb->query($sql);

        $this->iaDb->delete(iaDb::convertIds($id, 'blog_id'), $this->_tableBlogEntriesTags);

        $allTagTitles = $this->iaDb->keyvalue(['title', 'id'], null, $this->_tableBlogTags);

        foreach ($tags as $tag) {
            $tagAlias = iaSanitize::alias(strtolower($tag));
            $tagEntry = [
                'title' => $tag,
                'alias' => $tagAlias
            ];
            $tagId = isset($allTagTitles[$tag])
                ? $allTagTitles[$tag]
                : $this->iaDb->insert($tagEntry, null, $this->_tableBlogTags);

            $tagBlogIds = [
                'blog_id' => $id,
                'tag_id' => $tagId
            ];

            $this->iaDb->insert($tagBlogIds);
        }
    }
}
