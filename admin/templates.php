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
				$iaView->setMessages(iaLanguage::getf('template_installed', array('name' => $this->getHelper()->title)), iaView::SUCCESS);

				$this->_iaCore->iaCache->clearAll();

				$this->_iaCore->factory('log')->write(iaLog::ACTION_INSTALL, array('type' => 'template', 'name' => $this->getHelper()->title));

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

		$templates = $this->_getList();

		if ($this->_messages)
		{
			$iaView->setMessages($this->_messages, $this->_error ? iaView::ERROR : iaView::SUCCESS);
		}

		$iaView->assign('templates', $templates);
		$iaView->assign('tmpl', $this->_iaCore->get('tmpl'));
	}

	protected function _gridRead()
	{
		$output = array('error' => true, 'message' => iaLanguage::get('invalid_parameters'));

		if (isset($_POST['get']) && 'info' == $_POST['get'])
		{
			$output = array('tabs' => array());
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
							$output['tabs'][] = array(
								'title' => iaLanguage::get('extra_' . $tab, $tab),
								'html' => ('changelog' == $tab ? preg_replace('/#(\d+)/', '<a href="http://dev.subrion.org/issues/$1" target="_blank">#$1</a>', $contents) : $contents),
								'cls' => 'extension-docs'
							);
						}
					}
				}

				$iaTemplate = $this->getHelper();

				$iaTemplate->getFromPath(IA_FRONT_TEMPLATES . $template . IA_DS . iaTemplate::INSTALL_FILE_NAME);
				$iaTemplate->parse();

				$replacements = array(
					'{icon}' => file_exists(IA_FRONT_TEMPLATES . $template . IA_DS . 'docs' . IA_DS . 'img/icon.png') ? '<tr><td class="plugin-icon"><img src="' . $this->_iaCore->iaView->assetsUrl . 'templates/' . $template . '/docs/img/icon.png" alt=""></td></tr>' : '',
					'{link}' => '<tr><td><a href="http://www.subrion.org/template/' . $template . '.html" class="btn btn-block btn-info" target="_blank">Additional info</a><br></td></tr>',
					'{name}' => $iaTemplate->title,
					'{author}' => $iaTemplate->author,
					'{contributor}' => $iaTemplate->contributor,
					'{version}' => $iaTemplate->version,
					'{date}' => $iaTemplate->date,
					'{compatibility}' => $iaTemplate->compatibility,
				);

				$output['info'] = str_replace(array_keys($replacements), array_values($replacements),
					file_get_contents(IA_ADMIN . 'templates' . IA_DS . $this->_iaCore->get('admin_tmpl') . IA_DS . 'extra_information.tpl'));
			}
		}

		return $output;
	}

	private function _getList()
	{
		$templates = $this->getHelper()->getList(); // get list of available local templates
		$remoteTemplates = array();

		if ($this->_iaCore->get('allow_remote_templates'))
		{
			if ($cachedData = $this->_iaCore->iaCache->get('subrion_templates', 3600, true))
			{
				$remoteTemplates = $cachedData; // get templates list from cache, cache lives for 1 hour
			}
			else
			{
				if ($response = iaUtil::getPageContent(iaUtil::REMOTE_TOOLS_URL . 'list/template/' . IA_VERSION))
				{
					$response = iaUtil::jsonDecode($response);
					if (!empty($response['error']))
					{
						$this->_messages[] = $response['error'];
						$this->_error = true;
					}
					elseif ($response['total'] > 0)
					{
						if (isset($response['extensions']) && is_array($response['extensions']))
						{
							foreach ($response['extensions'] as $entry)
							{
								$templateInfo = (array)$entry;

								// exclude installed templates
								if (!array_key_exists($templateInfo['name'], $templates))
								{
									$templateInfo['date'] = gmdate(iaDb::DATE_FORMAT, $templateInfo['date']);
									$templateInfo['buttons'] = '';
									$templateInfo['notes'] = array();
									$templateInfo['remote'] = true;

									$remoteTemplates[] = $templateInfo;
								}
							}

							// cache well-formed results
							$this->_iaCore->iaCache->write('subrion_templates', $remoteTemplates);
						}
						else
						{
							$this->addMessage('error_incorrect_format_from_subrion');
							$this->_error = true;
						}
					}
				}
				else
				{
					$this->addMessage('error_incorrect_response_from_subrion');
					$this->_error = true;
				}
			}
		}

		return array_merge($templates, $remoteTemplates);
	}

	private function _installTemplate()
	{
		if (empty($_POST['template']))
		{
			$this->_error = true;
			$this->_messages[] = iaLanguage::get('template_name_empty');
		}

		$templateName = $_POST['template'];

		if (!is_dir(IA_FRONT_TEMPLATES . $templateName) && !$this->_error)
		{
			$this->_error = true;
			$this->_messages[] = iaLanguage::get('template_folder_error');
		}

		$infoXmlFile = IA_FRONT_TEMPLATES . $templateName . IA_DS . iaTemplate::INSTALL_FILE_NAME;

		if (!file_exists($infoXmlFile) && !$this->_error)
		{
			$this->_error = true;
			$this->_messages[] = iaLanguage::getf('template_file_error', array('file' => $templateName));
		}

		if (!$this->_error)
		{
			$iaTemplate = $this->getHelper();

			$iaTemplate->getFromPath($infoXmlFile);
			$iaTemplate->parse();
			$iaTemplate->check();
			$iaTemplate->rollback();
			$iaTemplate->install();

			if (!$iaTemplate->error)
			{
				return true;
			}

			$this->_error = true;
			$this->_messages[] = $iaTemplate->getMessage();
		}

		return false;
	}

	private function _downloadTemplate()
	{
		$templateName = $_POST['download'];
		$templatesTempFolder = IA_TMP . 'templates' . IA_DS;
		if (!is_dir($templatesTempFolder))
		{
			mkdir($templatesTempFolder);
		}

		$filePath = $templatesTempFolder . $templateName;
		$fileName = $filePath . '.zip';

		// save remote template file
		iaUtil::downloadRemoteContent(iaUtil::REMOTE_TOOLS_URL . 'install/' . $templateName . IA_URL_DELIMITER . IA_VERSION, $fileName);

		if (file_exists($fileName))
		{
			if (is_writable(IA_FRONT_TEMPLATES))
			{
				// delete previous folder
				if (is_dir(IA_FRONT_TEMPLATES . $templateName))
				{
					unlink(IA_FRONT_TEMPLATES . $templateName);
				}

				include_once (IA_INCLUDES . 'utils' . IA_DS . 'pclzip.lib.php');

				$pclZip = new PclZip($fileName);
				$pclZip->extract(PCLZIP_OPT_PATH, IA_FRONT_TEMPLATES . $templateName);

				$this->addMessage(iaLanguage::getf('template_downloaded', array('name' => $templateName)), false);

				return true;
			}
			else
			{
				$this->_error = true;
				$this->addMessage('upload_template_error');
			}
		}

		return false;
	}
}