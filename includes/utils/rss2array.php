<?php
#
# rss2array
#
# example usage:
#
#       require("inc.rss2array.php");
#       $feed = "http://news.bbc.co.uk/rss/newsonline_world_edition/front_page/rss091.xml";
#		$feed = "http://news.bbc.co.uk/rss/newsonline_world_edition/front_page/rss.xml";
#       $feed = "/path/to/feed.rss";
#       $rss_array = rss2array($feed);
#       print "<pre>";
#       print_r($rss_array);
#       print "</pre>";
#
# author: dan@freelancers.net
#

function rss2array($url, $filter_enabled = true)
{

	/** empty our global array **/
	$rss2array = array();

	/** if the URL looks ok **/
	if (preg_match("/^http:\/\/([^\/]+)(.*)$/", $url, $matches))
	{
		$host = $matches[1];
		$uri = $matches[2];

		$request = "GET $uri HTTP/1.0\r\n";
		$request .= "Host: $host\r\n";
		$request .= "User-Agent: RSSMix/0.1 http://www.rssmix.com\r\n";
		$request .= "Connection: close\r\n\r\n";

		/** open the connection **/
		if ($http = fsockopen($host, 80, $errno, $errstr, 5))
		{
			$response = '';
			/** make the request **/
			fwrite($http, $request);

			/** read in for max 5 seconds **/
			$timeout = time() + 5;
			while(time() < $timeout && !feof($http))
			{
				$response .= fgets($http, 4096);
			}

			/** split on two newlines **/
			@list($header, $xml) = preg_split("/\r?\n\r?\n/", $response, 2);

			/** get the status **/
			if (preg_match("/^HTTP\/[0-9\.]+\s+(\d+)\s+/", $header, $matches))
			{
				$status = $matches[1];

				/** if 200 OK **/
				if ($status == 200)
				{
					$_SESSION['rss2array']['filtering'] = $filter_enabled;

					/** create the parser **/
					$xml_parser = xml_parser_create();
					xml_set_element_handler($xml_parser, "startElement", "endElement");
					xml_set_character_data_handler($xml_parser, "characterData");

					/** parse! **/
					xml_parse($xml_parser, trim($xml), true) or $_SESSION['rss2array']['errors'][] = xml_error_string(xml_get_error_code($xml_parser)) . " at line " . xml_get_current_line_number($xml_parser);

					/** free parser **/
					xml_parser_free($xml_parser);
				}
				else
				{
					$_SESSION['rss2array']['errors'][] = "Can't get feed: HTTP status code $status";
				}
			}
			/** Can't get status from header **/
			else
			{
				$_SESSION['rss2array']['errors'][] = "Can't get status from header";
			}
		}
		/** Can't connect to host **/
		else
		{
			$_SESSION['rss2array']['errors'][] = "Can't connect to $host";
		}
	}
  /** $url may be a file on the filesystem **/
	elseif (is_file($url))
	{
		$xml = file_get_contents($url);
		/** create the parser **/
		$xml_parser = xml_parser_create();

		xml_set_element_handler($xml_parser, "startElement", "endElement");
		xml_set_character_data_handler($xml_parser, "characterData");

		/** parse **/
		xml_parse($xml_parser, trim($xml), true) or $_SESSION['rss2array']['errors'][] = xml_error_string(xml_get_error_code($xml_parser)) . " at line " . xml_get_current_line_number($xml_parser);

		/** free parser **/
		xml_parser_free($xml_parser);
	}
	/** feed url looks wrong **/
	else
	{
		$_SESSION['rss2array']['errors'][] = "Invalid url: $url";
  }
	
	#
	# unset all the working vars
	#
	unset($_SESSION['rss2array']['filtering']);
	unset($_SESSION['rss2array']['item_data']);

	unset($_SESSION['rss2array']['channel_title']);
	unset($_SESSION['rss2array']['inside_rdf']);
	unset($_SESSION['rss2array']['inside_rss']);
	unset($_SESSION['rss2array']['inside_channel']);
	unset($_SESSION['rss2array']['inside_item']);

	unset($_SESSION['rss2array']['current_tag']);
	unset($_SESSION['rss2array']['current_title']);
	unset($_SESSION['rss2array']['current_link']);
	unset($_SESSION['rss2array']['current_description']);

	$return = $_SESSION['rss2array'];
	unset($_SESSION['rss2array']);
	return $return;
}

#
# this function will be called everytime a tag starts
#
function startElement($parser, $name, $attrs)
{
	$rss2array = $_SESSION['rss2array'];
	$rss2array['current_tag'] = $name;
	if ($name == "RSS")
	{
		$rss2array['inside_rss'] = true;
	}
	elseif ($name == "RDF:RDF")
	{
		$rss2array['inside_rdf'] = true;
	}
	elseif ($name == "CHANNEL")
	{
		$rss2array['inside_channel'] = true;
		$rss2array['channel_title'] = "";
	}
  elseif ((isset($rss2array['inside_rss']) && $rss2array['inside_rss'] && isset($rss2array['inside_channel']) && $rss2array['inside_channel']) 
  	|| isset($rss2array['inside_rdf']) && $rss2array['inside_rdf'])
  {
		if ($name == "ITEM")
		{
			$rss2array['inside_item'] = true;
		}
    elseif ($name == "IMAGE")
    {
			$rss2array['inside_image'] = true;
		}
  }
  $_SESSION['rss2array'] = $rss2array;
}

#
# this function will be called everytime there is a string between two tags
#
function characterData($parser, $data)
{
	$allowed_fields = array('title', 'link', 'description');
	$rss2array = $_SESSION['rss2array'];

	if (isset($rss2array['inside_item']))
	{
		$field = strtolower($rss2array['current_tag']);
		if(!$rss2array['filtering'] || ($rss2array['filtering'] && in_array($field, $allowed_fields)))
		{
			if(isset($rss2array['item_data'][$field]))
			{
				$rss2array['item_data'][$field] .= $data;
			}
			else
			{
				$rss2array['item_data'][$field] = $data;
			}
		}
	}
/*	elseif (isset($rss2array['inside_image']))
	{
	}*/
	elseif (isset($rss2array['inside_channel']))
	{
		switch($rss2array['current_tag'])
		{
			case "TITLE":
				$rss2array['channel_title'] .= $data;
				break;
		}
	}
	$_SESSION['rss2array'] = $rss2array;
}

/**
 * this function will be called everytime a tag ends
 */
function endElement($parser, $name)
{
	$rss2array = $_SESSION['rss2array'];
	/** end of item, add complete item to array **/
	if ($name == 'ITEM')
	{
		$rss2array['items'][] = $rss2array['item_data'];

		/** reset these vars for next loop **/
		$rss2array['item_data'] = array();
		$rss2array['current_title'] = '';
		$rss2array['current_description'] = '';
		$rss2array['current_link'] = '';
		$rss2array['inside_item'] = false;
	}
	elseif ($name == 'RSS')
	{
		$rss2array['inside_rss'] = false;
	}
	elseif ($name == 'RDF:RDF')
	{
		$rss2array['inside_rdf'] = false;
	}
	elseif ($name == 'CHANNEL')
	{
		$rss2array['channel']['title'] = trim($rss2array['channel_title']);
		$rss2array['inside_channel'] = false;
	}
	elseif ($name == 'IMAGE')
	{
		$rss2array['inside_image'] = false;
	}
	$_SESSION['rss2array'] = $rss2array;
}
