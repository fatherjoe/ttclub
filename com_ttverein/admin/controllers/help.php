<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class HelpControllerHelp extends AbstractController
{
	function __construct()
	{
		parent::__construct();
	}

	function display( $cachable = false, $urlparams = false )
	{
		/*
		 * Defaultlayout setzen
		 */
		$layout = JRequest::getVar('layout');
		if(!$layout)
			JRequest::setVar('layout', 'update');

		parent::display($cachable, $urlparams);
	}
}
?>
