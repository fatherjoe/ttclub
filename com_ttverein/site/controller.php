<?php

defined('_JEXEC') or die();
jimport('joomla.application.component.controller');


class TeamsController extends JControllerLegacy
{
	function display($cachable = false, $urlparams = false)
	{
		// Setzt einen Standard view
		if ( ! JRequest::getCmd( 'view' ) ) {
			JRequest::setVar('view', 'teams' );
		}
		
		parent::display($cachable, $urlparams);
	}
}
