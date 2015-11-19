<?php

defined('_JEXEC') or die();
jimport('joomla.application.component.model');

require( JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'lib'.'/'. 'clicktt'.'/'. 'clicktt.php' );
require( JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'lib'.'/'. 'config'.'/'. 'config.php' );


class TeamsModelCalendar extends JModelLegacy
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
		parent::__construct($options);
		
	}
	
	function getData($id) {
		$config = $this->getConfig();
		$this->getClickttTable();
	}
	
	function getClickttTable() {
		$config = $this->getConfig();
		$this->ajaxclicktt->verband = $config['clicktt_verband'];
		$this->ajaxclicktt->vereinsnummer = $config['clicktt_club_nummer'];
		$this->ajaxclicktt->clubname = $config['clicktt_club_name'];
		$this->ajaxclicktt->clubid = $config['clicktt_club_id'];
	}

	function getConfig() {
		return Config::getConfig(array('clicktt_verband','clicktt_club_nummer', 'clicktt_club_name' ,'clicktt_club_id'));
	}
	
	function getAjax(){
		return $this->ajaxclicktt;
	}
}
?>
