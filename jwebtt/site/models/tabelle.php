<?php
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.file');

JLoader::import('helpers.webtt', JPATH_COMPONENT);
JLoader::import('helpers.bw', JPATH_COMPONENT);
JLoader::import('helpers.qttr', JPATH_COMPONENT);

JLoader::import('helpers.spielplan', JPATH_COMPONENT);


class WebttModelTabelle extends JModelItem
{
		// GIBT DIE GET-VARIABLE "TEAM" ZURÜCK
		public function getTeam()
		{
				// GET-Variable team abfragen
				$jinput = JFactory::getApplication()->input;
				$team = $jinput->get('team', null, null);

				// Eingetragene Webtt-Mannschaften abfragen
				$this->WebttHelper = new WebttHelper;
				$teams_webtt = $this->WebttHelper->getTeamsWebTT();

				// Überprüfen, ob GET-Variable team eine Webtt-Manschaft ist
				if (array_search($team, $teams_webtt) === FALSE)
				{
						return FALSE;
				}
				
				return $team;
		}
		
		// PRÜFT, OB DIE AKTUALISIERUNGSZEIT ABGELAUFEN IST
		private function update_test()
		{
				// GET-Variable team abfragen
				$jinput = JFactory::getApplication()->input;
				$team = $jinput->get('team', null, null);

                // Create a new query object.           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                // Aktualisierungsdatum 
                $query
                    ->select('datum')
                    ->from('#__webtt_tabellen')
					->where('typ='. $db->quote("tabelle") AND 'team='. $db->quote($team));
									
				$db->setQuery($query);
                $timestamp = $db->loadObject()->datum;
                $time = strtotime($timestamp);
                if ($time < (time() - 48 * 3600))
                {
						return TRUE;
				}

                return FALSE;

			
		}


		// Clicktt-Staffel einer Mannschaft abfragen
        public function getLeagueClicktt()
        {
				$team = $this->getTeam();
				
                // Create a new query object.           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('league_clicktt')
                    ->from('#__webtt_teams')
                    ->where('name_webtt='.$db->quote($team));
 
				$db->setQuery($query);
                $results = $db->loadObject()->league_clicktt;

                return $results;
        }

		// Clicktt-Links zu den Staffeln
        public function getPathLeagueClicktt()
        {
				$team = $this->getTeam();
				
                // Create a new query object.           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('path_league_clicktt')
                    ->from('#__webtt_teams')
                    ->where('name_webtt='.$db->quote($team));
 
				$db->setQuery($query);
                $results = $db->loadObject()->path_league_clicktt;

                return $results;
        }

		// Abrufen der Tabelle von clicktt und in der Datenbank speichern
		// private - wird von getRow() aufgerufen
		private function update($team)
		{
				// Helper-Klasse
				$this->WebttHelper = new WebttHelper;
//				$this->WebttHelperSpielplan = new WebttHelperSpielplan;
//				$this->WebttHelperHallen = new WebttHelperHallen;

				// GET-Variable team abfragen
				$team = $this->WebttHelper->getTeam();


				$verein_nr = JComponentHelper::getParams('com_webtt')->get('verein_nr');
				$verband = JComponentHelper::getParams('com_webtt')->get('verband');
				$verein = JComponentHelper::getParams('com_webtt')->get('verein');

				$transport = NEW JHttpTransportCurl(NEW JRegistry);
				$http = new JHttp(NEW JRegistry, $transport);

				// Pfad der Staffelseite von clicktt abfragen
				$path_league = $this->getPathLeagueClicktt($team);

				$leaguepage = $http->get('http://' . $verband . '.click-tt.de' . $path_league)->body;

				if ($leaguepage)  {
						$dom = @DOMDocument::loadHTML($leaguepage);

						if (!$dom)
						{ 
								echo 'Fehler beim Parsen des Dokuments'; 
						} 

						$xml = '<?xml version="1.0" encoding="utf-8"?>';
						$xml .= "<BODY>";
						$dom->preserveWhiteSpace = false;
						$tables = $dom->getElementsByTagName('table');
						$rows = $tables->item(0)->getElementsByTagName('tr');

						$trim_array = array(chr(194), chr(160), chr(10));
						foreach ($rows as $row)
						{

								$cols = $row->getElementsByTagName('td');
								$links = $row->getElementsByTagName('a');
								if ($cols->length)
								{
										$xml .= "<ZEILE>";

										foreach($links as $link)
										{
												$path_team = trim($link->getattribute('href'));
										}
										
//										$update_team = $this->update_popup($path_team,trim($cols->item(2)->nodeValue));

										// SPALTE TENDENZ
										$tendenz = "";
										$xml .= "<TENDENZ>";
										if (isset($cols->item(0)->getElementsByTagName('img')->item(0)->nodeValue))
										{
												if (trim($cols->item(0)->getElementsByTagName('img')->item(0)->getAttribute('title')) == "Aufsteiger")
												{
														$tendenz = "up";
												}
												if (trim($cols->item(0)->getElementsByTagName('img')->item(0)->getAttribute('title')) == "Absteiger")
												{
														$tendenz = "down";
												}
												if (trim($cols->item(0)->getElementsByTagName('img')->item(0)->getAttribute('title')) == "Relegation")
												{
														if (strstr($cols->item(0)->getElementsByTagName('img')->item(0)->getAttribute('src'), "up_grey"))
														{
																$tendenz = "rel_up";
														}
														if (strstr($cols->item(0)->getElementsByTagName('img')->item(0)->getAttribute('src'), "down_grey"))
														{
																$tendenz = "rel_down";
														}
												}

												if ($tendenz == "up")
												{
														$xml .= "up";
												}
												if ($tendenz == "down")
												{
														$xml .= "down";
												}
												if ($tendenz == "rel_up")
												{
														$xml .= "rel_up";
												}
												if ($tendenz == "rel_down")
												{
														$xml .= "rel_down";
												}
													
										}
										$xml .= "</TENDENZ>";

										// SPALTE RANG
										$xml .= '<RANG>';
										$xml .= $cols->item(1)->nodeValue;
										$xml .= '</RANG>';

										// SPALTE MANNSCHAFT
										$xml .= "<TEAM>";
										$xml .= trim($cols->item(2)->nodeValue);
										$xml .= "</TEAM>";
										$xml .= "<TEAM_PATH>";
										$xml .= str_replace("&", "&amp;", $path_team);
										$xml .= "</TEAM_PATH>";

										// SPALTE ANZAHL BEGEGNUNGEN
										$xml .= "<BEGEGNUNGEN>";
										if (isset($cols->item(3)->nodeValue) && is_numeric($cols->item(3)->nodeValue))
										{
												$xml .= $cols->item(3)->nodeValue;
										}
										$xml .= "</BEGEGNUNGEN>";
										
										// SPALTEN DETAILS
										$xml .= "<DETAILS>";
										$xml .= "<SIEGE>";
										if (isset($cols->item(4)->nodeValue) && is_numeric($cols->item(4)->nodeValue))
										{
												$xml .= $cols->item(4)->nodeValue;
										}
										$xml .= "</SIEGE>";

										$xml .= "<UNENTSCHIEDEN>";
										if (isset($cols->item(5)->nodeValue) && is_numeric($cols->item(5)->nodeValue))
										{
												$xml .= $cols->item(5)->nodeValue;
										}
										$xml .= "</UNENTSCHIEDEN>";

										$xml .= "<NIEDERLAGEN>";
										if (isset($cols->item(6)->nodeValue) && is_numeric($cols->item(6)->nodeValue))
										{
												$xml .= $cols->item(6)->nodeValue;
										}
										$xml .= "</NIEDERLAGEN>";
										$xml .= "</DETAILS>";


										// SPALTE SPIELE
										$xml .= "<SPIELE>";
										if (isset($cols->item(7)->nodeValue))
										{
												$xml .= trim($cols->item(7)->nodeValue);
										}
										$xml .= "</SPIELE>";
											
										// SPALTE DIFFERENZ
										$xml .= "<DIFFERENZ>";
										if (isset($cols->item(8)->nodeValue))
										{
												$xml .= $cols->item(8)->nodeValue;
										}
										$xml .= "</DIFFERENZ>";

										// SPALTE PUNKTE
										$xml .= "<PUNKTE>";
										if (isset($cols->item(9)->nodeValue))
												$xml .= $cols->item(9)->nodeValue;
										$xml .= "</PUNKTE>";

										$xml .= "</ZEILE>";

								}

								
						}

						$xml .= "</BODY>";

						/*
						 * XML IN DB SCHREIBEN
						 *
						 */
 
						// Überprüfen, ob die Tabelle schon existiert --> insert oder update           
						$db = JFactory::getDBO();
						$query = $db->getQuery(true);

						$query
							->select('datum')
							->from('#__webtt_tabellen')
							->where(array('team='.$db->quote($team), 'typ=' . $db->quote('tabelle')));
		 
						$db->setQuery($query);
						
						if (isset($db->loadObject()->datum))
						{

								$db = JFactory::getDbo();
								$query = $db->getQuery(true);
								 
								// Fields to update.
								$fields = array(
									$db->quoteName('datum') . ' = ' . $db->quote(date("Y-m-d H:i:s")),
									$db->quoteName('xml') . ' = ' . $db->quote($xml)
								);
								 
								// Conditions for which records should be updated.
								$conditions = array(
									$db->quoteName('team') . ' = ' . $db->quote($team), 
									$db->quoteName('typ') . ' = ' . $db->quote('tabelle')
								);
								 
								$query->update($db->quoteName('#__webtt_tabellen'))->set($fields)->where($conditions);
								 
								$db->setQuery($query);
								 
								$result = $db->execute();
						}
						
						else
						{
								$db = JFactory::getDbo();
								$query = $db->getQuery(true);
								 
								// Fields to update.
								$fields = array(
									$db->quoteName('datum') . ' = ' . $db->quote(date("Y-m-d H:i:s")),
									$db->quoteName('typ') . ' = ' . $db->quote('tabelle'),
									$db->quoteName('team') . ' = ' . $db->quote($team),
									$db->quoteName('xml') . ' = ' . $db->quote($xml)
								);
								 
								$query->insert($db->quoteName('#__webtt_tabellen'))->set($fields);
								 
								$db->setQuery($query);
								 
								$result = $db->execute();
						}
				}
				
		}

		// Liefern der Datenbankzeile mit dem Datum und dem xml an die View
        public function getRow()
        {

				// Eingetragene Webtt-Mannschaften abfragen
				$this->WebttHelper = new WebttHelper;
				$teams_webtt = $this->WebttHelper->getTeamsWebTT();


				// GET-Variable team abfragen
				$team = $this->WebttHelper->getTeam();
				// Überprüfen, ob GET-Variable team eine Webtt-Manschaft ist
				if (array_search($team, $teams_webtt) === FALSE)
				{
						return FALSE;
				}

				// Wenn Aktualisierungsintervall abgelaufen ist, dann aktualisieren
				if ($this->WebttHelper->update_test() === TRUE)
				{
						$this->update($team);
				}
				

                // sql-Query bilden und DB abfragen           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('datum,xml')
                    ->from('#__webtt_tabellen')
					->where(
							array(
									'typ='. $db->quote("tabelle"),
									'team='. $db->quote($team))
									);
									
				$db->setQuery($query);

				$results = $db->loadObject();
                $results->timestamp = $db->loadObject()->datum;                
				$results->xml = new SimpleXMLElement($db->loadObject()->xml);
//$results->xml = JFactory::getXML($db->loadObject()->xml);

                return $results;

		}

		// Liefern xml für die Popups
		// - Aufstellungen der Mannschaften für die Tabelle
        public function getXMLPopups()
        {
				$typ = 'mannschaft';
				
				$this->WebttHelper = new WebttHelper;
				$staffel = $this->WebttHelper->getLeagueClicktt();
								
				// GET-Variable team abfragen
				$jinput = JFactory::getApplication()->input;				

                // sql-Query bilden und DB abfragen           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('datum,idclicktt,name,xml')
                    ->from('#__webtt_popups')
					->where(
							array(
									'typ='. $db->quote($typ),
									'staffel='. $db->quote($staffel))
									);
									
				$db->setQuery($query);

				$results = $db->loadObjectList('name');
				
				$xml = $this->getRow();

				foreach ($xml->xml->ZEILE as $zeile)
				{
						$team_url = "http://" . JComponentHelper::getParams('com_webtt')->get('verband') . ".clicktt.de" . $zeile->TEAM_PATH;
						$team_url_query = parse_url($team_url, PHP_URL_QUERY);
						parse_str($team_url_query, $query);
						$teamtable = $query['teamtable'];

						// Wenn Aktualisierungsintervall abgelaufen ist, dann aktualisieren
						if ($this->WebttHelper->update_test_popup($teamtable) === TRUE)
						{
								$this->update_popup($zeile->TEAM_PATH,$this->WebttHelper->trimClickttField($zeile->TEAM),$teamtable);
						}
						
						// Array mit Spielerids aufbauen, um die 
						$idsclicktt[] = $teamtable;

				}

				$objects = new stdClass;

				foreach ($results as $result)
				{
						$objects->{$result->name} = new stdClass;
						$objects->{$result->name}->xml = new stdClass;
						$objects->{$result->name}->xml = new SimpleXMLElement($result->xml);
						$objects->{$result->name}->datum = $result->datum;
				}

                return $objects;
		}

		private function update_popup($path,$mannschaft,$teamtable)
		{
				$this->WebttHelper = new WebttHelper;
				$this->WebttHelperBw = new WebttHelperBw;
				$this->WebttHelperQttr = new WebttHelperQttr;
				
				$staffel = $this->WebttHelper->getLeagueClicktt();
				$host = $this->WebttHelper->getHostClicktt();
				
				$verband = JComponentHelper::getParams('com_webtt')->get('verband');

				$transport = NEW JHttpTransportCurl(NEW JRegistry);
				$http = new JHttp(NEW JRegistry, $transport);

				$teampage = $http->get($host . $path)->body;

				if ($teampage)
				{
						$dom = @DOMDocument::loadHTML($teampage);

						$xml = '<?xml version="1.0" encoding="utf-8"?>';
						$xml .= "<BODY>";
						$dom->preserveWhiteSpace = true;
						$tables = $dom->getElementsByTagName('table');
			
						// Beim Rückzug einer Mannschaft ist nur noch eine Tabelle auf der Mannschaftsseite (mit den Vereinsdaten). Spielplan und Aufstellung wurden entfernt. Am Anfang einer Halbserie können die Spielerbilanzen und/oder die Spieltermine noch nicht eingetragen sein
						$h2s = $dom->getElementsByTagName('h2');

						if ($h2s->length >= 1)
						{
								if ((isset($h2s->item(0)->nodeValue) && strstr($h2s->item(0)->nodeValue, "Spielerbilanzen")) OR (isset($h2s->item(1)->nodeValue) && strstr($h2s->item(1)->nodeValue, "Spielerbilanzen")))
								{
										$a = $tables->item(0)->getElementsByTagName('a');
										if (isset($a->item(0)->nodeValue))
										{
												parse_url($a->item(0)->nodeValue);
										}
										$club_path = $a->item(0)->getAttribute("href");
										$club = $a->item(0)->nodeValue;
										$club_url = "http://" . $verband . ".clicktt.de" . $club_path;
										$club_url_query = parse_url($club_url, PHP_URL_QUERY);
										$club_nr = explode("=", trim($club_url_query));

										$bw = $this->WebttHelperBw->getBW();
										
										// QTTR-WERTE ABFRAGEN
										$qttr = $this->WebttHelperQttr->getQTTR($club_nr[1]);

										if (isset($tables->item(2)->nodeValue))
										{
												$rows = $tables->item(2)->getElementsByTagName('tr');

												$trim_array = array(chr(194), chr(160), chr(10));
												foreach ($rows as $row)
												{
														$cols_th = $row->getElementsByTagName('th');
														if (isset($cols_th->item(0)->nodeValue))
														{
																for($s=0; $s<$cols_th->length; $s++)
																{
																		if ($cols_th->item($s)->nodeValue == "gesamt")
																				$spalte_bil_nr = $s;
																		if ($cols_th->item($s)->nodeValue == "Bilanzwert")
																				$spalte_bw_nr = $s;
																}
														}

														$cols = $row->getElementsByTagName('td');
														$links = $row->getElementsByTagName('a');
														if (isset($cols->item($cols_th->length)->nodeValue) && $cols->item(0)->nodeValue)
														{
																$xml .= "<ZEILE>";

																$pos = $this->WebttHelper->trimClickttField($cols->item(0)->nodeValue);
																
																$xml .= "<POSITION>";
																$xml .= $pos;
																$xml .= "</POSITION>";
																
																$spieler = trim(str_replace($trim_array, " ", $cols->item(1)->nodeValue));
																$spieler_zus = "";

																if (strstr($spieler, " ("))
																{
																		$sp_exp = explode(" (", $spieler);
																		$spieler = trim($sp_exp[0]);
																		$spieler_zus = "(" . trim($sp_exp[1]);
																		$sp_exp = "";
																}					

																$xml .= "<SPIELER>";
																$xml .= $spieler;
																$xml .= "</SPIELER>";

																$xml .= "<SPIELER_ZUS>";
																$xml .= $spieler_zus;
																$xml .= "</SPIELER_ZUS>";

																if (isset($spalte_bil_nr) && isset($cols->item($spalte_bil_nr)->nodeValue))
																{
																		$bilanz = trim(str_replace($trim_array, "", $cols->item($spalte_bil_nr)->nodeValue));
																		if ($bilanz == "")
																		{
																				$bilanz = "0:0";
																		}
																		$xml .= "<BILANZ>";
																		$xml .= $bilanz;
																		$xml .= "</BILANZ>";
																}

																if (isset($spalte_bw_nr) && isset($cols->item($spalte_bw_nr)->nodeValue))
																{
																		$bw = trim(str_replace($trim_array, "", $cols->item($spalte_bw_nr)->nodeValue));
																		$xml .= "<BILANZWERT>";
																		$xml .= $bw;
																		$xml .= "</BILANZWERT>";
																}
																
																else if (isset($bw))
																{
																		$xml .= "<BILANZWERT>";
																		if (isset($bw) && array_search($spieler, $bw))
																		{
																				$xml .= $bw[$spieler];
																		}
																		$xml .= "</BILANZWERT>";
																}

																$xml .= "<QTTR>";
																$xml .= $qttr[$spieler];
																$xml .= "</QTTR>";
																
																$xml .= "</ZEILE>";

														}
												}
										}
								}
						}

						$xml .= "</BODY>";


						// Überprüfen, ob die Tabelle schon existiert           
						$db = JFactory::getDBO();
						$query = $db->getQuery(true);

						$query
							->select('datum')
							->from('#__webtt_popups')
							->where(
									array(
											'typ=' . $db->quote('mannschaft'),
											'idclicktt=' . $db->quote($teamtable)
											));
		 
						$db->setQuery($query);
						
						if (isset($db->loadObject()->datum))
						{
								// Schreiben des xml in die DB
								$db = JFactory::getDbo();
								 
								$query = $db->getQuery(true);
								 
								// Fields to update.
								$fields = array(
									$db->quoteName('datum') . ' = ' . $db->quote(date("Y-m-d H:i:s")),
									$db->quoteName('xml') . '=' . $db->quote($xml)
								);
								 
								// Conditions for which records should be updated.
								$conditions = array(
									$db->quoteName('typ') . ' = ' . $db->quote('mannschaft'),
									$db->quoteName('idclicktt') . ' = ' . $db->quote($teamtable)
								);
								 
								$query->update($db->quoteName('#__webtt_popups'))->set($fields)->where($conditions);
								 
								$db->setQuery($query);
								 
								$result = $db->execute();
						}

						else
						{
								$db = JFactory::getDbo();
								$query = $db->getQuery(true);
								 
								// Fields to insert
								$fields = array(
									$db->quoteName('datum') . ' = ' . $db->quote(date("Y-m-d H:i:s")),
									$db->quoteName('typ') . ' = ' . $db->quote('mannschaft'),
									$db->quoteName('idclicktt') . ' = ' . $db->quote($teamtable),
									$db->quoteName('name') . ' = ' . $db->quote($mannschaft),
									$db->quoteName('staffel') . ' = ' . $db->quote($staffel),
									$db->quoteName('pfad') . ' = ' . $db->quote($path),
									$db->quoteName('xml') . ' = ' . $db->quote($xml)
								);
								 
								$query->insert($db->quoteName('#__webtt_popups'))->set($fields);
								 
								$db->setQuery($query);
								 
								$result = $db->execute();								
						}
						
				}
				
		}

		public function getHtmlPopupFiles()
		{
				$xmlPopups = $this->getXMLPopups();

				$anz_mann_box_pos 	= JComponentHelper::getParams('com_webtt')->get('tabelle_anz_mann_box_pos');
				$anz_mann_box_bil 	= JComponentHelper::getParams('com_webtt')->get('tabelle_anz_mann_box_bil');
				$anz_mann_box_bw 	= JComponentHelper::getParams('com_webtt')->get('tabelle_anz_mann_box_bw');
				$anz_mann_box_qttr	= JComponentHelper::getParams('com_webtt')->get('tabelle_anz_mann_box_qttr');

				foreach($xmlPopups as $mannschaft => $xml)
				{
						$html = '<html>';
						$html .= '<link rel="stylesheet" href="' . JURI::base() . 'media/com_webtt/css/tabelle_popup.css" type="text/css" />';
						$html .= '<body>';
						$html .= '<div id="webtt">';
						$html .= '<div id="tabelle">';

						if ($xml)
						{
								$html .= '<table>';

								$html .= '<caption>' . $mannschaft . '</caption>';
								
								$html .= "<thead>";
								
								$html .= "<tr>";
								
								if ($anz_mann_box_pos)
								{
										$html .= '<th class="pos">Pos</th>';
								}
								
								$html .= '<th class="spieler">Spieler</th>';

								if ($anz_mann_box_bil)
								{
										$html .= '<th class="bilanz">Bilanz</th>';
								}

								if ($anz_mann_box_bw)
								{
										$html .= '<th class="bw" title="Bilanzwert">BW</th>';
								}

								if ($anz_mann_box_qttr)
								{
										$html .= '<th class="qttr">QTTR</th>';
								}

								$html .= '</tr>';
								$html .= '</thead>';
									
								$html .= '<tbody>';
								foreach ($xmlPopups->{$mannschaft}->xml->ZEILE as $spieler)
								{
										if (isset($spieler->POSITION) && trim($spieler->POSITION)) // nur Einzel anzeigen, bei Doppel steht keine Position
										{
										$html .= '<tr>';

										if ($anz_mann_box_pos)
										{
												$html .= '<td class="position">' . $spieler->POSITION . '</td>';
										}

										$html .= '<td class="paarung">' . $spieler->SPIELER . '</td>';

										if ($anz_mann_box_bil)
										{
												$html .= '<td class="bilanz">' . $spieler->BILANZ . '</td>';
										}

										if ($anz_mann_box_bw)
										{
												$html .= '<td class="bw">' . $spieler->BILANZWERT . '</td>';
										}
										
										if ($anz_mann_box_qttr)
										{
												$html .= '<td class="qttr">' . $spieler->QTTR . '</td>';
										}

										$html .= '</tr>';
										}
								}
									
								$html .= '</tbody>';
								$html .= '</table>';
						}
						
						else
						{
								$html .= '<p>Am ' . date("d.m.y", strtotime($xmlPopups->{$mannschaft}->datum)) . ' war noch keine Aufstellung auf Clicktt eingetragen.</p>';
						}

				$html .= '</div>';
				$html .= '</div>';
				$html .= '</body>';
				$html .= '</html>';

				// SPEICHERN DES POPUPS ALS HTML
				$savePath = JPATH_SITE . '/media/com_webtt/tmp';
				$filename = 'mannschaft_' . $mannschaft . '.html';

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
