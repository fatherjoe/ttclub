<?php
defined('_JEXEC') or die();



class LigenControllerLigen extends AbstractController
{
    private $linkToSelf;

	function __construct()
	{
		parent::__construct();

        $this->linkToSelf = JRoute::_('index.php?option=com_ttverein&controller=ligen', false);

		$this->registerTask( 'add'  , 	'edit' );
	}


	function edit()
	{
		JRequest::setVar( 'view', 'liga' );
		JRequest::setVar( 'layout', 'form'  );
		JRequest::setVar('hidemainmenu', 1);

		parent::display();
	}


	function save()
	{
		$model = $this->getModel('liga');
		$post = JRequest::get( 'post') ;

		if ($model->store($post)) {
			$msg = JText::_( 'Liga Gespeichert!' );
		} else {
			$msg = JText::_( 'Fehler beim Speichern der Liga' );
		}

		$cache = JFactory::getCache('com_ttverein');
		$cache->clean();

		$this->setRedirect($this->linkToSelf, $msg);
	}


	function remove()
	{
		$model = $this->getModel('liga');
		if(!$model->delete()) {
			$msg = JText::_( 'Fehler beim löschen einer Liga' );
		} else {
			$msg = JText::_( 'Ligen gelöscht' );
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
