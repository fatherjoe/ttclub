<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
jimport('joomla.application.component.model');

require( JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'lib'.'/'. 'config'.'/'. 'config.php' );

/**
 * @author Sven Nissel
 */
class PlayersModelPlayer extends JModelLegacy
{
	function __construct()
	{
		parent::__construct();

		$array = JRequest::getVar('cid',  0, '', 'array');
		$this->setId((int)$array[0]);
	}

	/**
	 * Beim Erzeugen des Objektes setzt Joomla automatisch die ID
	 * 
	 * @access public
	 * @param int $id
	 */
	function setId($id) {
		$this->_id		= intval($id);
	}


	/**
	 * Lädt die Spielerdaten, alle Mannschaften mit ihrer Altersklasse 
	 * und Alle Mannschaften in der der Spieler aufgestellt ist.
	 * 
	 * @access	public
	 * @return mixed Ein Objekt mit allen Spielerdaten.
	 */
	function getData()
	{
		$row = $this->getTable();
		$row->load($this->_id);

		return $row;
	}
	
	/**
	 * Gibt die Konfigurationsdaten zurück die im Zusammenhang mit dem Spieler stehen.
	 * 
	 * @access public
	 * @return array 'player_thumb_size', 'player_image_path', 'player_image_size'
	 */
	function getConfig(){
		return Config::getConfig(array('player_thumb_size',
								'player_image_path',
								'player_image_size'));
	}

	/**
	 * Speichert die Spielerdaten und die Aufstellung
	 * 
	 * @access public
	 * @param array $data Is $data = null wird versucht über 
	 * JRequest::get( 'post' ) an die Daten zu gelangen.
	 * @return	boolean	True bei Erfolg
	 */
	function store($data=null)
	{
		if($data == null)
			$data = JRequest::get( 'post' );
			
		//Keine Daten Vorhanden.
		if(!is_array($data))
			return false;
			
		//Speichern der Spielerdaten
		$row = $this->getTable();
		
		if (!$row->bind($data)) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

		if (!$row->check()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

		if (!$row->store(true)) {
			$this->setError( $this->_db->getErrorMsg() );
			return false;
		}
		
		return true;
	}

	/**
	 * Löscht alle Spieler Daten
	 *
	 * @access	public
	 * @return	boolean	True bei Erfolg
	 */
	function delete()
	{
		$cids = JRequest::getVar( 'cid', array(0), 'post', 'array' );


		if (count( $cids ) > 0)
		{
			foreach($cids as $cid) {
				$row = $this->getTable();
				/*
				 * Spieler löschen
				 */
				if (!$row->delete( $cid )) {
					return false;
				}
			}
		}
		return true;
	}


}
?>
