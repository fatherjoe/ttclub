<?php

// no direct access
defined('_JEXEC') or die;

JLoader::import('helpers.webtt', JPATH_COMPONENT);


class WebttHelperTabellenstaende extends WebttHelper
{

		// Abrufen der Tabelle von clicktt und in der Datenbank speichern
		// public - wird von getXML() aufgerufen
		public function update()
		{
				$verband = JComponentHelper::getParams('com_webtt')->get('verband');
				$verein_nr = JComponentHelper::getParams('com_webtt')->get('verein_nr');
				$transport = NEW JHttpTransportCurl(NEW JRegistry);
				$http = new JHttp(NEW JRegistry, $transport);

				$HostClicktt = WebttHelper::getHostClicktt();
				$path = "/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubTeams?club=$verein_nr";
				
				$page = $http->get($HostClicktt . $path)->body;
				
				if ($page)
				{
						$dom = @DOMDocument::loadHTML($page);

						$xml = '<?xml version="1.0" encoding="utf-8"?>';
						$xml .= "<BODY>";
						$dom->preserveWhiteSpace = false;
						$tables = $dom->getElementsByTagName('table');

						if ($verband != "httv")
						{
								$rows = $tables->item(0)->getElementsByTagName('tr');
						}
						
						else
						{
								$rows = $tables->item(1)->getElementsByTagName('tr');
						}

						$trim_array = array(chr(194), chr(160), chr(10));
						
						// ZEILEN DES SPIELPLANS
						foreach ($rows as $tr)
						{
								$tds = $tr->getElementsByTagName('td');

								if ($tds->length == 5 && stristr($tds->item(1)->nodeValue, "Pokal") === false)
								{
										$team_name = trim(str_replace($trim_array, "", $tds->item(0)->nodeValue));
										$liga_name = trim(str_replace($trim_array, "", $tds->item(1)->nodeValue));
										$liga_path = $tds->item(1)->getElementsByTagName('a')->item(0)->getAttribute('href');
										$platz = trim(str_replace($trim_array, "", $tds->item(3)->nodeValue));
										$punkte = trim(str_replace($trim_array, "", $tds->item(4)->nodeValue));

										$xml .= "<ZEILE>";
										$xml .= "<TEAM>$team_name</TEAM>";
										$xml .= "<STAFFEL>$liga_name</STAFFEL>";
										$xml .= "<PLATZ>$platz</PLATZ>";
										$xml .= "<PUNKTE>$punkte</PUNKTE>";
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
							->where(array( 'typ=' . $db->quote('tabellenstaende')));
		 
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
									$db->quoteName('typ') . ' = ' . $db->quote('tabellenstaende')
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
									$db->quoteName('typ') . ' = ' . $db->quote('tabellenstaende'),
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
