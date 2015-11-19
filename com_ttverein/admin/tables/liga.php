<?php


defined('_JEXEC') or die('Restricted access');

class TableLiga extends JTable
{
	/** @var int Primary key */
	var $id					= 0;

	var $name				= "";

	var $reihenfolge		= 99;

	function TableLiga(& $db) {
		parent::__construct('#__ttverein_ligen', 'id', $db);
	}
}
?>
