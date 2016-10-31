<?php

// no direct access
defined('_JEXEC') or die;

class WebttHelper
{
		// GIBT DEN CLICKTT-HOST ZURÜCK
		public static function getHostClicktt()
		{
				$host = 'http://' . JComponentHelper::getParams('com_webtt')->get('verband') . '.click-tt.de';
				
				return $host;
		}

		public function trimClickttField($field)
		{
				$trim_array = array(chr(194), chr(160), chr(10));
				
				$trim = trim(str_replace($trim_array, "", $field));
				
				return $trim;
		}

		// GIBT DEN TIMESTAMP EINES XML-EINTRAGS ZURÜCK
		public function getTimestamp()
		{
				// View abfragen
				$typ = $this->getView();
				
				// GET-Variable team abfragen
				$team = $this->getTeam();

                // Create a new query object.           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                // Aktualisierungsdatum abfragen 
                $query
                    ->select('datum')
                    ->from('#__webtt_tabellen')
					->where(
								array(
										'typ='. $db->quote($typ),
										'team='. $db->quote($team)
								)
						);
									
				$db->setQuery($query);

				if (isset($db->loadObject()->datum) === FALSE)
				{
						return FALSE;
				}

				$timestamp = $db->loadObject()->datum;
				
				return $timestamp;
		}

		// GIBT DEN TIMESTAMP EINES POPUP-XML-EINTRAGS ZURÜCK
		public function getTimestampPopup($idclicktt)
		{
				// View abfragen
				$view = $this->getView();

				if ($view == 'aufstellung')
				{
						$typ = 'person';
				}

				else if ($view == 'tabelle')
				{
						$typ = 'mannschaft';
				}
				
				else if ($view == "spielplan" OR $view == "vspielplan" OR $view == "spiele_rec" OR $view == "pokal")
				{
						$typ = "meeting";
				}

				// GET-Variable team abfragen
				$team = $this->getTeam();

                // Create a new query object.           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                // Aktualisierungsdatum abfragen 
                $query
                    ->select('datum')
                    ->from('#__webtt_popups')
					->where(
								array(
										'typ='. $db->quote($typ),
										'idclicktt='. $db->quote($idclicktt)
								)
						);
									
				$db->setQuery($query);

				if (isset($db->loadObject()->datum) === FALSE)
				{
						return FALSE;
				}

				$timestamp = $db->loadObject()->datum;
				
				return $timestamp;
		}

		// GIBT DEN TIMESTAMP EINES XML-EINTRAGS ZURÜCK
		public function getTimestampTTR()
		{
                // Create a new query object.           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                // Aktualisierungsdatum abfragen 
                $query
                    ->select('datum')
                    ->from('#__webtt_ttr')
					->where(
								array(
										'typ='. $db->quote('ttr')
								)
						);
									
				$db->setQuery($query);

				if (isset($db->loadObject()->datum) === FALSE)
				{
						return FALSE;
				}

				$timestamp = $db->loadObject()->datum;
				
				return $timestamp;
		}

		// GIBT DEN TIMESTAMP EINES XML-EINTRAGS FÜR TABELLEN DES GESAMTVEREINS ZURÜCK
		public function getTimestampVerein()
		{
				// View abfragen
				$typ = $this->getView();
				
                // Create a new query object.           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                // Aktualisierungsdatum abfragen 
                $query
                    ->select('datum')
                    ->from('#__webtt_tabellen')
					->where(
								array(
										'typ='. $db->quote($typ)
								)
						);
									
				$db->setQuery($query);

				if (isset($db->loadObject()->datum) === FALSE)
				{
						return FALSE;
				}

				$timestamp = $db->loadObject()->datum;
				
				return $timestamp;
		}

		// GIBT DIE EINGETRAGENEN WEBTT-MANNSCHAFTEN ZURÜCK
		public function getTeamsWebTT()
		{
				// Eingetragene Webtt-Mannschaften abfragen
				$db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('name_webtt')
                    ->from('#__webtt_teams');
									
				$db->setQuery($query);

				$teams_webtt = $db->loadColumn();

				return $teams_webtt;
		}

		// GIBT DIE EINTRÄGE DER MANNSCHAFTEN ZURÜCK
		public function getRowsTeams()
		{
				// Eingetragene Webtt-Mannschaften abfragen
				$db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('*')
                    ->from('#__webtt_teams')
                    ->where('typ' . '=' . $db->quote('punkt'));
									
				$db->setQuery($query);

				$rowsTeams = $db->loadAssocList();

				return $rowsTeams;
		}

		// GIBT DIE EINTRÄGE DER AKTUELLEN (VIEW) MANNSCHAFT ZURÜCK
		public function getRowTeam()
		{
				$team = $this->getTeam();

				// Eingetragene Webtt-Mannschaften abfragen
				$db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('*')
                    ->from('#__webtt_teams')
                    ->where('name_webtt' . '=' . $db->quote($team)
					);
									
				$db->setQuery($query);

				$rowTeam = $db->loadObject();

				return $rowTeam;
		}

		// GIBT DIE EINTRÄGE DER AKTUELLEN (VIEW) MANNSCHAFT ZURÜCK
		public function getRowTeamSp($team,$staffel)
		{
				// Eingetragene Webtt-Mannschaften abfragen
				$db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('*')
                    ->from('#__webtt_teams')
                    ->where(array(
									'name_sp_clicktt' . '=' . $db->quote($team),
									'league_clicktt' . '=' . $db->quote($staffel)
									)
					);
									
				$db->setQuery($query);

				$rowTeam = $db->loadObject();

				return $rowTeam;
		}

		// GIBT DIE EINGETRAGENEN MANNSCHAFTEN ZURÜCK
		//    - Webtt-Bezeichnung, Clicktt-Bezeichnung, Clicktt-Staffel
		public function getTeamsStaffeln()
		{
				// Eingetragene Webtt-Mannschaften abfragen
				$db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('name_webtt,name_clicktt,league_clicktt')
                    ->from('#__webtt_teams');
									
				$db->setQuery($query);

				$teams_webtt = $db->loadAssocList('name_clicktt');

				return $teams_webtt;
		}

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

		// GIBT DIE LANG- UND KURZFORM DER CLICKTT-STAFFELN ZURÜCK
		public function getStaffeln()
		{
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('*')
                    ->from('#__webtt_staffeln');
                    
				$db->setQuery($query);
								 
				$result = $db->loadAssocList('clicktt_kurz');
				
				return $result;
		}
		
		// GIBT DIE GET-VARIABLE "VIEW" ZURÜCK
		public function getView()
		{
				// GET-Variable team abfragen
				$jinput = JFactory::getApplication()->input;
				$view = $jinput->get('view', null, null);

				// Eingetragene Webtt-Mannschaften abfragen
				$this->WebttHelper = new WebttHelper;
				$views = array('aufstellung','spielplan','tabelle','pokal','tabellenstaende', 'spielerliste','vspielplan',  'spiele_next','spiele_rec', 'pokaluebersicht','hallen');

				// Überprüfen, ob GET-Variable team eine Webtt-Manschaft ist
				if (array_search($view, $views) === FALSE)
				{
						return FALSE;
				}
				
				return $view;
		}

		// Clicktt-Staffel einer Mannschaft abfragen
        public function getLeagueClicktt()
        {
				// GET-Variable team abfragen
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
				// GET-Variable team abfragen
				$team = $this->getTeam();

				// VIEW abfragen
				$view = $this->getView();

				// TYP festlegen
				if ($view == "pokal")
				{
						$typ = "pokal";
				}
				
				else
				{
						$typ = "punkt";
				}
				
				
                // Create a new query object.           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('path_league_clicktt')
                    ->from('#__webtt_teams')
                    ->where(array(
									'name_webtt='.$db->quote($team),
									'typ=' . $db->quote($typ)
									)
							);
 
				$db->setQuery($query);
                $results = $db->loadObject()->path_league_clicktt;

                return $results;
        }

		// Clicktt-Link zur Pokalstaffel
        public function getPokalLeagueClicktt()
        {
				// GET-Variable team abfragen
				$team = $this->getTeam();
				
                // Create a new query object.           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('league_clicktt')
                    ->from('#__webtt_teams')
                    ->where(array(
									'name_webtt='.$db->quote($team),
									'typ='.$db->quote('pokal')
							)
					);
 
				$db->setQuery($query);
                $result = $db->loadObject()->league_clicktt;

                return $result;
        }

		// Pokalmannschaften + alle Spalten zurückgeben
        public function getTeamPokal()
        {
				// GET-Variable team abfragen
				$team = $this->getTeam();
				
                // Create a new query object.           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('*')
                    ->from('#__webtt_teams')
                    ->where(array(
									'name_webtt='.$db->quote($team),
									'typ='.$db->quote('pokal')
							)
					);
 
				$db->setQuery($query);
                $results = $db->loadObject();

                return $results;
        }

		// GIBT DEN PFAD ZUR CLICKTT-MANNSCHAFTSSEITE ZURÜCK
        public function getPathTeamClicktt()
        {
				// GET-Variable team abfragen
				$team = $this->getTeam();
				
                // Create a new query object.           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('path_clicktt')
                    ->from('#__webtt_teams')
                    ->where('name_webtt='.$db->quote($team));
 
				$db->setQuery($query);
                $results = $db->loadObject()->path_clicktt;

                return $results;
        }



		// PRÜFT, OB DIE AKTUALISIERUNGSZEIT ABGELAUFEN IST
		public function update_test()
		{
				// View abfragen
				$typ = $this->getView();
				
				// Timestamp abfragen
                $timestamp = $this->getTimestamp($typ);

                $time = strtotime($timestamp);
                
                if ($time < (time() - 24 * 3600))
                {
						return TRUE;
				}

                return FALSE;
		}

		// PRÜFT, OB DIE AKTUALISIERUNGSZEIT ABGELAUFEN IST
		public function update_test_verein()
		{
				// Timestamp abfragen
                $timestamp = $this->getTimestampVerein();

                $time = strtotime($timestamp);
                
                if ($time < (time() - 24 * 3600))
                {
						return TRUE;
				}

                return FALSE;
		}

		// PRÜFT FÜR POPUP-TABELLEN, OB DIE AKTUALISIERUNGSZEIT ABGELAUFEN IST
		public function update_test_popup($idclicktt)
		{
				// View abfragen
				$view = $this->getView();
				
				// Timestamp abfragen
                $timestamp = $this->getTimestampPopup($idclicktt);

                $time = strtotime($timestamp);
                
                if ($time < (time() - 48 * 3600) OR isset($timestamp) === FALSE)
                {

						return TRUE;
				}

                return FALSE;
		}

		// PRÜFT, OB DIE AKTUALISIERUNGSZEIT ABGELAUFEN IST
		public function update_test_ttr()
		{
				// Timestamp abfragen
                $timestamp = $this->getTimestampTTR();

                $time = strtotime($timestamp);
                
                if ($time < (time() - 24 * 3600))
                {
						return TRUE;
				}

                return FALSE;
		}

}

?>
