<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla view library
jimport('joomla.application.component.view');
 
/**
 * Config View
 */
class WebttViewMannschaftsnamens extends JViewLegacy
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
				JToolBarHelper::title(JText::_('COM_WEBTT_MANAGER_REPL_CLICKTT_TEAMNAMES'));
				JToolBarHelper::addNew('mannschaftsnamen.add');
				JToolBarHelper::editList('mannschaftsnamen.edit');
				JToolBarHelper::deleteList('', 'mannschaftsnamens.delete');
                JToolBarHelper::cancel('mannschaftsnamens.cancel');
        }
}
