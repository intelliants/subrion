<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2018 Intelliants, LLC <https://intelliants.com>
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

class iaBackendController extends iaAbstractControllerBackend
{
    protected $_name = 'pages';

    protected $_tooltipsEnabled = true;

    protected $_gridColumns = ['name', 'status', 'last_updated'];
    protected $_gridFilters = ['name' => self::LIKE, 'module' => self::EQUAL];
    protected $_gridQueryMainTableAlias = 'p';

    protected $_phraseAddSuccess = 'page_added';

    protected $_permissionsEdit = true;


    public function __construct()
    {
        parent::__construct();

        $iaPage = $this->_iaCore->factory('page', iaCore::ADMIN);
        $this->setHelper($iaPage);

        $this->setTable(iaPage::getTable());
    }

    protected function _indexPage(&$iaView)
    {
        if (isset($_POST['preview'])) {
            $this->_previewPage($iaView->get('action'));
        }

        parent::_indexPage($iaView);
    }

    protected function _gridRead($params)
    {
        if (1 == count($this->_iaCore->requestPath) && 'url' == $this->_iaCore->requestPath[0]) {
            return $this->_getJsonUrl($params);
        }

        return parent::_gridRead($params);
    }

    protected function _gridModifyParams(&$conditions, &$values, array $params)
    {
        if (isset($values['module']) && iaCore::CORE == strtolower($values['module'])) {
            $values['module'] = '';
        }

        if (!empty($params['text'])) {
            $conditions[] = 'l1.`value` LIKE :text OR l2.`value` LIKE :text';
            $values['text'] = '%' . iaSanitize::sql($params['text']) . '%';
        }

        $conditions[] = 'p.`service` = 0';
    }

    protected function _gridQuery($columns, $where, $order, $start, $limit)
    {
        $sql = <<<SQL
SELECT :columns,
  l1.`value` `title`,
  l2.`value` `content`,
  IF(p.`custom_url` != '', `custom_url`, IF(p.`alias` != '', p.`alias`, CONCAT(p.`name`, '/'))) `url`,
  p.`id` `update`,
  IF(p.`readonly` = 0, 1, 0) `delete`
  FROM `:table_pages` p 
LEFT JOIN `:table_phrases` l1 ON (l1.`key` = CONCAT("page_title_", p.`name`) AND l1.`category` = "page" AND l1.`code` = ':code')
LEFT JOIN `:table_phrases` l2 ON (l2.`key` = CONCAT("page_content_", p.`name`) AND l2.`category` = "page" AND l2.`code` = ':code')
WHERE :where :order 
LIMIT :start, :limit
SQL;
        $sql = iaDb::printf($sql, [
            'table_pages' => $this->_iaDb->prefix . self::getTable(),
            'table_phrases' => $this->_iaDb->prefix . iaLanguage::getTable(),
            'code' => $this->_iaCore->language['iso'],
            'columns' => $columns,
            'where' => $where,
            'order' => $order,
            'start' => $start,
            'limit' => $limit
        ]);

        return $this->_iaDb->getAll($sql);
    }

    protected function _gridModifyOutput(array &$entries)
    {
        foreach ($entries as &$entry) {
            $entry['content'] = iaSanitize::tags($entry['content']);
        }
    }

    protected function _setDefaultValues(array &$entry)
    {
        $entry = [
            'name' => '',
            'parent' => '',
            'filename' => 'page',
            'custom_tpl' => 0,
            'template_filename' => '',
            'alias' => '',
            'module' => '',
            'readonly' => false,
            'service' => false,
            'nofollow' => false,
            'new_window' => false,
            'status' => iaCore::STATUS_ACTIVE
        ];
    }

    protected function _preSaveEntry(array &$entry, array $data, $action)
    {
        $this->_iaCore->startHook('phpAdminAddPageValidation', ['entry' => &$entry]);

        iaUtil::loadUTF8Functions('ascii', 'bad', 'utf8_to_ascii', 'validation');

        $entry['name'] = preg_replace('#[^a-z0-9-_]#iu', '',
            strtolower($data['name'] = !utf8_is_ascii($data['name']) ? utf8_to_ascii($data['name']) : $data['name']));
        $entry['status'] = isset($data['preview']) ? iaCore::STATUS_DRAFT : $data['status'];

        if (empty($data['title'][iaLanguage::getMasterLanguage()->iso])) {
            $this->addMessage(iaLanguage::getf('field_is_empty',
                ['field' => iaLanguage::get('title')]), false);
        }

        if (iaCore::ACTION_ADD == $action) {
            $entry['group'] = 2;
            $entry['filename'] = 'page';
        }

        if (!isset($data['service']) || !$data['service']) {
            $entry['alias'] = empty($data['alias']) ? $data['name'] : $data['alias'];
            $entry['custom_url'] = empty($data['custom_url']) ? '' : $data['custom_url'];
            $entry['passw'] = empty($data['passw']) ? '' : trim($data['passw']);

            $entry['alias'] = utf8_is_ascii($entry['alias']) ? $entry['alias'] : utf8_to_ascii($entry['alias']);
            $entry['alias'] = empty($entry['alias']) ? '' : iaSanitize::alias($entry['alias']);
            $entry['alias'] .= $data['extension'];

            if ($data['parent_id']) {
                $parentPage = $this->getById($data['parent_id']);
                $parentAlias = empty($parentPage['alias']) ? $parentPage['name'] . IA_URL_DELIMITER : $parentPage['alias'];

                $entry['parent'] = $parentPage['name'];
                $entry['alias'] = $parentAlias . (IA_URL_DELIMITER == substr($parentAlias, -1,
                        1) ? '' : IA_URL_DELIMITER) . $entry['alias'];
            } else {
                $entry['parent'] = '';
            }

            if ($this->_iaDb->exists('`id` != :id AND `alias` = :alias',
                ['id' => $this->getEntryId(), 'alias' => $entry['alias']])
            ) {
                $this->addMessage('page_alias_exists');
            }

            if (isset($data['nofollow'])) {
                $entry['nofollow'] = (int)$data['nofollow'];
            }

            if (isset($data['new_window'])) {
                $entry['new_window'] = (int)$data['new_window'];
            }

            // delete custom url
            if (isset($data['unique']) && 0 == $data['unique']) {
                $entry['custom_url'] = '';
            }

            if (isset($data['custom_tpl']) && $data['custom_tpl']) {
                $entry['custom_tpl'] = (int)$data['custom_tpl'];
                $entry['template_filename'] = $data['template_filename'];

                if (!$data['template_filename']) {
                    $this->addMessage('page_incorrect_template_filename');
                }
            } else {
                $entry['custom_tpl'] = 0;
                $entry['template_filename'] = '';
            }
        }

        if (empty($entry['name'])) {
            $this->addMessage(iaLanguage::getf('field_is_empty', ['field' => iaLanguage::get('name')]), false);
        } elseif (iaCore::ACTION_ADD == $action
            && $this->_iaDb->exists('`name` = :name', ['name' => $entry['name']])
        ) {
            $this->addMessage('page_name_exists');
        }

        return !$this->getMessages();
    }

    protected function _entryAdd(array $entryData)
    {
        $order = $this->_iaDb->getMaxOrder() + 1;

        $entryData['last_updated'] = (new \DateTime())->format(iaDb::DATETIME_FORMAT);
        $entryData['order'] = $order ? $order : 1;

        return parent::_entryAdd($entryData);
    }

    protected function _entryUpdate(array $entryData, $entryId)
    {
        $currentData = $this->getById($entryId);

        $entryData['last_updated'] = (new \DateTime())->format(iaDb::DATETIME_FORMAT);

        $result = parent::_entryUpdate($entryData, $entryId);

        if ($result) {
            if (!empty($currentData['alias']) && $entryData['alias'] && $currentData['alias'] != $entryData['alias']) {
                $this->_massUpdateAlias($currentData['alias'], $entryData['alias'], $this->getEntryId());
            }
        }

        return $result;
    }

    protected function _postSaveEntry(array &$entry, array $data, $action)
    {
        // saving selected menus
        $selectedMenus = empty($data['menus']) ? [] : $data['menus'];
        $this->_saveMenus($entry['name'], $selectedMenus);

        // setting as the home page if needed
        if (isset($data['home_page']) && $data['home_page']) {
            if ($this->_iaCore->factory('acl')->isAccessible($this->getName(), 'home')) {
                $this->_iaCore->set('home_page', $entry['name'], true);
            }
        }

        $this->_saveMultilingualData($entry, $data['module'], $action);

        // writing to log
        $pageTitle = $data['title'][$this->_iaCore->iaView->language];

        $iaLog = $this->_iaCore->factory('log');
        $actionCode = (iaCore::ACTION_ADD == $action) ? iaLog::ACTION_CREATE : iaLog::ACTION_UPDATE;
        $iaLog->write($actionCode, ['item' => 'page', 'name' => $pageTitle, 'id' => $this->getEntryId()]);
    }

    protected function _entryDelete($entryId)
    {
        $result = false;

        if ($row = $this->getById($entryId)) {
            $result = parent::_entryDelete($entryId);

            if ($result) {
                $pageName = $row['name'];

                $this->_iaCore->factory('log')->write(iaLog::ACTION_DELETE, [
                    'item' => 'page',
                    'name' => $this->_iaCore->factory('page')->getPageTitle($pageName),
                    'id' => (int)$entryId
                ]);

                // remove associated phrases
                $this->_iaDb->setTable(iaLanguage::getTable());
                foreach(['title', 'content', 'meta_keywords', 'meta_description', 'meta_title'] as $type) {
                    $this->_iaDb->delete(sprintf("`key` IN ('page_%s_%s')", $type, $pageName));
                }
                $this->_iaDb->resetTable();

                // remove associated blocks
                $this->_iaCore->factory('block', iaCore::ADMIN);
                $this->_iaDb->delete('`page_name` = :page', iaBlock::getMenusTable(), ['page' => $pageName]);
            }
        }

        return $result;
    }

    protected function _assignValues(&$iaView, array &$entryData)
    {
        $this->_iaCore->factory('block', iaCore::ADMIN);

        if ($this->_iaCore->factory('acl')->isAccessible($this->getName(), iaCore::ACTION_ADD)) {
            $selectedMenus = empty($_POST['menus'])
                ? $this->_iaDb->onefield('menu_id', iaDb::convertIds($entryData['name'], 'page_name'), null, null,
                    iaBlock::getMenusTable())
                : $_POST['menus'];

            $iaView->assign('selectedMenus', $selectedMenus);
        }

        $parentAlias = '';
        if ($entryData['parent']) {
            $parentAlias = $this->getHelper()->getByName($entryData['parent'], false);
            $parentAlias = empty($parentAlias['alias']) ? $parentAlias['name'] . IA_URL_DELIMITER : $parentAlias['alias'];
        }

        $last = explode('.', $entryData['alias']);
        $entryData['extension'] = (false === strpos($entryData['alias'], '.')) ? '' : end($last);
        $entryData['alias'] = substr($entryData['alias'], strlen($parentAlias), -1 - strlen($entryData['extension']));

        if ($entryData['name'] == $entryData['alias']) {
            $entryData['alias'] = '';
        }

        $parentPage = $this->getHelper()->getByName($entryData['parent'], false);
        $groups = $this->getHelper()->getGroups([$this->_iaCore->get('home_page'), $entryData['name']]);
        $isHomepage = ($this->_iaCore->get('home_page', iaView::DEFAULT_HOMEPAGE) == $entryData['name']);
        $homePageTitle = $this->_iaDb->one_bind('value', '`key` = :key AND `category` = :category',
            ['key' => 'page_title_' . $this->_iaCore->get('home_page'), 'category' => iaLanguage::CATEGORY_PAGE], iaLanguage::getTable());

        list($title, $content, $metaDescription, $metaKeywords, $metaTitles) = $this->_loadMultilingualData($entryData['name']);

        $iaView->assign('title', $title);
        $iaView->assign('content', $content);
        $iaView->assign('metaDescription', $metaDescription);
        $iaView->assign('metaKeywords', $metaKeywords);
        $iaView->assign('metaTitles', $metaTitles);

        $iaView->assign('isHomePage', $isHomepage);
        $iaView->assign('homePageTitle', $homePageTitle);
        $iaView->assign('extensions', $this->getHelper()->extendedExtensions);
        $iaView->assign('menus', $this->_getMenus());
        $iaView->assign('pages', $this->getHelper()->getNonServicePages(['index']));
        $iaView->assign('pagesGroup', $groups);
        $iaView->assign('parentPageId', $parentPage['id']);
    }


    private function _getMenus()
    {
        $menus = [
            ['title' => iaLanguage::get('core_menus', 'Core menus'), 'items' => []],
            ['title' => iaLanguage::get('custom_menus', 'Custom menus'), 'items' => []]
        ];

        if ($this->_iaCore->factory('acl')->isAccessible($this->getName(), iaCore::ACTION_ADD)) {
            $sql = <<<SQL
SELECT m.`removable`, m.`id`, p.`value` `title` 
  FROM `:prefix:table_menus` m 
LEFT JOIN `:prefix:table_phrases` p ON (p.`key` = CONCAT('block_title_', m.`id`) && p.`code` = ':lang') 
WHERE m.`type` = 'menu' 
ORDER BY `title`
SQL;
            $sql = iaDb::printf($sql, [
                'prefix' => $this->_iaDb->prefix,
                'table_menus' => iaBlock::getTable(),
                'table_phrases' => iaLanguage::getTable(),
                'lang' => $this->_iaCore->language['iso']
            ]);

            $rows = $this->_iaDb->getAssoc($sql);

            $menus[0]['items'] = $rows[0];
            isset($rows[1]) && $menus[1]['items'] = $rows[1];
        }

        return $menus;
    }

    private function _previewPage($action)
    {
        if (iaCore::ACTION_ADD == $action) {
            $_POST['save'] = true;
        } else {
            iaUtil::loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

            $newPage = [];
            $name = strtolower($_POST['name'] = !utf8_is_ascii($_POST['name']) ? utf8_to_ascii($_POST['name']) : $_POST['name']);
            if (isset($_POST['content']) && is_array($_POST['content'])) {
                function utf8_validation(&$item)
                {
                    $item = !utf8_is_valid($item) ? utf8_bad_replace($item) : $item;
                }

                foreach ($_POST['content'] as $key => $content) {
                    utf8_validation($_POST['content'][$key]);
                }

                $newPage['content'] = $_POST['content'];
            }

            $newPage['title'] = $_POST['title'];
            $newPage['passw'] = iaSanitize::sql($_POST['passw']);

            isset($_SESSION['preview_pages']) || $_SESSION['preview_pages'] = [];
            $_SESSION['preview_pages'][$name] = $newPage;

            $languagesEnabled = $this->_iaCore->get('language_switch') && count($this->_iaCore->languages);
            $redirectUrl = IA_CLEAR_URL . ($languagesEnabled ? $_POST['language'] . IA_URL_DELIMITER : '') . 'page' . IA_URL_DELIMITER . $name . IA_URL_DELIMITER . '?preview';

            iaUtil::go_to($redirectUrl);
        }
    }

    private function _massUpdateAlias($previous, $new, $entryId)
    {
        $previous = iaSanitize::sql($previous);
        $previous = (IA_URL_DELIMITER == $previous[strlen($previous) - 1]) ? substr($previous, 0, -1) : $previous;

        $new = iaSanitize::sql($new);
        $new = (IA_URL_DELIMITER == $new[strlen($new) - 1]) ? substr($new, 0, -1) : $new;

        $cond = iaDb::printf("`alias` LIKE ':alias%' AND `id` != :id", ['alias' => $previous, 'id' => $entryId]);
        $stmt = ['alias' => "REPLACE(`alias`, '$previous', '$new')"];

        $this->_iaDb->update(null, $cond, $stmt);
    }

    private function _saveMenus($entryName, $menus)
    {
        if ($this->_iaCore->factory('acl')->isAccessible($this->getName(), iaCore::ACTION_ADD)) {
            $iaDb = &$this->_iaDb;
            $iaBlock = $this->_iaCore->factory('block', iaCore::ADMIN);

            $iaDb->setTable($iaBlock::getMenusTable());

            $menusList = $iaDb->all(['id'], iaDb::convertIds('menu', 'type'), null, null, $iaBlock::getTable());
            foreach ($menusList as $item) {
                if (in_array($item['id'], $menus)) {
                    if (!$iaDb->exists('`menu_id` = :menu AND `page_name` = :page',
                        ['menu' => $item['id'], 'page' => $entryName])
                    ) {
                        $entry = [
                            'parent_id' => 0,
                            'menu_id' => $item['id'],
                            'el_id' => $this->getEntryId() . '_' . iaUtil::generateToken(5),
                            'level' => 0,
                            'page_name' => $entryName
                        ];

                        $iaDb->insert($entry);
                    }
                } else {
                    $iaDb->delete('`menu_id` = :menu AND `page_name` = :page', null,
                        ['menu' => $item['id'], 'page' => $entryName]);
                }

                $this->_iaCore->iaCache->remove('menu_' . $item['id']);
            }

            $iaDb->resetTable();
        }
    }

    private function _loadMultilingualData($pageName)
    {
        $title = $content = $metaDescription = $metaKeywords = $metaTitles = [];

        if (isset($_POST['save'])) {
            list($title, $content, $metaDescription, $metaKeywords, $metaTitles) = [
                $_POST['title'],
                $_POST['content'],
                $_POST['meta_description'],
                $_POST['meta_keywords'],
                $_POST['meta_title']
            ];
        } elseif (iaCore::ACTION_EDIT == $this->_iaCore->iaView->get('action')) {
            $this->_iaDb->setTable(iaLanguage::getTable());

            $title = $this->_iaDb->keyvalue(['code', 'value'],
                "`key` = 'page_title_{$pageName}' AND `category` = 'page'");
            $content = $this->_iaDb->keyvalue(['code', 'value'],
                "`key` = 'page_content_{$pageName}' AND `category` = 'page'");
            $metaDescription = $this->_iaDb->keyvalue(['code', 'value'],
                "`key` = 'page_meta_description_{$pageName}' AND `category` = 'page'");
            $metaKeywords = $this->_iaDb->keyvalue(['code', 'value'],
                "`key` = 'page_meta_keywords_{$pageName}' AND `category` = 'page'");
            $metaTitles = $this->_iaDb->keyvalue(['code', 'value'],
                "`key` = 'page_meta_title_{$pageName}' AND `category` = 'page'");

            $this->_iaDb->resetTable();
        }

        return [$title, $content, $metaDescription, $metaKeywords, $metaTitles];
    }

    private function _saveMultilingualData(array $pageEntry, $module, $action)
    {
        $pageName = $pageEntry['name'];
        $masterLangCode = iaLanguage::getMasterLanguage()->iso;

        foreach ($this->_iaCore->languages as $iso => $language) {
            foreach (['title', 'content', 'meta_description', 'meta_keywords', 'meta_title'] as $key) {
                if (isset($_POST[$key][$iso])) {
                    $phraseKey = sprintf('page_%s_%s', $key, $pageName);

                    $value = $_POST[$key][$iso];
                    if (!$value && iaCore::ACTION_ADD == $action) {
                        $value = $_POST[$key][$masterLangCode];
                    }

                    utf8_is_valid($value) || $value = utf8_bad_replace($value);

                    iaLanguage::addPhrase($phraseKey, $value, $iso, $module, iaLanguage::CATEGORY_PAGE, true);
                }
            }
        }
    }

    private function _getJsonUrl(array $params)
    {
        iaUtil::loadUTF8Functions('ascii', 'utf8_to_ascii');

        $name = $params['name'];
        $name = utf8_is_ascii($name) ? $name : utf8_to_ascii($name);
        $name = preg_replace('#[^a-z0-9-_]#iu', '', $name);

        $url = $params['url'];
        $url = utf8_is_ascii($url) ? $url : utf8_to_ascii($url);
        $url = preg_replace('#[^a-z0-9-_]#iu', '', $url);

        $url = $url ? $url : $name;

        if (is_numeric($params['parent']) && $params['parent']) {
            $parentPage = $this->getById($params['parent']);
            $parentAlias = empty($parentPage['alias']) ? $parentPage['name'] . IA_URL_DELIMITER : $parentPage['alias'];

            $url = $parentAlias . (IA_URL_DELIMITER == substr($parentAlias, -1, 1) ? '' : IA_URL_DELIMITER) . $url;
        }

        $url .= $params['ext'];

        $exists = $this->_iaDb->exists('`alias` = :url AND `name` != :name', ['url' => $url, 'name' => $name]);
        $url = IA_URL . $url;

        return ['url' => $url, 'exists' => $exists];
    }
}
