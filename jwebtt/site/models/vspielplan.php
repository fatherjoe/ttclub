<?php
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.file');

JLoader::import('helpers.webtt', JPATH_COMPONENT);
JLoader::import('helpers.vspielplan', JPATH_COMPONENT);
JLoader::import('helpers.spielplaene', JPATH_COMPONENT);
JLoader::import('helpers.hallen', JPATH_COMPONENT);

class WebttModelVspielplan extends JModelItem
{

		// Liefern der Datenbankzeile mit dem Datum und dem xml an die View
        public function getXML()
        {

				// Helper-Klasse
				$this->WebttHelper = new WebttHelper;
				$this->WebttHelperVspielplan = new WebttHelperVspielplan;
				$this->WebttHelperHallen = new WebttHelperHallen;

				// Wenn Aktualisierungsintervall abgelaufen ist, dann aktualisieren
				if ($this->WebttHelper->update_test_verein('vspielplan') === TRUE)
				{
						$update = $this->WebttHelperVspielplan->update();
				}

                // sql-Query bilden und DB abfragen           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('xml')
                    ->from('#__webtt_tabellen')
					->where(
							array(
									'typ='. $db->quote("vspielplan")
								)
							);
									
				$db->setQuery($query);

				$results = $db->loadObject();

				$xml = new SimpleXMLElement($db->loadObject()->xml);

				$hallen = $this->WebttHelperHallen->getHallen();
				$staffeln = $this->WebttHelper->getStaffeln();
				
				foreach ($xml->ZEILE as $row)
				{
						// HALEENINFORMATIONEN IN DEN XML EINFÜGEN
						if ($row->HALLE == "(1)" OR $row->HALLE == "")
						{
								foreach ($hallen as $verein => $array)
								{
										$row->HALLE = "1";
										if (strstr($row->HEIM, $verein))
										{
												$row->HALLE_TITLE = $hallen[$verein]['halle_1'];
												$row->HALLE_ADRESSE = $hallen[$verein]['addr_1'];
										}
								}
						}

						else if ($row->HALLE == "(2)")
						{
								$row->HALLE = "2";
								foreach ($hallen as $verein => $array)
								{
										if (strstr($row->HEIM, $verein))
										{
												$row->HALLE_TITLE = $hallen[$verein]['halle_2'];
												$row->HALLE_ADRESSE = $hallen[$verein]['addr_2'];
										}
								}
						}

						else if ($row->HALLE == "(3)")
						{
								$row->HALLE = "3";
								foreach ($hallen as $verein => $array)
								{
										if (strstr($row->HEIM, $verein))
										{
												$row->HALLE_TITLE = $hallen[$verein]['halle_3'];
												$row->HALLE_ADRESSE = $hallen[$verein]['addr_3'];
										}
								}
						}
						
						if ($row->HALLE == "(H)" OR $row->HALLE == "H")
						{
								$row->HALLE = "H";
								foreach ($hallen as $verein => $array)
								{
										if (strstr($row->HEIM, $verein))
										{
												if (stristr($row->HALLE_VERL, ","))
												{
														$expl = explode(",", $row->HALLE_VERL); 
														$row->HALLE_TITLE = $expl[0];
														$row->HALLE_ADRESSE = $expl[count($expl) - 2] . $expl[count($expl) - 1];
												}
										}
								}
						}


						// AUSTAUSCHEN DER STAFFEL-KURZFORM GEGEN DIE LANGFORM
						foreach ($staffeln as $kurzform => $array)
						{
								if ($row->STAFFEL == $kurzform)
								{
										$row->STAFFEL = $array['clicktt_lang'];
								}
						}
						
						// MONATS-TAG HINZUFÜGEN
						if (strlen($row->DATUM))
						{
								$datum = explode(".", $row->DATUM);
						}
				}
						
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
				$this->WebttHelperVspielplan = new WebttHelperVspielplan;
				$this->WebttHelperSpielplaene = new WebttHelperSpielplaene;

				// GET-Variable team abfragen
//				$team = $this->WebttHelper->getTeam();

				// GET-Variable view abfragen
				$view = $this->WebttHelper->getView();

				if ($view == "vspielplan")
				{
						$typ = "meeting";
				}

				// NAME DER CLICKTT-STAFFEL
//				$staffel = $this->get('leagueclicktt');

				$trim_array = array(chr(194), chr(160), chr(10));

				// DB nach letzten Aktualisierungsdaten abfragen und 
				// einen Eintrag nach dem anderen überprüfen, ob aktualisiert werden muß und
				// ggf. aktualisieren

                // sql-Query bilden und DB abfragen           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('datum,idclicktt')
                    ->from('#__webtt_popups')
					->where(array('typ='. $db->quote('meeting')));
									
				$db->setQuery($query);

				$results = $db->loadObjectList();
				
				// SPIELPLANDATEN HOLEN
				$xml = $this->getXML();
				
				foreach ($xml->ZEILE as $zeile)
				{
						if (trim($zeile->ERGEBNIS_PFAD))
						{
								$meeting_url = "http://" . JComponentHelper::getParams('com_webtt')->get('verband') . ".clicktt.de" . $zeile->ERGEBNIS_PFAD;
								$meeting_url_query = parse_url($meeting_url, PHP_URL_QUERY);
								parse_str($meeting_url_query, $query);
								$meeting = $query['meeting'];

								// Wenn mit "Staffelleiter genehmigt" noch nicht gespeichert, dann aktualisieren
								if ($this->WebttHelper->update_test_popup($meeting) === TRUE)
								{
										$this->WebttHelperSpielplaene->update_popup($meeting,$zeile->ERGEBNIS_PFAD);
								}
								
								// Array mit Spielerids aufbauen, um die 
								$idsclicktt[] = $meeting;
						}
				}                
                
                // sql-Query bilden und DB abfragen           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('datum,idclicktt,name,xml,staffel')
                    ->from('#__webtt_popups')
					->where(
							array(
									'typ='. $db->quote($typ))
									);
									
				$db->setQuery($query);

				$results = $db->loadObjectList('idclicktt');


				$objects = new stdClass;

				foreach ($results as $result)
				{
						if (array_search($result->idclicktt, $idsclicktt))
						{ 
								$objects->{$result->idclicktt} = new stdClass;
								$objects->{$result->idclicktt}->xml = new stdClass;
								
								$objects->{$result->idclicktt}->xml = new SimpleXMLElement($result->xml);
								$objects->{$result->idclicktt}->name = $result->name;
								$objects->{$result->idclicktt}->staffel = $result->staffel;
								$objects->{$result->idclicktt}->datum = $result->datum;
						}
				}

                return $objects;
		}




		public function getHtmlPopupFiles()
		{
				$xmlPopups = $this->getXMLPopups();

				foreach($xmlPopups as $meeting => $xml)
				{
						$html = '<html>';
						$html .= '<link rel="stylesheet" href="' . JURI::base() . 'media/com_webtt/css/spielplan_popup.css" type="text/css" />';
						$html .= '<body>';
						$html .= '<div id="webtt">';
						$html .= '<div id="spielpl_verein">';

						if ($xmlPopups->{$meeting}->xml->ZEILE)
						{
								$html .= '<table class="table">';

								$html .= '<caption>' . $xmlPopups->{$meeting}->name . '</caption>';
								
								$html .= "<thead>";
								
								$html .= "<tr>";
								
								$html .= '<th class="paarung">Paarung</th>';
								
								$html .= '<th class="heimspieler">Heim</th>';

								$html .= '<th class="gastspieler">Gast</th>';

								$html .= '<th class="satzergebnis">Satz 1</th>';
								$html .= '<th class="satzergebnis">Satz 2</th>';
								$html .= '<th class="satzergebnis">Satz 3</th>';
								$html .= '<th class="satzergebnis">Satz 4</th>';
								$html .= '<th class="satzergebnis">Satz 5</th>';

								$html .= '<th class="saetze">Sätze</th>';

								$html .= '<th class="punkte">Punkte</th>';

								$html .= '</tr>';
								$html .= '</thead>';
									
								$html .= '<tbody>';
								foreach ($xmlPopups->{$meeting}->xml->ZEILE as $spielbericht)
								{									
										if (strlen($spielbericht->SATZERGEBNISSE->SATZ_1) > 2)
										{
												$html .= '<tr>';

												$html .= '<td class="paarung">' . $spielbericht->PAARUNG . '</td>';

												$html .= '<td class="heimspieler">' . str_replace("\n", "<br />", $spielbericht->HEIMSPIELER) . '</td>';

												$html .= '<td class="gastspieler">' . str_replace("\n", "<br />", $spielbericht->GASTSPIELER) . '</td>';
												
												$html .= '<td class="satzergebnis">' . $spielbericht->SATZERGEBNISSE->SATZ_1 . '</td>';
												$html .= '<td class="satzergebnis">' . $spielbericht->SATZERGEBNISSE->SATZ_2 . '</td>';
												$html .= '<td class="satzergebnis">' . $spielbericht->SATZERGEBNISSE->SATZ_3 . '</td>';
												$html .= '<td class="satzergebnis">' . $spielbericht->SATZERGEBNISSE->SATZ_4 . '</td>';
												$html .= '<td class="satzergebnis">' . $spielbericht->SATZERGEBNISSE->SATZ_5 . '</td>';

												$html .= '<td class="saetze">' . $spielbericht->SAETZE . '</td>';

												$html .= '<td class="punkte">' . $spielbericht->PUNKTE . '</td>';

												if (isset($spielbericht->SUMME))
												{
														$html .= '<td colspan="3" class="baelle">' . $spielbericht->BAELLE . '</td>';
														$html .= '<td class="saetze">' . $spielbericht->SAETZE . '</td>';
														$html .= '<td class="spiele">' . $spielbericht->SPIELE . '</td>';
												}
												
												$html .= '</tr>';
										}
								}
									
								$html .= '</tbody>';
								$html .= '</table>';
						}

				$html .= '</div>';
				$html .= '</div>';
				$html .= '</body>';
				$html .= '</html>';

				// SPEICHERN DES POPUPS ALS HTML
				$savePath = JPATH_SITE . '/media/com_webtt/tmp';
				$filename = 'meeting_' . $meeting . '.html';

				JFILE::write($savePath . '/' . $filename, $html);
				chmod($savePath . '/' . $filename, 0666);
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
}
