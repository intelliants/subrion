<?php

/**
 * Cron expression parser and validator
 *
 * @author René Pollesch
 */
class Cron
{
    /**
     * Weekday look-up table
     *
     * @var array
     */
    protected static $weekdays = [
        'sun' => 0,
        'mon' => 1,
        'tue' => 2,
        'wed' => 3,
        'thu' => 4,
        'fri' => 5,
        'sat' => 6
    ];

    /**
     * Month name look-up table
     *
     * @var array
     */
    protected static $months = [
        'jan' => 1,
        'feb' => 2,
        'mar' => 3,
        'apr' => 4,
        'may' => 5,
        'jun' => 6,
        'jul' => 7,
        'aug' => 8,
        'sep' => 9,
        'oct' => 10,
        'nov' => 11,
        'dec' => 12
    ];

    /**
     * Value boundaries
     *
     * @var array
     */
    protected static $boundaries = [
        0 => [
            'min' => 0,
            'max' => 59
        ],
        1 => [
            'min' => 0,
            'max' => 23
        ],
        2 => [
            'min' => 1,
            'max' => 31
        ],
        3 => [
            'min' => 1,
            'max' => 12
        ],
        4 => [
            'min' => 0,
            'max' => 7
        ]
    ];

    /**
     * Cron expression
     *
     * @var string
     */
    protected $expression;

    /**
     * Time zone
     *
     * @var \DateTimeZone
     */
    protected $timeZone;

    /**
     * Matching register
     *
     * @var array|null
     */
    protected $register;

    /**
     * Class constructor sets cron expression property
     *
     * @param string $expression cron expression
     * @param \DateTimeZone $timeZone
     */
    public function __construct($expression = '* * * * *', \DateTimeZone $timeZone = null)
    {
        $this->setExpression($expression);
        $this->setTimeZone($timeZone);
    }

    /**
     * Set expression
     *
     * @param string $expression
     * @return self
     */
    public function setExpression($expression)
    {
        $this->expression = trim((string)$expression);
        $this->register = null;

        return $this;
    }

    /**
     * Set time zone
     *
     * @param \DateTimeZone $timeZone
     * @return self
     */
    public function setTimeZone(\DateTimeZone $timeZone = null)
    {
        $this->timeZone = $timeZone;
        return $this;
    }

    /**
     * Calculate next matching timestamp
     *
     * @param mixed $dtime \DateTime object, timestamp or null
     * @return int|bool next matching timestamp, or false on error
     */
    public function getNext($dtime = null)
    {
        $result = false;

        if ($this->isValid()) {
            if ($dtime instanceof \DateTime) {
                $timestamp = $dtime->getTimestamp();
            } elseif ((int)$dtime > 0) {
                $timestamp = $dtime;
            } else {
                $timestamp = time();
            }

            $dt = new \DateTime('now', $this->timeZone);
            $dt->setTimestamp(ceil($timestamp / 60) * 60);

            if ($this->isMatching($dt)) {
                $dt->modify('+1 minute');
            }

            $pointer = sscanf($dt->format('i G j n Y'), '%d %d %d %d %d');

            do {
                $current = $this->adjust($dt, $pointer);
            } while ($this->forward($dt, $current));

            $result = $dt->getTimestamp();
        }

        return $result;
    }

    /**
     * @param \DateTime $dtime
     * @param array $pointer
     * @return array
     */
    private function adjust(\DateTime $dtime, array &$pointer)
    {
        $current = sscanf($dtime->format('i G j n Y w'), '%d %d %d %d %d %d');

        switch (true) {
            case ($pointer[1] !== $current[1]):
                $pointer[1] = $current[1];
                $dtime->setTime($current[1], 0);
                break;

            case ($pointer[0] !== $current[0]):
                $pointer[0] = $current[0];
                $dtime->setTime($current[1], $current[0]);
                break;

            case ($pointer[4] !== $current[4]):
                $pointer[4] = $current[4];
                $dtime->setDate($current[4], 1, 1);
                $dtime->setTime(0, 0);
                break;

            case ($pointer[3] !== $current[3]):
                $pointer[3] = $current[3];
                $dtime->setDate($current[4], $current[3], 1);
                $dtime->setTime(0, 0);
                break;

            case ($pointer[2] !== $current[2]):
                $pointer[2] = $current[2];
                $dtime->setTime(0, 0);
                break;
        }

        return $current;
    }

    /**
     * @param \DateTime $dtime
     * @param array $current
     * @return bool
     */
    private function forward(\DateTime $dtime, array $current)
    {
        $result = false;

        if (isset($this->register[3][$current[3]]) === false) {
            $dtime->modify('+1 month');
            $result = true;
        } elseif (false === (isset($this->register[2][$current[2]]) && isset($this->register[4][$current[5]]))) {
            $dtime->modify('+1 day');
            $result = true;
        } elseif (isset($this->register[1][$current[1]]) === false) {
            $dtime->modify('+1 hour');
            $result = true;
        } elseif (isset($this->register[0][$current[0]]) === false) {
            $dtime->modify('+1 minute');
            $result = true;
        }

        return $result;
    }

    /**
     * Parse and validate cron expression
     *
     * @return bool true if expression is valid, or false on error
     */
    public function isValid()
    {
        $result = true;

        if ($this->register === null) {
            try {
                $this->register = $this->parse();
            } catch (\Exception $e) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Match current or given date/time against cron expression
     *
     * @param mixed $dtime \DateTime object, timestamp or null
     * @return bool
     */
    public function isMatching($dtime = null)
    {
        if (false === ($dtime instanceof \DateTime)) {
            $dt = new \DateTime();
            $dt->setTimestamp($dtime === null ? time() : $dtime);

            $dtime = $dt;
        }

        if ($this->timeZone !== null) {
            $dtime->setTimezone($this->timeZone);
        }

        try {
            $result = $this->match(sscanf($dtime->format('i G j n w'), '%d %d %d %d %d'));
        } catch (\Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * @param array $segments
     * @return bool
     * @throws \Exception
     */
    private function match(array $segments)
    {
        $result = true;

        foreach ($this->parse() as $i => $item) {
            if (isset($item[(int)$segments[$i]]) === false) {
                $result = false;
                break;
            }
        }

        return $result;
    }

    /**
     * Parse whole cron expression
     *
     * @return array
     * @throws \Exception
     */
    private function parse()
    {
        $register = [];

        if (sizeof($segments = preg_split('/\s+/', $this->expression)) === 5) {
            foreach ($segments as $index => $segment) {
                $this->parseSegment($index, $register, $segment);
            }

            if (isset($register[4][7])) {
                $register[4][0] = true;
            }
        } else {
            throw new \Exception('invalid number of segments');
        }

        return $register;
    }

    /**
     * Parse one segment of a cron expression
     *
     * @param int $index
     * @param string $segment
     * @param array $register
     * @throws \Exception
     */
    private function parseSegment($index, array &$register, $segment)
    {
        $allowed = [false, false, false, self::$months, self::$weekdays];

        // month names, weekdays
        if ($allowed[$index] !== false && isset($allowed[$index][strtolower($segment)])) {
            // cannot be used with lists or ranges, see crontab(5) man page
            $register[$index][$allowed[$index][strtolower($segment)]] = true;
        } else {
            // split up current segment into single elements, e.g. "1,5-7,*/2" => [ "1", "5-7", "*/2" ]
            foreach (explode(',', $segment) as $element) {
                $this->parseElement($index, $register, $element);
            }
        }
    }

    /**
     * @param int $index
     * @param array $register
     * @param string $element
     * @throws \Exception
     */
    private function parseElement($index, array &$register, $element)
    {
        $stepping = 1;

        if (false !== strpos($element, '/')) {
            $this->parseStepping($index, $element, $stepping);
        }

        if (is_numeric($element)) {
            $this->validateValue($index, $element);

            if ($stepping !== 1) {
                throw new \Exception('invalid combination of value and stepping notation');
            }

            $register[$index][intval($element)] = true;
        } else {
            $this->parseRange($index, $register, $element, $stepping);
        }
    }

    /**
     * Parse range of values, e.g. "5-10"
     *
     * @param int $index
     * @param array $register
     * @param string $range
     * @param int $stepping
     * @throws \Exception
     */
    private function parseRange($index, array &$register, $range, $stepping)
    {
        if ($range === '*') {
            $range = [self::$boundaries[$index]['min'], self::$boundaries[$index]['max']];
        } elseif (strpos($range, '-') !== false) {
            $range = $this->validateRange($index, explode('-', $range));
        } else {
            throw new \Exception('failed to parse list segment');
        }

        $this->fillRegister($index, $register, $range, $stepping);
    }

    /**
     * Parse stepping notation, e.g. "5-10/2" => 2
     *
     * @param int $index
     * @param string $element
     * @param int $stepping
     * @throws \Exception
     */
    private function parseStepping($index, &$element, &$stepping)
    {
        $segments = explode('/', $element);

        $this->validateStepping($index, $segments);

        $element = (string)$segments[0];
        $stepping = (int)$segments[1];
    }

    /**
     * Validate whether a given range of values exceeds allowed value boundaries
     *
     * @param int $index
     * @param array $range
     * @return array
     * @throws \Exception
     */
    private function validateRange($index, array $range)
    {
        if (sizeof($range) !== 2) {
            throw new \Exception('invalid range notation');
        }

        foreach ($range as $value) {
            $this->validateValue($index, $value);
        }

        return $range;
    }

    /**
     * @param int $index
     * @param int $value
     * @throws \Exception
     */
    private function validateValue($index, $value)
    {
        if (is_numeric($value)) {
            if (intval($value) < self::$boundaries[$index]['min'] ||
                intval($value) > self::$boundaries[$index]['max']) {
                throw new \Exception('value boundary exceeded');
            }
        } else {
            throw new \Exception('non-integer value');
        }
    }

    /**
     * @param int $index
     * @param array $segments
     * @throws \Exception
     */
    private function validateStepping($index, array $segments)
    {
        if (sizeof($segments) !== 2) {
            throw new \Exception('invalid stepping notation');
        }

        if ((int)$segments[1] <= 0 || (int)$segments[1] > self::$boundaries[$index]['max']) {
            throw new \Exception('stepping out of allowed range');
        }
    }

    /**
     * @param int $index
     * @param array $register
     * @param array $range
     * @param int $stepping
     */
    private function fillRegister($index, array &$register, array $range, $stepping)
    {
        for ($i = self::$boundaries[$index]['min']; $i <= self::$boundaries[$index]['max']; $i++) {
            if (($i - $range[0]) % $stepping === 0) {
                if ($range[0] < $range[1]) {
                    $this->fillRegisterBetweenBoundaries($index, $register, $range, $i);
                } else {
                    $this->fillRegisterAcrossBoundaries($index, $register, $range, $i);
                }
            }
        }
    }

    /**
     * @param int $index
     * @param array $register
     * @param array $range
     * @param int $value
     */
    private function fillRegisterAcrossBoundaries($index, array &$register, $range, $value)
    {
        if ($value >= $range[0] || $value <= $range[1]) {
            $register[$index][$value] = true;
        }
    }

    /**
     * @param int $index
     * @param array $register
     * @param array $range
     * @param int $value
     */
    private function fillRegisterBetweenBoundaries($index, array &$register, $range, $value)
    {
        if ($value >= $range[0] && $value <= $range[1]) {
            $register[$index][$value] = true;
        }
    }
}
