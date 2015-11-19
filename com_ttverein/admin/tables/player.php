<?php

defined('_JEXEC') or die('Restricted access');

class TablePlayer extends JTable
{
	var $id						= 0;
	var $vorname				= '';
	var $nachname				= '';
	var $published_gebutstag	= 1;
	var $published				= 1;
	var $clicktt_person_id		= null;
	var $image_orginal			= '';
	var $image_resize			= '';
	var $image_thumb			= '';
	
	var $felder					= array();
	
	var $mannschaften			= array();
	var $aufstellungen			= array();
	
	function TablePlayer(& $db) {
		parent::__construct('#__ttverein_spieler', 'id', $db);
		
		/*
		 * Möglichen eingabefelder Laden
		 */		
		$query = "SELECT id, name_backend, typ, tooltip " .
				" FROM #__ttverein_felder " .
				" ORDER BY reihenfolge, id ASC ";
		$db->setQuery( $query );
		$felder = $db->loadObjectList();
		
		
		$this->felder = array();
		foreach($felder as $feld) {
			$this->felder[$feld->id]= new stdClass();
			$this->felder[$feld->id]->typ = $feld->typ;
			$this->felder[$feld->id]->name = $feld->name_backend;
			$this->felder[$feld->id]->wert = null;
			$this->felder[$feld->id]->tooltip = $feld->tooltip;
		}
		
		/*
		 * Alle Mannschaften laden
		 */
		$query = "SELECT mannschaften.id, mannschaften.hinrunde, mannschaften.saisonstart, " .
					"CONCAT(mannschaften.nummer, '. ',altersklassen.name ) AS mannschaft " .
				' FROM #__ttverein_mannschaften AS mannschaften, ' .
					' #__ttverein_altersklassen AS altersklassen ' .
				' WHERE altersklassen.id = mannschaften.altersklasse ' .
				' ORDER BY mannschaften.saisonstart DESC, mannschaften.hinrunde DESC, ' .
					' altersklassen.reihenfolge ASC, mannschaften.nummer ASC';
		$db->setQuery( $query );
		$this->mannschaften = $db->loadObjectList();
		
	}
	
	function bind( $from, $ignore=array() ) {
		$felder = $this->felder;
		if(!parent::bind($from, $ignore))
			return false;
		$this->felder = $felder;

		if(array_key_exists("felder", $from) && is_array($from["felder"])) {
			foreach($from["felder"] as $id=>$wert) {
				if(!isset($wert) || $wert == "") {
					$this->felder[$id]->wert = null;
					$this->felder[$id]->typ = $from["typen"][$id];
				} else {
					$this->felder[$id]->wert = $wert;
					$this->felder[$id]->typ = $from["typen"][$id];
				}
				
			}	
		}
		
		return true;	
	}

	/**
	 * Lädt zusätzlich die Mannschaften und die Aufstellungen
	 */
	function load($oid=null, $reset=true) {		
	
		//Spielerdaten Laden
		if(parent::load($oid, $reset) === false)
			return false;
				
		//Datenbankverbindung
		$db = $this->_db;
		
		//Nachdem parent::load() aufgerufen wurde, ist die ID auf jden fall im Objekt
		$key = $this->_tbl_key;
		$id = $this->$key;
		
		
		/*
		 * Alle Aufstellungen in der, der Spieler vorhanden ist laden
		 */
		$query = "SELECT mannschafts_id, position " .
				' FROM #__ttverein_aufstellungen AS aufstellungen ' .
				' WHERE aufstellungen.spieler_id = ' . $id;
		$db->setQuery( $query );
		$this->aufstellungen = $db->loadObjectList();
		
		/*
		 * Zusätzliche Spielerfelder laden
		 */
		$query = "SELECT f.id, f.name_backend, sf.kurz_text, sf.datum, sf.text, f.typ, f.tooltip " .
				" FROM #__ttverein_felder AS f" .
				" LEFT JOIN #__ttverein_spieler_felder AS sf " .
					" ON (f.id=sf.felder_id AND sf.spieler_id = $id) " .
				" ORDER BY f.reihenfolge ASC";
		
		$db->setQuery( $query );
		$spielerFelder = $db->loadObjectList();
		
		$this->felder = array();
		foreach($spielerFelder as $feld) {
			$wert = $feld->kurz_text;
			if($feld->typ == "jahre seit")
				$wert = $feld->datum;
			$this->felder[$feld->id]= new stdClass();
			$this->felder[$feld->id]->typ = $feld->typ;
			$this->felder[$feld->id]->name = $feld->name_backend;
			$this->felder[$feld->id]->wert = $wert;
			$this->felder[$feld->id]->tooltip = $feld->tooltip;
		}
		
		return true;
	}
	
	function store( $updateNulls=false ) {
		parent::store($updateNulls);
		
		//Datenbankverbindung
		$db = $this->_db;
		
		$spielerID = $this->id;
		
		
		/*
		 * Profielfelder eintragen
		 */
		foreach($this->felder as $felderID=>$feld) {
			
			/*
			 * Datum
			 */
			if($feld->typ == "jahre seit") {
				$spalte = "datum";
				
				//Datum in SQL (Englisches) Format umwandeln
				if($feld->wert) {
					$date = explode(".",$feld->wert);
					if(count($date) == 3)//Normales Deutsches Format tt.mm.jjjj
						$feld->wert = date("Y-m-d", mktime(0, 0, 0, $date[1], $date[0], $date[2]));
					else if( ($date = strtotime($feld->wert)) )//strtotime() wandelt verschiedene Formate in einen Timestamp um. Erst seit PHP5 wirklich gut.
						$feld->wert = date("Y-m-d", $date);
					else //Falsches Datum wird nicht gespeichert
						$feld->wert = null;
				}
				
				
			} 
			/*
			 * Text, email, Telefon
			 */
			else {
				$spalte = "kurz_text";				
			} 
			
			/*
			 * Leere Felder werden als null eingefügt
			 */
			if($feld->wert == "" || $feld->wert == null){
				$wert = "null";
				$query = "DELETE FROM #__ttverein_spieler_felder " .
						" WHERE felder_id = $felderID " .
							" AND spieler_id = $spielerID ";
				$db->setQuery($query);
				$db->query();
			}
			else {
				$wert = "'" . $feld->wert . "'";
				
				/*
				 * "ON DUPLICATE KEY UPDATE" bei MySQL 4.0 nicht möglich
				 */
				$query = "SELECT felder_id FROM #__ttverein_spieler_felder " .
						" WHERE felder_id=$felderID " .
							" AND spieler_id=$spielerID";
				$db->setQuery($query);

				if(count($db->loadObjectList()) >= 1) {
					$query = "UPDATE #__ttverein_spieler_felder " .
							" SET $spalte=$wert " .
							" WHERE felder_id=$felderID " .
								" AND spieler_id=$spielerID ";
					$db->setQuery($query);
					$db->query();
						
				} else {
					$query = "INSERT INTO #__ttverein_spieler_felder " .
							" SET felder_id=$felderID, spieler_id=$spielerID, " .
								" $spalte=$wert ";
					$db->setQuery($query);
					$db->query();
				}
			}
		}

		//Speichern der Aufstellung
		if($this->mannschaften != null && is_array($this->mannschaften)) {
			foreach($this->mannschaften as $key =>$id) {
				$id = intval($id);
				$tmp = explode("-", $key);
				$saison = intval($tmp[0]);
				$hinrunde = intval($tmp[1]);
				$position = intval($this->aufstellungen[$key]);

				//Löschen des vorher gesetzten Spielers
				

				/*
				 * Spieler aus den Aufstellungen der Saison herauslöschen.
				 */
				$query = "DELETE #__ttverein_aufstellungen " .
						" FROM #__ttverein_aufstellungen " .
							" INNER JOIN #__ttverein_mannschaften " .
						" WHERE #__ttverein_aufstellungen.mannschafts_id=#__ttverein_mannschaften.id " .
							" AND #__ttverein_mannschaften.saisonstart=$saison " .
							" AND #__ttverein_mannschaften.hinrunde=$hinrunde" .
							" AND #__ttverein_aufstellungen.spieler_id=$spielerID";
				$this->_db->setQuery($query);
				$this->_db->query();
				/*if ( !$this->_db->query() ) {
               		JError::raiseError(112, $this->_db->getErrorMsg());
               		return false;
            	}*/
				if ($id > 0 && $position > 0)  {
					// Vorhandenen Spieler auf der Position löschen
					//Vorher "DELETE IGNORE" - Inkompatibel zu MySQL 4.0
					$query = "DELETE FROM #__ttverein_aufstellungen " .
							" WHERE mannschafts_id = $id " .
							" AND position = $position ";
					$this->_db->setQuery($query);
					@$this->_db->query();
					/*if ( !$this->_db->query() ) {
               			JError::raiseError(111, $this->_db->getErrorMsg());
               			return false;
            		}*/
					
					//Speichern der Aufstellung
					$query = "INSERT INTO #__ttverein_aufstellungen " .
							" SET mannschafts_id=$id, spieler_id=$spielerID" .
							", position=$position ";
					$this->_db->setQuery($query);

					if ( !$this->_db->query() ) {
               			JError::raiseError(113, $this->_db->getErrorMsg());
               			return false;
            		}

				}

			}
		}
		
		return true;
	}

	function delete($oid=null) {
		$key = $this->_tbl_key;
		if ($oid) {
			$this->$key = intval( $oid );
		}

		//Datenbankverbindung
		$db = $this->_db;

		/*
		 * Bilder löschen
		 */
		 //TODO löschen konfigurierbar machen. 
		 //Nicht immer möchte mann, dass auch die Bilder glöscht werden sollen.
		$query = 'SELECT image_orginal, image_resize, image_thumb ' .
					' FROM #__ttverein_spieler ' .
					' WHERE id = ' . $this->$key;
		$db->setQuery($query);
		$images = $db->loadObjectList();
		
		if($images[0]->image_orginal)
			@unlink(JPATH_ROOT . $images[0]->image_orginal);
		if($images[0]->image_resize)
			@unlink(JPATH_ROOT . $images[0]->image_resize);
		if($images[0]->image_thumb)
			@unlink(JPATH_ROOT . $images[0]->image_thumb);
		
		
		/*
		 * Spielerdaten löschen
		 */
		if(parent::delete($oid) === false)
			return false;	
		
		/*
		 * Zusätliche Felder löschen
		 */
		$query = "DELETE FROM #__ttverein_spieler_felder " .
				" WHERE spieler_id = ". $this->$key;
		$db->setQuery( $query );
						
		if($db->query() === false)
			return false;
		
		/*
		 * Aufstellungen löschen
		 */
		$query = "DELETE FROM #__ttverein_aufstellungen " .
				" WHERE spieler_id=" . $this->$key;
		$db->setQuery($query);
		
		return $db->query();
	}
}
?>
