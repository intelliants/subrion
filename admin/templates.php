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

class iaBackendController extends iaAbstractControllerBackend
{
	protected $_name = 'templates';

	private $_error = false;


	public function __construct()
	{
		parent::__construct();

		$iaTemplate = $this->_iaCore->factory('template', iaCore::ADMIN);
		$this->setHelper($iaTemplate);
	}

	protected function _indexPage(&$iaView)
	{
		// set default template
		if (isset($_POST['install']) || isset($_POST['reinstall']))
		{
			if ($this->_installTemplate())
			{
				$iaView->setMessages(iaLanguage::getf('template_installed', ['name' => $this->getHelper()->title]), iaView::SUCCESS);

				$this->_iaCore->iaCache->clearAll();

				$this->_iaCore->factory('log')->write(iaLog::ACTION_INSTALL, ['type' => 'template', 'name' => $this->getHelper()->title]);

				iaUtil::go_to(IA_SELF);
			}
		}

		// download template
		if (isset($_POST['download']))
		{
			if ($this->_downloadTemplate())
			{
				$this->_iaCore->iaCache->remove('subrion_templates');
			}
		}
	}

	protected function _gridRead($params)
	{
		$output = ['error' => true, 'message' => iaLanguage::get('invalid_parameters')];

		if (isset($_POST['get']) && 'info' == $_POST['get'])
		{
			$output = ['tabs' => []];
			$template = $_POST['name'];

			$documentationPath = IA_FRONT_TEMPLATES . $template . IA_DS . 'docs' . IA_DS;

			// make sure template information exists
			if (file_exists($documentationPath) && is_dir($documentationPath))
			{
				$docs = scandir($documentationPath);

				foreach ($docs as $doc)
				{
					if (substr($doc, 0, 1) != '.' && 'html' == substr($documentationPath . $doc, -4))
					{
						if (is_file($documentationPath . $doc))
						{
							$tab = substr($doc, 0, count($doc) - 6);
							$contents = file_get_contents($documentationPath . $doc);
							$output['tabs'][] = [
								'title' => iaLanguage::get('extra_' . $tab, $tab),
								'html' => ('changelog' == $tab ? preg_replace('/#(\d+)/', '<a href="https://dev.subrion.org/issues/$1" target="_blank">#$1</a>', $contents) : $contents),
								'cls' => 'extension-docs'
							];
						}
					}
				}

				$iaTemplate = $this->getHelper();

				$iaTemplate->getFromPath(IA_FRONT_TEMPLATES . $template . IA_DS . iaTemplate::INSTALL_FILE_NAME);
				$iaTemplate->parse();

				$replacements = [
					'{icon}' => file_exists(IA_FRONT_TEMPLATES . $template . IA_DS . 'docs' . IA_DS . 'img/icon.png') ? '<tr><td class="plugin-icon"><img src="' . $this->_iaCore->iaView->assetsUrl . 'templates/' . $template . '/docs/img/icon.png" alt=""></td></tr>' : '',
					'{link}' => '<tr><td><a href="https://subrion.org/template/' . $template . '.html" class="btn btn-block btn-info" target="_blank">Additional info</a><br></td></tr>',
					'{name}' => $iaTemplate->title,
					'{author}' => $iaTemplate->author,
					'{contributor}' => $iaTemplate->contributor,
					'{version}' => $iaTemplate->version,
					'{date}' => $iaTemplate->date,
					'{compatibility}' => $iaTemplate->compatibility,
				];

				$output['info'] = str_replace(array_keys($replacements), array_values($replacements),
					file_get_contents(IA_ADMIN . 'templates' . IA_DS . $this->_iaCore->get('admin_tmpl') . IA_DS . 'extra_information.tpl'));
			}
		}

		return $output;
	}



}