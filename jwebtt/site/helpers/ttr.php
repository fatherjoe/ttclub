<?php

// no direct access
defined('_JEXEC') or die;

JLoader::import('helpers.webtt', JPATH_COMPONENT);

/*
 * Klasse zum Auslesen der TTR-Werte aus der DB und
 * zum Aktualiieren von mytt
 * 
 */

class WebttHelperTtr extends WebttHelper
{
		// GIBT DIE TTR-WERTE ZURÜCK
		public function getTTR()
		{		
				if (parent::update_test_ttr() === NULL)
				{
				}
						$this->update_ttr();
				
				// Abrufen der QTTR-Werte aus der DB
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);
 
                $query
                    ->select('werte,datum')
                    ->from('#__webtt_ttr')
					->where( array(
										'typ='. $db->quote('ttr')								
									)
						);
									
				$db->setQuery($query);
				$result = $db->loadObject();
				
				
				
				// TTR-Feld auslesen und Array erstellen
				$ttr = $db->loadObject()->werte;

				$ttr_array = explode("\n", $ttr);

				$ttr = array();
				foreach ($ttr_array as $zeile)
				{
						if (trim($zeile))
						{
								$expl = explode(";", $zeile);
								if (isset($expl[1]))
								{
										$ttr[trim($expl[0])] = trim($expl[1]);
								}
						}
				}
				
				return $ttr;
		}

		
		function update_ttr()
		{
				$transport = NEW JHttpTransportCurl(NEW JRegistry);
				$http = new JHttp(NEW JRegistry, $transport);

				$url = "https://www.mytischtennis.de/community/login";
				$post = "userNameB=" . urlencode(JComponentHelper::getParams('com_webtt')->get('mytt_user')) . "&userPassWordB=" . urlencode(JComponentHelper::getParams('com_webtt')->get('mytt_pw')) . "&targetPage=%0D%0Ahttp%3A%2F%2Fmytischtennis.de%2Fcommunity%2Findex%3Ffromlogin%26";
				$post = "userNameB=" . urlencode(JComponentHelper::getParams('com_webtt')->get('mytt_user')) . "&userPassWordB=" . urlencode(JComponentHelper::getParams('com_webtt')->get('mytt_pw')) . "&targetPage=%0D%0Ahttp%3A%2F%2Fwww.mytischtennis.de%2Fcommunity%2Findex%3Ffromlogin%26";

				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("charset=UTF-8", "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8", "Accept-Language: de,en-US;q=0.7,en;q=0.3", "Accept-Encoding: deflate"));
				curl_setopt($ch, CURLOPT_POST ,1);
				curl_setopt($ch, CURLOPT_POSTFIELDS , $post);
				curl_setopt($ch, CURLOPT_HEADER, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
				curl_setopt($ch, CURLOPT_AUTOREFERER, true);
				curl_setopt($ch, CURLOPT_COOKIESESSION, true); 
				curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
				curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:34.0) Gecko/20100101 Firefox/34.0");


			//				$mytt_login = $http->post($url, $post)->body;
				$login = curl_exec($ch);
				curl_close($ch);
				$dom = @DOMDocument::loadHTML($login);
				
				if (!$login)
					return "Fehler1";

				if (strstr($login, "Sie erlauben keine Cookies?"))
					return "Fehler2a";

				if (strstr($login, "Sie haben noch keinen Login?"))
					return "Fehler2";

				if (strstr($login, "Basis-Mitglied"))
					return "Fehler3";


				$href_2 = "http://mytischtennis.de/community/ranking";

				$mytt_verband = JComponentHelper::getParams('com_webtt')->get('mytt_verband');

				if ($mytt_verband)
					$verband = $mytt_verband;
				else if ($verband == "httv")
					$verband = "HeTTV";
				else if ($verband == "bttv")
					$verband = "ByTTV";
				else
					$verband = strtoupper($verband);
					
				$query = "?panel=1&kontinent=Europa&land=DE&alleSpielberechtigen=yes&verband=Alle&bezirk=&kreis=&regionPattern123=&vereinId=" . JComponentHelper::getParams('com_webtt')->get('mytt_vereinid') . "%2C" . JComponentHelper::getParams('com_webtt')->get('mytt_verband') . "&verein=" . urlencode(JComponentHelper::getParams('com_webtt')->get('mytt_verein')) . "&geschlecht=&geburtsJahrVon=&geburtsJahrBis=&ttrVon=&ttrBis=&ttrQuartalorAktuell=aktuell&anzahlErgebnisse=500&goAssistent=Anzeigen";

				$ch = curl_init($href_2 . $query);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("charset=UTF-8", "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8", "Accept-Language: de,en-US;q=0.7,en;q=0.3", "Accept-Encoding: deflate"));
				curl_setopt($ch, CURLOPT_HTTPGET ,1);
			//	curl_setopt($ch, CURLOPT_CRLF , 1);
				curl_setopt($ch, CURLOPT_HEADER, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
				curl_setopt($ch, CURLOPT_AUTOREFERER, true); 
				curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
				curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:34.0) Gecko/20100101 Firefox/34.0");
				curl_setopt($ch, CURLOPT_VERBOSE, true);

				$rangl_page = curl_exec($ch);


				if (curl_error($ch))
					echo "Curl-Fehler nach Login:" . curl_error($ch) . "<br>";
				curl_close($ch);

				if (!$rangl_page)
					return "Fehler4";

	/*
	 * Ajax-Abfrage für die Tabelle mit Spielern/TTR-Werten
	 */

	$ajax_request_2 = "https://www.mytischtennis.de/community/ajax/_rankingList?kontinent=Europa&land=DE&deutschePlusGleichgest=no&alleSpielberechtigen=yes&verband=Alle&bezirk=&kreis=&regionPattern123=&regionPattern4=&regionPattern5=&geschlecht=&geburtsJahrVon=&geburtsJahrBis=&ttrVon=&ttrBis=&ttrQuartalorAktuell=aktuell&anzahlErgebnisse=500&vorname=&nachname=&verein=" . urlencode(JComponentHelper::getParams('com_webtt')->get('mytt_verein')) . "&vereinId=" . JComponentHelper::getParams('com_webtt')->get('mytt_vereinid') . "%2C" . JComponentHelper::getParams('com_webtt')->get('mytt_verband') . "&vereinPersonenSuche=&vereinIdPersonenSuche=&ligen=&groupId=&showGroupId=&deutschePlusGleichgest2=no&ttrQuartalorAktuell2=aktuell&showmyfriends=0";

	$ch = curl_init($ajax_request_2);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("charset=UTF-8", "Accept: */*", "Accept-Language: de,en-US;q=0.7,en;q=0.3", "Accept-Encoding: deflate", "X-Requested-With: XMLHttpRequest"));
	curl_setopt($ch, CURLOPT_HTTPGET ,1);
//	curl_setopt($ch, CURLOPT_CRLF , 1);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
	curl_setopt($ch, CURLOPT_AUTOREFERER, true); 
	curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:34.0) Gecko/20100101 Firefox/34.0");
	curl_setopt($ch, CURLOPT_VERBOSE, true);

	$ajax_exec_2 = curl_exec($ch);
	curl_close($ch);

	unlink('cookie.txt');


	/*
	 * HTML AUSLESEN
	 */

	$dom = @DOMDocument::loadHTML($ajax_exec_2);
				if (!$dom)
					return "Fehler5";
				$dom->preserveWhiteSpace = true;

				$table = $dom->getElementsByTagName("table");

				if (isset($table->item(0)->nodeValue))
				{
						$tr = $table->item(0)->getElementsByTagName("tr");
						for ($x=0;$x<$tr->length;$x++)
						{
								$td = $tr->item($x)->getElementsByTagName("td");
								if (isset($td->item(2)->nodeValue))
								{
										$strong = $td->item(2)->getElementsByTagName("strong");
										$vorname = utf8_decode(trim($td->item(2)->getElementsByTagName("span")->item(0)->firstChild->nodeValue));
										$nachname = utf8_decode(trim($strong->item(0)->nodeValue));
										$ttr = trim($td->item(4)->nodeValue);
										if ($nachname)
										{
												$ttrs .= $nachname . ", " . $vorname . ";" . $ttr . "\n";
										}
								}
						}
				}
				else
				{
						return "Fehler6";
				}


				// Überprüfen, ob die Tabelle schon existiert           
				$db = JFactory::getDBO();
				$query = $db->getQuery(true);

				$query
					->select('datum')
					->from('#__webtt_ttr')
					->where(
							array(
									'typ=' . $db->quote('ttr')
								)
							);
 
				$db->setQuery($query);


				if (isset($db->loadObject()->datum))
				{
						$db = JFactory::getDbo();
						 
						$query = $db->getQuery(true);
						 
						// Fields to update.
						$fields = array(
											$db->quoteName('werte') . '=' . $db->quote($ttrs),
											$db->quoteName('datum') . ' = ' . $db->quote(date("Y-m-d H:i:s"))
										);
						 
						// Conditions for which records should be updated.
						$conditions = array(
							$db->quoteName('typ') . ' = ' . $db->quote('ttr')
						);
						 
						$query->update($db->quoteName('#__webtt_ttr'))->set($fields)->where($conditions);
						 
						$db->setQuery($query);
						 
						$result = $db->execute();
				}

				else
				{
						$db = JFactory::getDbo();
						$query = $db->getQuery(true);

						$fields = array(
							$db->quoteName('typ') . ' = ' . $db->quote('ttr'),
							$db->quoteName('datum') . ' = ' . $db->quote(date("Y-m-d H:i:s")),
							$db->quoteName('verein_nr') . ' = ' . $db->quote(''),
							$db->quoteName('werte') . ' = ' . $db->quote($ttrs)
						);
						 
						$query->insert($db->quoteName('#__webtt_ttr'))->set($fields);
						 
						$db->setQuery($query);
						 
						$result = $db->execute();
				}
		}
}

?>
