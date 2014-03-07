<?php
//##copyright##

require_once IA_INCLUDES . 'phpmailer' . IA_DS . 'class.phpmailer.php';

class iaMailer extends PHPMailer
{
	protected $_table = 'mailer';

	public $iaCore;
	public $replace = array(); // fill replace rules in the constructor


	public function getTable()
	{
		return $this->_table;
	}

	public function init()
	{
		$this->iaCore = iaCore::instance();

		$this->Mailer = 'sendmail' == $this->iaCore->get('mail_function') || 'smtp' == $this->iaCore->get('mail_function')
			? $this->iaCore->get('mail_function') : 'mail';
		$this->CharSet = $this->iaCore->get('charset');
		$this->IsHTML($this->iaCore->get('mimetype') ? true : false);
		$this->From = $this->iaCore->get('site_email');
		$this->FromName = $this->iaCore->get('site_from_name', 'Subrion CMS');
		$this->Sendmail = $this->iaCore->get('sendmail_path');
		$this->SingleTo = true;

		// PROPERTIES FOR SMTP
		$this->Host = $this->iaCore->get('smtp_server');
		$this->SMTPAuth = (bool)$this->iaCore->get('smtp_auth');
		$this->Username = $this->iaCore->get('smtp_user');
		$this->Port = (int)$this->iaCore->get('smtp_port');
		$this->Port || $this->Port = 25;
		$this->Password = $this->iaCore->get('smtp_password');

		// replace templates
		$this->replace = array(
			'{%SITE_URL%}' => IA_URL,
			'{%SITE_NAME%}' => $this->iaCore->get('site'),
			'{%SITE_EMAIL%}' => $this->iaCore->get('site_email')
		);
	}

	/*
	 * Load Subrion email template
	 * Mail subject loads also
	 *
	 * @param string $name template name
	 */
	public function load_template($name)
	{
		$this->Subject = $this->iaCore->get($name . '_subject');
		$this->Body = $this->iaCore->get($name . '_body');
	}

	/**
	 * Append mail to mass mailer queue
	 * {%FULLNAME%} in the body will be replaced with user full name
	 *
	 * @param mixed $aRctp recipients
	 * @param string $aSubj mail subject
	 * @param string $aBody mail body
	 * @param string $aFrom from address
	 */

	// ACTUALLY NOT USED
	/*
	public function queue($aRctp, $aSubj, $aBody, $aFrom = '')
	{
		$iaDb = &$this->iaCore->iaDb;
		$name_replace = false; // insert full name in the body
		$aRctp = (array)$aRctp;

		$aSubj = str_replace(array_keys($this->replace), array_values($this->replace), $aSubj);
		$aBody = str_replace(array_keys($this->replace), array_values($this->replace), $aBody);

		$mail = array(
			'subj' => $aSubj,
			'body' => $aBody,
			'from' => $this->From,
			'html' => (false !== strpos($this->ContentType, 'html')),
			'group_id' => time()
		);

		// fetch full names from DB
		if (false !== strpos($aBody, '{%FULLNAME%}')) {
			$names = $iaDb->keyvalue(
				'`email`, `fullname`',
				"`email` IN ('" . implode("','", $aRctp) . "')",
				iaUsers::getTable()
			);
			$name_replace = true;
		}

		// append mails to queue
		foreach ($aRctp as $to)
		{
			$mail['to'] = $to;
			if ($name_replace) {
				$mail['body'] = str_replace(
					'{%FULLNAME%}',
					(array_key_exists($to, $names) ? $names[$to] : $to),
					$aBody
				);
			}

			$iaDb->insert($mail, null, 'mailer');
		}
	}
*/
	public function Send($ClearAddresses = false)
	{
		$this->Body = str_replace(array_keys($this->replace), array_values($this->replace), $this->Body);
		$this->Subject = str_replace(array_keys($this->replace), array_values($this->replace), $this->Subject);
		$this->isHTML(true);

		$return = parent::Send();

		if ($ClearAddresses)
		{
			parent::ClearAddresses();
		}

		return $return;
	}

	/**
	 * dispatcher
	 *
	 * Sends email by the given action
	 *
	 * @param arr $event event info (listing, category etc)
	 * @access public
	 * @return void
	 */
/*	function dispatcher(&$event)
	{
		if (!empty($event['params']['from']))
		{
			$this->From = $event['params']['from'];
		}

		if (!empty($event['params']['fromname']))
		{
			$this->FromName	= $event['params']['fromname'];
		}

		switch ($event['action'])
		{
			case 'admin_password_restoration':
				$this->setAdminPasswordRestorationOptions($event);
				break;

			case 'admin_new_password_send':
				$this->setAdminNewPasswordOptions($event);
				break;
		}

		// set recipients
		if (!empty($event['params']['rcpts']) && is_array($event['params']['rcpts']))
		{
			foreach ($event['params']['rcpts'] as $addr)
			{
				$this->AddAddress($addr);
			}
		}
		elseif (isset($event['params']['item']) && !empty($event['params']['item']['email']))
		{
			$this->AddAddress($event['params']['item']['email']);
		}
	    elseif (isset($event['params']['item']) && !empty($event['params']['item']['email']))
		{
			$this->AddAddress($event['params']['item']['email']);
		}
		elseif(empty($event['params']['bccs']) && empty($event['params']['ccs']))
		{
			trigger_error("No recipient specified", E_USER_WARNING);
		}

		if (!empty($event['params']['bccs']))
		{
			foreach ($event['params']['bccs'] as $b)
			{
				$this->AddBCC($b);
			}
		}

		if (!empty($event['params']['ccs']))
		{
			foreach ($event['params']['ccs'] as $b)
			{
				$this->AddCC($b);
			}
		}

		$r = $this->Send();

	    if (!$r)
		{
			trigger_error("Error occured when sending email with subject \n Subject: '" . $this->Subject."'", E_USER_WARNING);
			if ($this->IsError())
			{
				trigger_error("PHPMAILER Error '" . $this->ErrorInfo."'", E_USER_WARNING);
			}
		}

		$this->ClearAllRecipients();

		// Administrator notifying section
		/*
		switch($event['action'])
		{
			case "listing_submit":
				if (!empty($this->admins))
				{
					foreach ($this->admins as $key=>$value)
					{
						if ($value['submit_notif'])
						{
							$this->notifyAdministrator($event, $value['email'], $value['fullname']);
						}
					}
				}
				break;
		}
		*//*
		return $r;
	}
*/
	/**
	 * setAdminPasswordRestorationOptions
	 *
	 * Sends email when user requests admin password
	 *
	 * @param mixed $event
	 * @access public
	 * @return bool
	 */
	function setAdminPasswordRestorationOptions(&$event)
	{
		$subject = 'Admin password restoration';
		$body = 'Please follow this URL: {url} in order to reset your password.';

		$url = $this->iaCore->get('admin_url') . "/login/?action=success&code=" . urlencode($event['params']['code']);

		$body = str_replace('{url}', $url, $body);

		$this->Subject = $subject;
		$this->IsHtml(false);
		$this->Body = $body;
	}

	function setAdminNewPasswordOptions(&$event)
	{
		$subject = "Admin password restoration";
		$body = "Your new password: {password}";

		$body = str_replace('{password}', $event['params']['password'], $body);

		$this->Subject = $subject;
		$this->IsHtml(false);
		$this->Body = $body;
	}
}