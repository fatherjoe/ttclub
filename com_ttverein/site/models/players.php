<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
jimport('joomla.application.component.model');

require( JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'lib' .'/'. 'clicktt' .'/'. 'clicktt.php');
require( JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'lib' .'/'. 'config' .'/'. 'config.php' );

class TeamsModelPlayers extends JModelLegacy
{
	var $_data = null;
	var $clicktt = null;
	var $saions = null;
	var $config = null;
	
	function __construct($options = array()) {
		parent::__construct($options);
		$this->config = Config::getConfig(array('clicktt_verband', 'clicktt_club_nummer', 'clicktt_club_name', 'clicktt_use', 'player_thumb_size'));
		if($this->config['clicktt_use'] == "1" && $this->config['clicktt_verband'] && $this->config['clicktt_club_name']) {
			$this->clicktt = new ClickTT($this->config['clicktt_verband'], $this->config['clicktt_club_nummer'], $this->config['clicktt_club_name']);
			$this->saions = $this->clicktt->getSaisonStarts();
		}		
	}
	
	function _getPlayerQuery($type) {
		$query = "SELECT DISTINCT spieler.* " .
				" FROM #__ttverein_spieler AS spieler " .
					" , #__ttverein_aufstellungen AS aufstellungen " .
					" , #__ttverein_mannschaften AS mannschaften " .
					" , #__ttverein_altersklassen AS altersklassen " . 
				" WHERE spieler.id = aufstellungen.spieler_id " .
					" AND mannschaften.id = ( SELECT MAX(aufstellungen.mannschafts_id) FROM #__ttverein_aufstellungen AS aufstellungen where aufstellungen.spieler_id = spieler.id ) " .
					" AND aufstellungen.mannschafts_id = mannschaften.id " .
					" AND mannschaften.altersklasse = altersklassen.id " .
					" AND spieler.published = 1 ";
		
		if($type == "position") {
			$query .= " ORDER BY altersklassen.reihenfolge ASC, " .
						" mannschaften.nummer ASC, " .
						" aufstellungen.position ASC ";
		}
		
		return $query;
	}

	function getPlayers($type = "position") {
		if(!$this->_data) {
			$query = $this->_getPlayerQuery($type);
			$this->_db->setQuery( $query );
			$this->_data = $this->_data = $this->_db->loadObjectList();
		}
		return $this->_data;
	}
	
	function getConfig() {
		return $this->config;
	}
}
?>
