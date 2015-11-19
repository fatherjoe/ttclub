<?php

defined('_JEXEC') or die();
jimport('joomla.application.component.model');


/**
 * @author Sven Nissel
 */
class AltersklassenModelAltersklasse extends JModelLegacy
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


	function getData()
	{
		$row =$this->getTable();
		$row->load($this->_id);
		return $row;
		
		/*//TODO Table Load funktion benutzen
		$query = ' SELECT * FROM #__ttverein_altersklassen ' .
					' WHERE id = '.$this->_id;
		$this->_db->setQuery( $query );
		$data = $this->_db->loadObject();
		
		if($data == null) {
			$data = new stdClass();
			$data->id = 0;
			$data->name = "";
			$data->minalter = null;
			$data->maxalter = null;
			$data->reihenfolge = null;
		}

		return $data;*/
	}
	
	


	function store($data=null)
	{
		if($data == null)
			$data = JRequest::get( 'post' );
			
		//Keine Daten Vorhanden.
		if(!is_array($data))
			return false;
		
		/*
		 * String(0)'' Values werden herrausgefiltert, damit sie in nicht 
		 * als String sondern mit null in die Datenbank gespeidchert werden.
		 */
		foreach($data as $key=>$value) {
			if(trim($value) == '')
				$data[$key] = null;
		}


	
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

	
	function delete()
	{
		$cids = JRequest::getVar( 'cid', array(0), 'post', 'array' );


		if (count( $cids ))
		{
			foreach($cids as $cid) {
				
				$row = $this->getTable();
	
				if (!$row->delete( $cid )) {
					$this->setError( $this->_db->getErrorMsg() );
					return false;
				}
			}
		}
		return true;
	}


}
?>
