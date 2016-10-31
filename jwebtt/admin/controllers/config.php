<?php
// No direct access to this file
defined('_JEXEC') or die;

// import Joomla controllerform library
jimport('joomla.application.component.controllerform');

 
/**
 * General Controller of Webtt component
 */
class WebttControllerConfig extends JControllerForm
{
		public function cancel() {
				// Check for request forgeries
				JRequest::checkToken() or die('Invalid Token');
				$this->setRedirect(JRoute::_('index.php?option=com_webtt', false));
		}

		public function apply() {
				// Check for request forgeries
				JRequest::checkToken() or die('Invalid Token');
				
				// 
				$data = JRequest::getVar('jform', array(), 'post', 'array');
print_r($data);

				JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_webtt'.DS.'tables');
				$row =& JTable::getInstance('webtt', 'WebttTable');

				foreach($data as $id => $val) {
						$row->save( array('id' => $id, 'val' => $val) );
echo $id;
echo $val;
				}
exit;
print_r($data);
print_r(JRequest::get( 'post' ));
	// Initialize variables.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

// $tbl = & $this->getTable('webtt', 'WebttTable', array());
 
//    $return = $model->save($data);


// Create and populate an object.
$profile = new stdClass();
foreach ($data as $row) {
	foreach ($row as $k) { 
echo $k;
}
$profile->id = $z++;
$profile->val = $v;
 
// Insert the object into the user profile table.
$result = JFactory::getDbo()->insertObject('#__webtt', $profile);
}

 exit;
				$this->setRedirect(JRoute::_('index.php?option=com_webtt&view=config', false));
		}

		public function save() {
				// Check for request forgeries
				JRequest::checkToken() or die('Invalid Token');
				$this->setRedirect(JRoute::_('index.php?option=com_webtt', false));
		}
}
