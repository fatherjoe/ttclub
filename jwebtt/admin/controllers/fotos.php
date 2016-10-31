<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.file');

/**
 * Fotos Controller
 *
 * @since  0.0.1
 */
class WebttControllerFotos extends JControllerAdmin
{
		public function save()
		{
				$filenames = JRequest::getVar('filedata', array(), 'post', 'array');
				$beschreibungen = JRequest::getVar('beschreibungen', array(), 'post', 'array');
				
				$model= $this->getModel('fotos');

				$save = $model->saveImagepaths($filenames);
				$save2 = $model->saveBeschreibung($beschreibungen);
				
				$this->setRedirect(JRoute::_('index.php?option=com_webtt', false));
		}

		public function apply()
		{
				$filenames = JRequest::getVar('filedata', array(), 'post', 'array');
				$beschreibungen = JRequest::getVar('beschreibungen', array(), 'post', 'array');
				
				$model= $this->getModel('fotos');

				$save = $model->saveImagepaths($filenames);
				$save2 = $model->saveBeschreibung($beschreibungen);
				
				$this->setRedirect(JRoute::_('index.php?option=com_webtt&view=fotos', false));
		}

		public function cancel()
		{
				// Check for request forgeries
				JRequest::checkToken() or die('Invalid Token');
				$this->setRedirect(JRoute::_('index.php?option=com_webtt', false));
		}
}
