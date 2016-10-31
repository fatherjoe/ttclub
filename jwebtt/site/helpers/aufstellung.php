<?php

// no direct access
defined('_JEXEC') or die;

JLoader::import('helpers.webtt', JPATH_COMPONENT);
JLoader::import('helpers.bw', JPATH_COMPONENT);
JLoader::import('helpers.qttr', JPATH_COMPONENT);


class WebttHelperAufstellung extends WebttHelper
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

				$teampage = $http->get('http://' . $verband . '.click-tt.de' . $path_team)->body;
				$this->WebttHelperBw = new WebttHelperBw;

				if ($teampage)  {

						$dom = @DOMDocument::loadHTML($teampage);

						$xml = '<?xml version="1.0" encoding="utf-8"?>';
						$xml .= "<BODY>";
						$dom->preserveWhiteSpace = false;
						$tables = $dom->getElementsByTagName('table');
						$h2s = $dom->getElementsByTagName('h2');

						// Bestimmen, in der wievielten Tabelle die Aufstellung steht
						// im HTTV 
						for ($q = 0; $q < $h2s->length; $q++)
						{

								//	echo $q." ".$h2s->item($q)->nodeValue."<br>";
								if (strstr($h2s->item($q)->nodeValue, "Spielerbilanzen"))
								{
										if ($verband != "httv")
										{
												$tablenr_aufst = $q + 1;	// plus 1, da in der ersten Tabelle die Vereinsinformationen stehen
										}
										else
										{
												$tablenr_aufst = $q;  // Im HTTV befindet sich auf allen Seite der Link zum Login als h2-Überschrift
										}
								}
						}


						if (isset($tablenr_aufst))
						{

								// Mannschaftskontakt auslesen
								$td_mk = $tables->item(0)->getElementsByTagName('tr')->item(1)->getElementsByTagName('td')->item(1)->nodeValue;
								$children = $tables->item(0)->getElementsByTagName('tr')->item(1)->getElementsByTagName('td')->item(1)->childNodes;
								$innerHTML = "";
								$mk_name = $children->item(0)->nodeValue;
								foreach ($children as $child)
								{
										if (strstr($child->nodeValue, "Tel"))
										{
												$mk_tel = $child->nodeValue;
										}
										if (strstr($child->nodeValue, "Geschäft"))
										{
												$mk_gesch = $child->nodeValue;
										}
										if (strstr($child->nodeValue, "Mobil"))
										{
												$mk_mobil = $child->nodeValue;
										}
										if (strstr($child->nodeValue, "encode"))
										{
												preg_match("/\(.*\)/", $child->nodeValue, $result);
												preg_match_all("/\'([a-zA-Z0-9-.]*)\'/", $result[0], $result2);
												$mk_mail = $result2[1][1];
												if ($result2[1][1] && $result2[1][3])
												{
														$mk_mail .= ".";
												}
												$mk_mail .= $result2[1][3] . "@" . $result2[1][2] . "." . $result2[1][0];
										}
								}

								$trim_array = array(chr(194), chr(160), chr(10));
								
								$rows = $tables->item($tablenr_aufst)->getElementsByTagName('tr');
								foreach ($rows as $row)
								{

										$colsth = $row->getElementsByTagName('th');
										if (isset($colsth->item(0)->nodeValue))
										{
												// Spalte von Bilanz und Bilanzwert bestimmen
												for($s=0; $s<$colsth->length; $s++)
												{
														if ($colsth->item($s)->nodeValue == "gesamt")
														{
																$spalte_bil_nr = $s;
														}
														if ($colsth->item($s)->nodeValue == "Bilanzwert")
														{
																$spalte_bw_nr = $s;
														}
												}
										}

										$cols = $row->getElementsByTagName('td');

										if (isset($cols->item(0)->nodeValue))
										{

												$temp = explode(".", trim($cols->item(0)->nodeValue));
												if (isset($temp[0]) && isset($temp[1]))
												{ // Keine Doppelspiele, dort ist die Positionsspalte leer
															
														$a = $cols->item(1)->getElementsByTagName('a');
														if (isset($a->item(0)->nodeValue))
														{
																$spieler_path = $a->item(0)->getAttribute("href");
																$spieler_url = "http://" . $verband . ".clicktt.de" . $spieler_path;
														}

														$xml .= "<ZEILE>";

														// CLICKTT-MANNSCHAFTSNUMMER
														$mann_nr = $temp[0];
														$xml .= "<MANNSCH_NR>$mann_nr</MANNSCH_NR>";
														
														// POSITION
														$pos = $temp[1];
														$xml .= "<POSITION>$pos</POSITION>";
														
														// SPIELER
														$spieler = trim(str_replace($trim_array, " ", $cols->item(1)->nodeValue));
														$xml .= "<SPIELER>$spieler</SPIELER>";
														$xml .= "<SPIELER_PFAD>" . str_replace("&", "&amp;", $spieler_path) . "</SPIELER_PFAD>";

														$spieler_zus = "";
														if (strstr($spieler, " ("))
														{
																$sp_exp = explode(" (", $spieler);
																$spieler = trim($sp_exp[0]);
																$spieler_zus = "(" . $sp_exp[1];
																$sp_exp = "";
														}
														$xml .= "<SPIELER_ZUS>$spieler_zus</SPIELER_ZUS>";

														// SPALTE : ANZAHL DER EINSÄTZE
														$eins = trim(str_replace($trim_array, " ", $cols->item(3)->nodeValue));
														$xml .= "<EINSAETZE>$eins</EINSAETZE>";

														// SPALTE : BILANZ
														if (isset($spalte_bil_nr) && isset($cols->item($spalte_bil_nr)->nodeValue))
														{
																$bilanz = trim(str_replace($trim_array, "", $cols->item($spalte_bil_nr)->nodeValue));
														}

														// SPALTE : BILANZWERT
														if (isset($spalte_bw_nr) && isset($cols->item($spalte_bw_nr)->nodeValue))
														{
																$bw = trim(str_replace($trim_array, "", $cols->item($spalte_bw_nr)->nodeValue));
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
									->where(array('team='.$db->quote($team), 'typ=' . $db->quote('aufstellung')));
				 
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
											$db->quoteName('typ') . ' = ' . $db->quote('aufstellung')
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
											$db->quoteName('typ') . ' = ' . $db->quote('aufstellung'),
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
		
		
		// AKTUALISIEREN DER EINZELERGEBNISSE VON DER CLICKTT-SPIELERSEITE
		public function update_popup($person,$name,$path)
		{
				$staffel = $this->getLeagueClicktt();
				$verband = JComponentHelper::getParams('com_webtt')->get('verband');

				$transport = NEW JHttpTransportCurl(NEW JRegistry);
				$http = new JHttp(NEW JRegistry, $transport);

				$spielerpage = $http->get('http://' . $verband . '.click-tt.de' . $path)->body;
				$trim_array = array(chr(194), chr(160), chr(10));

				if ($spielerpage)
				{
						$dom = @DOMDocument::loadHTML($spielerpage);

						$dom->preserveWhiteSpace = true;
						$tables = $dom->getElementsByTagName('table');
			
						if ($dom)
						{ 
								$dom->preserveWhiteSpace = true;
								$tables = $dom->getElementsByTagName('table');

								$xml = '<?xml version="1.0" encoding="utf-8"?>';
								$xml .= "<BODY>";

								// Überprüfung, ob die Tabelle mit den Bilanzen existiert (sind Ergebnisse von diesem Spieler vorhanden?), wenn nicht vorhanden, dann wird leere Datei für diesen Spieler angelegt
								if (isset($tables->item(1)->nodeValue))	
								{

										$rows = $tables->item(1)->getElementsByTagName('tr');
										foreach ($rows as $row)
										{
												$cols = $row->getElementsByTagName('td');
												if (isset($cols->item(1)->nodeValue) && strstr($cols->item(1)->nodeValue, "-")) //Wenn in zweiter Spalte ein 	"-" ist, wie "1-2"
												{
														$xml .= '<ZEILE>';

														$xml .= '<DATUM>';
														$xml .= str_replace($trim_array, "", $cols->item(0)->nodeValue);
														$xml .= '</DATUM>';

														$xml .= '<PAARUNG>';
														$xml .= trim($cols->item(1)->nodeValue);
														$xml .= '</PAARUNG>';
														
														$xml .= '<GEGNER>';
														$xml .= str_replace($trim_array, "", $cols->item(2)->nodeValue);
														$xml .= '</GEGNER>';

														$xml .= '<SAETZE>';
														$xml .= trim($cols->item(3)->nodeValue);
														$xml .= '</SAETZE>';

														$xml .= '<GEGNER_TEAM>';
														$xml .= str_replace($trim_array, "", $cols->item(9)->nodeValue);
														$xml .= '</GEGNER_TEAM>';

														$xml .= '<ERGEBNIS>';
														$xml .= str_replace($trim_array, "", $cols->item(10)->nodeValue);
														$xml .= '</ERGEBNIS>';

														$xml .= '</ZEILE>';
												}
												
												else if (isset($cols->item(0)->nodeValue))
												{

														$staffel_h2 = $cols->item(0)->getElementsByTagName('h2');
														if ($staffel_h2->length > 0)
														{
														$xml .= '<ZEILE>';

														$xml .= '<STAFFEL>';
														$xml .= trim(str_replace($trim_array, "", $staffel_h2->item(0)->nodeValue));
														$xml .= '</STAFFEL>';

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
											'typ=' . $db->quote('person'),
											'idclicktt=' . $db->quote($person)
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
									$db->quoteName('typ') . ' = ' . $db->quote('person'),
									$db->quoteName('idclicktt') . ' = ' . $db->quote($person)
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
									$db->quoteName('typ') . ' = ' . $db->quote('person'),
									$db->quoteName('idclicktt') . ' = ' . $db->quote($person),
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

}

?>
