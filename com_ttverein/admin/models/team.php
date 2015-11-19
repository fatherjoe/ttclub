<?php

defined('_JEXEC') or die();
jimport('joomla.application.component.model');

require( JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'lib'.'/'. 'config'.'/'. 'config.php' );

/**
 * @author Sven Nissel
 */
class TeamsModelTeam extends JModelLegacy
{
	function __construct()
	{
		parent::__construct();

		$array = JRequest::getVar('cid',  0, '', 'array');
		$this->setId((int)$array[0]);
	}

	function setId($id)
	{
		$this->_id		= $id;
	}

	function getData($id=null)
	{
		if($id == null)
			$id = $this->_id;
		$query = ' SELECT * FROM #__ttverein_mannschaften '.
				'  WHERE id = '.$id;
		$this->_db->setQuery( $query );
		$data = $this->_db->loadObject();
		
		if($data == null) {
			$data = new stdClass();
			$data->id 					= 0;
			$data->published 			= 1;
			
			$data->nummer				= null;
			$data->saisonstart	  		= 0;
			$data->hinrunde				= 1;
			$data->altersklasse			= 0;
			$data->mannschaftsfuehrer	= null;
		
			$data->image_orginal		= null;
			$data->image_resize			= null;
			$data->image_thumb			= null;
			$data->image_text			= null;
		
			$data->liga					= null;
			$data->clicktt_championship = null;
			$data->clicktt_group 		= null;
			$data->clicktt_teamtable    = null;
			
		}
		
		$data->altersklassen = $this->getAltersKlassen();
		$data->ligen = $this->getLigen();
		$data->aufstellungen = $this->getAufstellungen($id);
		$data->spieler = $this->getSpieler();
	
		return $data;
	}

	function getLigen() {
		$options = array();
		$query = ' SELECT id, name ' .
				' FROM #__ttverein_ligen ' .
				' ORDER BY reihenfolge ASC ';
		return $this->_getList( $query );

	}

	function getAltersKlassen() {
		$query = ' SELECT * FROM #__ttverein_altersklassen ';
		return $this->_getList( $query );
	}

	function getAltersklassenName($id=null) {
		if($id == null) {
			JError::raiseWarning(192, "ID der Altersklasse wurde nicht angegeben");
			return null;
		}
		foreach($this->getAltersKlassen() as $klasse) {
			if( $klasse->id == $id ) {
				return $klasse->name;
			}
		}
		JError::raiseWarning(193, "Keine passende Altersklasse gefunden");
		return null;
	}
	
	function getAufstellungen($id=null) {
		if($id == null)
			$id = $this->_id;
		$query = "SELECT aufstellungen.position, spieler.id, spieler.vorname, spieler.nachname" .
				' FROM #__ttverein_aufstellungen AS aufstellungen, ' .
					' #__ttverein_spieler AS spieler ' .
				' WHERE aufstellungen.spieler_id = spieler.id ' .
					' AND aufstellungen.mannschafts_id = '.$id .
				' ORDER BY aufstellungen.position ASC ';
		return $this->_getList( $query );
	}
	
	function getSpieler() {
		$query = "SELECT id, CONCAT(nachname, ', ', vorname) AS name " .
				' FROM #__ttverein_spieler ' .
				' ORDER BY nachname ASC ';
		return $this->_getList( $query );
	}

	function store($data=null)	{
		$row = $this->getTable();

		if($data == null)
			$data = JRequest::get( 'post' );

		//Keine Daten Vorhanden.
		if(!is_array($data)) {
			JError::raiseWarning(191, "Es wurden keine Daten gespeichert");
			return false;
		}
		
		$spielerliste = $data['spieler'];
		/*
		 * String(0)'' Values werden herausgefiltert, damit sie in nicht 
		 * als String sondern mit null in die Datenbank gespeichert werden.
		 */
		foreach($data as $key=>$value) {
			if($value != null) {
				if (is_string ($value))
					if(trim($value) == '')
						$data[$key] = null;
			}
		}
		if($data['mannschaftsfuehrer'] == 0)
			$data['mannschaftsfuehrer'] = null;
		
		if (!$row->bind($data)) {
			JError::raiseError(101, $this->_db->getErrorMsg());
			return false;
		}

		if (!$row->check()) {
			JError::raiseError(102, $this->_db->getErrorMsg());
			return false;
		}

		if (!$row->store(true)) {
			JError::raiseError(103, $this->_db->getErrorMsg());
			return false;
		}
		
		/*
		 * Bugfix - wenn Saison der Mannschaft geändert wird, darf nicht die alte Tabelle geladen werden.
		 */
		$query = "UPDATE #__ttverein_mannschaften " .
				" SET clicktt_group=NULL, clicktt_championship=NULL " .
				" WHERE id=$row->id";
		
		// Löschen der gesamten Aufstellung
		$query = "DELETE FROM #__ttverein_aufstellungen " .
					" WHERE mannschafts_id=". $row->id;
		$this->_db->setQuery($query);
		if ( !$this->_db->query() ) {
			JError::raiseError(105, $this->_db->getErrorMsg());
			return false;
		}

		// Neu schreiben der Gesamten Aufstellung
		foreach($spielerliste as $position=>$spielerID) {
			if($spielerID > 0) {
				//TODO Aufstellungen in anderen mannschaften löschen (gleiche Saison)
				$query = "INSERT INTO #__ttverein_aufstellungen " .
							" SET mannschafts_id=" . $row->id . 
							", spieler_id=" . $spielerID .
							", position=$position ";
				$this->_db->setQuery($query);
				if ( !$this->_db->query() ) {
               		JError::raiseError(104, $this->_db->getErrorMsg());
               		return false;
            	}
			}
		}

		return true;
	}

	function delete()
	{
		$cids = JRequest::getVar( 'cid', array(0), 'post', 'array' );

		$row = $this->getTable();

		if (count( $cids ))		{
			foreach($cids as $cid) {
				 /*
				  * Bilder löschen
				  */
				 //TODO löschen konfigurierbar machen. 
				 //Nicht immer möchte mann, dass auch die Bilder glöscht werden sollen.
				$query = 'SELECT image_orginal, image_resize, image_thumb ' .
							' FROM #__ttverein_mannschaften ' .
							' WHERE id = ' . $cid;
				$images = $this->_getList($query);
				@unlink(JPATH_ROOT . $images[0]->image_orginal);
				@unlink(JPATH_ROOT . $images[0]->image_resize);
				@unlink(JPATH_ROOT . $images[0]->image_thumb);
				/* 
				 * Aufstellungen löschen
				 */
				$query = "DELETE FROM #__ttverein_aufstellungen " .
						" WHERE mannschafts_id=" . $cid;
				$this->_db->setQuery($query);
				if ( !$this->_db->query() ) {
					JError::raiseError(105, $this->_db->getErrorMsg());
               		return false;
				}
				
				/*
				 * Mannschaft löschen
				 */
				if (!$row->delete( $cid )) {
					JError::raiseError(106, $this->_db->getErrorMsg());
					return false;
				}
			}
		}
		return true;
	}
	
	function getConfig(){
		return Config::getConfig(array('team_thumb_size',
								'team_image_path',
								'team_image_size'));
	}


}
?>
