<?php

defined('_JEXEC') or die();

jimport( 'joomla.application.component.view' );

class ConfigViewConfig extends JViewLegacy
{
	function display($tpl = null)
	{
		JToolBarHelper::save();
		$config		= $this->get( 'Data');
		$verbaende = $this->get( 'ClickttVerbaende');

		$this->assignRef('config', $config);
		$this->assignRef('clickttVerbaende', $verbaende);
		
		parent::display($tpl);
	}
}
