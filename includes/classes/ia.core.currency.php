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

    public function refreshRates()
    {
        if (!$this->iaCore->get('exchange_rates_update')) {
            return;
        }

        $log = 'RATES REFRESH REQUEST' . PHP_EOL;
        $log .= 'PROVIDER IS ' . $this->iaCore->get('exchange_rates_provider') . PHP_EOL;

        $provider = $this->_instantiateProvider($this->iaCore->get('exchange_rates_provider'));

        if ($provider) {
            $log .= 'PROVIDER CLASS INSTANTIATED' . PHP_EOL;

            if ($rates = $provider->fetch()) {
                $log .= 'FRESH RATES FETCHED BY PROVIDER: ' . print_r($rates, true) . PHP_EOL;

                $this->iaDb->setTable(self::getTable());

                foreach ($rates as $currencyCode => $rate) {
                    $this->iaDb->update(['rate' => $rate], iaDb::convertIds($currencyCode, 'code'));
                }

                $this->iaDb->resetTable();

                $this->dropCache();
            }
        }

        if (INTELLI_DEBUG) {
            iaDebug::log($log);
        }
    }

    protected function _instantiateProvider($name)
    {
        $class = 'iaRatesProvider' . ucfirst($name);
        $file = IA_MODULES . $this->getModuleName() . '/includes/providers/' . $name . iaSystem::EXECUTABLE_FILE_EXT;

        if (file_exists($file)) {
            require_once $file;

            if (class_exists($class)) {
                $instance = new $class($this);
                $instance->init();

                return $instance;
            }
        }

        return false;
    }

    public function invalidateCache()
    {
        $this->_iaCache->remove(self::CACHE_KEY);
    }

    public function delete($entryId)
    {
        return (bool)$this->iaDb->delete(iaDb::convertIds($entryId, 'code'), self::getTable());
    }
}