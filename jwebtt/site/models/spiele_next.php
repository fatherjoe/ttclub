<?php
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');


JLoader::import('helpers.webtt', JPATH_COMPONENT);
JLoader::import('helpers.spiele_next', JPATH_COMPONENT);
JLoader::import('helpers.hallen', JPATH_COMPONENT);

class WebttModelSpiele_next extends JModelItem
{

		// Liefern der Datenbankzeile mit dem Datum und dem xml an die View
        public function getXML()
        {

				// Helper-Klasse
				$this->WebttHelper = new WebttHelper;
				$this->WebttHelperSpiele_next = new WebttHelperSpiele_next;
				$this->WebttHelperHallen = new WebttHelperHallen;

				// Wenn Aktualisierungsintervall abgelaufen ist, dann aktualisieren
				if ($this->WebttHelper->update_test_verein('spiele_next') === TRUE)
				{
						$update = $this->WebttHelperSpiele_next->update();
				}

                // sql-Query bilden und DB abfragen           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('xml')
                    ->from('#__webtt_tabellen')
					->where(
							array(
									'typ='. $db->quote("spiele_next")
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
								$row->HALLE = "1";
								foreach ($hallen as $verein => $array)
								{
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
}
