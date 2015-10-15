<?php
//##copyright##

class iaBackendController extends iaAbstractControllerBackend
{
	protected $_name = 'scheduled-tasks';

	protected $_processAdd = false;
	protected $_processEdit = false;

	protected $_gridColumns = array('id', 'name', 'description', 'date_prev_launch', 'date_next_launch', 'active', 'extras');


	public function __construct()
	{
		parent::__construct();

		$iaCron = $this->_iaCore->factory('cron');

		$this->setTable($iaCron::getTable());
		$this->setHelper($iaCron);
	}

	protected function _modifyGridResult(array &$entries)
	{
		$format = $this->_iaCore->get('date_format') . ', %H:%M';
		if ('win' == strtolower(substr(PHP_OS, 0, 3))) $format = str_replace('%e', '%#d', $format); // Windows compatibility

		foreach ($entries as &$entry)
		{
			$entry['date_prev_launch'] = $entry['date_prev_launch'] ? strftime($format, $entry['date_prev_launch']) : '';
			$entry['date_next_launch'] = $entry['date_next_launch'] ? strftime($format, $entry['date_next_launch']) : '';

			$entry['run'] = $this->getPath() . 'launch' . IA_URL_DELIMITER . $entry['id'] . IA_URL_DELIMITER;
			$entry['extras'] = empty($entry['extras']) ? iaLanguage::get('core', 'Core') : iaLanguage::get($entry['extras'], $entry['extras']);
		}
	}

	protected function _indexPage(&$iaView)
	{
		if (2 == count($this->_iaCore->requestPath) && 'launch' == $this->_iaCore->requestPath[0])
		{
			return $this->_launch($this->_iaCore->requestPath[1]);
		}

		$iaView->grid('admin/' . $this->getName());
	}

	protected function _launch($id)
	{
		//$this->getHelper()->run($id);

		// implemented via remote request because potentially some package's cron task
		// may use front classes which will cause conflicts if executed from backend side.
		// otherwise, the only call of iaCore::run() would be enough
		$cronUrl = IA_CLEAR_URL . 'cron/?_t&t=' . (int)$id;
		iaUtil::getPageContent($cronUrl, 300);
		//

		$this->_iaCore->iaView->setMessages(iaLanguage::get('scheduled_task_ran'), iaView::SUCCESS);

		iaUtil::go_to($this->getPath());
	}
}