<?php


defined('_JEXEC') or die('Restricted access');

class TableAltersklasse extends JTable
{
	/** @var int Primary key */
	var $id					= 0;

	var $name				= "";
	
	var $maxalter			= null;
	var $minalter			= null;
	var $reihenfolge		= 99;

	function TableAltersklasse(& $db) {
		parent::__construct('#__ttverein_altersklassen', 'id', $db);
	}
}
?>
