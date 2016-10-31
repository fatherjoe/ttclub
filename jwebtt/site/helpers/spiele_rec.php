<?php

// no direct access
defined('_JEXEC') or die;

JLoader::import('helpers.webtt', JPATH_COMPONENT);
JLoader::import('helpers.bw', JPATH_COMPONENT);
JLoader::import('helpers.qttr', JPATH_COMPONENT);


class WebttHelperSpiele_rec extends WebttHelper
{

		// Abrufen der Tabelle von clicktt und in der Datenbank speichern
		// public - wird von getXML() aufgerufen
		public function update()
		{
				$this->WebttHelper = new WebttHelper;

				$verein = JComponentHelper::getParams('com_webtt')->get('verein');
				$verein_nr = JComponentHelper::getParams('com_webtt')->get('verein_nr');
				$saison_hin = JComponentHelper::getParams('com_webtt')->get('saison_hin');
				$saison_rueck = JComponentHelper::getParams('com_webtt')->get('saison_rueck');
				$spiele_rec_days = JComponentHelper::getParams('com_webtt')->get('spiele_rec_days');

				$transport = NEW JHttpTransportCurl(NEW JRegistry);
				$http = new JHttp(NEW JRegistry, $transport);

				$HostClicktt = WebttHelper::getHostClicktt();
//				$path = "/cgi-bin/WebObjects/Click" . $reg_verband . ".woa/wa/clubMeetings?searchTimeRange=2&searchType=1&searchTimeRangeFrom=01.08." . $saison_hin . "&searchTimeRangeTo=31.05." . $saison_rueck . "&club=" . $verein_nr . "&searchMeetings=Suchen";
				$path = "/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubMeetings";
				$post = "searchTimeRange=1" . 
							"&searchType=1" . 
							"&searchTimeRangeFrom=" . date("d.m.Y", time() - 24 * 3600 * $spiele_rec_days) .
							"&searchTimeRangeTo=" . date("d.m.Y", time()) . 
							"&club=" . $verein_nr . 
							"&searchMeetings=Suchen"
							;
				
				$page = $http->post(WebttHelper::getHostClicktt() . $path, $post)->body;
				
				if ($page)
				{
						$dom = @DOMDocument::loadHTML($page);

						$xml = '<?xml version="1.0" encoding="utf-8"?>';
						$xml .= "<BODY>";
						$dom->preserveWhiteSpace = false;
						$tables = $dom->getElementsByTagName('table');

						if (isset($tables->item(0)->nodeValue))
						{
							
						$rows = $tables->item(0)->getElementsByTagName('tr');

						$trim_array = array(chr(194), chr(160), chr(10));
						
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
										
										$tag_alt = "";
										$datum_alt = "";
										$zeit_alt = "";
										$zeit_verl = "";
										$verl_unbek = "";
										$uhrzeit_verl = "";
										$halle_verl = "";
										$heim_tausch = "";
										$erg = "";
										$erg_path = "";
										$kampflos = "";
										
										$wt_alt = "";

										$cols = $row->getElementsByTagName('td');

										$xml .= "<ZEILE>";

										// WOCHENTAG
										$tag = trim(str_replace($trim_array, "", $cols->item(0)->nodeValue));

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

												// Wenn das Spiel verlegt wurde, aber das Datum gleich blieb, wurde nur die Uhrzeit verlegt
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
										
										$halle = trim(str_replace($trim_array, "", $cols->item(3)->getElementsByTagName('span')->item(0)->nodeValue));
										// EXTERNE HALLE
										if ($halle == "(H)")
										{
											$halle_verl = $cols->item(3)->getElementsByTagName('span')->item(0)->getAttribute('title');
										}
										
										// STAFFEL
										$staffel = trim(str_replace($trim_array, "", $cols->item(4)->nodeValue));

										// WOCHENTAG
										$xml .= "<WOCHENTAG>$tag</WOCHENTAG>";
										$xml .= "<WOCHENTAG_ALT>$tag_alt</WOCHENTAG_ALT>";

										// DATUM
										$xml .= "<DATUM>$datum</DATUM>";
										if ($datum_alt)
										{
												$xml .= "<DATUM_ALT>$datum_alt[0]</DATUM_ALT>";
										}

										// UHRZEIT
										$xml .= "<UHRZEIT>$zeit</UHRZEIT>";
										$xml .= "<UHRZEIT_ALT>$zeit_alt</UHRZEIT_ALT>";
										$xml .= "<VERL>$wt_alt</VERL>";	

										// HALLE
										$xml .= "<HALLE>$halle</HALLE>";
										$xml .= "<HALLE_VERL>$halle_verl</HALLE_VERL>";
										
										// STAFFEL
										$xml .= "<STAFFEL>$staffel</STAFFEL>";

										// HEIMMANNSCHAFT
										$heim = trim(str_replace($trim_array, "", $cols->item($cols->length-6)->nodeValue));
										$xml .= "<HEIM>$heim</HEIM>";
										$xml .= "<HEIM_TAUSCH>$heim_tausch</HEIM_TAUSCH>";
										
										// GASTMANNSCHAFT
										$gast = trim(str_replace($trim_array, "", $cols->item($cols->length-5)->nodeValue));
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
											
										if  (isset($erg) && $erg)
										{
												$xml .= "<ERGEBNIS>$erg</ERGEBNIS>";
										}
										
										if (isset($meeting) && $meeting)
										{	
												$xml .= "<MEETING>$meeting</MEETING>";
												$xml .= "<ERGEBNIS_PFAD>$erg_path</ERGEBNIS_PFAD>";
										}
										
										$xml .= "</ZEILE>";
								}

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
							->where(array( 'typ=' . $db->quote('spiele_rec')));
		 
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
									$db->quoteName('typ') . ' = ' . $db->quote('spiele_rec')
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
									$db->quoteName('typ') . ' = ' . $db->quote('spiele_rec'),
									$db->quoteName('xml') . ' = ' . $db->quote($xml)
								);
								 
								$query->insert($db->quoteName('#__webtt_tabellen'))->set($fields);
								 
								$db->setQuery($query);
								 
								$result = $db->execute();
						}
				}
		}
		
		
		// AKTUALISIEREN DER SPIELBERICHTE VOM CLICKTT-SPIELPLAN
		public function update_popup($meeting,$path,$staffel)
		{
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
												if (isset($cols->item(7)->nodeValue) && strstr($cols->item(1)->nodeValue, "-")) //Wenn in zweiter Spalte ein 	"-" ist, wie "1-2"
												{
														$xml .= '<ZEILE>';

														$xml .= '<PAARUNG>';
														$xml .= str_replace($trim_array, "", $cols->item(0)->nodeValue);
														$xml .= '</PAARUNG>';
														
														$xml .= '<HEIMSPIELER>';
														$u = "";
														foreach ($cols2->item(1)->childNodes as $heimspieler)
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
														foreach ($cols2->item(1)->childNodes as $gastspieler)
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
														$xml .= '<SATZ_1>' . trim(str_replace($trim_array, "", $cols2->item(3)->nodeValue)) . '</SATZ_1>';
														$xml .= '<SATZ_2>' . trim(str_replace($trim_array, "", $cols2->item(4)->nodeValue)) . '</SATZ_2>';
														$xml .= '<SATZ_3>' . trim(str_replace($trim_array, "", $cols2->item(5)->nodeValue)) . '</SATZ_3>';
														$xml .= '<SATZ_4>' . trim(str_replace($trim_array, "", $cols2->item(6)->nodeValue)) . '</SATZ_4>';
														$xml .= '<SATZ_5>' . trim(str_replace($trim_array, "", $cols2->item(7)->nodeValue)) . '</SATZ_5>';
														$xml .= '</SATZERGEBNISSE>';

														$xml .= '<SAETZE>' . trim(str_replace($trim_array, "", $cols2->item(8)->nodeValue)) . '</SAETZE>';
														$xml .= '<PUNKTE>' . trim(str_replace($trim_array, "", $cols2->item(9)->nodeValue)) . '</PUNKTE>';
												}														
														
												else if ($cols2->length == 4)
												{ // Bälle, Sätze, Spiele
														$xml .= '<ZEILE>';
														$xml .= '<SUMME>';
														$xml .= '<BAELLE>' . trim(str_replace($trim_array, "", $cols2->item(1)->nodeValue)) . '</td>';
														$xml .= '<SAETZE>' . trim(str_replace($trim_array, "", $cols2->item(2)->nodeValue)) . '</td>';
														$xml .= '<SPIELE>' . trim(str_replace($trim_array, "", $cols2->item(3)->nodeValue)) . '</td>';
														$xml .= '</SUMME>';
												}

												$xml .= '</ZEILE>';
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
