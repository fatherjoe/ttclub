<?php

// no direct access
defined('_JEXEC') or die;

JLoader::import('helpers.webtt', JPATH_COMPONENT);

/*
 * Klasse zum Auslesen der Bilanzwerte
 * 
 */

class WebttHelperBw extends WebttHelper
{
		// LIEST DIE BILANZWERTE VON CLICKTT AUS UND GIBT SIE ALS ASS. ARRAY ZURÜCK
		public function getBW()
		{
				if (strstr($this->getTeam(), " HR") OR strstr($this->getTeam(), " VR") OR strstr($this->getTeam(), " HR") OR strstr($this->getTeam(), " FR"))
				{
						$path = str_replace(array("groupPage", "?", "&amp;"), array("groupPortrait", "?site=GroupPortraitPage&type=topRatingTotal&", "&"), $this->getPathLeagueClicktt);
				}
				
				else
				{
						$path = str_replace(array("groupPage", "?", "&amp;"), array("groupPortrait", "?site=GroupPortraitPage&displayTyp=" . JComponentHelper::getParams('com_webtt')->get('hs') . "runde&type=topRatingTotal&", "&"), $this->getPathLeagueClicktt());
				}

				$transport = NEW JHttpTransportCurl(NEW JRegistry);
				$http = new JHttp(NEW JRegistry, $transport);

				$rangl_staffel = $http->get($this->getHostClicktt() . $path)->body;

				$dom = @DOMDocument::loadHTML($rangl_staffel);

				$trim_array = array(chr(194), chr(160), chr(10));
								
				$dom->preserveWhiteSpace = true;
				$tables = $dom->getElementsByTagName('table');
				$bw = array();
				if (isset($tables->item(0)->nodeValue))
				{
						$zeilen = $tables->item(0)->getElementsByTagName('tr');
						foreach($zeilen as $z)
						{
								$td = $z->getElementsByTagName('td');
								if (isset($td->item(2)->nodeValue) && isset($td->item($td->length - 1)->nodeValue))
								{
										$bw[trim($td->item(2)->nodeValue)] = str_replace($trim_array, "", $td->item($td->length - 1)->nodeValue); // <tr><td>Nachname, Vorname</td><td>BW</td></tr>
								}
						}
				}

				return $bw;
		}
}

?>
