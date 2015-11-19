<?php

// no direct access
defined('_JEXEC') or die('Restricted access');

class TableFormation extends JTable
{
	/** @var int Primary key */
	var $id					= 0;
	var $mannschafts_id		= 0;
	var $spieler_id			= 0;
	var $position			= 0;

	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	function TablePlayer(& $db) {
		parent::__construct('#__ttverein_aufstellungen', 'id', $db);
	}

	//TODO Check Duplicates
	function check()
	{
		return true;
	}

}
?>
