<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport( 'joomla.application.component.view' );

class AltersklassenViewAltersklasse extends JViewLegacy
{
	function display($tpl = null)
	{
		$altersklasse = $this->get('Data');
		$isNew        = ($altersklasse->id < 1);
		
		$text = $isNew ? JText::_( 'New' ) : JText::_( 'Edit' );
		JToolBarHelper::title(   JText::_( 'Altersklasse' ).': <small>[ ' . $text.' ]</small>' );
		JToolBarHelper::save();
		if ($isNew)  {
			JToolBarHelper::cancel();
		} else {
			JToolBarHelper::cancel( 'cancel', 'Close' );
		}

		$this->assignRef('altersklasse',		$altersklasse);

		parent::display($tpl);
	}
}
