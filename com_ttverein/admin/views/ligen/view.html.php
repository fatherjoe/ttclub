<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport( 'joomla.application.component.view' );

class LigenViewLigen extends JViewLegacy
{

	function display($tpl = null)
	{
		JToolBarHelper::title(   JText::_( 'Ligen verwalten' ), 'generic.png' );
		JToolBarHelper::deleteList();
		JToolBarHelper::editList();
		JToolBarHelper::addNew();

		$items		= $this->get( 'Data');

		$this->assignRef('items',		$items);

		parent::display($tpl);
	}
}
