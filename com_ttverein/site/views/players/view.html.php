<?php
jimport( 'joomla.application.component.view');


class TeamsViewplayers extends JViewLegacy
{
	function display($tpl = null)
	{
		$model	  = $this->getModel();
		
  		$players     = $model->getPlayers();
  		$this->assignRef('players'  , $players);
		
		$config     = $model->getConfig();
  		$this->assignRef('config'  , $config);
		
		parent::display($tpl);
	}
}