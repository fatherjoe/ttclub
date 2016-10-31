<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Qttr View
 */
class WebttViewQttr extends JViewLegacy
{
	/**
	 * View form
	 *
	 * @var         form
	 */
	protected $form = null;
 
	/**
	 * Display the Hello World view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		// Get the Data
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
 
		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode('<br />', $errors));
 
			return false;
		}
 
 
		// Set the toolbar
		$this->addToolBar();
 
		// Display the template
		parent::display($tpl);
	}
 
	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function addToolBar()
	{
		$input = JFactory::getApplication()->input;
 
		// Hide Joomla Administrator Main menu
		$input->set('hidemainmenu', true);
 
		$isNew = ($this->item->id == 0);
 
		if ($isNew)
		{
				JToolBarHelper::title(JText::_('COM_WEBTT_MANAGER_QTTR_NEW'));
		}
		else
		{
				JToolBarHelper::title(JText::_('COM_WEBTT_MANAGER_QTTR_EDIT'));
		}

		JToolBarHelper::save('qttr.save');
		JToolBarHelper::apply('qttr.apply');
		JToolBarHelper::cancel(
			'qttr.cancel',
			$isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE'
		);
	}
        
}
