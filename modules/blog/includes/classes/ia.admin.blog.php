<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2019 Intelliants, LLC <https://intelliants.com>
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

class iaBlog extends abstractModuleAdmin
{
    const ALIAS_SUFFIX = '.html';

    protected static $_table = 'blog_entries';
    protected $_tableBlogTags = 'blog_tags';
    protected $_tableBlogEntriesTags = 'blog_entries_tags';

    protected $_itemName = 'blog';
    public $dashboardStatistics = true;


    public function getDashboardStatistics($defaultProcessing = true)
    {
        $statuses = [iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE];
        $rows = $this->iaDb->keyvalue('`status`, COUNT(*)', '1 GROUP BY `status`', self::getTable());
        $total = 0;

        foreach ($statuses as $status) {
            isset($rows[$status]) || $rows[$status] = 0;
            $total += $rows[$status];
        }

        return [
            'icon' => 'quill',
            'item' => iaLanguage::get('blogposts'),
            'rows' => $rows,
            'total' => $total,
            'url' => 'blog/'
        ];
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
        $row = $this->getById($id);

        $result = parent::delete($id);

        if ($result) {
            $this->iaDb->delete(iaDb::convertIds($id, 'blog_id'), $this->_tableBlogEntriesTags);

            $sql = <<<SQL
DELETE FROM `:prefix:table_blog_tags` 
WHERE `id` NOT IN (SELECT DISTINCT `tag_id` FROM `:prefix:table_blog_entries_tags`)
SQL;
            $sql = iaDb::printf($sql, [
                'prefix' => $this->iaDb->prefix,
                'table_blog_entries_tags' => $this->_tableBlogEntriesTags,
                'table_blog_tags' => $this->_tableBlogTags
            ]);
            $this->iaDb->query($sql);

            $this->iaCore->factory('log')->write(iaLog::ACTION_DELETE,
                ['module' => 'blog', 'item' => 'blog', 'name' => $row['title'], 'id' => (int)$id]);
        }

        return $result;
    }

    public function getTags($id)
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
            'id' => $id
        ]);

        return $this->iaDb->getOne($sql);
    }

    public function getSitemapEntries()
    {
        $result = [];

        $stmt = '`status` = :status';
        $this->iaDb->bind($stmt, ['status' => iaCore::STATUS_ACTIVE]);

        if ($rows = $this->iaDb->all(['id', 'alias'], $stmt, null, null, self::getTable())) {
            $page = $this->iaCore->factory('page', iaCore::ADMIN)->getByName('blog', false);

            foreach ($rows as $row) {
                $result[] = IA_CLEAR_URL . $page['alias'] . $row['id'] . '-' . $row['alias'];
            }
        }

        return $result;
    }
}
