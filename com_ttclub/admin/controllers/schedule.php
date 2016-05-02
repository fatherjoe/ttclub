<?php
/**
 * @version     1.0.0
 * @package     com_ttclub
 * @copyright   Copyright (C) 2014. Alle Rechte vorbehalten.
 * @license     GNU General Public License Version 2 oder später; siehe LICENSE.txt
 * @author      Thomas Muster <tom.muster@dodgemail.de> - http://
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

/**
 * Schedule list controller class.
 */
class TtclubControllerSchedule extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function getModel($name = 'matchdate', $prefix = 'TtclubModel')
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));
		return $model;
	}
    
    
	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function saveOrderAjax()
	{
		// Get the input
		$input = JFactory::getApplication()->input;
		$pks = $input->post->get('cid', array(), 'array');
		$order = $input->post->get('order', array(), 'array');

		// Sanitize the input
		JArrayHelper::toInteger($pks);
		JArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		// Close the application
		JFactory::getApplication()->close();
	}
    
    
    
}