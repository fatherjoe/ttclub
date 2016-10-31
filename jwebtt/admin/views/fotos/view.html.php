<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 

class WebttViewFotos extends JViewLegacy
{
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
                JToolBarHelper::title(JText::_('COM_WEBTT_MANAGER_FOTOS'));
                $input = JFactory::getApplication()->input;
                JToolBarHelper::save('fotos.save');
                JToolBarHelper::apply('fotos.apply');
                JToolBarHelper::cancel('fotos.cancel');
        }
}
