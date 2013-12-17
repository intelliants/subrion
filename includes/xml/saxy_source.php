<?php
	require_once("xml_saxy_parser.php");
	
	class SAXY_Test  {
	
		function SAXY_Test() {
			$sp = new SAXY_Parser();
			$sp->xml_set_element_handler(array(&$this, "startElement"), array(&$this, "endElement"));
			$sp->xml_set_character_data_handler(array(&$this, "charData"));
			$sp->parse("<book><title><![CDATA[How to use SAXY]]></title><author>John Heinstein</author></book>");
		}//SAXY_Test
		
		function startElement($parser, $name, $attributes) {
			echo ("<br /><b>Open tag:</b> " . $name  . "<br /><b>Attributes:</b> " . print_r($attributes, true)  . "<br />");
		} //startElement
		
		function endElement($parser, $name) {
			echo ("<br /><b>Close tag:</b> " . $name  . "<br />");
		} //endElement		
		
		function charData($parser, $text) {
			echo ("<br /><b>Text node:</b> " . $text  . "<br />");
		} //charData
		
	} //SAXY_Test
	
	$st = new SAXY_Test();
?>