<?php
//##copyright##

class iaCurrency extends abstractModuleFront
{
    const CACHE_KEY = 'currencies';
    const SESSION_KEY = 'currency';

    protected static $_table = 'currencies';

    protected $_iaCache;


    public function init()
    {
        parent::init();

        $this->_iaCache = $this->iaCore->factory('cache');
    }

    public static function getDefaultCurrency()
    {
        static $row;

        if (is_null($row)) {
            $row = iaCore::instance()->iaDb->row(iaDb::ALL_COLUMNS_SELECTION,
                iaDb::convertIds(1, 'default'), self::getTable());
        }

        return (object)$row;
    }

    public function getByCode($code)
    {
        $entries = $this->fetch();

        return isset($entries[$code]) ? $entries[$code] : null;
    }

    public function fetch()
    {
        $result = $this->_iaCache->get(self::CACHE_KEY, 604800, true);

        if (false === $result) {
            $result = $this->fetchFromDb();

            $this->_iaCache->write(self::CACHE_KEY, $result);
        }

        return $result;
    }

    public function get()
    {
        if (isset($_SESSION[self::SESSION_KEY])) {
            return $_SESSION[self::SESSION_KEY];
        } else {
            foreach ($this->fetch() as $entry) {
                if ($entry['default']) {
                    $this->set($entry['code']);
                    break;
                }
            }

            return $entry;
        }
    }

    public function set($currencyCode)
    {
        if ($currency = $this->getByCode($currencyCode)) {
            $this->iaCore->startHook('phpFrontCurrencyChanged', ['currency' => $currency]);

            $_SESSION[self::SESSION_KEY] = $currency;
        }
    }

    public function format($number, $currency = null)
    {
        if (is_null($currency)) {
            $currency = $this->get();
        }

        $converted = $currency['default']
            ? $number
            : $number * $currency['rate'];
        $converted = number_format($converted, $currency['fmt_num_decimals'], $currency['fmt_dec_point'], $currency['fmt_thousand_sep']);

        $prefix = ('pre' == $currency['sym_pos']) ? $currency['symbol'] : '';
        $postfix = ('post' == $currency['sym_pos']) ? $currency['symbol'] : '';

        return $prefix . $converted . $postfix;
    }

    public function fetchFromDb()
    {
        $where = '`status` = :status';
        $this->iaDb->bind($where, ['status' => iaCore::STATUS_ACTIVE]);

        $rows = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $where . ' ORDER BY `order`', null, null, self::getTable());

        $result = [];
        foreach ($rows as $row) {
            unset($row['order']);

            $row['rate'] = (float)$row['rate'];
            $row['default'] = (bool)$row['default'];

            $result[$row['code']] = $row;
        }

        return $result;
    }

    public function invalidateCache()
    {
        $this->_iaCache->remove(self::CACHE_KEY);

        if (isset($_SESSION[self::SESSION_KEY])) {
            unset($_SESSION[self::SESSION_KEY]);
        }
    }

    public function delete($entryId)
    {
        return (bool)$this->iaDb->delete(iaDb::convertIds($entryId, 'code'), self::getTable());
    }
}