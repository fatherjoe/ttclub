<?php

// no direct access
defined('_JEXEC') or die;

JLoader::import('helpers.webtt', JPATH_COMPONENT);

/*
 * Klasse zum Auslesen der QTTR-Werte aus der DB und zum Aktualiieren von clicktt
 * 
 */

class WebttHelperQttr extends WebttHelper
{
		// GIBT DIE LETZTEN QTTR-WERTE ZURÜCK
		public function getQTTR($verein_nr = "")
		{
				if ($verein_nr == "")
				{
						$verein_nr = JComponentHelper::getParams('com_webtt')->get('verein_nr');
				}
				
				$qttr_last_date = $this->getQTTRLastDate();

                // Überprüfen, ob die letzten QTTR-Werte schon eingetragen wurden        
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);
 
                $query
                    ->select('werte')
                    ->from('#__webtt_ttr')
					->where( array(
										'typ='. $db->quote('qttr'),
										'verein_nr='. $db->quote($verein_nr),
										'datum='. $db->quote($qttr_last_date)
								)
						);
									
				$db->setQuery($query);
				$result = $db->loadrow();
				
				if ($result === NULL)
				{
						$this->update_qttr($qttr_last_date,$verein_nr);
				}
				
				// Abrufen der QTTR-Werte aus der DB
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);
 
                $query
                    ->select('werte')
                    ->from('#__webtt_ttr')
					->where( array(
										'typ='. $db->quote('qttr'),
										'verein_nr='. $db->quote($verein_nr),
										'datum='. $db->quote($qttr_last_date)
								)
						);
									
				$db->setQuery($query);
				$result = $db->loadObject()->werte;
				
				
				
				// QTTR-Feld auslesen und Array erstellen
				$qttr = $db->loadObject()->werte;

				$qttr_array = explode("\n", $qttr);

				$qttr = array();
				foreach ($qttr_array as $zeile)
				{
						if (trim($zeile))
						{
								$expl = explode(";", $zeile);
								$qttr[trim($expl[0])] = trim($expl[1]);
						}
				}
				
				return $qttr;
		}


		// GIBT DEN LETZTEN QTTR-STICHTAG ZURÜCK
		public function getQTTRLastDate()
		{
				$monate = array("02", "05", "08", "12");
				// Letzte QTTR-Aktualisierung ermitteln
				for ($x=2005;$x<=date("Y", time());$x++)
				{
						foreach ($monate as $y)
						{
								if (strtotime("$x-$y-11") > time() - 4*24*3600)
								{
										break;
								}
								$qttr_last_date = "$x-$y-11";
						}
				}
				
				return $qttr_last_date;
		}
		
		
		private function update_qttr($datum,$verein_nr)
		{
				$transport = NEW JHttpTransportCurl(NEW JRegistry);
				$http = new JHttp(NEW JRegistry, $transport);

				$url = $this->getHostClicktt() . '/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/ttrFilter';
				$post = "date=" . $datum . "&clubId=" . $verein_nr . "&filterType=club&federation=HeTTV&ttrFilter=suchen&WOSubmitAction=ttrFilter";

				$qttr_verein = $http->post($url, $post)->body;
				$dom = @DOMDocument::loadHTML($qttr_verein);

				if (!$dom)
				{ 		
						return FALSE;
				}
								
				$dom->preserveWhiteSpace = true;
				$tables = $dom->getElementsByTagName('table');
				$qttr = "";
				if (isset($tables->item(0)->nodeValue))	
				{

						$zeilen = $tables->item(0)->getElementsByTagName('tr');
						foreach($zeilen as $z)
						{
								$td = $z->getElementsByTagName('td');
								if (isset($td->item(1)->nodeValue))
								{
//										$qttr[$td->item(0)->nodeValue] = $td->item(1)->nodeValue; // <tr><td>Nachname, Vorname</td><td>Q-TTR</td></tr>
										$qttr .= $td->item(0)->nodeValue . ";" . $td->item(1)->nodeValue . "\n";
								}
						}
				}

				 
				// Schreiben der QTTR-Werte in die DB
				$db = JFactory::getDbo();
				$query = $db->getQuery(true);

				$fields = array(
					$db->quoteName('typ') . ' = ' . $db->quote('qttr'),
					$db->quoteName('verein_nr') . ' = ' . $db->quote($verein_nr),
					$db->quoteName('datum') . ' = ' . $db->quote($datum),
					$db->quoteName('werte') . ' = ' . $db->quote($qttr)
				);
				 
				$query->insert($db->quoteName('#__webtt_ttr'))->set($fields);
				 
				$db->setQuery($query);
				 
				$result = $db->execute();
				
		}
}

?>
