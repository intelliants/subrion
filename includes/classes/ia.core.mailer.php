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

require_once IA_INCLUDES . 'phpmailer' . IA_DS . 'class.phpmailer.php';

class iaMailer extends PHPMailer
{
	protected $_replacements = array();

	protected $_iaCore;

	protected $_bccEmails;

	protected $_defaultSignature;


	/*
	 * Set replacements for email body
	 *
	 * @param array of key/value pairs or $key, $value parameters
	 */
	public function setReplacements()
	{
		$replacements = array();

		switch (func_num_args())
		{
			case 1:
				$values = func_get_arg(0);
				if (is_array($values))
				{
					$replacements = $values;
				}
				break;

			case 2:
				$key = func_get_arg(0);
				$value = func_get_arg(1);

				if (is_string($key) && is_string($value))
				{
					$replacements[$key] = $value;
				}
		}

		if ($replacements)
		{
			foreach ($replacements as $key => $value)
			{
				$keyPattern = '{%' . strtoupper($key) . '%}';
				$this->_replacements[$keyPattern] = $value;
			}
		}
	}

	protected function _applyReplacements()
	{
		$this->Body = str_replace(array_keys($this->_replacements), array_values($this->_replacements), $this->Body);
		$this->Subject = str_replace(array_keys($this->_replacements), array_values($this->_replacements), $this->Subject);
	}

	public function init()
	{
		$this->_iaCore = iaCore::instance();

		$this->CharSet = 'UTF-8';
		$this->From = $this->_iaCore->get('site_email');
		$this->FromName = $this->_iaCore->get('site_from_name', 'Subrion CMS');
		$this->SingleTo = true;

		$this->isHTML($this->_iaCore->get('mimetype'));

		switch ($this->_iaCore->get('mail_function'))
		{
			case 'smtp':
				$this->isSMTP();

				$this->Host = $this->_iaCore->get('smtp_server');
				$this->SMTPAuth = (bool)$this->_iaCore->get('smtp_auth');
				$this->Username = $this->_iaCore->get('smtp_user');
				$this->Password = $this->_iaCore->get('smtp_password');
				$this->SMTPSecure = strtolower($this->_iaCore->get('smtp_secure'));
				$this->SMTPDebug = (bool)$this->_iaCore->get('smtp_debug') ? 3 : 0;

				if ($port = $this->_iaCore->get('smtp_port'))
				{
					$this->Port = (int)$port;
				}

				break;

			case 'sendmail':
				$this->isSendmail();
				$this->Sendmail = $this->_iaCore->get('sendmail_path');

				break;

			default: // PHP's mail function
				$this->isMail();
		}

		// global patterns
		$this->setReplacements(array(
			'site_url' => IA_URL,
			'site_name' => $this->_iaCore->get('site'),
			'site_email' => $this->_iaCore->get('site_email')
		));
	}

	/*
	 * Load email template
	 * Mail subject loaded as well
	 *
	 * @param string $name template name
	 */
	public function loadTemplate($name)
	{
		$this->Subject = $this->_iaCore->get($name . '_subject');
		$this->Body = $this->_iaCore->get($name . '_body');

		$options = json_decode($this->_iaCore->iaDb->one('options', iaDb::convertIds($name, 'name'), iaCore::getConfigTable()));
		$this->_defaultSignature = empty($options->signature);
	}

	public function sendToAdministrators()
	{
		$where = '`usergroup_id` = :group AND `status` = :status';
		$this->_iaCore->iaDb->bind($where, array('group' => iaUsers::MEMBERSHIP_ADMINISTRATOR, 'status' => iaCore::STATUS_ACTIVE));

		$administrators = $this->_iaCore->iaDb->all(array('email', 'fullname'), $where, null, null, iaUsers::getTable());

		if (!$administrators)
		{
			return false;
		}

		foreach ($administrators as $entry)
		{
			$this->addAddress($entry['email'], $entry['fullname']);
		}

		return $this->send(true);
	}

	protected function _setBcc()
	{
		if (is_null($this->_bccEmails))
		{
			$bccEmail = $this->_iaCore->get('bcc_email');
			$array = explode(',', $bccEmail);

			$this->_bccEmails = array_map('trim', $array);
		}

		foreach ($this->_bccEmails as $email)
		{
			$this->addBCC($email);
		}
	}

	public function send($toAdmins = false)
	{
		$this->_applyReplacements();
		$this->_setBcc();
		if ($this->Body && $this->_defaultSignature && !$toAdmins)
		{
			$this->Body .= $this->_iaCore->get('default_email_signature');
		}

		$result = (bool)parent::send();

		if (!$result)
		{
			iaDebug::debug($this->ErrorInfo, 'Email submission');
		}

		parent::clearAllRecipients();

		return $result;
	}

	public function getError()
	{
		return $this->ErrorInfo;
	}
}