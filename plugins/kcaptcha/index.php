<?php
//##copyright##

require_once IA_PLUGINS . 'kcaptcha' . IA_DS . 'includes' . IA_DS . 'kcaptcha' . IA_DS . 'captcha.php';

$captcha = new KCAPTCHA();

$captcha->length = $iaCore->get('captcha_num_chars');

$captcha->getImage();

$_SESSION['pass'] = $captcha->getKeyString();