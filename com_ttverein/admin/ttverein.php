<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

// Require the base controller
require_once (JPATH_COMPONENT.'/'.'controller.php');

$controller = JRequest::getVar('controller');

/*
 * Standart ist das controlpanel falls kein Controler angegeben wird. 
 * Hier werden auch alle Gültigen Controler für das Menü angegeben. 
 */
switch($controller)  {
	default:
		$controller = 'controlpanel';
	case 'teams':
	case 'players':
	case 'altersklassen':
	case 'ligen':
	case 'config':
	case 'controlpanel':
	case 'help':
	case 'felder':	
}

require_once (JPATH_COMPONENT.'/'.'controllers'.'/'. $controller . '.php');
// Create the controller
$classname	= $controller . 'Controller' . $controller;
$controller = new $classname( );

// Perform the Request task
$controller->execute(JFactory::getApplication()->input->getCmd('task'));
//$controller->execute( JRequest::getVar('task'));

// Redirect if set by the controller
$controller->redirect();

?>
