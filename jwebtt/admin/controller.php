<?php
// No direct access to this file
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');
 
/**
 * General Controller of Webtt component
 */
class WebttController extends JControllerLegacy
{
        /**
         * display task
         *
         * @return void
         */
        function display($cachable = false, $urlparams = false) 
        {
                // set default view if not set
                $input = JFactory::getApplication()->input;
                $input->set('view', $input->getCmd('view', 'Webtt'));
 
                // call parent behavior
                parent::display($cachable);
        }
}
