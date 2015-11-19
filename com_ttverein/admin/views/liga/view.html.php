<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport( 'joomla.application.component.view' );

class LigenViewLiga extends JViewLegacy
{
	function display($tpl = null)
	{
		$liga		= $this->get('Data');
		$isNew		= ($liga->id < 1);

		$text = $isNew ? JText::_( 'New' ) : JText::_( 'Edit' );
		JToolBarHelper::title(   JText::_( 'Liga' ).': <small>[ ' . $text.' ]</small>' );
		JToolBarHelper::save();
		if ($isNew)  {
			JToolBarHelper::cancel();
		} else {
			JToolBarHelper::cancel( 'cancel', 'Close' );
		}

		$this->assignRef('liga',		$liga);

		parent::display($tpl);
	}
}
