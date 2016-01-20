<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2016 Intelliants, LLC <http://www.intelliants.com>
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
 * @link http://www.subrion.org/
 *
 ******************************************************************************/

abstract class abstractCore
{
	public $iaCore;
	public $iaDb;
	public $iaView;

	protected $_message;

	protected static $_table;


	public function init()
	{
		$this->iaCore = iaCore::instance();
		$this->iaDb = &$this->iaCore->iaDb;
		$this->iaView = &$this->iaCore->iaView;
	}

	public static function getTable($prefix = false)
	{
		if (version_compare('5.3.0', PHP_VERSION, '<='))
		{
			eval('$_table = static::$_table;');
		}
		else
		{
			$_table = 'none';
			eval('$_table = ' . get_called_class() . '::$_table;');
		}

		if ($prefix)
		{
			return iaCore::instance()->iaDb->prefix . $_table;
		}

		return $_table;
	}

	public function getMessage()
	{
		return (string)$this->_message;
	}

	public function setMessage($message)
	{
		$this->_message = $message;
	}
}

abstract class abstractUtil
{
	public $iaCore;


	public function init()
	{
		$this->iaCore = iaCore::instance();
	}
}

class iaStore implements Countable, Iterator, ArrayAccess
{
	private $_values = array();


	public function __construct(array $values = array())
	{
		$this->_values = $values;
	}

	public function toArray()
	{
		return $this->_values;
	}

	public function &__get($key)
	{
		return $this->_values[$key];
	}

	public function __set($key, $value)
	{
		$this->_values[$key] = $value;
	}

	public function count()
	{
		return count($this->_values);
	}

	public function current()
	{
		return current($this->_values);
	}
	public function next()
	{
		return next($this->_values);
	}
	public function key()
	{
		return key($this->_values);
	}
	public function valid()
	{
		return (!is_null(key($this->_values)));
	}
	public function rewind()
	{
		reset($this->_values);
	}

	public function offsetExists($offset)
	{
		return isset($this->_values[$offset]);
	}
	public function offsetGet($offset)
	{
		return $this->_values[$offset];
	}
	public function offsetSet($offset, $value)
	{
		$this->_values[$offset] = $value;
	}
	public function offsetUnset($offset)
	{
		unset($this->_values[$offset]);
	}
}


// php 5.2 extra compatibility
if (!function_exists('get_called_class'))
{
	function get_called_class($backTrace = false, $l = 1)
	{
		$backTrace || $backTrace = debug_backtrace();

		if (!isset($backTrace[$l]))
		{
			throw new Exception('Cannot find called class -> stack level too deep.');
		}

		if (!isset($backTrace[$l]['type']))
		{
			throw new Exception('type not set');
		}
		else
		{
			switch ($backTrace[$l]['type'])
			{
				case '::':
					$lines = file($backTrace[$l]['file']);
					$i = 0;
					$callerLine = '';
					do
					{
						$i++;
						$callerLine = $lines[$backTrace[$l]['line'] - $i] . $callerLine;
					}
					while (false === stripos($callerLine, $backTrace[$l]['function']));

					preg_match('#([a-zA-Z0-9\_]+)::' . $backTrace[$l]['function'] . '#', $callerLine, $matches);
					if (!isset($matches[1]))
					{
						throw new Exception('Could not find caller class: originating method call is obscured.');
					}

					switch ($matches[1])
					{
						case 'self':
						case 'parent':
							return get_called_class($backTrace, $l + 1);
						default:
							return $matches[1];
					}

				case '->':
					switch ($backTrace[$l]['function'])
					{
						case '__get':
							if (!is_object($backTrace[$l]['object']))
							{
								throw new Exception('Edge case fail. __get called on a non object.');
							}
							return get_class($backTrace[$l]['object']);

						default:
							if (strrpos($backTrace[$l]['class'], 'ia') !== 0)
							{
								return get_class($backTrace[$l]['object']);
							}
							return $backTrace[$l]['class'];
					}

				default:
					throw new Exception('Unknown backtrace method type.');
			}
		}
	}
}