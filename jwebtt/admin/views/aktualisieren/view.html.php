<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Aktualisieren View
 */
class WebttViewAktualisieren extends JViewLegacy
{
        /**
         * Aktualisieren view display method
         * @return void
         */
        function display($tpl = null) 
        { 
                // Check for errors.
                if (count($errors = $this->get('Errors'))) 
                {
                        JError::raiseError(500, implode('<br />', $errors));
                        return false;
                }

				// Display Title
				JToolBarHelper::title(JText::_('COM_WEBTT_MANAGER_AKTUALISIEREN'));

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
				JToolBarHelper::title(JText::_('COM_WEBTT_MANAGER_AKTUALISIEREN'));
                JToolBarHelper::cancel('aktualisieren.cancel');
        }
        
}
