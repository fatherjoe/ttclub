<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 

// jimport('joomla.application.component.controllerform');


/**
 * Webtt Controller
 * 
 * Aktualisierung der Vereinsdaten von clicktt :
 *  Mannschaften Spieler Hallen Gegnerhallen
 */
class WebttControllerAktualisieren extends JControllerForm
{
		function get_teams()
		{
				$model = $this->getModel('aktualisieren');
				$test = $model->update_teams_from_clicktt();
		}
		
		function get_hallen()
		{
				$model = $this->getModel('aktualisieren');
				$update = $model->update_hallen_from_clicktt();
		}

		function get_hallen_ausw()
		{
				$model = $this->getModel('aktualisieren');
				$update = $model->update_hallen_auswaerts_from_clicktt();
		}

		function get_spieler()
		{
				$model = $this->getModel('aktualisieren');
				$update = $model->update_spieler_from_clicktt();
		}

		public function cancel() {
				// Check for request forgeries
				JRequest::checkToken() or die('Invalid Token');
				$this->setRedirect(JRoute::_('index.php?option=com_webtt', false));
		}
}
