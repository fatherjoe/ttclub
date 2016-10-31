<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla controllerform library
jimport('joomla.application.component.controllerform');
 
/**
 * Webtt Controller
 * 
 * Bearbeiten / Aktualisieren der QTTR-Werte von clicktt
 * 
 */
class WebttControllerQttrs extends JControllerForm
{
		/**
		 * Proxy for getModel.
		 *
		 * @param   string  $name    The model name. Optional.
		 * @param   string  $prefix  The class prefix. Optional.
		 * @param   array   $config  Configuration array for model. Optional.
		 *
		 * @return  object  The model.
		 *
		 * @since   1.6
		 */
		public function getModel($name = 'Qttr', $prefix = 'WebttModel', $config = array('ignore_request' => true))
		{
			$model = parent::getModel($name, $prefix, $config);
	 
			return $model;
		}


		public function cancel() {
				// Check for request forgeries
				JRequest::checkToken() or die('Invalid Token');
				$this->setRedirect(JRoute::_('index.php?option=com_webtt', false));
		}
}
