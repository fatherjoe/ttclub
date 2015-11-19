<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport( 'joomla.application.component.view' );

class PlayersViewPlayer extends JViewLegacy
{
	function display($tpl = null)
	{
		$player = $this->get('Data');
		$isNew = ($player->id < 1);

		$text = $isNew ? JText::_( 'New' ) : JText::_( 'Edit' );
		JToolBarHelper::title(   JText::_( 'Spieler' ).': <small>[ ' . $text.' ]</small>' );
		JToolBarHelper::save();
		if ($isNew)  {
			JToolBarHelper::cancel();
		} else {
			JToolBarHelper::cancel( 'cancel', 'Close' );
		}

		$this->assignRef('player', $player);

		parent::display($tpl);
	}
}
