<?php

// no direct access
defined('_JEXEC') or die;


class WebttHelper
{
		// GIBT DEN CLICKTT-HOST ZURÜCK
		public static function getHostClicktt()
		{
				$host = 'http://' . JComponentHelper::getParams('com_webtt')->get('verband') . '.click-tt.de';
				
				return $host;
		}

		public function trimClickttField($field)
		{
				$trim_array = array(chr(194), chr(160), chr(10));
				
				$trim = trim(str_replace($trim_array, "", $field));
				
				return $trim;
		}
}

?>
