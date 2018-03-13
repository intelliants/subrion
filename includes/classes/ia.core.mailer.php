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

use PHPMailer\PHPMailer\PHPMailer;

require_once IA_INCLUDES . 'phpmailer/Exception.php';
require_once IA_INCLUDES . 'phpmailer/PHPMailer.php';
require_once IA_INCLUDES . 'phpmailer/SMTP.php';

class iaMailer extends PHPMailer
{
    protected $_table = 'email_templates';

    protected $_iaCore;

    protected $_replacements = [];

    protected $_bccEmails;

    protected $_templateName;

    protected $_recipients = [];
    protected $_languages = [];

    protected $_subjects = [];
    protected $_bodies = [];


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
        $this->XMailer = ' ';

        $this->isHTML($this->_iaCore->get('mimetype'));

        switch ($this->_iaCore->get('mail_function')) {
            case 'smtp':

                $this->isSMTP();

                $this->Host = $this->_iaCore->get('smtp_server');
                $this->SMTPAuth = (bool)$this->_iaCore->get('smtp_auth');
                $this->Username = $this->_iaCore->get('smtp_user');
                $this->Password = $this->_iaCore->get('smtp_password');
                $this->SMTPSecure = strtolower($this->_iaCore->get('smtp_secure'));

                if ($this->_iaCore->get('smtp_debug')) {
                    $this->SMTPDebug = 3;
                    $this->Debugoutput = $this->_iaCore->get('smtp_debug_output', 'echo');
                }

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
            case 3:
                $key = func_get_arg(0);
                $value = func_get_arg(1);

                if (is_string($key)) {
                    $replacements[$key] = $value;
                }
        }

        if (3 !== func_num_args() || false !== func_get_arg(2)) {
            self::_escapeTemplateVars($replacements);
        }

        $this->_replacements = array_merge($this->_replacements, $replacements);
    }

    protected static function _escapeTemplateVars(array &$replacements)
    {
        foreach ($replacements as $key => &$value) {
            if (is_array($value)) {
                self::_escapeTemplateVars($value);
            } elseif (is_string($value)) {
                $value = iaSanitize::html($value);
            }
        }
    }

    public function reset()
    {
        $this->_subjects = [];
        $this->_bodies = [];

        $this->Subject = '';
        $this->Body = '';

        $this->_templateName = null;
    }

    public function addAddressByMemberId($memberId)
    {
        if (!$memberId) {
            return;
        }

        $this->_iaCore->factory('users');

        $member = $this->_iaCore->iaDb->row(['email', 'fullname', 'email_language'],
            iaDb::convertIds($memberId), iaUsers::getTable());

        if ($member) {
            $this->addAddressByMember($member);
        }
    }

    public function addAddressByMember(array $member)
    {
        $this->addAddress($member['email'], $member['fullname'], $member['email_language']);
    }

    public function addAddress($address, $name = '', $langCode = null)
    {
        if (is_null($langCode) || !isset($this->_iaCore->languages[$langCode])) {
            $langCode = iaLanguage::getMasterLanguage()->iso;
        }

        $this->_recipients[$address] = [$name, $langCode];
    }

    /**
     * Load email Subject & Body template
     *
     * @param string $name template name
     *
     * @return boolean
     */
    public function loadTemplate($name)
    {
        $row = $this->_iaCore->iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION,
            '`name` = :name AND `active` = 1', ['name' => $name], $this->_table);

        if (!$row) {
            $this->reset();
            return false;
        }

        foreach ($this->_iaCore->languages as $iso => $language) {
            $this->_subjects[$iso] = $row['subject_' . $iso];
            $this->_bodies[$iso] = $row['body_' . $iso];
        }

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

        foreach ($this->_iaCore->languages as $iso => $language) {
            $subject = $iaSmarty->fetch('eval:' . $this->_subjects[$iso]);

            $iaSmarty->assign('subject', $subject);
            $iaSmarty->assign('content', $iaSmarty->fetch('eval:' . $this->_bodies[$iso]));

            $this->_subjects[$iso] = htmlspecialchars_decode($subject);
            $this->_bodies[$iso] = $iaSmarty->fetch(IA_HOME . 'admin/templates/emails/dist/email.layout.html');
        }
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

        $administrators = $this->_iaCore->iaDb->all(['email', 'fullname', 'email_language'],
            $where, null, null, iaUsers::getTable());

        if (!$administrators) {
            return false;
        }

        foreach ($administrators as $entry) {
            $this->addAddressByMember($entry);
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

        $results = [];

        foreach ($this->_recipients as $email => $params) {
            if (parent::addAddress($email, $params[0])) {
                $langCode = $params[1];

                $this->Subject = $this->_subjects[$langCode];
                $this->Body = $this->_bodies[$langCode];

                $this->_callHook('phpEmailToBeSent', $email, $toAdmins);

                if ($result = (bool)parent::send()) {
                    $this->_callHook('phpEmailSent', $email, $toAdmins);
                } else {
                    iaDebug::debug($this->ErrorInfo, 'Email submission');
                }

                parent::clearAddresses();

                $results[] = $result;
            }
        }

        $this->_recipients = [];

        return $results && !in_array(false, $results, true);
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

    protected function _callHook($name, $address, $toAdmins)
    {
        $params = [
            'template' => $this->_templateName,
            'subject' => $this->Subject,
            'body' => $this->Body,
            'recipient' => $address
        ];

        $this->_iaCore->startHook($name . ($toAdmins ? 'ToAdministrators' : ''), $params);
    }

    public function setSubject($subject)
    {
        foreach ($this->_iaCore->languages as $iso => $language) {
            $this->_subjects[$iso] = $subject;
        }
    }

    public function setBody($body)
    {
        foreach ($this->_iaCore->languages as $iso => $language) {
            $this->_bodies[$iso] = $body;
        }
    }
}
