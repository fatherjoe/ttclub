<?php
defined('_JEXEC') or die();
jimport( 'joomla.application.component.model' );

require( JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'lib'.'/'. 'clicktt'.'/'. 'clicktt.php' );
require_once( JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'lib'.'/'. 'clicktt'.'/'. 'verband.php' );

/**
 * @author Sven Nissel
 */
class ConfigModelConfig extends JModelLegacy
{
	var $needUpdate = array();
	
	function getData()
	{
		$query = ' SELECT name, value ' .
				' FROM #__ttverein_config ' .
				' WHERE show_in_config = 1';
		$result = $this->_getList( $query );
		
		//Result in eine einfach handhabbare Version bringen.
		$return = array();
		foreach($result as $row) {
			$return[$row->name] = $row->value;
		}
		return $return;
	}
	
	function getClickttVerbaende() {
		
		return Verband::getVerbaende();
		
	}
	
	function ImagesNeedUpdate() {
		return $this->needUpdate;
	}
	
	function setImageNeededUpdates($updates) {
		$this->needUpdate = $updates;
	}

	function store($post=null)
	{
		
		$this->_makeUpdate($post, 'team_thumb_size', 'team_thumb_size_old');
		$this->_makeUpdate($post, 'team_image_size', 'team_image_size_old');
		$this->_makeUpdate($post, 'player_thumb_size', 'player_thumb_size_old');
		$this->_makeUpdate($post, 'player_image_size', 'player_image_size_old');

		$this->_makeUpdate($post, 'player_image_path');
		$this->_makeUpdate($post, 'team_image_path');
		
		/*
		 * Löschen Einstellungen. Parameter um zu steuern ob nach der Deinstallation 
		 * die Daten gelöscht werden sollen.
		 */
		$this->_makeUpdate($post, 'delete_database');
		$this->_makeUpdate($post, 'delete_pictures');
		
		/*
		 * Click TT Einstellungen
		 */
		$this->_makeUpdate($post, 'clicktt_use');
		$this->_makeUpdate($post, 'clicktt_verband');
		$this->_makeUpdate($post, 'clicktt_club_nummer');
		$this->_makeUpdate($post, 'clicktt_club_name');
		$this->_makeUpdate($post, 'clicktt_calc_leistungsindex');
		
		/* Layout einstellungen */
		$this->_makeUpdate($post, 'div_team_height');
				
		return true;
	}

	function _makeUpdate($post, $name, $oldImageSize=null) {
		if($oldImageSize) {
			//Wenn Daten sich nicht geändert haben, müssen sie nicht gespeichert werden.
			if($post[$name] == $post[$oldImageSize])
				return null;
			//Für späteres verkleiner/vergrößern der Bilder im Controler
			$this->needUpdate[] = $name;
		}
		$query = 'UPDATE #__ttverein_config ' .
					" SET value='" . trim($post[$name]) . "'" .
					" WHERE name='". $name . "'";
		$this->_db->setQuery($query);
		return $this->_db->query();
	}

}
