<?php
jimport( 'joomla.application.component.view');



class teamsViewCalendar extends JViewLegacy
{
	
	function display($tpl = null)
	{		
 		$model = $this->getModel();
		$data = $model->getData(1);
		$ajax = $model->getAjax();
		$config = $model->getConfig();
		
  		$this->assignRef('nextMatches', $data );
		$this->assignRef('ajax', $ajax);  		 		
		$this->assignRef('config', $config);

		parent::display($tpl);
	}	
}