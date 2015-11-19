<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
jimport('joomla.application.component.model');

require( JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'lib'.'/'. 'clicktt'.'/'. 'clicktt.php' );
require( JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'lib'.'/'. 'config'.'/'. 'config.php' );

class TeamsModelPlayer extends JModelLegacy
{
	var $_data = null;
	var $clicktt = null;
	//var $saions = null;
	var $config = null;
	function __construct($options = array()) {
		parent::__construct($options);
		$this->config = Config::getConfig(array('clicktt_verband', 'clicktt_club_nummer', 'clicktt_club_name', 'clicktt_use', 'clicktt_calc_leistungsindex', 'clicktt_club_id'));
		if($this->config['clicktt_use'] == "1" && $this->config['clicktt_verband'] && $this->config['clicktt_club_name']&& $this->config['clicktt_club_nummer']) {
			$this->clicktt = new ClickTT($this->config['clicktt_verband'], $this->config['clicktt_club_nummer'], $this->config['clicktt_club_name'],$this->config['clicktt_club_id']);
			if(!$this->config['clicktt_club_id']) {
				Config::setConfig("clicktt_club_id", $this->clicktt->getCache("clubID"));
			}
		}

	}
	
	function getPlayer( $id ) {
		if(!$this->_data) {
			$query = "SELECT * " .
				" FROM #__ttverein_spieler " . 
				" WHERE id = " . $id . 
					" AND published = 1 ";
			$this->_db->setQuery( $query );
			$this->_data = $this->_db->loadObject();
			
			$query = "SELECT sf.kurz_text, sf.datum, sf.text, f.typ, f.name_frontend AS name" .
					" FROM #__ttverein_spieler_felder AS sf, #__ttverein_felder AS f " .
					" WHERE sf.spieler_id = $id " .
						" AND sf.felder_id = f.id " .
					" ORDER BY f.reihenfolge, f.id ASC ";
			$this->_db->setQuery( $query );
			$this->_data->felder = $this->_db->loadObjectList();
		}
		
		if($this->_data->nachname == '') {
			throw new JException("Der Spieler wurde nicht gefunden. Möglicherweise ist er nicht mehr aktiv.", 410);
		}
		
		$this->_data->aufstellungen = null;
		return $this->_data;
	}
	
	function getAufstellungen($playerId=null) {
		if(!$this->_data)
			getPlayer($playerId);
		if($this->_data->aufstellungen)
			return $this->_data->aufstellungen;
		
		$id	= $this->_data->id;
		
		$query = "SELECT mannschaften.id, mannschaften.nummer, " .
					" altersklassen.name AS altersklasse, aufstellungen.position, " .
					" #__ttverein_ligen.name AS liga, " .
					" mannschaften.saisonstart, mannschaften.hinrunde" .
				" FROM #__ttverein_altersklassen AS altersklassen, " .
					" #__ttverein_aufstellungen AS aufstellungen," .
					" #__ttverein_mannschaften AS mannschaften " .
				" LEFT JOIN #__ttverein_ligen ON (#__ttverein_ligen.id=mannschaften.liga)" .
				" WHERE mannschaften.id = aufstellungen.mannschafts_id " .
					" AND mannschaften.altersklasse = altersklassen.id " .
					" AND aufstellungen.spieler_id = $id " .
				" ORDER BY mannschaften.saisonstart DESC, mannschaften.hinrunde ASC ";
		$this->_data->aufstellungen = $this->_getList( $query );
		return $this->_data->aufstellungen;
	}
	
	
	/**
	 * @return int|bool Bei Erfolg wird die personID von click-TT zurück gegeben.
	 * 	Bei Misserfolg wird false zurück gegeben.  
	 */
	function getClickTTPersonId($playerId=null) {
				
		if(!$this->_data->clicktt_person_id) {
			if($this->clicktt == null)
				return false;
			
			if(!$this->_data || !$this->_data->aufstellungen)
				$this->getAufstellungen($playerId);
			
			if(count($this->_data->aufstellungen) <= 0)
				return false;
			
			$aufstellung = current($this->_data->aufstellungen);
			$altersklasse = $aufstellung->altersklasse;
			
			$saisons = $this->clicktt->getSaisonStarts();
			$person = $this->clicktt->getPersonByName($this->_data->vorname, $this->_data->nachname, $saisons[0], $altersklasse, true);
			if($person == null)
				return false;
			$this->_data->clicktt_person_id = intval($person->id);
			
			/*
			 * Speichert die clicktt Personen ID in die Datenbank, 
			 * damit diese bein nächsten mal nicht neu gesucht werden muss.
			 */
			$this->_db->setQuery("UPDATE #__ttverein_spieler " .
								" SET clicktt_person_id=" . $person->id . 
								" WHERE id = " . $this->_data->id);
			$this->_db->query();
		}
		return $this->_data->clicktt_person_id;
	}
	
	function getAjax($playerID=null){
		if(!$this->_data)
			$this->getPlayer($playerID);
		$this->config['personid'] = $this->getClickTTPersonId($playerID);
		return $this->config;
	}
	
	function getLeistungsIndex($playerID=null) {
		if($this->config['clicktt_calc_leistungsindex'] == "0")
			return null;
		if(!$this->_data || !$this->_data->leistungsIndex)
			$this->getBilanzen($playerID);
		return $this->_data->leistungsIndex;
	}
}
?>
