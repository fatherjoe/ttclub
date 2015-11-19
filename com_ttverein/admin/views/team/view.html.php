<?php
defined('_JEXEC') or die();
jimport( 'joomla.application.component.view' );

/**
 * @author Sven Nissel
 */
class TeamsViewTeam extends JViewLegacy
{
	function display($tpl = null)
	{
		$team = $this->get('Data');
		$isNew = ($team->id < 1);

		$text = $isNew ? JText::_( 'New' ) : JText::_( 'Edit' );
		JToolBarHelper::title(   JText::_( 'Mannschaft' ).': <small>[ ' . $text.' ]</small>' );
		JToolBarHelper::save();
		if ($isNew)  {
			JToolBarHelper::cancel();
		} else {
			JToolBarHelper::cancel( 'cancel', 'Close' );
		}

		$this->assignRef('team', $team);

		parent::display($tpl);
	}
}
