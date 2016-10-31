<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.file');

/**
 * Fotos Controller
 *
 * @since  0.0.1
 */
class WebttControllerSpielers extends JControllerAdmin
{
		public function save()
		{
				$filenames = JRequest::getVar('filedata', array(), 'post', 'array');
				
				$model= $this->getModel('spielers');

				$save = $model->saveImagepaths($filenames);
				
				$this->setRedirect(JRoute::_('index.php?option=com_webtt', false));
		}

		public function apply()
		{
				$filenames = JRequest::getVar('filedata', array(), 'post', 'array');
				
				$model= $this->getModel('spielers');

				$save = $model->saveImagepaths($filenames);
				
				$this->setRedirect(JRoute::_('index.php?option=com_webtt&view=spielers', false));
		}

		public function cancel()
		{
				// Check for request forgeries
				JRequest::checkToken() or die('Invalid Token');
				$this->setRedirect(JRoute::_('index.php?option=com_webtt', false));
		}
}
