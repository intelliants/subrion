<?php
//##copyright##

class iaBackendController extends iaAbstractControllerBackend
{
    protected $_name = 'currencies';

    protected $_gridColumns = '`code`, `title`, `rate`, `default`, `order`, `code` `id`, 1 `update`, 1 `delete`';
    protected $_gridFilters = ['code' => self::EQUAL, 'title' => self::LIKE];


    public function __construct()
    {
        parent::__construct();

        $this->setHelper($this->_iaCore->factory('currency'));
    }

    protected function _setPageTitle(&$iaView, array $entryData, $action)
    {
        $iaView->title(iaLanguage::getf($action . '_currency', ['currency' => $entryData['title']]));
    }

    public function getById($id)
    {
        return $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id, 'code'));
    }

    protected function _gridRead($params)
    {
        if ('sorting' == $this->_iaCore->requestPath[0]) {
            $output = ['result' => false, 'message' => iaLanguage::get('invalid_parameters')];

            if (isset($_POST['codes']) && is_array($_POST['codes'])) {
                $order = 1;

                foreach ($_POST['codes'] as $currencyCode) {
                    $this->_iaDb->update(['order' => $order], iaDb::convertIds($currencyCode, 'code'));
                    $order++;
                }

                $this->getHelper()->invalidateCache();

                $output['result'] = true;
                $output['message'] = iaLanguage::get('saved');
            }

            return $output;
        } else {
            return iaView::errorPage(iaView::ERROR_NOT_FOUND);
        }
    }

    protected function _indexPage(&$iaView)
    {
        $action = isset($this->_iaCore->requestPath[0]) ? $this->_iaCore->requestPath[0] : null;

        switch ($action) {
            case 'default':
                if (2 != count($this->_iaCore->requestPath)) {
                    return iaView::errorPage(iaView::ERROR_NOT_FOUND);
                }

                $result = $this->_setAsDefault($this->_iaCore->requestPath[1]);

                $result
                    ? $iaView->setMessages(iaLanguage::get('saved'), iaView::SUCCESS)
                    : $iaView->setMessages(iaLanguage::get('something_went_wrong'));

                $this->getHelper()->invalidateCache();

                iaUtil::go_to($this->getPath());

                break;

            default:
                $currencies = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, '1 ORDER BY `order`');

                $number = 2540.99;

                foreach ($currencies as &$currency) {
                    $currency['format'] = $this->getHelper()->format($number, $currency);
                }

                $iaView->assign('currencies', $currencies);
        }
    }

    protected function _entryUpdate(array $entryData, $entryId)
    {
        $this->_iaDb->update($entryData, iaDb::convertIds($entryId, 'code'));

        return (0 === $this->_iaDb->getErrorNumber());
    }

    protected function _setDefaultValues(array &$entry)
    {
        $entry = [
            'code' => '',
            'title' => '',
            'rate' => 0.00,
            'symbol' => '',
            'sym_pos' => 'pre',
            'default' => false,
            'status' => iaCore::STATUS_ACTIVE
        ];
    }

    protected function _preSaveEntry(array &$entry, array $data, $action)
    {
        $entry['code'] = $data['code'];
        $entry['title'] = $data['title'];

        $entry['rate'] = (float)$data['rate'];

        $entry['symbol'] = $data['symbol'];
        $entry['sym_pos'] = $data['sym_pos'];

        $entry['default'] = (int)$data['default'];
        $entry['status'] = $data['status'];

        $requiredFields = ['code', 'title', 'rate', 'symbol' => 'currency_symbol'];

        foreach ($requiredFields as $fieldName => $fieldLabel) {
            if (is_numeric($fieldName)) {
                $fieldName = $fieldLabel;
            }
            if (empty($entry[$fieldName])) {
                $this->addMessage(iaLanguage::getf('field_is_empty', ['field' => iaLanguage::get($fieldLabel)]), false);
            }
        }

        return !$this->getMessages();
    }

    protected function _postSaveEntry(array &$entry, array $data, $action)
    {
        if ($entry['default']) {
            $this->_setAsDefault($this->getEntryId(), false);
        }

        $this->getHelper()->invalidateCache();
    }

    protected function _setAsDefault($currencyCode, $performDbUpdate = true)
    {
        if (array_key_exists($currencyCode, $this->getHelper()->fetchFromDb())) {
            if ($performDbUpdate) {
                $this->_iaDb->update(['default' => true], iaDb::convertIds($currencyCode, 'code'));
            }

            $this->_iaDb->update(['default' => false], iaDb::convertIds($currencyCode ,'code', false));

            return true;
        }

        return false;
    }

    protected function _htmlAction(&$iaView)
    {
        if (iaCore::ACTION_DELETE == $iaView->get('action')) {
            if (1 != count($this->_iaCore->requestPath)) {
                return iaView::errorPage(iaView::ERROR_NOT_FOUND);
            }

            $this->getHelper()->delete($this->_iaCore->requestPath[0]);

            $iaView->setMessages(iaLanguage::get('deleted'), iaView::SUCCESS);

            $this->getHelper()->invalidateCache();

            iaUtil::go_to($this->getPath());
        }
    }
}