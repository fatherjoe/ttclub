<?php
jimport( 'joomla.application.component.view');


class TeamsViewplayer extends JViewLegacy
{
	function display($tpl = null)
	{
		$id = JRequest::getInt('id', '-1', 'GET');

		if ($id === -1){
			$params = JComponentHelper::getParams( 'com_ttverein' );
			$id = $params->get( 'spielerid' ); 
		}

 		$model = $this->getModel();
		$ajax = $model->getAjax($id);
		$player = $model->getPlayer($id);
		$aufstellungen = $model->getAufstellungen();
		
  		$this->assignRef('player', $player);
		$this->assignRef('ajax', $ajax);
		$this->assignRef('aufstellungen', $aufstellungen);
		
		parent::display($tpl);
	}
}