<?php


defined('_JEXEC') or die('Restricted access');

class TableTeam extends JTable
{
	/** @var int Primary key */
	var $id					= 0;

	var $nummer				= 0;

	var $saisonstart	  	= 0;
	var $hinrunde			= 1;

	var $altersklasse		= 0;

	var $image_orginal		= null;
	var $image_resize		= null;
	var $image_thumb		= null;
	var $image_text			= null;
	var $mannschaftsfuehrer = null;

	var $liga				= null;
	var $clicktt_championship = null;
	var $clicktt_group 		= null;
	

	var $published			= 0;

	function TableTeam(& $db) {
		parent::__construct('#__ttverein_mannschaften', 'id', $db);
	}
}
?>
