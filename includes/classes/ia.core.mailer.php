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

require_once IA_INCLUDES . 'phpmailer/class.phpmailer.php';

class iaMailer extends PHPMailer
{
    protected $_replacements = [];

    protected $_iaCore;

    protected $_bccEmails;

    protected $_defaultSignature;

    protected $_templateName;
    protected $_recipients = [];

    protected $_table = 'email_templates';


    /**
     * Class initializer
     */
    public function init()
    {
        $this->_iaCore = iaCore::instance();

        $this->CharSet = 'UTF-8';
        $this->From = $this->_iaCore->get('site_email');
        $this->FromName = $this->_iaCore->get('site_from_name', 'Subrion CMS');
        $this->SingleTo = true;

        $this->isHTML($this->_iaCore->get('mimetype'));

        switch ($this->_iaCore->get('mail_function')) {
            case 'smtp':
                require_once IA_INCLUDES . 'phpmailer/class.smtp.php';

                $this->isSMTP();

                $this->Host = $this->_iaCore->get('smtp_server');
                $this->SMTPAuth = (bool)$this->_iaCore->get('smtp_auth');
                $this->Username = $this->_iaCore->get('smtp_user');
                $this->Password = $this->_iaCore->get('smtp_password');
                $this->SMTPSecure = strtolower($this->_iaCore->get('smtp_secure'));
                $this->SMTPDebug = (bool)$this->_iaCore->get('smtp_debug') ? 3 : 0;

                if ($port = $this->_iaCore->get('smtp_port')) {
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
        $this->setReplacements([
            'siteUrl' => IA_URL,
            'siteName' => $this->_iaCore->get('site'),
            'siteEmail' => $this->_iaCore->get('site_email')
        ]);
    }

    /**
     * Set key/value replacements array for email body
     */
    public function setReplacements()
    {
        $replacements = [];

        switch (func_num_args()) {
            case 1:
                $values = func_get_arg(0);
                if (is_array($values)) {
                    $replacements = $values;
                }
                break;

            case 2:
                $key = func_get_arg(0);
                $value = func_get_arg(1);

                if (is_string($key) && is_string($value)) {
                    $replacements[$key] = $value;
                }
        }

        $replacements = array_map(['iaSanitize', 'html'], $replacements);

        $this->_replacements = array_merge($this->_replacements, $replacements);
    }

    public function reset()
    {
        $this->Subject = '';
        $this->Body = '';

        $this->_templateName = null;
    }

    public function addAddress($address, $name = '')
    {
        if (parent::addAddress($address, $name)) {
            $this->_recipients[$address] = $name;

            return true;
        }

        return false;
    }

    /**
     * Load email Subject & Body template
     *
     * @param string $name template name
     */
    public function loadTemplate($name, $langCode = null)
    {
        if (!$langCode || !isset($this->_iaCore->languages[$langCode])) {
            $langCode = iaLanguage::getMasterLanguage()->iso;
        }

        $row = $this->_iaCore->iaDb->row_bind(['subject' => 'subject_' . $langCode, 'body' => 'body_' . $langCode],
            '`name` = :name AND `active` = 1', ['name' => $name], $this->_table);

        if (!$row) {
            $this->reset();
            return false;
        }

        $this->Subject = $row['subject'];
        $this->Body = $row['body'];

        $this->_templateName = $name;

        return true;
    }

    protected function _compileTemplate()
    {
        if (!class_exists('iaSmarty')) {
            $this->_iaCore->iaView->loadSmarty(true);
        }

        $iaSmarty = &$this->_iaCore->iaView->iaSmarty;

        $iaSmarty->assign('core', [
            'config' => $this->_iaCore->getConfig(),
            'member' => iaUsers::getIdentity(true)
        ]);

        $iaSmarty->assign($this->_replacements);

        $subject = $iaSmarty->fetch('eval:' . $this->Subject);

        $iaSmarty->assign('subject', $subject);
        $iaSmarty->assign('content', $iaSmarty->fetch('eval:' . $this->Body));

        $this->Subject = $subject;
        $this->Body = $iaSmarty->fetch(IA_HOME . 'admin/templates/emails/dist/email.layout.html');
    }

    /**
     * Send email notifications to administrators
     *
     * @return bool
     */
    public function sendToAdministrators()
    {
        $where = '`usergroup_id` = :group AND `status` = :status';
        $this->_iaCore->iaDb->bind($where, ['group' => iaUsers::MEMBERSHIP_ADMINISTRATOR, 'status' => iaCore::STATUS_ACTIVE]);

        $administrators = $this->_iaCore->iaDb->all(['email', 'fullname'], $where, null, null, iaUsers::getTable());

        if (!$administrators) {
            return false;
        }

        foreach ($administrators as $entry) {
            $this->addAddress($entry['email'], $entry['fullname']);
        }

        return $this->send(true);
    }

    /**
     * Set a list of bcc recipients
     */
    protected function _setBcc()
    {
        if (is_null($this->_bccEmails)) {
            $bccEmail = $this->_iaCore->get('bcc_email');
            $array = explode(',', $bccEmail);

            $this->_bccEmails = array_map('trim', $array);
        }

        foreach ($this->_bccEmails as $email) {
            if (!empty($email)) {
                $this->addBCC($email);
            }
        }
    }

    /**
     * Send email
     *
     * @param bool $toAdmins if true, send the same email to administrators
     *
     * @return bool
     */
    public function send($toAdmins = false)
    {
        $this->_compileTemplate();

        $this->_setBcc();
        $this->_callHook('phpEmailToBeSent', $toAdmins);

        if ($result = (bool)parent::send()) {
            $this->_callHook('phpEmailSent', $toAdmins);
        } else {
            iaDebug::debug($this->ErrorInfo, 'Email submission');
        }

        parent::clearAllRecipients();

        return $result;
    }

    /**
     * Get error text
     *
     * @return string
     */
    public function getError()
    {
        return $this->ErrorInfo;
    }

    protected function _callHook($name, $toAdmins)
    {
        $params = [
            'template' => $this->_templateName,
            'subject' => $this->Subject,
            'body' => $this->Body,
            'recipients' => $this->_recipients
        ];

        $this->_iaCore->startHook($name . ($toAdmins ? 'ToAdministrators' : ''), $params);
    }
}
