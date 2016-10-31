<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
 
// Helper-Klassen
JLoader::import('helpers.webtt', JPATH_COMPONENT_ADMINISTRATOR);
JLoader::import('helpers.menu', JPATH_COMPONENT_ADMINISTRATOR);


class WebttModelAktualisieren extends JModelList
{
        /**
         * Method to build an SQL query to load the list data.
         *
         * @return      string  An SQL query
         */
        protected function getListQuery()
        {
                // Create a new query object.           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                // Select some fields from the hello table
                $query
                    ->select('id,name_webtt,league_clicktt,name_clicktt,name_sp_clicktt')
                    ->from('#__webtt_teams');
 
                return $query;
		}

		// Zuordnung, mit der beim Aktualisieren der Mannschaften Clicktt-Beteichnungen durch interne WebTT-Bezeichnungenersetzt werden sollen
        protected function getMannschaftsnamen()
        {
                // Create a new query object.           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('clicktt,webtt')
                    ->from('#__webtt_mannsch');
 
				$db->setQuery($query);
                $results = $db->loadAssocList();

                return $results;
        }
        
		public function update_teams_from_clicktt()
		{
				$transport = NEW JHttpTransportCurl(NEW JRegistry);
				$http = new JHttp(NEW JRegistry, $transport);
				
				$tmp = $this->getMannschaftsnamen();
				$mannschaftsnamen = array();

				// Assoziatives Array erstellen
				foreach($tmp as $t)
				{
						$mannschaftsnamen[$t['webtt']] = $t['clicktt'];
				}
				
				$verein_nr = JComponentHelper::getParams('com_webtt')->get('verein_nr');
				$verband = JComponentHelper::getParams('com_webtt')->get('verband');
				$verein = JComponentHelper::getParams('com_webtt')->get('verein');
				
				// Pfad zur Clicktt-Seite "Mannschaften und Ligeneinteilung"
				$path = "/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubTeams?club=$verein_nr";
				
				// Clicktt-Vereinsseite "Mannschaften und Ligeneinteilung" lesen
				$page_club = $http->get('http://' . $verband . '.click-tt.de' . $path)->body;
				
				if ($page_club == "")
				{
						return;
				}
				
				$infile_punkt = array();
				$infile_pokal = array();
						
				$dom = @DOMDocument::loadHTML($page_club);
				$tables = $dom->getElementsByTagName('table');
				$h2s = $dom->getElementsByTagName('h2');
				
				$trs = $tables->item(0)->getElementsByTagName('tr');
				foreach ($trs as $tr)
				{
						$tds = $tr->getElementsByTagName('td');

						// Zeile mit Überschrift des Verbandes
						if ($tds->length == 1)
						{
								$h2 = $tds->item(0)->getElementsByTagName('h2');
								$sk_verband = $h2->item(0)->nodeValue;
						}
						
						// Zeile mit Mannschaften
						else if ($tds->length > 2)
						{
								$team_name_clicktt = trim($tds->item(0)->nodeValue);	
								$staffel_name_clicktt = trim($tds->item(1)->nodeValue);	
								$staffel_pfad_clicktt = trim($tds->item(1)->getElementsByTagName('a')->item(0)->getAttribute('href'));

								// Römische Ziffer ermitteln, um die Mannschaft bei mehreren Mannschaften in derselben Staffel zu finden
								$team = explode(" ", $team_name_clicktt);
								if (count($team) && (	$team[count($team)-1] != "II" && 
														$team[count($team)-1] != "III" &&
														$team[count($team)-1] != "IV" &&
														$team[count($team)-1] != "V" &&
														$team[count($team)-1] != "VI" &&
														$team[count($team)-1] != "VII" &&
														$team[count($team)-1] != "VIII" && 
														$team[count($team)-1] != "IX" &&
														$team[count($team)-1] != "X" &&
														$team[count($team)-1] != "XI" &&
														$team[count($team)-1] != "XII" &&
														$team[count($team)-1] != "XIII" &&
														$team[count($team)-1] != "XIV" &&
														$team[count($team)-1] != "XV" ))
								{
										$team_name_clicktt_liga = $verein;
								}

								else
								{
										$rz = $team[count($team)-1];
										$team_name_clicktt_liga = $verein . " " . $rz;
								}

								// Ersetzen des clicktt-Mannschaftsnamens mit den Vorgaben 
								if ( array_search($team_name_clicktt, $mannschaftsnamen) )
								{
										$team_name_webtt = array_search($team_name_clicktt, $mannschaftsnamen);
								}
								
								else
								{
										$team_name_webtt = $team_name_clicktt;
								}
								
								
								// Anpassen des WebTT-Mannschaftsnamens bei halbjährigen Staffeln, meistens in Jugendligen
								if ( strstr($staffel_name_clicktt, " RR") OR strstr($staffel_name_clicktt, "Rückrunde") OR strstr($staffel_name_clicktt, " (RR)") )
								{
										$team_name_webtt = $team_name_webtt . " RR";
								}

								if ( strstr($staffel_name_clicktt, " VR") OR strstr($staffel_name_clicktt, "Vorrunde") )
								{
										$team_name_webtt = $team_name_webtt . " VR";
								}

								if ( strstr($staffel_name_clicktt, " HR")  OR strstr($staffel_name_clicktt, " Hin") OR strstr($staffel_name_clicktt, "Hinrunde") )
								{
										$team_name_webtt = $team_name_webtt . " HR";
								}

								if ( strstr($staffel_name_clicktt, " FR") )
								{
										$team_name_webtt = $team_name_webtt . " FR";
								}
									
								// Anpassen des WebTT-Mannschaftsnamens bei Relegationsspielen
								if ( strstr($staffel_name_clicktt, " Relegation") )
								{
										$team_name_webtt = $team_name_webtt . " REL";
								}

								// Bei Pokalmeldung Eintrag ins Pokal-Array
								if (stristr($sk_verband, "pokal"))
								{
										$infile_pokal[] = "$team_name_webtt|||$staffel_name_clicktt|||$staffel_pfad_clicktt|||$team_name_clicktt|||$team_name_clicktt_liga|||$sk_verband";			
								}

								// Bei Punktspielmeldung den clicktt-Mannschaftspfad ermitteln und Eintrag ins Punktspiel-Array
								else
								{
										$page_liga = $http->get('http://' . $verband . '.click-tt.de' . str_replace("&amp;", "&", $staffel_pfad_clicktt))->body;
										if ($page_liga)
										{
												$dom_liga = @DOMDocument::loadHTML($page_liga);

												if ($dom_liga)
												{
														$tables_liga = $dom_liga->getElementsByTagName('table');
														$tr_liga = $tables_liga->item(0)->getElementsByTagName('tr');

														for ($e = 0 ; $e < $tr_liga->length ; $e++)
														{

																// Prüfen, ob 3.Spalte der Ligatabelle existiert
																if (isset($tr_liga->item($e)->getElementsByTagName('td')->item(2)->nodeValue))
																{
																		$ligaverein = $tr_liga->item($e)->getElementsByTagName('td')->item(2)->nodeValue;
																		$ligaverein = trim($ligaverein);

																		// Prüfen, ob in der Zeile der eigene Verein steht
																		// Bei Relegationsspielen wird in der Tabelle nicht verlinkt
																		if (isset($ligaverein) && $ligaverein == $team_name_clicktt_liga && isset($tr_liga->item($e)->getElementsByTagName('td')->item(2)->getElementsByTagName('a')->item(0)->nodeValue) )
																		{
																				$team_pfad = trim($tr_liga->item($e)->getElementsByTagName('td')->item(2)->getElementsByTagName('a')->item(0)->getAttribute('href'));
																		}
																}
														}
												}
										}
										$infile_punkt[] = "$team_name_webtt|||$team_pfad|||$staffel_name_clicktt|||$staffel_pfad_clicktt|||$team_name_clicktt|||$team_name_clicktt_liga|||$sk_verband";
								}
						}
				}


				// Bisherige Einträge löschen
				$db = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->delete($db->quoteName('#__webtt_teams'));
				$db->setQuery($query);
				$result = $db->execute();

		
				// Speichern in DB
				// Datenbankobjekt laden
				$db = JFactory::getDBO();
				 
				foreach ($infile_punkt as $row)
				{
						// Objekt erstellen
						$wert = new StdClass();
						
						$k = explode("|||", $row);
						// Werte zuweisen
						$wert->id= null;
						$wert->typ = 'punkt';
						$wert->name_webtt = $k[0];
						$wert->path_clicktt = $k[1];
						$wert->league_clicktt = $k[2];
						$wert->path_league_clicktt = $k[3];
						$wert->name_clicktt = $k[4];
						$wert->name_sp_clicktt = $k[5];
						$wert->sk_verband = $k[6];
						 
						$tabelle = '#__webtt_teams';
						 
						// Werte Speichern/Query ausführen
						$db->insertObject($tabelle, $wert, 'id');
				}

				foreach ($infile_pokal as $row)
				{
						// Objekt erstellen
						$wert = new StdClass();
						
						$k = explode("|||", $row);
						// Werte zuweisen
						$wert->id= null;
						$wert->typ = 'pokal';
						$wert->name_webtt = $k[0];
						$wert->league_clicktt = $k[1];
						$wert->path_league_clicktt = $k[2];
						$wert->name_clicktt = $k[3];
						$wert->name_sp_clicktt = $k[4];
						$wert->sk_verband = $k[5];
						 
						$tabelle = '#__webtt_teams';
						 
						// Werte Speichern/Query ausführen
						$db->insertObject($tabelle, $wert, 'id');
				}
				
				//NOCH NICHT EINGETRAGENE STAFFELN IN #__webtt_staffeln SCHREIBEN

				// BISHER GESPEICHERTE SPIELER ABRUFEN
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('clicktt_lang')
                    ->from('#__webtt_staffeln');
									
				$db->setQuery($query);

				$staffeln_old = $db->loadColumn();

				$db = JFactory::getDBO();
				foreach ($infile_punkt as $row)
				{
						// Objekt erstellen
						
						$k = explode("|||", $row);
						
						if (in_array($k[2], $staffeln_old) === false)
						{
								$wert = new StdClass();
								
								$wert->id= null;
								$wert->clicktt_lang = $k[2];
						 
								$tabelle = '#__webtt_staffeln';

								$db->insertObject($tabelle, $wert, 'id');
						}
				}

				// MENÜPUNKTE ERSTELLEN
				$this->WebttHelper = new WebttHelper;
				$this->WebttHelperMenu = new WebttHelperMenu;
				$createMenupoints = $this->WebttHelperMenu->createMenupoints();
		}



		// Clicktt-Links zu den Staffeln
        protected function getPathLeaguesClicktt()
        {
                // Create a new query object.           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('path_league_clicktt')
                    ->from('#__webtt_teams');
 
				$db->setQuery($query);
                $results = $db->loadColumn();

                return $results;
        }

		public function update_hallen_auswaerts_from_clicktt()
		{
				$transport = NEW JHttpTransportCurl(NEW JRegistry);
				$http = new JHttp(NEW JRegistry, $transport);
		
				$verein_nr = JComponentHelper::getParams('com_webtt')->get('verein_nr');
				$verband = JComponentHelper::getParams('com_webtt')->get('verband');
				$verein = JComponentHelper::getParams('com_webtt')->get('verein');
				
				
				// Holen der Links zu den Staffeln aus der webtt_teams - Tabelle
				$paths = $this->getPathLeaguesClicktt();
				
				// Ermitteln der Links zu den Mannschaftsseiten in $hrefs
				foreach ($paths as $path_league)
				{
						// 
						$leaguepage = $http->get('http://' . $verband . '.click-tt.de' . $path_league)->body;

						if ($leaguepage)  {
								$dom = @DOMDocument::loadHTML($leaguepage);

								if (!$dom)
								{ 
										echo 'Fehler beim Parsen des Dokuments'; 
								} 
						
								$dom->preserveWhiteSpace = false;
								$tables = $dom->getElementsByTagName('table');
								$rows = $tables->item(0)->getElementsByTagName('tr');

								foreach ($rows as $row)
								{
										$links = $row->getElementsByTagName('a');
										foreach($links as $link)
										{
												// Array mit 'Vereinsname aus der Tabelle', 'Link zur Mannschaftsseite der Staffel'
//												if (!array_search())
												$hrefs[$link->nodeValue] = trim($link->getattribute('href'));
										}
								}
						}
				}

				// Array mit Vereine, bei denen die Hallen ermittelt wurden
				// Wird gebraucht, um Mansschaften aus demselben Verein nicht einzutragen
				$clubs = array();
				
				foreach($hrefs as $club =>$path_team)
				{
						// Mannschaftsseite con clicktt holen 
						$teampage = $http->get('http://' . $verband . '.click-tt.de' . $path_team)->body;

						$teampage = str_replace("<br />", "<br />\n", $teampage);
						$dom2 = @DOMDocument::loadHTML($teampage);
						$dom2->preserveWhiteSpace = false;
						$tables2 = $dom2->getElementsByTagName('table');
						$rows2 = $tables2->item(0)->getElementsByTagName('tr');

						foreach ($rows2 as $row2)
						{
								if(strstr($row2->nodeValue, "Verein"))
								{

										$cols2 = $row2->getElementsByTagName('td');
										$a2 = $row2->getElementsByTagName('a');

										if ($cols2->length > 1)
										{

												$daten = explode("\n", $cols2->item(1)->nodeValue);
												$spl = array();

												$club = trim($daten[1]);
												if (!in_array($club, $clubs))
												{
														$clubs[] = $club;

														for($x=0;$x<count($daten);$x++)
														{
																
																if (trim($daten[$x]))
																{
																		$daten[$x] = str_replace('Spiellokal ', '', $daten[$x]);
																		$daten[$x] = str_replace(':', '', $daten[$x]);
																		$spl[] = trim($daten[$x]);
																}
														}
														$hallen[] = $spl;
														break; // 1.Zeile (2.Spalte) stehen die Spiellokale
												}
										}
								}
						}
				}


				// Bisherige Einträge löschen
				$db = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->delete($db->quoteName('#__webtt_hallen'));
				$db->setQuery($query);
				$result = $db->execute();

		
				// Speichern in DB
				// Datenbankobjekt laden
				$db = JFactory::getDBO();
				 
				foreach ($hallen as $k)
				{
						// Objekt erstellen
						$wert = new StdClass();
						
//						$k = explode("|||", $row);
						// Werte zuweisen
						$wert->id= null;
						$wert->verein = $k[0];
						$wert->halle_1 = $k[2];
						$wert->addr_1 = $k[3];
						
						if (isset($k[6]))
						{
								$wert->halle_2 = $k[5];
								$wert->addr_2 = $k[6];
						}
						if (isset($k[9]))
						{
								$wert->halle_3 = $k[8];
								$wert->addr_3 = $k[9];
						}
						 
						$tabelle = '#__webtt_hallen';
						 
						// Werte Speichern/Query ausführen
						$db->insertObject($tabelle, $wert, 'id');
				}
		}

		// AKTUALISIERT DIE SPIELER VON CLICKTT
		public function update_spieler_from_clicktt()
		{
				$transport = NEW JHttpTransportCurl(NEW JRegistry);
				$http = new JHttp(NEW JRegistry, $transport);
				
				$this->WebttHelper = new WebttHelper;
		
				$verein_nr = JComponentHelper::getParams('com_webtt')->get('verein_nr');
				$verein = JComponentHelper::getParams('com_webtt')->get('verein');
				$saison_hin = JComponentHelper::getParams('com_webtt')->get('saison_hin');
				$saison_rueck = JComponentHelper::getParams('com_webtt')->get('saison_rueck');
				$saison = JComponentHelper::getParams('com_webtt')->get('saison');
				if ($saison == "hin")
				{
						$saison = "vor";
				}


				$path = "/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubPools?club=$verein_nr";
				$page = $http->get($this->WebttHelper->getHostClicktt() . $path)->body;

				$dom = @DOMDocument::loadHTML($page);
				$dom->preserveWhiteSpace = true;
				
				$tables = $dom->getElementsByTagName('table');
				$rows = $tables->item(0)->getElementsByTagName('tr');
				
				foreach ($rows as $row)
				{
						$cols = $row->getElementsByTagName('td');
						$ths = $row->getElementsByTagName('th');
						
						if ($cols->length > 2)
						{
								$ak = $cols->item(0)->nodeValue;
								$meldungen = $cols->item(1)->nodeValue;
								$bilanzen = $cols->item(2)->nodeValue;
								
								if (isset($cols->item(1)->nodeValue))
								{
										$a = $cols->item(1)->getElementsByTagName('a');
										if (isset($a->item(0)->nodeValue))
										{
												$url = WebttHelper::getHostClicktt() . $a->item(0)->getattribute('href');
												$url_query = parse_url($url, PHP_URL_QUERY);
												parse_str($url_query, $query);
												$season = $query['seasonName'];

												if (strstr($season, $saison_hin . "/"))
												{
														$paths_meldungen[$ak] = $a->item(0)->getattribute('href');
												}
										}
								}
						}
						
				}


				foreach($paths_meldungen as $ak => $path)
				{
						// MANNSCHAFTEN UND LIGENEINTEILUNG
						
						// Clicktt-Vereinsseite lesen
						$page = $http->get(WebttHelper::getHostClicktt() . $path)->body;
						
						$dom = @DOMDocument::loadHTML($page);
						$dom->preserveWhiteSpace = true;

						$h2s = $dom->getElementsByTagName('h2');
						foreach ($h2s as $h2)
						{
						}
						
						$tables = $dom->getElementsByTagName('table');
						$rows = $tables->item(0)->getElementsByTagName('tr');
						
						foreach ($rows as $row)
						{
								$th = $row->getElementsByTagName('th');
								
								if (isset($th->item(0)->nodeValue) === FALSE)
								{
										$cols = $row->getElementsByTagName('td');
										$expl = explode(".", $cols->item(0)->nodeValue);
										
										
										if (isset($cols->item(2)->nodeValue))
										{
												$qttr = $cols->item(1)->nodeValue;
												$spielername = $this->WebttHelper->trimClickttField(($cols->item(2)->nodeValue));

												$a = $cols->item(2)->getElementsByTagName('a');
												$spieler_url = WebttHelper::getHostClicktt() . $a->item(0)->getattribute('href');
												$spieler_url_query = parse_url($spieler_url, PHP_URL_QUERY);
												parse_str($spieler_url_query, $spieler_query);
												$person = $spieler_query['person'];
												
												$status = $cols->item(3)->nodeValue;

												$spieler[] = array($ak, $expl[0], $expl[1], $qttr, $status, $person, $spielername);
												
										}
								}
						}
				}
				
				// BISHER GESPEICHERTE SPIELER ABRUFEN
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('name')
                    ->from('#__webtt_spieler');
									
				$db->setQuery($query);

				$spieler_old = $db->loadColumn();

		
				// Speichern in DB
				// Datenbankobjekt laden
				$db = JFactory::getDBO();
				 
				foreach ($spieler as $k)
				{
						// Objekt erstellen
						$wert = new StdClass();
						
//						$k = explode("|||", $row);
						// Werte zuweisen
						$wert->id= null;
						$wert->name = $k[6];
						$wert->ak = $k[0];
						$wert->team = $k[1];
						$wert->position = $k[2];
						$wert->qttr = $k[3];
						$wert->status = $k[4];
						$wert->clicktt_nr = $k[5];

						$tabelle = '#__webtt_spieler';
						
						if (in_array($k[6], $spieler_old))
						{
								$db->updateObject($tabelle, $wert, 'name');
						}
						
						else
						{
								$db->insertObject($tabelle, $wert, 'id');						 
						}
				}
		}
}
