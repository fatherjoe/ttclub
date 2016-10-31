<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla view library
jimport('joomla.application.component.view');
 
/**
 * Config View
 */
class WebttViewStaffelnamens extends JViewLegacy
{
        /**
         * display method of Webtt view
         * @return void
         */
        function display($tpl = null) 
        {
                // Get data from the model
                $items = $this->get('Items');
                $pagination = $this->get('Pagination');
 
                // Check for errors.
                if (count($errors = $this->get('Errors'))) 
                {
                        JError::raiseError(500, implode('<br />', $errors));
                        return false;
                }
                // Assign data to the view
				$this->state = $this->get('State');
                $this->items = $items;
                $this->pagination = $pagination;

                // Set the toolbar
                $this->addToolBar();

                // Display the template
                parent::display($tpl);
        }
 
        /**
         * Setting the toolbar
         */
        protected function addToolBar() 
        {
				JToolBarHelper::title(JText::_('COM_WEBTT_MANAGER_STAFFELNAMENS'));
				JToolBarHelper::addNew('staffelnamen.add');
				JToolBarHelper::editList('staffelnamen.edit');
				JToolBarHelper::deleteList('', 'staffelnamens.delete');
                JToolBarHelper::cancel('staffelnamens.cancel');
        }
}
