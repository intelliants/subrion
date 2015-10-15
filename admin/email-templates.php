<?php
//##copyright##

class iaBackendController extends iaAbstractControllerBackend
{
	protected $_name = 'email-templates';

	protected $_processAdd = false;
	protected $_processEdit = false;


	public function __construct()
	{
		parent::__construct();

		$this->setTable(iaCore::getConfigTable());
	}

	protected function _indexPage(&$iaView)
	{
		$iaView->display($this->getName());

		$templates = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`config_group` = 'email_templates' AND `type` IN ('radio', 'divider') ORDER BY `order`");

		$iaView->assign('templates', $templates);
	}

	protected function _gridRead($params)
	{
		$template = $params['id'];

		$result = array(
			'config' => (bool)$this->_iaCore->get($template, null, false, true),
			'signature' => (bool)$this->_iaDb->one_bind('`show`', '`name` = :template', array('template' => $template)),
			'subject' => $this->_iaCore->get($template . '_subject', null, false, true),
			'body' => $this->_iaCore->get($template . '_body', null, false, true)
		);

		// composing the patterns description
		if ($array = $this->_iaDb->one_bind('multiple_values', '`name` = :name', array('name' => $template . '_body')))
		{
			$array = array_filter(explode(',', $array));
			$patterns = array();

			foreach ($array as $entry)
			{
				list($key, $value) = explode('|', $entry);
				$patterns[$key] = $value;
			}

			$result['patterns'] = $patterns;
		}

		return $result;
	}

	protected function _gridUpdate($params)
	{
		$template = $params['id'];

		$this->_iaCore->set($template . '_subject', $params['subject'], true);
		$this->_iaCore->set($template . '_body', $params['body'], true);
		$this->_iaCore->set($template, (int)$params['enable_template'], true);

		$signature = $params['enable_signature'] ? '1' : '';
		$this->_iaDb->update(array('show' => $signature), iaDb::convertIds($template, 'name'));

		$result = (0 == $this->_iaDb->getErrorNumber());

		return array('result' => $result);
	}
}