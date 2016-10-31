<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();



class WebttModelPokaluebersicht extends JModelList
{

		// Liefern der Datenbankzeile mit dem xml an die View
        public function getXML()
        {

				// Helper-Klasse
				$this->WebttHelper = new WebttHelper;
				$this->WebttHelperPokaluebersicht = new WebttHelperPokaluebersicht;

				// Wenn Aktualisierungsintervall abgelaufen ist, dann aktualisieren
				if ($this->WebttHelper->update_test_verein('pokaluebersicht') === TRUE)
				{
						$update = $this->WebttHelperPokaluebersicht->update();
				}
				

                // sql-Query bilden und DB abfragen           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('xml')
                    ->from('#__webtt_tabellen')
					->where(
							array(
									'typ='. $db->quote("pokaluebersicht")
								)
							);
									
				$db->setQuery($query);

				$results = $db->loadObject();

				$xml = new SimpleXMLElement($db->loadObject()->xml);

				$teamsstaffeln = $this->WebttHelperPokaluebersicht->getTeamsPokalStaffeln();

				// Ersetzen der Clicktt-Mannschaftsnamen mit den WebTT-Mannschaftsnamen
				foreach ($xml->ZEILE as $row)
				{
						$row->TEAM = $teamsstaffeln["$row->TEAM"]['name_webtt'];
				}

                return $xml;
		}
}
