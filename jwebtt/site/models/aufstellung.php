<?php
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.file');

JLoader::import('helpers.webtt', JPATH_COMPONENT);
JLoader::import('helpers.aufstellung', JPATH_COMPONENT);
JLoader::import('helpers.fotos', JPATH_COMPONENT);

class WebttModelAufstellung extends JModelItem
{

		// Liefern der Datenbankzeile mit dem Datum und dem xml an die View
        public function getXML()
        {

				// Helper-Klasse
				$this->WebttHelper = new WebttHelper;
				$this->WebttHelperAufstellung = new WebttHelperAufstellung;
				$this->WebttHelperFotos = new WebttHelperFotos;

				// GET-Variable team abfragen
				$team = $this->WebttHelper->getTeam();
			
				// Überprüfen, ob GET-Variable team eine Webtt-Manschaft ist
				if ($team === FALSE)
				{
						return FALSE;
				}

				// Wenn Aktualisierungsintervall abgelaufen ist, dann aktualisieren
				if ($this->WebttHelper->update_test('aufstellung') === TRUE)
				{
						$update = $this->WebttHelperAufstellung->update($team);
				}
				

                // sql-Query bilden und DB abfragen           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('xml')
                    ->from('#__webtt_tabellen')
					->where(
							array(
									'typ='. $db->quote("aufstellung"),
									'team='. $db->quote($team))
									);
									
				$db->setQuery($query);

				$results = $db->loadObject();
				$xml = new SimpleXMLElement($db->loadObject()->xml);
				
				// THUMBNAILS
				$thumbnails = $this->WebttHelperFotos->createThumbnail();
				
		
                return $xml;

		}

		// Liefern xml für die Popups
		// - Aufstellungen der Mannschaften für die "Tabelle"
		// - Einzelergebnisse für jeden Spieler für die "Aufstellungen"
		// - Spielberichte auf den "Spielplänen
        public function getXMLPopups()
        {
				// Helper-Klasse
				$this->WebttHelper = new WebttHelper;
				$this->WebttHelperAufstellung = new WebttHelperAufstellung;

				// GET-Variable team abfragen
				$team = $this->WebttHelper->getTeam();

				// GET-Variable view abfragen
				$view = $this->WebttHelper->getView();

				if ($view == "aufstellung")
				{
						$typ = "person";
				}

				// NAME DER CLICKTT-STAFFEL
				$staffel = $this->get('leagueclicktt');

				$trim_array = array(chr(194), chr(160), chr(10));

				// DB nach letzten Aktualisierungsdaten abfragen und 
				// einen Eintrag nach dem anderen überprüfen, ob aktualisiert werden muß und
				// ggf. aktualisieren

                // sql-Query bilden und DB abfragen           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('datum,idclicktt,name,xml')
                    ->from('#__webtt_popups')
					->where(
							array(
									'typ='. $db->quote($typ))
//									'staffel='. $db->quote($staffel))
									);
									
				$db->setQuery($query);

				$results = $db->loadObjectList();
				

				$xml = $this->getXML();
				
				foreach ($xml->ZEILE as $zeile)
				{
						$spieler_url = "http://" . JComponentHelper::getParams('com_webtt')->get('verband') . ".clicktt.de" . $zeile->SPIELER_PFAD;
						$spieler_url_query = parse_url($spieler_url, PHP_URL_QUERY);
						parse_str($spieler_url_query, $query);
						$person = $query['person'];

						// Wenn Aktualisierungsintervall abgelaufen ist, dann aktualisieren
						if ($this->WebttHelper->update_test_popup($person) === TRUE)
						{
								$this->WebttHelperAufstellung->update_popup($person,str_replace($trim_array, "", $zeile->SPIELER),$zeile->SPIELER_PFAD);
						}
						
						// Array mit Spielerids aufbauen, um die 
						$idsclicktt[] = $person;
				}
                
                
                // sql-Query bilden und DB abfragen           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('datum,idclicktt,name,xml')
                    ->from('#__webtt_popups')
					->where(
							array(
									'typ='. $db->quote($typ))
									);
									
				$db->setQuery($query);

				$results = $db->loadObjectList('name');


				$objects = new stdClass;

				foreach ($results as $result)
				{
						if (in_array($result->idclicktt, $idsclicktt))
						{ 
								$objects->{$result->name} = new stdClass;
								$objects->{$result->name}->xml = new stdClass;
								
								$objects->{$result->name}->xml = new SimpleXMLElement($result->xml);
								$objects->{$result->name}->datum = $result->datum;
						}
				}

                return $objects;
		}

		// SPEICHERN DER AUFKLAPPBOXEN ALS HTML, UM SIE IN DIE JAVASCRIPT-LIGHTBOX EINZUBINDEN
		public function getHtmlPopupFiles()
		{
				$xmlPopups = $this->getXMLPopups();

				$anz_spieler_box_datum			= JComponentHelper::getParams('com_webtt')->get('aufst_anz_spieler_box_datum');
				$anz_spieler_box_paarung		= JComponentHelper::getParams('com_webtt')->get('aufst_anz_spieler_box_paarung');
				$anz_spieler_box_gegnerteam		= JComponentHelper::getParams('com_webtt')->get('aufst_anz_spieler_box_gegnerteam');
				$anz_spieler_box_erg			= JComponentHelper::getParams('com_webtt')->get('aufst_anz_spieler_box_erg');

				foreach($xmlPopups as $spieler => $xml)
				{
						$html = '<html>';
						$html .= '<link rel="stylesheet" href="' . JURI::base() . 'media/com_webtt/css/bilanz_popup.css" type="text/css" />';
						$html .= '<body>';

						if ($xml)
						{
								$html .= '<div id="webtt">';
								$html .= '<div id="aufstellung">';
								$html .= "<table>";

								$html .= '<caption>' . $spieler . '</caption>';
								
								$html .= "<thead>";
								
								$html .= "<tr>";
								
								if ($anz_spieler_box_datum)
								{
										$html .= '<th class="datum">Datum</th>';
								}

								if ($anz_spieler_box_paarung)
								{
										$html .= '<th class="paarung">Paarung</th>';
								}

								$html .= '<th class="gegner">Gegner</th>';

								$html .= '<th class="saetze">Sätze</th>';

								if ($anz_spieler_box_gegnerteam)
								{
										$html .= '<th class="gegnerteam">Gegner-Team</th>';
								}

								if ($anz_spieler_box_erg)
								{
										$html .= '<th class="ergebnis">Ergebnis</th>';
								}

								$html .= '</tr>';
								$html .= '</thead>';
									
								$html .= '<tbody>';
								foreach ($xmlPopups->{$spieler}->xml as $spiel)
								{
										if (isset($spiel->STAFFEL))
										{
												$html .= '<tr>';
												$html .= '<td colspan="10" class="staffel">' . $spiel->STAFFEL . '</td>';
												$html .= '</tr>';
										}
										
										else
										{
											
										$html .= '<tr>';
										if ($anz_spieler_box_datum)
										{
												$html .= '<td class="datum">' . $spiel->DATUM . '</td>';
										}

										if ($anz_spieler_box_paarung)
										{
												$html .= '<td class="paarung">' . $spiel->PAARUNG . '</td>';
										}

										$html .= '<td class="saetze">' . $spiel->SAETZE . '</td>';

										$html .= '<td class="gegner">' . $spiel->GEGNER . '</td>';

										if ($anz_spieler_box_gegnerteam)
										{
												$html .= '<td class="bw">' . $spiel->GEGNER_TEAM . '</td>';
										}

										if ($anz_spieler_box_erg)
										{
												$html .= '<td class="qttr">' . $spiel->ERGEBNIS . '</td>';
										}
										
										$html .= '</tr>';
										
										}
								}
									
								$html .= '</tbody>';
								$html .= '</table>';
						}
						
						else
						{
								$html .= '<p>Am ' . date("d.m.y", strtotime($xmlPopups->{$spieler}->datum)) . ' noch keine gespielten Einzel</p>';
						}

				$html .= '</div>';
				$html .= '</div>';
				$html .= '</body>';
				$html .= '</html>';
				
				// SPEICHERN DES POPUPS ALS HTML
				$savePath = JPATH_SITE . '/media/com_webtt/tmp';
				$filename = $spieler . '.html';

				$saveFile = JFILE::write($savePath . '/person_' . $filename, $html);
				chmod($savePath . '/person_' . $filename, 0666);
				}
		}


	public function getMsg()
	{
		if (!isset($this->message))
		{
			$this->message = 'Hello World!';
		}

 
		return $this->message;
	}

		public function getArraySpielerFotos()
		{
				$db = JFactory::getDBO();
				$query = $db->getQuery(true);

				$query
					->select('name,foto')
					->from('#__webtt_spieler');
									
				$db->setQuery($query);

				$results = $db->loadAssocList('name');
				
				return $results;
		}

}
