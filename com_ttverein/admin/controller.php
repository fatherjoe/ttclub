<?php

jimport('joomla.application.component.controller');

/**
 * @author Sven Nissel
 */
class AbstractController extends JControllerLegacy
{
	function display($cachable = false, $urlparams = false)
	{
		parent::display($cachable, $urlparams);
	}
}
?>
