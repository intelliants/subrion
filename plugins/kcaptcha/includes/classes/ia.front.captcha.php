<?php
//##copyright##

class iaCaptcha extends abstractUtil
{


	public function getImage()
	{
		$html =
			'<p class="field-captcha">' .
			'<img src=":url" onclick="$(this).attr(\'src\', \':url?\'+Math.random())" title=":title" alt="captcha" style="cursor:pointer; margin-right: 10px;" align="left">' .
			':text<br />' .
			'<input type="text" class="span1" name="security_code" size=":length" maxlength=":length" id="securityCode">' .
			'</p>' .
			'<div class="clearfix"></div>'
		;
		$html = iaDb::printf($html, array(
			'length' => (int)$this->iaCore->get('captcha_num_chars'),
			'url' => IA_URL . 'captcha/',
			'text' => iaLanguage::get('captcha_annotation'),
			'title' => iaLanguage::get('click_to_redraw')
		));

		return $html;
	}

	public function validate()
	{
		if (iaUsers::hasIdentity())
		{
			return true;
		}

		$sc1 = isset($_POST['security_code']) ? $_POST['security_code'] : (isset($_GET['security_code']) ? $_GET['security_code'] : '');
		$sc2 = $_SESSION['pass'];

		$functionName = $this->iaCore->get('captcha_case_sensitive') ? 'strcmp' : 'strcasecmp';

		if (empty($_SESSION['pass']) || $functionName($sc1, $sc2) !== 0)
		{
			return false;
		}

		$_SESSION['pass'] = '';

		return true;
	}

	public function getPreview()
	{
		$html = '<img src=":url" onclick="$(this).attr(\'src\', \':url?\'+Math.random())" alt="captcha" style="cursor:pointer; margin-right: 10px;" align="left" />';
		$html = iaDb::printf($html, array(
			'url' => IA_URL . 'captcha/'
		));

		return $html;
	}
}