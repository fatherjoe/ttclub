<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport( 'joomla.application.component.view' );

class PlayersViewPlayers extends JViewLegacy
{
	protected $items = array();
	
	function display($tpl = null)
	{
		$items	=  $this->get( 'Data');
       		$this->items = $items;
       		$this->state = $this->get('State');

		JToolBarHelper::title(   JText::_( 'Spieler Manager' ), 'generic.png' );
		JToolBarHelper::publishList();
		JToolBarHelper::unpublishList();
		JToolBarHelper::deleteList();
		JToolBarHelper::editList();
		JToolBarHelper::addNew();

		$this->assignRef('items', $items);

		parent::display($tpl);
	}
}
