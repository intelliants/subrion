<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2014 Intelliants, LLC <http://www.intelliants.com>
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

class iaPatchParser
{
	const VALID_MAGIC_NUMBER = 25058;
	const VALID_SIGNATURE = 'INTELLIANTSPF';

	const FORMAT_RAW = false;

	const FILE_FLAG_COMPRESSED = 0x80;

	const LENGTH_SALT = 16;
	const LENGTH_SIGNATURE = 15;
	const LENGTH_GLOBAL_HEADER = 48;

	const MAX_QUERIES_NUMBER = 1000000;

	const EXTRA_TYPE_PLUGIN = true;
	const EXTRA_TYPE_PACKAGE = false;

	protected $_extraTypesMap = array(self::EXTRA_TYPE_PLUGIN => 'plugin', self::EXTRA_TYPE_PACKAGE => 'package');

	protected $_data = '';
	protected $_offset = 0;

	public $patch;


	public function __construct($data)
	{
		list($this->patch['header'], $this->_data) = $this->_parseHeader($data);

		// execution order is important since we have $_offset changed during _read() calls
		$this->patch['info'] = $this->_parseSectionInfo();
		$this->patch['files'] = $this->_parseSectionFiles();
		$this->patch['queries'] = $this->_parseSectionQueries();
		$this->patch['extras'] = $this->_parseSectionExtras();
		$this->patch['executables'] = $this->_parseSectionExecutables();
	}

	protected function _parseHeader($data)
	{
		$header = unpack('a13signature/a1major/a1minor', substr($data, 0, self::LENGTH_SIGNATURE));
		if (!isset($header['signature']) || $header['signature'] != self::VALID_SIGNATURE)
		{
			$this->_error('Patch file format is invalid.');
		}

		$hash = substr($data, self::LENGTH_SIGNATURE, self::LENGTH_SALT);
		$crypted = substr($data, self::LENGTH_SIGNATURE + self::LENGTH_SALT);

		return array(
			$header,
			$this->_decrypt($crypted, $hash)
		);
	}

	private function _decrypt($data, $hash)
	{
		$result = '';

		for ($i = 0; $i < strlen($data); $i++)
		{
			$key = substr($hash, ($i % strlen($hash)) - 1, 1);
			$char = chr(ord($data{$i}) - ord($key));
			$result .= $char;
		}

		return $result;
	}

	protected function _error($message)
	{
		throw new Exception($message);
	}

	protected function _read($format = self::FORMAT_RAW, $size = false)
	{
		$map = array( // code => bytes number
			'c' => 1,
			'C' => 1,
			's' => 2,
			'S' => 2,
			'l' => 4,
			'L' => 4
		);

		$length = (int)0;

		if ($size || $format === self::FORMAT_RAW)
		{
			$result = substr($this->_data, $this->_offset, $size);
			$length = strlen($result);
		}
		else
		{
			$result = unpack($format, substr($this->_data, $this->_offset));

			if ($result)
			{
				$array = explode('/', $format);
				foreach ($array as $item)
				{
					$len = $this->_getInt($item);
					if ($len == 0)
					{
						$c = (string)$item{0};
						if (isset($map[$c]))
						{
							$length += $map[$c];
						}
					}
					else
					{
						$length += $len;
					}
				}
			}

			if (1 == count($result))
			{
				$result = array_shift($result);
			}
		}

		if ($length)
		{
			$this->_offset += $length;
		}

		return $result;
	}

	protected function _getInt($string)
	{
		preg_match('/(\d+)/', $string, $array);
		return isset($array[1]) ? $array[1] : 0;
	}

	protected function _parseSectionInfo()
	{
		$info = $this->_read('Sversion_from/Sversion_to/lnum_files/lnum_queries/Cnum_extras/lcompilation_date/a34author/Smagic');

		$date = getdate($info['compilation_date']);
		if ($info['magic'] != self::VALID_MAGIC_NUMBER || !checkdate($date['mon'], $date['mday'], $date['year']))
		{
			$this->_error('Section SECTION_GLOBAL_INFO is corrupt');
		}

		$info['compilation_date'] = date('d/m/Y h:i', $info['compilation_date']);

		return $info;
	}

	protected function _parseSectionFiles()
	{
		if (!isset($this->patch['info']['num_files']))
		{
			$this->_error('GLOBAL_INFO section is not initialised.');
		}
		if (!$this->patch['info']['num_files'])
		{
			return false;
		}

		$index = (int)0;
		$items = array();

		while ($index < $this->patch['info']['num_files'])
		{
			$entry = $this->_read('Cflags/Clength_fp/Clength_fn/a32hash/Lsize');
			if ($entry['length_fp']
			 && $entry['length_fn']
			 && $entry['hash'])
			{
				$entry['path'] = $this->_read(self::FORMAT_RAW, $entry['length_fp']);
				$entry['name'] = $this->_read(self::FORMAT_RAW, $entry['length_fn']);
				$entry['contents'] = $this->_read(self::FORMAT_RAW, $entry['size']);

				if ($entry['flags'] & self::FILE_FLAG_COMPRESSED)
				{
					if (function_exists('gzuncompress'))
					{
						$entry['contents'] = gzuncompress($entry['contents']);
					}
					else
					{
						$this->_error('Files could not be unpacked.');
					}
				}

				$items[] = $entry;
			}
			else
			{
				$this->_error('Error parsing file entry.');
			}
			$index++;
		}

		return $items;
	}

	protected function _parseSectionQueries()
	{
		if (!isset($this->patch['info']['num_queries']))
		{
			$this->_error('GLOBAL_INFO section is not initialised');
		}
		if (!$this->patch['info']['num_queries'])
		{
			return false;
		}
		elseif ($this->patch['info']['num_queries'] > self::MAX_QUERIES_NUMBER)
		{
			$this->_error('Patch file is seem to be corrupt.');
		}

		$index = (int)0;
		$items = array();

		while ($index < $this->patch['info']['num_queries'])
		{
			$len = $this->_read('S');
			if ($entry = $this->_read(self::FORMAT_RAW, $len))
			{
				$items[] = $entry;
			}
			$index++;
		}

		return $items;
	}

	protected function _parseSectionExtras()
	{
		$index = (int)0;
		$items = array();

		while ($index < $this->patch['info']['num_extras'])
		{
			$len = $this->_read('C');
			$type = $this->_read('C');
			$name = $this->_read(self::FORMAT_RAW, $len);

			if ($name)
			{
				$items[] = array(
					'type' => $this->_extraTypesMap[(bool)$type],
					'name' => $name
				);
			}

			$index++;
		}

		return $items;
	}

	protected function _parseSectionExecutables()
	{
		$lengths = $this->_read('Spre/Spost');

		return array(
			'pre' => $this->_read(self::FORMAT_RAW, $lengths['pre']),
			'post' => $this->_read(self::FORMAT_RAW, $lengths['post'])
		);
	}
}