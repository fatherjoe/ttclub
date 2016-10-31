<?php

// no direct access
defined('_JEXEC') or die;

JLoader::import('helpers.webtt', JPATH_COMPONENT);
JLoader::import('helpers.bw', JPATH_COMPONENT);
JLoader::import('helpers.qttr', JPATH_COMPONENT);
JLoader::import('helpers.kalender', JPATH_COMPONENT);


class WebttHelperSpielplan extends WebttHelper
{

		// Abrufen der Tabelle von clicktt und in der Datenbank speichern
		// public - wird von getXML() aufgerufen
		public function update($team)
		{
				$verein_nr = JComponentHelper::getParams('com_webtt')->get('verein_nr');
				$verband = JComponentHelper::getParams('com_webtt')->get('verband');
				$verein = JComponentHelper::getParams('com_webtt')->get('verein');

				$transport = NEW JHttpTransportCurl(NEW JRegistry);
				$http = new JHttp(NEW JRegistry, $transport);

				// Pfad der Staffelseite von clicktt abfragen
				$path_team = $this->getPathTeamClicktt($team);

				if (strstr($path_team, "pageState=vorrunde") OR strstr($path_team, "pageState=rueckrunde") )
				{
						$path_rueck = str_replace("pageState=vorrunde", "pageState=rueckrunde", $path_team);

						$path_vor = str_replace("pageState=rueckrunde", "pageState=vorrunde", $path_team);



		//				$teampage_rueck = $http->get('http://' . $verband . '.click-tt.de' . $path_rueck)->body;
						
						
						
						/** Tabelle Vorrunde */	

						// Wenn die Staffel nur für die Rückserie gilt, und der Mannschaftsname entsprechend ergänzt wurde, dann nicht nach Vorrundenpokal suchen
						if (!strstr(PARENT::getTeam(), " RR"))
						{
								$teampage_vor = $http->get($this->getHostClicktt() . $path_vor)->body;
		//					$sp_body = call_user_func("get_pokal", $page_team_vor, "vor");

								$xml = '<?xml version="1.0" encoding="utf-8"?>';
								$xml .= "<BODY>";

								if ($teampage_vor)
								{
$zeitraum = "vor";
$halbserie = "hin";
										$dom = @DOMDocument::loadHTML($teampage_vor);

										$dom->preserveWhiteSpace = false;
										$tables = $dom->getElementsByTagName('table');
										$h2s = $dom->getElementsByTagName('h2');

										// Bestimmen, in der wievielten Tabelle der Spielplan steht
										// im HTTV steht in der ersten Tabelle immer das Login-Feld
										for ($q = 0; $q < $h2s->length; $q++)
										{

												if (strstr($h2s->item($q)->nodeValue, "Spieltermine"))
												{
														if ($verband != "httv")
														{
																$tablenr_spterm = $q + 1;	// plus 1, da in der ersten Tabelle die Vereinsinformationen stehen
														}
														else
														{
																$tablenr_spterm = $q;  // Im HTTV befindet sich auf allen Seite der Link zum Login als h2-Überschrift
														}

														if (strstr($h2s->item($q)->nodeValue, "Spieltermine") && strstr($h2s->item($q)->nodeValue, "Vorrunde"))
														{
																$tabelle_vor = $q;
														}

														if (strstr($h2s->item($q)->nodeValue, "Spieltermine") && strstr($h2s->item($q)->nodeValue,  "Rückrunde"))
														{
																$tabelle_rueck = $q;
														}
												}
										}


										if (isset($tablenr_spterm))
										{
												if ($spielpl_anz_cal = "on")
												{
														$vcal = "BEGIN:VCALENDAR\r\n";
														$vcal .= "VERSION:1.0\r\n";
														$vcal .= "PRODID:WebTT\r\n";
														$vcal .= "METHOD:PUBLISH\r\n";
														$vcal .= "X-WR-TIMEZONE:Europe/Berlin\r\n";
														$vcal .= "CALSCALE:GREGORIAN\r\n";

														$vcal .= "BEGIN:VTIMEZONE\r\n";
														$vcal .= "TZID:Europe/Berlin\r\n";
														$vcal .= "X-LIC-LOCATION:Europe/Berlin\r\n";
														$vcal .= "BEGIN:DAYLIGHT\r\n";
														$vcal .= "TZOFFSETFROM:+0100\r\n";
														$vcal .= "TZOFFSETTO:+0200\r\n";
														$vcal .= "TZNAME:CEST\r\n";
														$vcal .= "DTSTART:19700329T020000\r\n";
														$vcal .= "RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU\r\n";
														$vcal .= "END:DAYLIGHT\r\n";
														$vcal .= "BEGIN:STANDARD\r\n";
														$vcal .= "TZOFFSETFROM:+0200\r\n";
														$vcal .= "TZOFFSETTO:+0100\r\n";
														$vcal .= "TZNAME:CET\r\n";
														$vcal .= "DTSTART:19701025T030000\r\n";
														$vcal .= "RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU\r\n";
														$vcal .= "END:STANDARD\r\n";
														$vcal .= "END:VTIMEZONE\r\n";	
														//X-WR-TIMEZONE:Europe/Berlin";

														$ical = "BEGIN:VCALENDAR\r\n";
														$ical .= "VERSION:2.0\r\n";
														$ical .= "METHOD:PUBLISH\r\n";
														$ical .= "PRODID:-//WebTT//\r\n";
														$ical .= "X-WR-TIMEZONE:Europe/Berlin\r\n";
														$ical .= "CALSCALE:GREGORIAN\r\n";
														
														$ical .= "BEGIN:VTIMEZONE\r\n";
														$ical .= "TZID:Europe/Berlin\r\n";
														$ical .= "X-LIC-LOCATION:Europe/Berlin\r\n";
														$ical .= "BEGIN:DAYLIGHT\r\n";
														$ical .= "TZOFFSETFROM:+0100\r\n";
														$ical .= "TZOFFSETTO:+0200\r\n";
														$ical .= "TZNAME:CEST\r\n";
														$ical .= "DTSTART:19700329T020000\r\n";
														$ical .= "RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU\r\n";
														$ical .= "END:DAYLIGHT\r\n";
														$ical .= "BEGIN:STANDARD\r\n";
														$ical .= "TZOFFSETFROM:+0200\r\n";
														$ical .= "TZOFFSETTO:+0100\r\n";
														$ical .= "TZNAME:CET\r\n";
														$ical .= "DTSTART:19701025T030000\r\n";
														$ical .= "RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU\r\n";
														$ical .= "END:STANDARD\r\n";
														$ical .= "END:VTIMEZONE\r\n";	

														$uid = "";
												}


												// Wenn Spielplan für Hin- und Rückserie auf derselben clicktt-Seite liegen
												if ($tables->length == 3 && $zeitraum == "rueck")
												{
														$rows = $tables->item(1)->getElementsByTagName('tr');
												}

												else if ($tables->length >= 3 && $zeitraum == "vor")
												{
														$rows = $tables->item(1)->getElementsByTagName('tr');
												}

												else if ($tables->length == 4 && $zeitraum = "rueck")
												{
														$rows = $tables->item(2)->getElementsByTagName('tr');
												}

												// Hin- und Rückserie auf verschiedenen Seiten
												else
												{
														$rows = $tables->item($tablenr_spterm)->getElementsByTagName('tr');
												}


												$trim_array = array(chr(194), chr(160), chr(10));
												
														
												$rows = $tables->item($tablenr_spterm)->getElementsByTagName('tr');
												
												// ZEILEN DES SPIELPLANS
												foreach ($rows as $row)
												{
														$head = $row->getElementsByTagName('th');

														// KOPFZEILEN AUSSCHLIEẞEN
														if (!isset($head->item(0)->nodeValue))
														{
																$datum = "";
																if ($datum)
																{
																		$datum_hidden = "";
																}
																
																$heim = "";
																$gast = "";
																$halle = "";
																$halle_addr = "";
																$halle_kuerz = "";
																$halle_beschr = "";
																
																$zeit_verl = "";
																$verl_unbek = "";
																$uhrzeit_verl = "";
																$halle_verl = "";
																$heim_tausch = "";
																$erg = "";
																$erg_path = "";
																$kampflos = "";
																$meeting = "";
																
																$heim_webtt = "";
																$gast_webtt = "";
																
																$wt_alt = "";

																$cols = $row->getElementsByTagName('td');

																$wochentag = $this->trimClickttField($cols->item(0)->nodeValue);

																if (trim(str_replace($trim_array, "", $cols->item(1)->nodeValue)))
																{
																		if ($datum)
																		{
																				$datum_hidden = $datum;
																		}

																		$datum = date("d.m.y", strtotime(trim(str_replace($trim_array, "", $cols->item(1)->nodeValue))));
																}
																	else
																{
																		$datum = ""; // Spiel findet am selben Datum statt
																}

																// Datumsformat für vcal- und ical-Kalender
																$datum_cal = date("Ymd", strtotime(trim(str_replace($trim_array, "", $cols->item(1)->nodeValue))));

																// UHRZEIT
																$zeit = trim(str_replace($trim_array, "", $cols->item(2)->nodeValue));
																
																if (stristr($zeit, "v"))
																{
																		$zeit = trim(str_replace("v", "", $zeit));
																		$zeit_title = $cols->item(2)->getAttribute('title');

																		preg_match('/(\d{2}\.\d{2}\.\d{4})/', $zeit_title, $datum_alt);
																		preg_match('/(\d{2}\:\d{2})/', $zeit_title, $uhrzeit_alt);
																		$da_expl = explode(".", trim($datum_alt[0]));
																		$tag_alt = trim($da_expl[0]);
																		$monat_alt = $da_expl[1];
																		$jahr_alt = trim($da_expl[2]);

																		if ($datum == trim($tag_alt) . "." . $monat_alt . "." . trim(substr($jahr_alt, 2)) OR $datum_hidden == $tag_alt . "." . $monat_alt . "." . trim(substr($jahr_alt, 2)) )
																		{
																				$uhrzeit_verl = 'ja';
																		}

																		else
																		{
																				$zeit_verl =  'ja';
																				$wt_array = array( "Mon" => "Mo", "Tue" => "Di", "Wed" => "Mi", "Thu" => "Do", "Fri" => "Fr", "Sat" => "Sa", "Sun" => "So");
																				$wt_alt = $wt_array[date("D", strtotime("$jahr_alt-$monat_alt-$tag_alt"))];
																		}
																}
																
																if (stristr($zeit, "t"))
																{
																		$zeit = str_replace("t", "", $zeit);
																		$heim_tausch = TRUE;
																}
																
																if (stristr($zeit, "h"))
																{
																		$zeit = trim(str_replace("h", "", $zeit));
																		$halle_verl = TRUE;
																}
																
																if (stristr($zeit, "u"))
																{
																		$zeit = trim(str_replace("u", "", $zeit));
																		$verl_unbek = TRUE;
																}
																
																if (strstr($zeit, "/"))
																{
																		$zeit = trim(str_replace("/", "", $zeit));
																}
																
																$xml .= "<ZEILE>";
																$xml .= "<WOCHENTAG>$wochentag</WOCHENTAG>";
																$xml .= "<DATUM>$datum</DATUM>";
																$xml .= "<UHRZEIT>$zeit</UHRZEIT>";
																$xml .= "<HEIM_TAUSCH>$heim_tausch</HEIM_TAUSCH>";
																$xml .= "<VERL>$wt_alt</VERL>";	

																// HALLE
																$halle = trim(str_replace($trim_array, "", $cols->item(3)->nodeValue));

																// EXTERNE HALLE
																if ($halle == "(H)")
																{
																	$halle_verl = $cols->item(3)->getElementsByTagName('span')->item(0)->getAttribute('title');
																}
																
																
																$xml .= "<HALLE>$halle</HALLE>";
																$xml .= "<HALLE_VERL>$halle_verl</HALLE_VERL>";
																
																// HEIMMANNSCHAFT
																$heim = trim(str_replace($trim_array, "", $cols->item($cols->length-6)->nodeValue));
																if ($this->WebttHelper->getRowTeam()->name_sp_clicktt == $heim)
																{
																		$heim_webtt = $team;
																}
																$xml .= "<HEIM>$heim</HEIM>";
																
																// GASTMANNSCHAFT
																$gast = trim(str_replace($trim_array, "", $cols->item($cols->length-5)->nodeValue));
																if ($this->WebttHelper->getRowTeam()->name_sp_clicktt == $gast)
																{
																		$gast_webtt = $team;
																}
																$xml .= "<GAST>$gast</GAST>";
																
																// LINK ZUM SPIELBERICHT AUSLESEN
																if (isset($cols->item($cols->length-4)->nodeValue))
																{
																		$erg = trim(str_replace($trim_array, "", $cols->item($cols->length-4)->nodeValue));
																		$a = $cols->item($cols->length-4)->getElementsByTagName('a');
																		if (isset($a->item(0)->nodeValue))
																		{
																				$path_erg = $a->item(0)->getAttribute('href'); // 
																				$erg_path = $a->item(0)->getAttribute('href');
																				$erg_path = str_replace("&", "&amp;", $a->item(0)->getAttribute('href'));
																				
																				$link_erg = $this->WebttHelper->getHostClicktt() . $erg_path;
																				
																				parse_str(parse_url($link_erg, PHP_URL_QUERY), $query);
																				$meeting = $query['meeting'];
																		}
																		else
																		{
																				$erg_path = "";
																		}

																		$img_checked = $cols->item($cols->length-2)->getElementsByTagName('img');
																		if (isset($img_checked->item(0)->nodeValue))
																		{
																				$sp_checked = trim($img_checked->item(0)->getAttribute('title'));
																		}
																		else
																		{
																				$sp_checked = "nein";
																		}

																		// Ergebniskommentar
																		$komm = trim(str_replace($trim_array, "", $cols->item($cols->length-2)->nodeValue));
																		if (strstr($komm, "NA"))
																		{
																				$kampflos = "ja";
																		}
																		
																}
																
																if (isset($meeting) && $meeting)
																{	
																		$xml .= "<MEETING>$meeting</MEETING>";
																		$xml .= "<ERGEBNIS>$erg</ERGEBNIS>";
																		$xml .= "<ERGEBNIS_PFAD>$erg_path</ERGEBNIS_PFAD>";
																}
																
																$xml .= "</ZEILE>";


																// SPIEL IN KALENDER EINTRAGEN
																if ($spielpl_anz_cal = "on")
																{
																		$zeit_cal = str_replace(":", "", trim($zeit));

																		$uid++;
																		$vcal .= "BEGIN:VEVENT\r\n";
																		$vcal .= "UID:$uid\r\n";
																		$vcal .= "SUMMARY:";
																		
																		if ($heim_webtt)
																		{
																				$vcal .= $heim_webtt;
																		}
																		
																		else
																		{
																				$vcal .= $heim;
																		}
																		
																		$vcal .= " - ";

																		if ($gast_webtt)
																		{
																				$vcal .= $gast_webtt;
																		}
																		
																		else
																		{
																				$vcal .= $gast;
																		}

																		$vcal .= "\r\n";

																		if ($halle_beschr)
																		{
																				$vcal .= "LOCATION:$halle_beschr\r\n";
																		}
																		
																		else
																		{
																				$vcal .= "LOCATION:n/a\r\n";
																		}

																		$vcal .= "DESCRIPTION;ENCODING=QUOTED-PRINTABLE:PUNKTSPIEL=0D=0A";
																		
																		if ($heim_webtt)
																		{
																				$vcal .= $heim_webtt;
																		}

																		else
																		{
																				$vcal .= $heim;
																		}

																		$vcal .= " - ";
																		
																		if ($gast_webtt)
																		{
																				$vcal .= $gast_webtt;
																		}
																			
																		else
																		{
																				$vcal .= $gast;
																		}

																		$vcal .= "=0D=0A=0D=0A SPIELORT=0D=0A";
																	
																		if ($halle_beschr)
																		{
																				$vcal .= "$halle_beschr=0D=0A$halle_addr";
																		}

																		else
																		{
																				$vcal .= "n/a";
																		}
																		 
																		if (isset($meeting) && $meeting)
																		{
																				$vcal .= "=0D=0A=0D=0AERGEBNIS=0D=0A$erg";
																		}
																		
																		$vcal .= "\r\n";
																		$vcal .= "CLASS:PUBLIC\r\n";
																		$vcal .= "CATEGORIES:".$this->WebttHelper->getTeam()."\r\n";
																		$vcal .= "DTSTART;TZID=Europe/Berlin:".$datum_cal."T".$zeit_cal."00\r\n";
																		$vcal .= "DTEND;TZID=Europe/Berlin:".$datum_cal."T".($zeit_cal+300)."00\r\n";
																		$vcal .= "BEGIN:VALARM\r\n";
																		$vcal .= "TRIGGER:-PT10080M\r\n";
																		$vcal .= "ACTION:DISPLAY\r\n";
																		$vcal .= "DESCRIPTION:Erinnerung\r\n";
																		$vcal .= "End:VALARM\r\n";
																		$vcal .= "END:VEVENT\r\n";

																		$ical .= "BEGIN:VEVENT\r\n";
																		$ical .= "UID:$uid\r\n";
																		$ical .= "SUMMARY:\r\n ";
																		
																		if ($heim_webtt)
																		{
																				$ical .= $heim_webtt;
																		}

																		else
																		{
																				$ical .= $heim;
																		}
																		
																		$ical .= "\r\n " . ' - ' . "\r\n ";
																		
																		if ($gast_webtt)
																		{
																				$ical .= $gast_webtt;
																		}
																		
																		else
																		{
																				$ical .= $gast;
																		}

																		$ical .= "\r\n";
																		$ical .= "LOCATION:";
																		if ($halle_beschr)
																		{
																				$ical .= $halle_beschr;
																		}

																		else
																		{
																				$ical .= "n/a";
																		}
																		
																		$ical .= "\r\n";
																		$ical .= 'DESCRIPTION:' . "\r\n "; 
																		$ical .= '<p style=\"margin:0;\"><span style=\"font-weight:bold;\">' . "\r\n ";
																		$ical .= 'PUNKTSPIEL</span><br />' . "\r\n ";

																		if ($heim_webtt)
																		{
																				$ical .= $heim_webtt;
																		}

																		else
																		{
																				$ical .= $heim;
																		}
																		
																		$ical .= "\r\n " . ' - ' . "\r\n ";

																		if ($gast_webtt)
																		{
																				$ical .= $gast_webtt;
																		}
																	
																		else
																		{
																				$ical .= $gast;
																		}

																		$ical .= "\r\n ";
																		$ical .= '</p>';
																		$ical .= '<p><span style=\"font-weight:bold;\">SPIELORT</span><br />' . "\r\n ";
																		if ($halle_beschr)
																		{
																				$ical .= $halle_beschr . '<br />' . "\r\n ";
																				$ical .= trim($halle_addr) . '</p>' . "\r\n ";
																		}

																		if (isset($meeting) && $meeting)
																		{
																				$ical .= '<p><span style=\"font-weight:bold;\">Ergebnis</span><br />' . "\r\n ";
																				$ical .= '<a href=\"' . "\r\n ";
																			
																				$link_erg = str_replace("&", "&amp;", $link_erg) . "\r\n ";
																				if (strlen($link_erg) > 70)
																				{
																						$link_erg = substr($link_erg,0,70) . "\r\n " . substr($link_erg,70);
																				}
																				
																				if (strlen($link_erg) > 143)
																				{
																						$link_erg = substr($link_erg,0,143) . "\r\n " . substr($link_erg,143);
																				}

																				$ical .= $link_erg;
																				$ical .= '\">' . $erg . '</a></p>';
																		}
												
																		$ical .= "\r\n";

																		$ical .= "CLASS:PUBLIC\r\n";
																		$ical .= "CATEGORIES:Punktspiel\,".$this->WebttHelper->getTeam()."\r\n";
																		$ical .= "DTSTART;TZID=Europe/Berlin:".$datum_cal."T".$zeit_cal."00\r\n";
																		$ical .= "DTEND;TZID=Europe/Berlin:".$datum_cal."T".($zeit_cal+300)."00\r\n";

																		$ical .= "BEGIN:VALARM\r\n";
																		$ical .= "TRIGGER:-PT10080M\r\n";
																		$ical .= "ACTION:DISPLAY\r\n";
																		$ical .= "DESCRIPTION:Reminder\r\n";
																		$ical .= "END:VALARM\r\n";

																		$ical .= "END:VEVENT\r\n";
																}

														}

												}
												$xml .= "</BODY>";

												// KALENDER
												if ($spielpl_anz_cal = "on")
												{
														$vcal .= "END:VCALENDAR\r\n";
														$ical .= "END:VCALENDAR\r\n";
												}


												/*
												 * KALENDER IN DB SCHREIBEN
												 *
												 */

												$this->WebttHelperKalender = new WebttHelperKalender;
												
												$storeVcal = $this->WebttHelperKalender->storeKalender($this->WebttHelper->getTeam(),$vcal,$ical,$halbserie);
										}
										
										else
										{
												$xml .= "<MESSAGE>Am noch kein Spielplan in clicktt eingetragen</MESSAGE></BODY>";
										}

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
											->where(array('team='.$db->quote($team), 'typ=' . $db->quote('spielplan')));
						 
										$db->setQuery($query);
										
										if (isset($db->loadObject()->datum))
										{

												$db = JFactory::getDbo();
												$query = $db->getQuery(true);
												 
												// Fields to update.
												$fields = array(
													$db->quoteName('xml') . ' = ' . $db->quote($xml),
													$db->quoteName('datum') . ' = ' . $db->quote(date("Y-m-d H:i:s"))
												);
												 
												// Conditions for which records should be updated.
												$conditions = array(
													$db->quoteName('team') . ' = ' . $db->quote($team), 
													$db->quoteName('typ') . ' = ' . $db->quote('spielplan')
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
													$db->quoteName('typ') . ' = ' . $db->quote('spielplan'),
													$db->quoteName('team') . ' = ' . $db->quote($team),
													$db->quoteName('xml') . ' = ' . $db->quote($xml)
												);
												 
												$query->insert($db->quoteName('#__webtt_tabellen'))->set($fields);
												 
												$db->setQuery($query);
												 
												$result = $db->execute();
										}
								}
						}
				}
		}
		
		
		// AKTUALISIEREN DER SPIELBERICHTE VOM CLICKTT-SPIELPLAN
		public function update_popup($meeting,$path)
		{
				$staffel = PARENT::getLeagueClicktt();
				$verband = JComponentHelper::getParams('com_webtt')->get('verband');

				$transport = NEW JHttpTransportCurl(NEW JRegistry);
				$http = new JHttp(NEW JRegistry, $transport);

				$meetingpage = $http->get('http://' . $verband . '.click-tt.de' . $path)->body;
				$trim_array = array(chr(194), chr(160), chr(10));

				if ($meetingpage)
				{
						$dom = @DOMDocument::loadHTML($meetingpage);

						$dom->preserveWhiteSpace = true;
						$tables = $dom->getElementsByTagName('table');
			
						if ($dom)
						{ 
								$dom->preserveWhiteSpace = true;
								$tables = $dom->getElementsByTagName('table');

								$xml = '<?xml version="1.0" encoding="utf-8"?>';
								$xml .= "<BODY>";

								// Überprüfung, ob die Tabelle mit den Bilanzen existiert (sind Ergebnisse von diesem Spieler vorhanden?), wenn nicht vorhanden, dann wird leere Datei für diesen Spieler angelegt
								if (isset($tables->item(0)->nodeValue))	
								{

										$rows = $tables->item(0)->getElementsByTagName('tr');
										foreach ($rows as $row)
										{
												$cols = $row->getElementsByTagName('td');

												if (isset($cols->item(1)->nodeValue)) //Wenn in zweiter Spalte ein 	"-" ist, wie "1-2"
												{
														$xml .= '<ZEILE>';

														$xml .= '<PAARUNG>';
														$xml .= str_replace($trim_array, "", $cols->item(0)->nodeValue);
														$xml .= '</PAARUNG>';
														
														$xml .= '<HEIMSPIELER>';
														$u = "";
														foreach ($cols->item(1)->childNodes as $heimspieler)
														{
																if (trim($heimspieler->textContent))
																{
																		if ($u)
																		{
																				$xml .= "<br />";
																		}
																		
																		$xml .= trim($heimspieler->textContent);
																		$u++;
																}
														}
														$xml .= '</HEIMSPIELER>';

														$xml .= '<GASTSPIELER>';
														$u = "";
														foreach ($cols->item(2)->childNodes as $gastspieler)
														{
																if (trim($gastspieler->textContent))
																{
																		if ($u)
																		{
																				$xml .= "<br />";
																		}
																		
																		$xml .= trim($gastspieler->textContent);
																		$u++;
																}
														}
														$xml .= '</GASTSPIELER>';

														$xml .= '<SATZERGEBNISSE>';
														$xml .= '<SATZ_1>' . trim(str_replace($trim_array, "", $cols->item(3)->nodeValue)) . '</SATZ_1>';
														$xml .= '<SATZ_2>' . trim(str_replace($trim_array, "", $cols->item(4)->nodeValue)) . '</SATZ_2>';
														$xml .= '<SATZ_3>' . trim(str_replace($trim_array, "", $cols->item(5)->nodeValue)) . '</SATZ_3>';
														$xml .= '<SATZ_4>' . trim(str_replace($trim_array, "", $cols->item(6)->nodeValue)) . '</SATZ_4>';
														$xml .= '<SATZ_5>' . trim(str_replace($trim_array, "", $cols->item(7)->nodeValue)) . '</SATZ_5>';
														$xml .= '</SATZERGEBNISSE>';

														$xml .= '<SAETZE>' . trim(str_replace($trim_array, "", $cols->item(8)->nodeValue)) . '</SAETZE>';
														$xml .= '<PUNKTE>' . trim(str_replace($trim_array, "", $cols->item(9)->nodeValue)) . '</PUNKTE>';

														$xml .= '</ZEILE>';
												}														
														
												else if ($cols->length == 4)
												{ // Bälle, Sätze, Spiele
														$xml .= '<ZEILE>';
														$xml .= '<SUMME>';
														$xml .= '<BAELLE>' . trim(str_replace($trim_array, "", $cols->item(1)->nodeValue)) . '</td>';
														$xml .= '<SAETZE>' . trim(str_replace($trim_array, "", $cols->item(2)->nodeValue)) . '</td>';
														$xml .= '<SPIELE>' . trim(str_replace($trim_array, "", $cols->item(3)->nodeValue)) . '</td>';
														$xml .= '</SUMME>';
														$xml .= '</ZEILE>';
												}

										}
								}
						}

						$xml .= "</BODY>";
				}

				// Überprüfen, ob die Tabelle schon existiert           
				$db = JFactory::getDBO();
				$query = $db->getQuery(true);

				$query
					->select('datum')
					->from('#__webtt_popups')
					->where(
							array(
									'typ=' . $db->quote('meeting'),
									'idclicktt=' . $db->quote($meeting)
									));
 
				$db->setQuery($query);
						
				if (isset($db->loadObject()->datum))
				{
						// Schreiben des xml in die DB
						$db = JFactory::getDbo();
						 
						$query = $db->getQuery(true);
						 
						// Fields to update.
						$fields = array(
											$db->quoteName('xml') . '=' . $db->quote($xml),
											$db->quoteName('datum') . ' = ' . $db->quote(date("Y-m-d H:i:s"))
										);
						 
						// Conditions for which records should be updated.
						$conditions = array(
							$db->quoteName('typ') . ' = ' . $db->quote('meeting'),
							$db->quoteName('idclicktt') . ' = ' . $db->quote($meeting)
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
							$db->quoteName('typ') . ' = ' . $db->quote('meeting'),
							$db->quoteName('idclicktt') . ' = ' . $db->quote($meeting),
							$db->quoteName('name') . ' = ' . $db->quote($name),
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

?>
