<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class ConfigControllerConfig extends AbstractController
{
	var $redirect = "index.php?option=com_ttverein&controller=config";
	
	function __construct()
	{
		parent::__construct();
		
		$this->registerTask( 'apply', 'save');
	}

	function display( $cachable = false, $urlparams = false)
	{
		parent::display($cachable, $urlparams);
	}

	function cancel( )
	{
		$msg = JText::_( 'Konfiguration Abgebrochen' );
		$link = JRoute::_('index.php?option=com_ttverein&controller=config',false);		
		$this->setRedirect( $link, $msg );
	}

	function save()
	{
		$model = $this->getModel('config');

		if ($model->store(JRequest::get( 'post' ))) {
			$msg = JText::_( 'Konfiguration gespeichert!' );
		} else {
			$msg = JText::_( 'Fehler beim speichern der Konfiguration' );
		}

		$cache = JFactory::getCache('com_ttverein');
		$cache->clean();

		$link = JRoute::_('index.php?option=com_ttverein&controller=config',false);		
		$this->setRedirect( $link, $msg );
	}
}
?>
