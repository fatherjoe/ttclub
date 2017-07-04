<?php
defined('_JEXEC') or die();

class AltersklassenControllerAltersklassen extends AbstractController
{
    private $linkToSelf;

	function __construct()
	{
		parent::__construct();

        $this->linkToSelf = JRoute::_('index.php?option=com_ttverein&controller=altersklassen', false);

		$this->registerTask( 'add'  , 	'edit' );
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

		$this->setRedirect($this->linkToSelf, $msg);
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

		$this->setRedirect( $this->linkToSelf, $msg );
	}


	function cancel()
	{
		$msg = JText::_( 'Abgebrochen' );
		$this->setRedirect( $this->linkToSelf, $msg );
	}
}
?>
