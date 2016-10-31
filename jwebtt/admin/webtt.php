<?php
// No direct access to this file
defined('_JEXEC') or die;
 
// Get an instance of the controller prefixed by Webtt
$controller = JControllerLegacy::getInstance('Webtt');
 
// Perform the Request task and Execute request task
$controller->execute(JFactory::getApplication()->input->getCmd('task'));
 
// Redirect if set by the controller
$controller->redirect();
