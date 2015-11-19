<?php

defined('_JEXEC') or die();
jimport('joomla.application.component.model');

require( JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'lib'.'/'. 'clicktt'.'/'. 'clicktt.php' );
require( JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'lib'.'/'. 'config'.'/'. 'config.php' );


class TeamsModelTeam extends JModelLegacy
{
	var $ajaxclicktt = null;
	
	function __construct($options = array()) {
		$this->ajaxclicktt = new stdClass();
		$this->ajaxclicktt->verband = null;
		$this->ajaxclicktt->vereinsnummer = null;
		$this->ajaxclicktt->clubname = null;
		$this->ajaxclicktt->clubid = null;
		$this->ajaxclicktt->championship = null;
		$this->ajaxclicktt->group = null;
		$this->ajaxclicktt->teamtable = null;
		parent::__construct($options);
		
	}
	
	function getTeamQuery( $id )
	{
		$select = 'mannschaften.id, mannschaften.nummer, mannschaften.saisonstart, ' .
				' mannschaften.hinrunde, mannschaften.mannschaftsfuehrer,' .
				' mannschaften.image_orginal, ' .
				' mannschaften.image_resize, mannschaften.image_thumb, ' .
				' mannschaften.image_text, #__ttverein_ligen.name AS liga, mannschaften.clicktt_championship, ' .
				' mannschaften.clicktt_group, ' .
				' mannschaften.clicktt_teamtable, ' .
				' altersklassen.name AS altersklasse';
		$from	= '#__ttverein_altersklassen AS altersklassen, #__ttverein_mannschaften AS mannschaften ';

		$wheres[] = 'mannschaften.published = 1';
		$wheres[] = 'altersklassen.id = mannschaften.altersklasse';
		$wheres[] = 'mannschaften.id = ' . $id;

		$query = "SELECT " . $select .
				"\n FROM " . $from .
				"\n LEFT JOIN #__ttverein_ligen ON (#__ttverein_ligen.id=mannschaften.liga)" .
				"\n WHERE " . implode( "\n  AND ", $wheres );

		return $query;
	}


	function getData($id) {
		$db = JFactory::getDBO();
		
		$team = $this->getTeam($id);

		if($team->nummer == null) {
			throw new JException("Mannschaft nicht gefunden", 404);
		}
		
		$team->players = $this->getPlayers($id);
		$team->spielerFelder = $this->getSpielerFelder();
		
		$team->table = null;
		$config = $this->getConfig();
		if($config['clicktt_use'] == "1") {
			if($team->hinrunde == 1)
				$team->table = $this->getClickttTable($id, $team->altersklasse, $team->nummer, true);
			else
				$team->table = $this->getClickttTable($id, $team->altersklasse, $team->nummer, false);
		}
			
		
		return $team;
	}
	
	function getTeam( $id )
	{
		$db = JFactory::getDBO();
		$db->setQuery( $this->getTeamQuery($id) );
		$team = $db->loadObject();
		
		if($team->mannschaftsfuehrer) {
			$query = "SELECT vorname, nachname " .
					" FROM #__ttverein_spieler " .
					" WHERE id=" . $team->mannschaftsfuehrer;
			$db->setQuery($query);
			$spieler = $db->loadObject();
			$team->mannschaftsfuehrerName = $spieler->vorname . " " . $spieler->nachname;
		} else {
			$team->mannschaftsfuehrerName = null;
		}
		
		return $team;
		
	}
	

	function getSpielerFelder() {
		$db = JFactory::getDBO();
		$query = "SELECT f.typ, f.name_frontend AS name" .
						" FROM #__ttverein_felder AS f " .
						" WHERE f.zeige_in_uebersicht = 1 " .
						" ORDER BY f.reihenfolge, f.id ASC ";
		$db->setQuery( $query );
		return $db->loadObjectList();
	}


	function getPlayers( $id ) {
		$db = JFactory::getDBO();
		$query = "SELECT spieler.id, spieler.vorname, spieler.nachname, spieler.published," .
					" aufstellungen.position " .
				" FROM #__ttverein_mannschaften AS mannschaften, " .
					" #__ttverein_aufstellungen AS aufstellungen, " .
					" #__ttverein_spieler AS spieler " .
				" WHERE mannschaften.id = aufstellungen.mannschafts_id " .
					" AND spieler.id = aufstellungen.spieler_id " .
					" AND mannschaften.id = $id " .
				" ORDER BY aufstellungen.position ASC ";
		$db->setQuery( $query );
		$spieler = $db->loadObjectList();
		
		foreach($spieler as $index=>$einSpieler) {
			$query = "SELECT sf.kurz_text, sf.datum, sf.text, f.typ, f.name_frontend AS name" .
						" FROM #__ttverein_spieler_felder AS sf, #__ttverein_felder AS f " .
						" WHERE sf.spieler_id = " . $einSpieler->id . 
							" AND sf.felder_id = f.id " .
							" AND f.zeige_in_uebersicht = 1 " .
						" ORDER BY f.reihenfolge, f.id ASC ";
			$db->setQuery( $query );
			$spieler[$index]->felder = $db->loadObjectList();
		}
		return $spieler;
	}
	
	function getNewestSaisons() {
		$db = JFactory::getDBO();
		$query = "SELECT max(saisonstart) AS aktuelle_saison " .
				" FROM #__ttverein_mannschaften ";
		$db->setQuery( $query );
		$result = $db->loadObject();
		return $result->aktuelle_saison;
	}
	
	function getClickttTable($id, $altersklasse, $mannschaftsNummer) {
		$db = JFactory::getDBO();
		
		$config = Config::getConfig(array('clicktt_use', 'clicktt_verband', 'clicktt_club_nummer', 'clicktt_club_name', 'clicktt_club_id'));		
		if($config['clicktt_use'] == "0" || !$config['clicktt_verband'] || !$config['clicktt_club_name'] || !$config['clicktt_club_nummer']) 
			return "";
			
		$clicktt = new ClickTT($config['clicktt_verband'], $config['clicktt_club_nummer'], $config['clicktt_club_name'], $config['clicktt_club_id']);
		if(!$config['clicktt_club_id']) {
			$config['clicktt_club_id'] = $clicktt->getCache("clubID");
			Config::setConfig("clicktt_club_id", $config['clicktt_club_id']);
		}
		
		$clicktt->setImageUrl(JURI::root() . "components/com_ttverein/images/");
		
		$team = $this->getTeam($id);
		$neusteSaison = $this->getNewestSaisons();
		
		if($team->clicktt_championship && $team->clicktt_group) {
			$championship =  $team->clicktt_championship;
			$group =  $team->clicktt_group;
			$teamtable = $team->clicktt_teamtable;
		} else if($neusteSaison == $team->saisonstart) {
			$clicktt_teams = $clicktt->getTeams($team->altersklasse, $team->nummer, $team->hinrunde);
			
			//Ist kein gültiges Ergebnis vorhanden wird abgebrochen.
			if(!is_array($clicktt_teams) || !array_key_exists($team->altersklasse,$clicktt_teams) || !array_key_exists($team->nummer,$clicktt_teams[$team->altersklasse]))
				return "";
			$championship = $clicktt_teams[$team->altersklasse][$team->nummer]['championship'];
			$group = $clicktt_teams[$team->altersklasse][$team->nummer]['group'];
			$teamtable = $clicktt_teams[$team->altersklasse][$team->nummer]['teamtable'];
			
			$db->setQuery( "UPDATE #__ttverein_mannschaften " .
							" SET clicktt_championship='" . $championship . "', " .
									"clicktt_group=" . intval($group) . 
							" WHERE id=" . $id);
			$db->query();
		} else 
			return "";
		
		$this->ajaxclicktt->verband = $config['clicktt_verband'];
		$this->ajaxclicktt->vereinsnummer = $config['clicktt_club_nummer'];
		$this->ajaxclicktt->clubname = $config['clicktt_club_name'];
		$this->ajaxclicktt->clubid = $config['clicktt_club_id'];
		$this->ajaxclicktt->championship = $championship;
		$this->ajaxclicktt->group = $group;
		$this->ajaxclicktt->teamtable = $teamtable;
	}

	function getConfig() {
		return Config::getConfig(array('team_image_size', 'clicktt_use'));
	}
	
	function getAjax(){
		return $this->ajaxclicktt;
	}
}
?>
