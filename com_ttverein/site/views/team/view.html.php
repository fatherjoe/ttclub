<?php
jimport( 'joomla.application.component.view');



class TeamsViewteam extends JViewLegacy
{
	
	function display($tpl = null)
	{		
		$id = JRequest::getInt('id', '-1', 'GET');
		if ($id === -1){
			$params = JComponentHelper::getParams( 'com_ttverein' );
			$id = $params->get( 'mannschaftsid' ); 
		}

 		$model = $this->getModel();
		$data = $model->getData($id);
		$ajax = $model->getAjax();
		$config = $model->getConfig();
		
  		$this->assignRef( 'team', $data);
		$this->assignRef( 'ajax', $ajax);  		 		
		$this->assignRef( 'config'  , $config);
		

		parent::display($tpl);
	}	
}