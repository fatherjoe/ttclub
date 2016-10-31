<?php

// no direct access
defined('_JEXEC') or die;

JLoader::import('helpers.webtt', JPATH_COMPONENT);
JLoader::import('helpers.bw', JPATH_COMPONENT);
JLoader::import('helpers.qttr', JPATH_COMPONENT);


class WebttHelperSpiele_next extends WebttHelper
{

		// Abrufen der Tabelle von clicktt und in der Datenbank speichern
		// public - wird von getXML() aufgerufen
		public function update()
		{
				$verein = JComponentHelper::getParams('com_webtt')->get('verein');
				$verein_nr = JComponentHelper::getParams('com_webtt')->get('verein_nr');
				$saison_hin = JComponentHelper::getParams('com_webtt')->get('saison_hin');
				$saison_rueck = JComponentHelper::getParams('com_webtt')->get('saison_rueck');
				$spiele_next_days = JComponentHelper::getParams('com_webtt')->get('spiele_next_days');

				$transport = NEW JHttpTransportCurl(NEW JRegistry);
				$http = new JHttp(NEW JRegistry, $transport);

				$HostClicktt = WebttHelper::getHostClicktt();
//				$path = "/cgi-bin/WebObjects/Click" . $reg_verband . ".woa/wa/clubMeetings?searchTimeRange=2&searchType=1&searchTimeRangeFrom=01.08." . $saison_hin . "&searchTimeRangeTo=31.05." . $saison_rueck . "&club=" . $verein_nr . "&searchMeetings=Suchen";
				$path = "/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubMeetings";
				$post = 	"searchTimeRange=1" . 
							"&searchType=1" .
							"&searchTimeRangeFrom=" . date("d.m.Y", time()) .
							"&searchTimeRangeTo=" . date("d.m.Y", time() +  24 * 3600 * $spiele_next_days) . 
							"&club=" . $verein_nr .
							"&searchMeetings=Suchen"
							;
				
				$page = $http->post($HostClicktt . $path, $post)->body;

				if ($page)
				{
						$dom = @DOMDocument::loadHTML($page);

						$xml = '<?xml version="1.0" encoding="utf-8"?>';
						$xml .= "<BODY>";
						$dom->preserveWhiteSpace = false;
						$tables = $dom->getElementsByTagName('table');

						if ($tables->length)
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
										$staffel = trim(str_replace($trim_array, "", $cols->item(5)->nodeValue));

										// WOCHENTAG
										$xml .= "<WOCHENTAG>$tag</WOCHENTAG>";
										$xml .= "<WOCHENTAG_ALT>$tag_alt</WOCHENTAG_ALT>";

										// DATUM
										$xml .= "<DATUM>$datum</DATUM>";
										$xml .= "<DATUM_ALT>$datum_alt</DATUM_ALT>";

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
																				
										$xml .= "</ZEILE>";
								}

						}
						
						}
						
						else
						{
								$xml .= "<MELDUNG>In den nächsten ... Tagen finden keine Spiele statt</MELDUNG>";
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
							->where(array( 'typ=' . $db->quote('spiele_next')));
		 
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
									$db->quoteName('typ') . ' = ' . $db->quote('spiele_next')
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
									$db->quoteName('typ') . ' = ' . $db->quote('spiele_next'),
									$db->quoteName('xml') . ' = ' . $db->quote($xml)
								);
								 
								$query->insert($db->quoteName('#__webtt_tabellen'))->set($fields);
								 
								$db->setQuery($query);
								 
								$result = $db->execute();
						}
				}
		}
}

?>
