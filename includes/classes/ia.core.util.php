<?php
//##copyright##

class iaUtil extends abstractUtil
{
	const JSON_SERVICES_FILE = 'Services_JSON.php';


	public static function jsonEncode($data)
	{
		if (function_exists('json_encode'))
		{
			return json_encode($data);
		}
		else
		{
			require_once IA_INCLUDES . 'utils' . IA_DS . self::JSON_SERVICES_FILE;
			$jsonServices = new Services_JSON();

			return $jsonServices->encode($data);
		}
	}

	public static function jsonDecode($data)
	{
		if (function_exists('json_decode'))
		{
			return json_decode($data, true);
		}
		else
		{
			require_once IA_INCLUDES . 'utils' . IA_DS . self::JSON_SERVICES_FILE;
			$jsonServices = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);

			return $jsonServices->decode($data);
		}
	}

	public static function downloadRemoteContent($sourceUrl, $savePath)
	{
		if (extension_loaded('curl'))
		{
			$fh = fopen($savePath, 'w');

			$ch = curl_init($sourceUrl);
			curl_setopt($ch, CURLOPT_FILE, $fh);
			$result = curl_exec($ch);
			curl_close($ch);

			fclose($fh);

			return (bool)$result;
		}

		return false;
	}

	public static function getPageContent($url)
	{
		$result = null;
		$user_agent = 'Subrion CMS Bot';
		if (extension_loaded('curl'))
		{
			set_time_limit(60);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_REFERER, IA_CLEAR_URL);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
			$result = curl_exec($ch);
			curl_close($ch);
		}
		elseif (ini_get('allow_url_fopen'))
		{
			ini_set('user_agent', $user_agent);
			$result = file_get_contents($url, false);
			ini_restore('user_agent');
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	/*
	 * Makes safe XHTML code, strip only dangerous tags and attributes
	 *
	 * @param string $string HTML text
	 *
	 * @return string
	 */
	public static function safeHTML($string)
	{
		include_once IA_INCLUDES . 'htmlpurifier' . IA_DS . 'HTMLPurifier.auto' . iaSystem::EXECUTABLE_FILE_EXT;

		$config = HTMLPurifier_Config::createDefault();
		$config->set('HTML.Doctype', 'XHTML 1.0 Transitional');

		// generate cache folder
		$purifier_cache_dir = IA_CACHEDIR . 'Serializer' . IA_DS;
		if (!file_exists($purifier_cache_dir))
		{
			@mkdir($purifier_cache_dir, 0777);
		}

		$config->set('Cache.SerializerPath', $purifier_cache_dir);
		$config->set('Attr.AllowedFrameTargets', array('_blank'));
		$config->set('Attr.AllowedRel', 'facebox,nofollow,print,ia_lightbox');

		// allow YouTube and Vimeo
		$config->set('HTML.SafeIframe', true);
		$config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%');

		$purifier = new HTMLPurifier($config);
		$string = $purifier->purify($string);

		return $string;
	}

	public static function go_to($url)
	{
		if (empty($url))
		{
			trigger_error('Fatal error: empty url param of function ' . __METHOD__);
		}
		if (!headers_sent())
		{
			unset($_SESSION['info'], $_SESSION['msg']);

			$_SESSION['msg'] = iaCore::instance()->iaView->getMessages();
//			$_SESSION['error_msg'] = true;

			header('Location: ' . $url);

			exit;
		}
		else
		{
			trigger_error('Headers already sent. Redirection is impossible.');
		}
	}

	public static function post_goto($goto, $name = 'goto')
	{
		$action = 'stay';
		if (isset($_POST[$name]))
		{
			$action = $_POST[$name];
		}
		isset($goto[$action]) || $action = 'list';

		self::go_to($goto[$action]);
	}

	public static function makeDirCascade($path, $mode = 0777, $chmod = false)
	{
		if (!is_dir(dirname($path)))
		{
			self::makeDirCascade(dirname($path), $mode);
		}
		if (!is_dir($path))
		{
			$result = mkdir($path, $mode);
			if ($chmod && !function_exists('posix_getuid') || function_exists('posix_getuid') && posix_getuid() != fileowner(IA_HOME . 'index.php'))
			{
				chmod($path, $mode);
			}
			return $result;
		}

		return true;
	}

	public static function generateToken($length = 10)
	{
		$result = md5(uniqid(rand(), true));
		$result = substr($result, 0, $length);

		return $result;
	}

	public static function redirect($title, $message, $url = null, $isAjax = false)
	{
		$url = $url ? $url : IA_URL;
		$message = is_array($message) ? implode('<br />', $message) : $message;
		unset($_SESSION['redir']);

		$_SESSION['redir'] = array(
			'caption' => $title,
			'msg' => $message,
			'url' => $url
		);

		if (!$isAjax)
		{
			$redirectUrl = IA_URL . 'redirect/';

			if (iaCore::instance()->get('redirect_time', 4000) == 0)
			{
				$redirectUrl = $url;
			}

			header('Location: ' . $redirectUrl);
			exit;
		}
	}

	public static function reload($params = null)
	{
		$url = IA_SELF;

		if (is_array($params))
		{
			foreach ($params as $k => $v)
			{
				// remove key
				if (is_null($v))
				{
					unset($params[$k]);
					if (array_key_exists($k, $_GET))
					{
						unset($_GET[$k]);
					}
				}
				elseif (array_key_exists($k, $_GET)) // set new value
				{
					$_GET[$k] = $v;
					unset($params[$k]);
				}
			}
		}

		if ($_GET || $params)
		{
			$url .= '?';
		}
		foreach ($_GET as $k => $v)
		{
			// Unfort. At this time we delete an individual items using GET requests instead of POST
			// so when reloading we should skip delete action
			if ($k == 'action' && $v == 'delete')
			{
				continue;
			}
			$url .= $k . '=' . urlencode($v) . '&';
		}

		if ($params)
		{
			if (is_array($params))
			{
				foreach ($params as $k => $v)
				{
					$url .= $k . '=' . urlencode($v) . '&';
				}
			}
			else
			{
				$url .= $params;
			}
		}
		$url = rtrim($url, '&');

		self::go_to($url);
	}

	/*
	* Converts text to snippet
	*
	* The function cuts text to specified length,
	* also it strips all special tags like [b] etc.
	*
	* @params array $params - full text, if 'summary' not used, create snippet from it
	*
	* @return string
	*/

	// TODO: separate out 2 parameters: $text and $length
	public function text_to_snippet($params)
	{
		iaUTF8::loadUTF8Core();

		$text = &$params['text'];
		$length = isset($params['length']) ? $params['length'] : 600;

		// Strip HTML and BB codes
		$pattern = '/(\[\w+[^\]]*?\]|\[\/\w+\]|<\w+[^>]*?>|<\/\w+>)/i';
		$text = preg_replace($pattern, '', $text);

		// remove repeated spaces and new lines
		$text = preg_replace('/\s{2,}/', PHP_EOL, $text);
		$text = trim($text, PHP_EOL);

		if (utf8_strlen($text) > $length)
		{
			$text = utf8_substr($text, 0, $length);
			$_tmp = utf8_decode($text);
			if (preg_match('#.*([\.\s]).*#s', $_tmp, $matches, PREG_OFFSET_CAPTURE))
			{
				$end_pos = $matches[1][1];
				$text = utf8_substr($text, 0, $end_pos + 1);
				$text .= ' ...';
			}
		}

		return $text;
	}

	/*
	 * Check that personal folder exists and return path
	 *
	 * @param string $userName
	 *
	 * @return str path from UPLOADS directory (you can completely insert it into DB)
	 */
	public static function getAccountDir($userName = '')
	{
		if (empty($userName))
		{
			$userName = iaUsers::hasIdentity() ? iaUsers::getIdentity()->username : false;
		}

		$serverDirectory = '';
		umask(0);

		if (empty($userName))
		{
			$serverDirectory .= '_notregistered' . IA_DS;
			if (!is_dir(IA_UPLOADS . $serverDirectory))
			{
				mkdir(IA_UPLOADS . $serverDirectory);
			}
		}
		else
		{
			$subFolders = array();
			$subFolders[] = strtolower(substr($userName, 0, 1)) . IA_DS;
			$subFolders[] = $userName . IA_DS;
			foreach ($subFolders as $test)
			{
				$serverDirectory .= $test;
				if (!is_dir(IA_UPLOADS . $serverDirectory))
				{
					mkdir(IA_UPLOADS . $serverDirectory);
				}
			}
		}

		return $serverDirectory;
	}

	/**
	 * Provides a basic check if file is a zip archive
	 *
	 * @param $file file path
	 * @return bool
	 */
	public static function isZip($file)
	{
		if (function_exists('zip_open'))
		{
			if (is_resource($zip = zip_open($file)))
			{
				zip_close($zip);

				return true;
			}
		}
		else
		{
			$fh = fopen($file, 'r');
			if (is_resource($fh))
			{
				$signature = fread($fh, 2);
				fclose($fh);

				if ('PK' === $signature)
				{
					return true;
				}
			}
		}

		return false;
	}

	public static function checkPostParam($key, $default = '')
	{
		if (isset($_POST[$key]))
		{
			return $_POST[$key];
		}
		if (is_array($default))
		{
			if (isset($default[$key]))
			{
				$default = $default[$key];
			}
			else
			{
				$default = '';
			}
		}

		return $default;
	}

	public static function getFormattedTimezones()
	{
		$result = array();
		$regions = array('Africa', 'America', 'Antarctica', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific');
		$timezones = DateTimeZone::listIdentifiers();
		foreach ($timezones as $timezone)
		{
			$array = explode('/', $timezone);
			if (2 == count($array) && in_array($array[0], $regions))
			{
				$result[$array[0]][$timezone] = str_replace('_', ' ', $timezone);
			}
		}

		return $result;
	}

	public static function getIp($long = true)
	{
		// test if it is a shared client
		if (!empty($_SERVER['HTTP_CLIENT_IP']))
		{
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		// is it a proxy address
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		else
		{
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return $long ? ip2long($ip) : $ip;
	}

	public static function getLetters()
	{
		return array(
			'0-9','A','B','C','D','E','F','G','H','I','J','K','L',
			'M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'
		);
	}

	public static function deleteFile($file)
	{
		if (is_file($file))
		{
			return @unlink($file);
		}

		return false;
	}
}

class iaUTF8
{
	protected static $_path = 'phputf8';


	public static function loadUTF8Core()
	{
		static $loaded = false;

		if ($loaded)
		{
			return false;
		}

		$path = IA_INCLUDES . self::$_path . IA_DS;
		if (function_exists('mb_internal_encoding'))
		{
			mb_internal_encoding('UTF-8');
			require_once $path . 'mbstring' . IA_DS . 'core' . iaSystem::EXECUTABLE_FILE_EXT;
		}
		else
		{
			require_once $path . 'utils' . IA_DS . 'unicode' . iaSystem::EXECUTABLE_FILE_EXT;
			require_once $path . 'native' . IA_DS . 'core' . iaSystem::EXECUTABLE_FILE_EXT;
		}

		$loaded = true;

		return true;
	}

	public static function loadUTF8Function($fn)
	{
		iaUTF8::loadUTF8Core();
		$p = IA_INCLUDES . self::$_path . IA_DS . $fn . iaSystem::EXECUTABLE_FILE_EXT;
		if (file_exists($p))
		{
			require_once $p;
			if (function_exists($fn))
			{
				return true;
			}
			trigger_error("No such function from phputf8 package: '$fn'", E_USER_ERROR);
		}
	}

	public static function loadUTF8Util()
	{
		iaUTF8::loadUTF8Core();
		if (func_num_args() == 0)
		{
			return false;
		}
		foreach (func_get_args() as $fn)
		{
			require_once IA_INCLUDES . self::$_path . IA_DS . 'utils' . IA_DS . $fn . iaSystem::EXECUTABLE_FILE_EXT;
		}
	}
}