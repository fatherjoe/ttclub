<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla view library
jimport('joomla.application.component.view');
 
/**
 * Config View
 */
class WebttViewMannschaftens extends JViewLegacy
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
				$form = $this->get('Form');
 
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
                $this->form = $form;

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
                $input = JFactory::getApplication()->input;

				JToolBarHelper::title(JText::_('COM_WEBTT_MANAGER_MANNSCHAFTENS'));
				JToolBarHelper::addNew('mannschaften.add');
				JToolBarHelper::editList('mannschaften.edit');
				JToolBarHelper::deleteList('', 'mannschaftens.delete');
                JToolBarHelper::cancel('mannschaftens.cancel');
        }
}
