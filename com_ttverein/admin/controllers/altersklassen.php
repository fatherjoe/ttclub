<?php
defined('_JEXEC') or die();



class AltersklassenControllerAltersklassen extends AbstractController
{
	var $redirect = "index.php?option=com_ttverein&controller=altersklassen";

	function __construct()
	{
		parent::__construct();

		$this->registerTask( 'add'  , 	'edit' );
		//$this->registerTask( 'unpublish', 	'publish');
	}


	function edit()
	{
		JRequest::setVar( 'view', 'altersklasse' );
		JRequest::setVar( 'layout', 'form'  );
		JRequest::setVar('hidemainmenu', 1);

		parent::display();
	}


	function save()
	{
		$model = $this->getModel('altersklasse');
		$post = JRequest::get( 'post') ;

		if ($model->store($post)) {
			$msg = JText::_( 'Alterklasse Gespeichert!' );
		} else {
			$msg = JText::_( 'Fehler beim Speichern der Altersklasse' );
		}

		$cache = JFactory::getCache('com_ttverein');
		$cache->clean();

		$this->setRedirect($this->redirect, $msg);
	}




	function remove()
	{
		$model = $this->getModel('altersklasse');
		if(!$model->delete()) {
			$msg = JText::_( 'Fehler beim löschen einer Altersklasse ' );
		} else {
			$msg = JText::_( 'Altersklasse gelöscht' );
		}
		
		$cache = JFactory::getCache('com_ttverein');
		$cache->clean();

		$this->setRedirect( $this->redirect, $msg );
	}


	function cancel()
	{
		$msg = JText::_( 'Abgebrochen' );
		$this->setRedirect( $this->redirect, $msg );
	}
}
?>
