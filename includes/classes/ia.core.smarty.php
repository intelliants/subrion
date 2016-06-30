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

require_once IA_SMARTY . 'Smarty.class.php';

class iaSmarty extends Smarty
{
	const INTELLI_RESOURCE = 'intelli';

	const DIRECT_CALL_MARKER = 'direct_call_marker';
	const FLAG_CSS_RENDERED = 'css_rendered';

	const LINK_STYLESHEET_PATTERN = '<link rel="stylesheet" type="text/css" href="%s">';
	const LINK_SCRIPT_PATTERN = '<script type="text/javascript" src="%s"></script>';

	const EXTENSION_CSS = '.css';
	const EXTENSION_JS = '.js';

	protected static $_positionsContent = array();

	public $iaCore;

	public $resources = array(
		'jquery' => 'text:Loading jQuery API..., js:jquery/jquery',
		'manage_mode' => 'css:_IA_URL_js/visual/css/visual, js:visual/js/slidebars.min, js:visual/js/jqueryui.min, js:visual/js/visual',
		'tree' => 'js:jquery/plugins/jstree/jstree.min, js:intelli/intelli.tree, css:_IA_URL_js/jquery/plugins/jstree/themes/default/style',
		'jcal' => 'js:jquery/plugins/jcal/jquery.jcal, css:_IA_URL_js/jquery/plugins/jcal/jquery.jcal',
		'bootstrap' => 'js:bootstrap/js/bootstrap.min, css:iabootstrap, css:user-style',
		'tagsinput' => 'js:jquery/plugins/tagsinput/jquery.tagsinput.min, css:_IA_URL_js/jquery/plugins/tagsinput/jquery.tagsinput',
		'underscore' => 'js:utils/underscore.min',
		'iadropdown' => 'js:jquery/plugins/jquery.ia-dropdown.min',
		'select2' => 'js:jquery/plugins/select2/select2.min, css:_IA_URL_js/jquery/plugins/select2/select2.min',
		'flexslider' => 'js:jquery/plugins/flexslider/jquery.flexslider.min, css:_IA_URL_js/jquery/plugins/flexslider/flexslider',
		'fotorama' => 'js:jquery/plugins/fotorama/fotorama, css:_IA_URL_js/jquery/plugins/fotorama/fotorama'
	);


	public function init()
	{
		parent::__construct();

		iaSystem::renderTime('main', 'beforeSmartyFuncInit');

		$this->iaCore = iaCore::instance();

		$this->registerPlugin(self::PLUGIN_FUNCTION, 'captcha', array(__CLASS__, 'captcha'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'ia_wysiwyg', array(__CLASS__, 'ia_wysiwyg'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'ia_add_media', array(__CLASS__, 'ia_add_media'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'ia_print_css', array(__CLASS__, 'ia_print_css'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'ia_print_js', array(__CLASS__, 'ia_print_js'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'ia_print_title', array(__CLASS__, 'ia_print_title'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'ia_page_url', array(__CLASS__, 'ia_page_url'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'lang', array(__CLASS__, 'lang'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'preventCsrf', array(__CLASS__, 'preventCsrf'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'printImage', array(__CLASS__, 'printImage'));

		$this->registerPlugin(self::PLUGIN_BLOCK, 'access', array(__CLASS__, 'access'));
		$this->registerPlugin(self::PLUGIN_BLOCK, 'ia_add_js', array(__CLASS__, 'ia_add_js'));

		$iaCore = iaCore::instance();

		if (iaCore::ACCESS_FRONT == $iaCore->getAccessType())
		{
			$this->registerPlugin(self::PLUGIN_FUNCTION, 'accountActions', array(__CLASS__, 'accountActions'));
			$this->registerPlugin(self::PLUGIN_FUNCTION, 'arrayToLang', array(__CLASS__, 'arrayToLang'));
			$this->registerPlugin(self::PLUGIN_FUNCTION, 'ia_blocks', array(__CLASS__, 'ia_blocks'));
			$this->registerPlugin(self::PLUGIN_FUNCTION, 'ia_block_view', array(__CLASS__, 'ia_block_view'));
			$this->registerPlugin(self::PLUGIN_FUNCTION, 'ia_url', array(__CLASS__, 'ia_url'));
			$this->registerPlugin(self::PLUGIN_FUNCTION, 'navigation', array(__CLASS__, 'pagination'));
			$this->registerPlugin(self::PLUGIN_FUNCTION, 'printFavorites', array(__CLASS__, 'printFavorites'));
			$this->registerPlugin(self::PLUGIN_FUNCTION, 'width', array(__CLASS__, 'width'));
			$this->registerPlugin(self::PLUGIN_FUNCTION, 'displayTreeNodes', array(__CLASS__, 'displayTreeNodes'));

			$this->registerPlugin(self::PLUGIN_BLOCK, 'ia_block', array(__CLASS__, 'ia_block'));
		}

		// uncomment this to get rid of useless whitespaces in html
		// $this->loadFilter('output', 'trimwhitespace');

		$iaCore->startHook('phpSmartyAfterFuncInit', array('iaSmarty' => &$this));

		iaSystem::renderTime('main', 'afterSmartyFuncInit');

		$this->assign('tabs_content', array());
		$this->assign('tabs_before', array());
		$this->assign('tabs_after', array());

		$this->assign('fieldset_before', array());
		$this->assign('fieldset_after', array());
		$this->assign('field_before', array());
		$this->assign('field_after', array());

		$this->resources['subrion'] = 'text:Loading Subrion Awesome Stuff..., js:intelli/intelli, js:_IA_URL_tmp/cache/intelli.config, '
			. (iaCore::ACCESS_ADMIN == $this->iaCore->getAccessType()
				? 'js:_IA_TPL_bootstrap.min, js:bootstrap/js/bootstrap-switch.min, js:bootstrap/js/passfield.min, js:intelli/intelli.admin, js:admin/footer, css:_IA_URL_js/bootstrap/css/passfield'
				: 'js:intelli/intelli.minmax, js:frontend/footer')
			. ',js:_IA_URL_tmp/cache/intelli' . (iaCore::ACCESS_ADMIN == $this->iaCore->getAccessType() ? '.admin' : '') . '.lang.' . $this->iaCore->iaView->language;
		$this->resources['extjs'] = 'text:Loading ExtJS..., css:_IA_URL_js/extjs/resources/ext-theme-neptune/ext-theme-neptune-all' . ($this->iaCore->get('sap_style') ? '-' . $this->iaCore->get('sap_style') : '') . ', js:extjs/ext-all';
		$this->resources['datepicker'] = 'js:bootstrap/js/datepicker/bootstrap-datepicker, js:bootstrap/js/datepicker/locales/bootstrap-datepicker.' . $this->iaCore->get('lang') . ', css:_IA_URL_js/bootstrap/css/datepicker3';


		$this->iaCore->startHook('phpSmartyAfterMediaInit', array('iaSmarty' => &$this));
	}

	public static function lang($params)
	{
		$key = isset($params['key']) ? $params['key'] : '';
		$default = isset($params['default']) ? $params['default'] : null;

		if (count($params) > 1 && !isset($params['default']))
		{
			unset($params['key']);
			return iaLanguage::getf($key, $params);
		}

		return iaLanguage::get($key, $default);
	}

	public static function ia_page_url($params)
	{
		$isoCode = isset($params['code']) ? $params['code'] : '';
		$currentUrl = isset($params['url']) ? $params['url'] : IA_SELF;

		return IA_CLEAR_URL . $isoCode . IA_URL_DELIMITER . str_replace(IA_URL, '', $currentUrl);
	}

	public static function ia_wysiwyg($params)
	{
		if (empty($params['name']))
		{
			return '';
		}

		$name = $params['name'];
		$value = isset($params['value']) ? iaSanitize::html($params['value']) : '';
		$toolbar = (isset($params['toolbar']) && in_array($params['toolbar'], array('simple', 'dashboard', 'extended')))
			? ",{toolbar:'{$params['toolbar']}'}"
			: '';

		$iaView = iaCore::instance()->iaView;

		$iaView->add_js('ckeditor/ckeditor');
		$iaView->resources->js->{'code:$(function(){if(!window.CKEDITOR)'
		. "$('textarea[id=\"{$name}\"]').show();else CKEDITOR.replace('{$name}'$toolbar);});"} = iaView::RESOURCE_ORDER_REGULAR;

		return sprintf(
			'<textarea style="display: none;" name="%s" id="%s">%s</textarea>',
			$name, $name, $value
		);
	}

	public static function ia_block_view($params, Smarty_Internal_Template &$smarty)
	{
		$block = $params['block'];

		switch ($block['type'])
		{
			case 'menu':
				if ($block['contents'])
				{
					$smarty->assign('menu', $block);

					$result = $smarty->fetch($block['tpl']);
				}

				break;

			case 'smarty':
				$smarty->assign('block', $block);

				$result = $smarty->fetch($block['external'] ? $block['filename'] : 'eval:' . $block['contents']);

				break;

			case 'php':
				if (!$block['external'])
				{
					if (iaSystem::phpSyntaxCheck($block['contents']))
					{
						$iaCore = iaCore::instance(); // predefine this variable to be used in the code below
						$result = eval($block['contents']);
					}
					else
					{
						iaDebug::debug(array(
							'name' => $block['name'],
							'code' => '<textarea style="width:80%;height:100px;">' . $block['contents'] . '</textarea>'
						), '<b style="color:red;">PHP syntax error in the block "' . $block['name'] . '"</b>', 'error');
					}
				}
				else
				{
					$result = include_once $block['filename'];
				}

				break;

			case 'html':
				$result = $block['contents'];

				break;

			case 'plain':
				$result = htmlspecialchars($block['contents']);
		}

		return empty($result) ? '' : $result;
	}

	public static function preventCsrf($params)
	{
		$token = iaCore::instance()->getSecurityToken();
		$html = '<input type="hidden" name="%s" value="%s">';

		return sprintf($html, iaCore::SECURITY_TOKEN_FORM_KEY, iaSanitize::html($token));
	}

	public static function ia_url($params)
	{
		if (empty($params['item']))
		{
			return '#';
		}

		$result = '';

		$defaults = array(
			'url' => '',
			'action' => 'view',
			'item' => '',
			'attr' => '',
			'text' => 'details',
			'type' => 'link',
			'data' => array()
		);
		$params = array_merge($defaults, $params);
		$params['text'] = iaLanguage::get($params['text'], $params['text']);
		$classname = isset($params['classname']) ? $params['classname'] : '';

		switch ($params['item'])
		{
			case iaUsers::getItemName():
				$fieldName = isset($params['field']) ? $params['field'] : 'username';
				$params['url'] = IA_URL . 'member/' . (is_array($params['data']) && $params['data'] ? $params['data'][$fieldName] : $params['data']) . '.html';

				break;

			default:
				$iaCore = iaCore::instance();
				$iaItem = $iaCore->factory('item');
				$package = $iaItem->getPackageByItem($params['item']);
				if (empty($package))
				{
					return $result;
				}
				$iaPackage = $iaCore->factoryPackage('item', $package, iaCore::FRONT, $params['item']);

				if (empty($iaPackage))
				{
					return $result;
				}
				$params['url'] = $iaPackage->url($params['action'], $params['data']);
		}

		if (!isset($params['icon']))
		{
			$params['icon'] = 'info';
		}
		$params['icon'] = '<span class="fa fa-' . $params['icon'] . '"></span>';

		switch ($params['type'])
		{
			case 'link':
				$result = '<a href="' . $params['url'] . '" ' . $params['attr'] . '>' . iaSanitize::html($params['text']) . '</a>';
				break;
			case 'icon':
			case 'icon_text':
				$params['text'] = ($params['type'] == 'icon') ? $params['icon'] : $params['icon'] . ' ' . iaSanitize::html($params['text']);

				$result = '<a href="' . $params['url'] . '" ' . $params['attr'] . ' class="btn btn-sm btn-default ' . $classname . '">' . $params['text'] . '</a>';
				break;
			case 'url':
				$result = $params['url'];
		}

		return $result;
	}

	public static function ia_add_media(array $params, &$smarty)
	{
		if (!isset($params['files']))
		{
			return;
		}

		$order = isset($params['order']) ? $params['order'] : iaView::RESOURCE_ORDER_REGULAR;
		$resources = explode(',', $params['files']);
		foreach ($resources as $file)
		{
			$file = trim($file);
			if (empty($file))
			{
				continue;
			}
			if (isset($smarty->resources[$file]))
			{
				self::ia_add_media(array('files' => $smarty->resources[$file], 'order' => $order), $smarty);
			}
			else
			{
				list($type, $file) = @explode(':', $file);
				switch ($type)
				{
					case 'js':
						self::add_js(array('files' => $file, 'order' => $order));
						break;
					case 'css':
						self::ia_print_css(array('files' => $file, 'order' => $order));
						break;
					case 'text':
						self::add_js(array('text' => $file, 'order' => $order));
				}
			}
		}
	}

	public static function ia_print_css(array $params)
	{
		$iaView = &iaCore::instance()->iaView;

		if (isset($params['files']))
		{
			$iaView->add_css(explode(',', $params['files']), isset($params['order']) ? $params['order'] : null);
		}

		// special case: resources marked to inclusion, but the HEAD html section is already rendered.
		// currently just print out the call directly into html body
		// TODO: check if a call of this resource was already printed out
		if ($iaView->get(self::FLAG_CSS_RENDERED))
		{
			$array = $iaView->resources->css;
			end($array);
			$resource = key($array);

			echo PHP_EOL . sprintf(self::LINK_STYLESHEET_PATTERN, $resource);
			iaDebug::debug('Lateness resource inclusion: ' . $resource, 'Notice');

			return '';
		}

		if (isset($params['display']) && 'on' == $params['display'])
		{
			if ($iaView->manageMode)
			{
				self::ia_add_media(array('files' => 'manage_mode'), $iaView->iaSmarty);
			}

			foreach (self::_arrayCopyKeysSorted($iaView->resources->css) as $resource)
			{
				$output = sprintf(self::LINK_STYLESHEET_PATTERN, $resource);
				echo PHP_EOL . "\t" . $output;
			}

			$iaView->set(self::FLAG_CSS_RENDERED, true);
		}

		return '';
	}

	public static function add_js(array $params)
	{
		$iaView = &iaCore::instance()->iaView;
		$order = isset($params['order']) ? $params['order'] : iaView::RESOURCE_ORDER_REGULAR;

		if (isset($params['files']))
		{
			$iaCore = iaCore::instance();

			$files = $params['files'];
			if (is_string($files))
			{
				$files = explode(',', $files);
			}
			foreach ($files as $filename)
			{
				$filename = trim($filename);
				if (empty($filename))
				{
					continue;
				}

				$compress = true;
				$remote = false;

				if (false !== stristr($filename, 'http://') || false !== stristr($filename, 'https://'))
				{
					$remote = true;
					$compress = false;
					$url = $filename;
				}
				elseif (strstr($filename, '_IA_TPL_'))
				{
					$url = str_replace('_IA_TPL_', IA_TPL_URL . 'js' . IA_URL_DELIMITER, $filename) . self::EXTENSION_JS;
					$file = str_replace('_IA_TPL_', IA_HOME . 'templates' . IA_DS . $iaCore->get('tmpl')  . IA_DS . 'js' . IA_DS, $filename) . self::EXTENSION_JS;
					$tmp = str_replace('_IA_TPL_', 'compress/', $filename);
				}
				elseif (strstr($filename, '_IA_URL_'))
				{
					$url = str_replace('_IA_URL_', $iaView->assetsUrl, $filename) . self::EXTENSION_JS;
					$file = str_replace('_IA_URL_', IA_HOME, $filename) . self::EXTENSION_JS;
					$tmp = str_replace('_IA_URL_', 'compress/', $filename);
				}
				else
				{
					$url = $iaView->assetsUrl . 'js/' . $filename . self::EXTENSION_JS;
					$file = IA_HOME . 'js/' . $filename . self::EXTENSION_JS;
					$tmp = 'compress/' . $filename;
				}

				$lastModified = 0;

				if ($compress)
				{
					$excludedFiles = array('ckeditor/ckeditor', 'jquery/jquery', 'extjs/ext-all', '_IA_TPL_bootstrap.min');

					// lang cache
					if (file_exists($file))
					{
						$lastModified = filemtime($file);
					}
					if ($filename == '_IA_URL_tmp/cache/intelli.admin.lang.en')
					{
						$url = str_replace('_IA_URL_', $iaView->assetsUrl, $filename) . self::EXTENSION_JS;
						$file = str_replace('_IA_URL_', IA_HOME, $filename) . self::EXTENSION_JS;
						$tmp = str_replace('_IA_URL_', 'compress/', $filename);
					}

					// start compress
					if ($iaCore->get('compress_js') && !in_array($filename, $excludedFiles))
					{
						$minifiedFilename = IA_TMP . $tmp . self::EXTENSION_JS;
						$minifiedLastModifiedTime = 0;

						// modified time of the compressed file
						if (file_exists($minifiedFilename))
						{
							$minifiedLastModifiedTime = filemtime($minifiedFilename);
						}

						// create directory for compressed files
						else
						{
							$compileDir = IA_TMP . implode( IA_DS, array_slice(explode(IA_DS, $tmp), 0, -1) );
							iaCore::util()->makeDirCascade($compileDir, 0777, true);
						}

						if (file_exists($file))
						{
							$lastModified = filemtime($file);
						}

						if (($lastModified > $minifiedLastModifiedTime || $minifiedLastModifiedTime == 0) && $lastModified != 0)
						{
							// need to compress
							iaDebug::debug($minifiedFilename . ' - ' . $lastModified . ' - ' . $minifiedLastModifiedTime, 'compress', 'info');

							require_once IA_INCLUDES . 'utils' . IA_DS . 'Minifier.php';
							$minifiedCode = \JShrink\Minifier::minify(file_get_contents($file));

							file_put_contents($minifiedFilename, $minifiedCode);
							$lastModified = time();
						}

						$url = $iaView->assetsUrl . 'tmp/' . $tmp . self::EXTENSION_JS;
					}
				}

				if (!$remote && $lastModified > 0)
				{
					$url .= '?fm=' . $lastModified;
				}

				$iaView->resources->js->$url = $order;
			}
		}
		elseif (isset($params['code']))
		{
			$iaView->resources->js->{'code:' . $params['code']} = $order;
		}
		elseif (isset($params['text']))
		{
			$iaView->resources->js->{'text:' . $params['text']} = $order;
		}
	}

	public function add_css(array $params)
	{
		$iaView = &iaCore::instance()->iaView;

		if (isset($params['files']))
		{
			$iaCore = iaCore::instance();

			$files = $params['files'];
			if (is_string($files))
			{
				$files = explode(',', $files);
			}

			foreach ($files as $file)
			{
				$file = trim($file);
				$local = true;

				// NOTE: this check may treat an inclusion of a single local file
				// with name starting from "http..." as remote
				if ('http' == substr($file, 0, 4))
				{
					$url = $file;
					$local = false;
				}
				elseif (strpos($file, '_IA_URL_') !== false)
				{
					$url = str_replace('_IA_URL_', $iaView->assetsUrl, $file);
				}
				else
				{
					$url = IA_TPL_URL . 'css/' . $file;
					if (defined('IA_CURRENT_PACKAGE'))
					{
						$suffix = 'templates' . IA_DS . $iaView->theme . IA_DS . 'packages' . IA_DS . $iaView->get('extras') . IA_DS . 'css/' . $file;
						if (is_file(IA_HOME . $suffix . self::EXTENSION_CSS) && iaCore::ACCESS_FRONT == $iaCore->getAccessType())
						{
							$url = IA_CLEAR_URL . $suffix;
						}
					}
				}

				$url .= self::EXTENSION_CSS;

				if ($local)
				{
					$file = str_replace($iaView->assetsUrl, IA_HOME, $url);
					if ($modifiedTime = filemtime($file))
					{
						$url .= '?fm=' . $modifiedTime;
					}
				}

				$iaView->resources->css->$url = isset($params['order']) ? (int)$params['order'] : iaView::RESOURCE_ORDER_REGULAR;
			}
		}
	}

	/**
	 * Converts array items to language file string
	 *
	 * @param array $params array of values
	 */
	public static function arrayToLang($params)
	{
		$list = array();

		if ($array = explode(',', $params['values']))
		{
			foreach ($array as $value)
			{
				if ($title = iaLanguage::get('field_' . $params['name'] . '_' . trim($value)))
				{
					$list[] = $title;
				}
			}
		}

		echo implode(', ', $list);
	}

	/**
	 * Prints picture in the box uses for display listing thumbnails, listing full picture, member avatar
	 *
	 * @param array $params image params
	 *
	 * @return string
	 */
	public static function printImage($params)
	{
		$iaCore = iaCore::instance();

		$imageName = isset($params['gravatar']) ? 'no-avatar.png' : 'no-preview.png';

		$gravatarUrl = '';
		if ($iaCore->get('gravatar_enabled') && isset($params['gravatar']) && isset($params['email']))
		{
			$d = $iaCore->get('gravatar_default_image') ? IA_CLEAR_URL . $iaCore->get('gravatar_default_image') : $iaCore->get('gravatar_type');
			$s = isset($params['gravatar_width']) ? (int)$params['gravatar_width'] : $iaCore->get('gravatar_size');
			$r = $iaCore->get('gravatar_rating');

			$protocol = $iaCore->get('gravatar_secure') ? 'https' : 'http';
			$gravatarUrl = $protocol . '://www.gravatar.com/avatar/' . md5(strtolower(trim($params['email']))) . "?s=$s&d=$d&r=$r";
		}

		// temporary solution
		// TODO: remove
		if ('a:' == substr($params['imgfile'], 0, 2))
		{
			$array = unserialize($params['imgfile']);

			$params['imgfile'] = $array['path'];
			$params['title'] = isset($array['title']) ? $array['title'] : '';
		}
		//

		if (!empty($params['imgfile']))
		{
			$thumbUrl = $iaCore->iaView->assetsUrl . 'uploads/';
			if (isset($params['fullimage']) && $params['fullimage'])
			{
				$imgfile = explode('/', $params['imgfile']);
				$imgfile[count($imgfile) - 1] = str_replace('.', '~.', $imgfile[count($imgfile) - 1]);

				$thumbUrl .= implode('/', $imgfile);
			}
			else
			{
				$thumbUrl .= $params['imgfile'];
			}
		}
		else
		{
			if ($gravatarUrl)
			{
				$thumbUrl = $gravatarUrl;
			}
			else
			{
				$thumbUrl = IA_TPL_URL . 'img/' . $imageName;
			}
		}

		if (!empty($params['url']))
		{
			return $thumbUrl;
		}

		$width = isset($params['width']) ? ' width="' . $params['width'] . '"' : '';
		$height = isset($params['height']) ? ' height="' . $params['height'] . '"' : '';
		$title = isset($params['title']) ? iaSanitize::html($params['title']) : '';
		$class = isset($params['class']) ? ' class="' . $params['class'] . '"' : '';

		return sprintf(
			'<img src="%s" alt="%s" title="%s"%s>',
			$thumbUrl,
			$title,
			$title,
			$width . $height . $class
		);
	}

	/**
	 * Prints add/remove favorites icons
	 *
	 * @param array $params button params
	 *
	 * @return string
	 */
	public static function printFavorites($params, Smarty_Internal_Template &$smarty)
	{
		if (
			// no need to display for guests by default
			(!iaUsers::hasIdentity() && (!isset($params['guests']) || false === $params['guests'])) ||
			// missing params
			(empty($params['item']) || empty($params['itemtype'])) ||
			// no need to display for self owned items
			(iaUsers::hasIdentity() && isset($params['item']['member_id']) && iaUsers::getIdentity()->id === $params['item']['member_id']) ||
			// no need to bookmark own account
			(iaUsers::hasIdentity() && iaUsers::getItemName() == $params['itemtype'] && iaUsers::getIdentity()->id == $params['item']['id'])
		)
		{
			return false;
		}

		// generate replacements array
		$_replace = array(
			'id' => (int)$params['item']['id'],
			'item' => $params['itemtype'],
			'class' => isset($params['classname']) ? $params['classname'] : '',
			'guests' => isset($params['guests']) ? (bool)$params['guests'] : false,
			'action' => (isset($params['item']['favorite']) && $params['item']['favorite'] == '1') ? 'delete' : 'add'
		);
		$_replace['text'] = 'favorites_action_' . $_replace['action'];
		$smarty->assign('replace', $_replace);

		$template = 'favorites-button' . (isset($params['tpl']) ? '-' . $params['tpl'] : '') . iaView::TEMPLATE_FILENAME_EXT;
		$output = $smarty->fetch($template);

		return $output;
	}

	public static function accountActions($params)
	{
		if (!iaUsers::hasIdentity()
			|| empty($params['item'])
			|| empty($params['itemtype'])
			|| (iaUsers::getItemName() == $params['itemtype'] && iaUsers::getIdentity()->id != $params['item']['id'])
			|| (iaUsers::getItemName() != $params['itemtype'] && isset($params['item']['member_id']) && iaUsers::getIdentity()->id != $params['item']['member_id'])
		)
		{
			return '';
		}

		$iaCore = iaCore::instance();
		$iaItem = $iaCore->factory('item');

		$params['img'] = $img = IA_CLEAR_URL . 'templates/' . $iaCore->iaView->theme . '/img/';
		$classname = isset($params['classname']) ? $params['classname'] : '';

		$upgradeUrl = '';
		$editUrl = '';
		$extraActions = '';
		$output = '';

		if (iaUsers::getItemName() == $params['itemtype'])
		{
			$editUrl = IA_URL . 'profile/';
		}
		else
		{
			$item = $iaItem->getPackageByItem($params['itemtype']);
			if (empty($item))
			{
				return '';
			}
			$iaPackage = $iaCore->factoryPackage('item', $item, iaCore::FRONT, $params['itemtype']);
			if (empty($iaPackage))
			{
				return '';
			}
			if (method_exists($iaPackage, __FUNCTION__))
			{
				list($editUrl, $upgradeUrl) = $iaPackage->{__FUNCTION__}($params);
			}
			if (method_exists($iaPackage, 'extraActions'))
			{
				$extraActions = $iaPackage->extraActions($params['item']);
			}
		}
		$iaCore->startHook('phpSmartyAccountActionsBeforeShow',
			array('params' => &$params, 'type' => $params['itemtype'], 'upgrade_url' => &$upgradeUrl, 'edit_url' => &$editUrl, 'output' => &$output));

		if ($editUrl)
		{
			$output .= '<a rel="nofollow" href="' . $editUrl . '" class="' . $classname . '" title="' . iaLanguage::get('edit') . '"><span class="fa fa-pencil"></span> ' . iaLanguage::get('edit') . '</a>';
		}

		return $output . $extraActions;
	}

	public static function ia_block(array $params, $content, Smarty_Internal_Template &$smarty)
	{
		$result = '';

		if (trim($content))
		{
			$smarty->assign('collapsible', isset($params['collapsible']) ? $params['collapsible'] : false);
			$smarty->assign('collapsed', isset($params['collapsed']) ? $params['collapsed'] : false);
			$smarty->assign('hidden', isset($params['hidden']) ? $params['hidden'] : false);
			$smarty->assign('icons', isset($params['icons']) ? $params['icons'] : array());
			$smarty->assign('id', isset($params['id']) ? $params['id'] : null);
			$smarty->assign('header', isset($params['header']) ? $params['header'] : true);
			$smarty->assign('name', isset($params['name']) ? $params['name'] : '');
			$smarty->assign('classname', isset($params['classname']) ? $params['classname'] : '');
			$smarty->assign('style', isset($params['style']) ? $params['style'] : '');
			$smarty->assign('title', isset($params['title']) ? $params['title'] : '');
			$smarty->assign('ismenu', isset($params['ismenu']) ? $params['ismenu'] : false);
			$smarty->assign('_block_content_', $content);

			if (!isset($params['tpl']) || empty($params['tpl']))
			{
				$params['tpl'] = 'block.tpl';
			}

			$result = $smarty->fetch($params['tpl']);
		}

		return $result;
	}

	public static function access($params, $content)
	{
		if (empty($content) || !isset($params['object']))
		{
			return '';
		}

		$user = isset($params['user']) ? (int)$params['user'] : 0;
		$group = isset($params['group']) ? (int)$params['group'] : 0;
		$objectId = isset($params['id']) ? $params['id'] : 0;
		$action = isset($params['action']) ? $params['action'] : iaCore::ACTION_READ;
		$object = $params['object'];

		return iaCore::instance()->factory('acl')->checkAccess($object . ':' . $action, $objectId, $user, $group)
			? $content
			: '';
	}

	public static function ia_add_js($params, $content)
	{
		if (!trim($content))
		{
			return;
		}

		$iaView = &iaCore::instance()->iaView;
		$iaView->resources->js->{'code:' . $content} = isset($params['order']) ? $params['order'] : 4;
	}

	public static function ia_print_js($params, Smarty_Internal_Template &$smarty)
	{
		$smarty->add_js($params);

		if (!isset($params['display']))
		{
			return '';
		}

		$iaCore = iaCore::instance();
		$resources = self::_arrayCopyKeysSorted($iaCore->iaView->resources->js);

		$output = '';
		foreach ($resources as $resource)
		{
			switch (true)
			{
				case (strpos($resource, 'code:') === 0):
					if ($code = trim(substr($resource, 5)))
					{
						$output .= PHP_EOL . "\t" . '<script type="text/javascript"><!-- ' . PHP_EOL . $code . PHP_EOL . ' --></script>';
					}
					continue;
				case (strpos($resource, 'text:') === 0):
					if (iaUsers::hasIdentity() && iaCore::ACCESS_ADMIN == iaCore::instance()->getAccessType())
					{
						$text = trim(substr($resource, 5));
						$output .= "<script type=\"text/javascript\">if(document.getElementById('js-ajax-loader-status'))document.getElementById('js-ajax-loader-status').innerHTML = '" . $text . "';</script>" . PHP_EOL;
					}
					continue;
				default:
					$output .= PHP_EOL . "\t" . sprintf(self::LINK_SCRIPT_PATTERN, $resource);
			}
		}

		return $output;
	}

	public static function captcha($params)
	{
		$preview = isset($params['preview']);

		$iaCore = iaCore::instance();

		if ($captchaName = $iaCore->get('captcha_name'))
		{
			$iaCaptcha = $iaCore->factoryPlugin($captchaName, iaCore::FRONT, 'captcha');

			return $preview
				? $iaCaptcha->getPreview()
				: $iaCaptcha->getImage();
		}

		if ($preview)
		{
			return iaLanguage::get('no_captcha_preview');
		}

		return '';
	}

	public static function ia_blocks(array $params, Smarty_Internal_Template &$smarty)
	{
		if (!isset($params['block']))
		{
			return '';
		}

		$directCall = isset($params[self::DIRECT_CALL_MARKER]);
		$position = $params['block'];

		// return immediately if position's content is already rendered
		if (!$directCall && isset(self::$_positionsContent[$position]))
		{
			// NULL will be an empty content marker
			return is_null(self::$_positionsContent[$position]) ? '' : self::$_positionsContent[$position];
		}

		// mark that we were here
		self::$_positionsContent[$position] = null;

		$iaView = iaCore::instance()->iaView;
		$blocks = $iaView->blocks;
		$blocks = isset($blocks[$position]) ? $blocks[$position] : null;

		if ($blocks || $iaView->manageMode)
		{
			// define if this position should be movable in visual mode
			$smarty->assign('position', $position);
			$smarty->assign('blocks', $blocks);

			$output = $smarty->fetch('render-blocks' . iaView::TEMPLATE_FILENAME_EXT, $position . mt_rand(1000, 9999));

			if (trim($output))
			{
				self::$_positionsContent[$position] = $output;
			}
		}

		return $directCall ? null : self::$_positionsContent[$position];
	}

	public static function width(array $params, Smarty_Internal_Template &$smarty)
	{
		$position = isset($params['position']) ? $params['position'] : 'center';
		$section = isset($params['section']) ? $params['section'] : 'content';

		$iaCore = iaCore::instance();

		$layoutData = $iaCore->get('tmpl_layout_data');
		$layoutData = empty($layoutData) ? array() : unserialize($layoutData);

		// pre-compilation of section's positions
		if (isset($layoutData[$section]))
		{
			foreach ($layoutData[$section] as $positionName => $options)
			{
				if (!isset(self::$_positionsContent[$positionName]))
				{
					self::ia_blocks(array('block' => $positionName, self::DIRECT_CALL_MARKER => true), $smarty);
				}
			}
		}

		$positions = array_keys(array_filter(self::$_positionsContent));
		$positions[] = 'center';

		if (!in_array($position, $positions))
		{
			$width = 0;
		}
		else
		{
			$width = 3; // default width

			// start to calculate a width specific to the Bootstrap CSS framework
			if (isset($layoutData[$section][$position]))
			{
				$sectionPositions = $layoutData[$section];

				if ($sectionPositions[$position]['fixed'])
				{
					$width = $sectionPositions[$position]['width'];
				}
				else
				{
					$unitsToDistribute = 0;
					$positionWidth = array();
					$flexiblePositions = array();

					// composing initial data
					foreach ($sectionPositions as $positionName => $options)
					{
						in_array($positionName, $positions)
							? $positionWidth[$positionName] = $options['width']
							: $unitsToDistribute += $options['width'];
						$options['fixed'] || $flexiblePositions[] = $positionName;
					}

					// if we need to distribute a width of hidden positions
					if ($flexiblePositions)
					{
						reset($positionWidth);
						while ($unitsToDistribute)
						{
							$key = key($positionWidth);
							if (is_null($key))
							{
								reset($positionWidth);
								$key = key($positionWidth);
							}
							// simply increment a width of flexible positions
							if (in_array($key, $flexiblePositions))
							{
								$positionWidth[$key]++;
								$unitsToDistribute--;
							}
							next($positionWidth);
						}
					}

					// width calculation
					$width = 12;
					foreach ($sectionPositions as $positionName => $options)
					{
						if ($positionName != $position && in_array($positionName, $positions))
						{
							$width -= $positionWidth[$positionName];
						}
					}
				}
			}
		}

		$tag = isset($params['tag']) ? $params['tag'] : 'span';

		return $tag . $width;
	}

	public static function pagination($params, &$smarty)
	{
		$output = '';

		$limit = $params['aItemsPerPage'];
		$total = $params['aTotal'];
		$ignoreParams = isset($params['aIgnore']);

		if ($total > $limit)
		{
			$buttonsNumber = isset($params['aNumPageItems']) ? (int)$params['aNumPageItems'] : 5;
			$pagesCount = ceil($total / $limit);
			$currentPage = min($pagesCount, isset($_GET['page']) && (int)$_GET['page'] ? (int)$_GET['page'] : 1);

			$first = max(1, $currentPage - (ceil($buttonsNumber / 2) - 1));
			$last = min($first + $buttonsNumber - 1, $pagesCount);
			$first = max(1, $last - $buttonsNumber + 1);

			$pages = array();
			$urlPattern = $params['aTemplate'];
			foreach (range($first, $last) as $pageNumber)
			{
				if (!$ignoreParams)
				{
					$url = str_replace('{page}', $pageNumber, $urlPattern);
				}

				if (1 == $pageNumber)
				{
					$url = $ignoreParams ? $urlPattern : preg_replace('#(\?|&|_)(.*?)({page})#', '', $urlPattern);
				}

				if ($ignoreParams)
				{
					$url = str_replace('{page}', $pageNumber, $urlPattern);
				}

				$pages[$pageNumber] = $url;
			}

			$params = array(
				'current_page' => $currentPage,
				'first_page' => $ignoreParams ? $urlPattern : preg_replace('#(\?|&|_)(.*?)({page})#', '', $urlPattern),
				'last_page' => str_replace('{page}', $pagesCount, $urlPattern),
				'pages_count' => $pagesCount,
				'pages_range' => $pages
			);

			if ($ignoreParams)
			{
				$params['first_page'] = str_replace('{page}', 1, $params['first_page']);
			}

			$smarty->assign('_pagination', $params);

			$output = $smarty->fetch('pagination.tpl');
		}

		return $output;
	}


	private static function _arrayCopyKeysSorted(ArrayObject $array)
	{
		$a = array();
		foreach ($array as $key => $value)
		{
			isset($a[$value]) || $a[$value] = array();
			$a[$value][] = $key;
		}
		ksort($a, SORT_NUMERIC);
		$result = array();
		foreach ($a as $values)
		{
			foreach ($values as $value)
			{
				$result[] = $value;
			}
		}

		return $result;
	}

	public static function ia_print_title($params)
	{
		$suffix = iaCore::instance()->get('suffix');
		$title = empty($params['title']) ? iaCore::instance()->iaView->get('title') : $params['title'];

		return $title . ' ' . $suffix;
	}

	public static function displayTreeNodes(array $params)
	{
		$ids = explode(',', $params['ids']);
		$nodes = iaCore::instance()->factory('util')->jsonDecode($params['nodes']);

		$result = array();

		foreach ($nodes as $node)
		{
			if (in_array($node['id'], $ids)) $result[] = $node['text'];
		}

		return implode(', ', $result);
	}
}