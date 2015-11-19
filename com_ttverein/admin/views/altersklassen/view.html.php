<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport( 'joomla.application.component.view' );

class AltersklassenViewAltersklassen extends JViewLegacy
{

	function display($tpl = null)
	{
		JToolBarHelper::title(   JText::_( 'Altersklassen verwalten' ), 'generic.png' );
		JToolBarHelper::deleteList();
		JToolBarHelper::editList();
		JToolBarHelper::addNew();

		$items		= $this->get( 'Data');
		//var_dump($this);
		//die();

		$this->assignRef('items',		$items);


		parent::display($tpl);
	}
}
