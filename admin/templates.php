<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2014 Intelliants, LLC <http://www.intelliants.com>
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

$iaTemplate = $iaCore->factory('template', iaCore::ADMIN);

// process ajax actions
if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	if ('info' == $_POST['get'])
	{
		$output = array('tabs' => null);
		$template = $_POST['name'];

		$documentationPath = IA_FRONT_TEMPLATES . $template . IA_DS . 'info' . IA_DS;

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

			$iaTemplate->getFromPath($documentationPath . iaTemplate::INSTALL_FILE_NAME);
			$iaTemplate->parse();

			$search = array(
				'{icon}',
				'{name}',
				'{author}',
				'{contributor}',
				'{version}',
				'{date}',
				'{compatibility}',
			);

			$icon = '&nbsp;';

			$replacement = array(
				$icon,
				$iaTemplate->title,
				$iaTemplate->author,
				$iaTemplate->contributor,
				$iaTemplate->version,
				$iaTemplate->date,
				$iaTemplate->compatibility
			);

			$output['info'] = str_replace($search, $replacement,
				file_get_contents(IA_ADMIN . 'templates' . IA_DS . $iaCore->get('admin_tmpl') . IA_DS . 'extra_information.tpl'));
		}

		$iaView->assign($output);
	}
}

// process page display
if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$messages = array();
	$error = false;

	$iaCache = $iaCore->factory('cache');

	// set default template
	if (isset($_POST['set_template']) || isset($_POST['reinstall']))
	{
		$template = $_POST['template'];

		if (empty($template))
		{
			$error = true;
			$messages[] = iaLanguage::get('template_empty');
		}
		if (!is_dir(IA_FRONT_TEMPLATES . $template) && !$error)
		{
			$error = true;
			$messages[] = iaLanguage::get('template_folder_error');
		}

		$infoXmlFile = IA_FRONT_TEMPLATES . $template . IA_DS . 'info' . IA_DS . iaTemplate::INSTALL_FILE_NAME;

		if (!file_exists($infoXmlFile) && !$error)
		{
			$error = true;
			$messages[] = iaLanguage::getf('template_file_error', array('file' => $template));
		}

		if (!$error)
		{
			$iaTemplate->getFromPath($infoXmlFile);
			$iaTemplate->parse();
			$iaTemplate->check();
			$iaTemplate->rollback();
			$iaTemplate->install();

			if ($iaTemplate->error)
			{
				$error = true;
				$messages[] = $iaTemplate->getMessage();
			}
			else
			{
				$iaView->setMessages(iaLanguage::getf('template_installed', array('name' => $iaTemplate->title)), iaView::SUCCESS);

				$iaCache->clearAll();

				$iaCore->factory('log')->write(iaLog::ACTION_INSTALL, array('type' => 'template', 'name' => $iaTemplate->title));

				iaUtil::go_to(IA_SELF);
			}
		}
	}

	// download template
	if (isset($_POST['download_template']))
	{
		$templateName = $_POST['download_template'];
		$templatesTempFolder = IA_TMP . 'templates' . IA_DS;
		if (!is_dir($templatesTempFolder))
		{
			mkdir($templatesTempFolder);
		}

		$filePath = $templatesTempFolder . $templateName;
		$fileName = $filePath . '.zip';

		// save remote template file
		$iaCore->util()->downloadRemoteContent('http://tools.subrion.com/download-template/?name=' . $templateName . '&version=' . IA_VERSION, $fileName);

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
				$files = $pclZip->extract(PCLZIP_OPT_PATH, IA_FRONT_TEMPLATES);

				$messages[] = iaLanguage::getf('template_downloaded', array('name' => $templateName));

				$iaCache->remove('subrion_templates.inc');
			}
			else
			{
				$error = true;
				$messages[] = iaLanguage::get('upload_template_error');
			}
		}
	}

	// get list of available local templates
	$templates = $iaTemplate->getList();

	// get templates list from cache, cache lives for 1 hour
	$remoteTemplates = array();
	if ($cachedData = $iaCache->get('subrion_templates', 3600, true))
	{
		$remoteTemplates = $cachedData;
	}
	else
	{
		if ($response = $iaCore->util()->getPageContent('http://tools.subrion.com/downloads/?type=templates&version=' . IA_VERSION))
		{
			$response = $iaCore->util()->jsonDecode($response);
			if (!empty($response['error']))
			{
				$messages[] = $response['error'];
				$error = true;
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
							$templateInfo['date'] = gmdate("Y-m-d", $templateInfo['date']);
							$templateInfo['buttons'] = '';
							$templateInfo['notes'] = array();
							$templateInfo['remote'] = 1;

							$remoteTemplates[] = $templateInfo;
						}
					}

					// cache well-formed results
					$iaCache->write('subrion_templates', $remoteTemplates);
				}
				else
				{
					$messages[] = iaLanguage::get('error_incorrect_format_from_subrion');
					$error = true;
				}
			}
		}
		else
		{
			$messages[] = iaLanguage::get('error_incorrect_response_from_subrion');
			$error = true;
		}
	}

	if ($messages)
	{
		$iaView->setMessages($messages, $error ? iaView::ERROR : iaView::SUCCESS);
	}

	$iaView->assign('templates', isset($remoteTemplates) ? array_merge($templates, $remoteTemplates) : $templates);
	$iaView->assign('tmpl', $iaCore->get('tmpl'));

	$iaView->display('templates');
}