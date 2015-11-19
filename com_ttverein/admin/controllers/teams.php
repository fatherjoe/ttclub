	<?php
defined('_JEXEC') or die();

require( JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'lib'.'/'. 'upload'.'/'. 'image.php' );

/**
 * @author Sven Nissel
 */
class TeamsControllerTeams extends AbstractController
{
	var $redirect = "index.php?option=com_ttverein&controller=teams";

	function __construct()
	{
		parent::__construct();

		$this->registerTask( 'add', 'edit' );
		$this->registerTask( 'unpublish', 'publish');
	}

	function edit()
	{
		JRequest::setVar('view', 'team' );
		JRequest::setVar('layout', 'form'  );
		JRequest::setVar('hidemainmenu', 1);

		parent::display();
	}


	function save()
	{
		$model = $this->getModel('team');
		$post = JRequest::get( 'post' );

		/*
		 * Hochgeladenes Bild Wird gespeichert und verkleinert
		 */
		$image = &$_FILES['image'];
		if(is_uploaded_file($image['tmp_name'])) {
			$config = $model->getConfig();

			$name = $post['nummer'] . '.' . $model->getAltersklassenName($post['altersklasse']) .
					'_' . $post['saisonstart'] . '-' . (intval($post['saisonstart'])+1);

			if($post['hinrunde'] == '1')
				$name .= '_hinrunde';
			else
				$name .= '_rueckrunde';
			

			$imagemanager = new Image();

			$files = $imagemanager->saveImage($image, $name,
						$config['team_image_path'],
						$config['team_image_size'],
						$config['team_thumb_size']);
						
			if($files == null) {
				JError::raiseWarning( 552, JText::_( $imageManager->error ) );
				$this->setRedirect($this->redirect, "Mannschaft nicht gespeichert");
				return false;
			}

			if($files != null) {
				$post['image_orginal'] = $files['image_orginal'];
				$post['image_resize'] = $files['image_resize'];
				$post['image_thumb'] = $files['image_thumb'];
			}
		}

		if ($model->store($post)) {
			$msg = JText::_( 'Mannschaft gespeichert!' );
		} else {
			$msg = JText::_( 'Mannschaft konnte nicht gespeichert werden' );
		}

		$cache = JFactory::getCache('com_ttverein');
		$cache->clean();

		//$this->setRedirect($this->redirect, $msg);
		//JFactory::getApplication()->enqueueMessage($msg);
		$link = JRoute::_('index.php?option=com_ttverein&controller=teams', false);		
		$this->setRedirect( $link, $msg );		
	}

	function remove()
	{
		$model = $this->getModel('team');
		if(!$model->delete()) {
			$msg = JText::_( 'Fehler: Mannschaft(en) konnten nicht gelöscht werden' );
		} else {
			$msg = JText::_( 'Mannschaft(en) Gelöscht' );
		}
		
		$cache = JFactory::getCache('com_ttverein');
		$cache->clean();

		//JFactory::getApplication()->enqueueMessage($msg);
		$link = JRoute::_('index.php?option=com_ttverein&controller=teams', false);		
		$this->setRedirect( $link, $msg );		
	}

	function publish()
	{
		$this->setRedirect( $this->redirect );

		// Initialize variables
		$db			= JFactory::getDBO();
		$user		= JFactory::getUser();
		$cid		= JRequest::getVar( 'cid', array(), 'post', 'array' );
		$task		= JRequest::getCmd( 'task' );
		$publish	= ($task == 'publish');
		$n			= count( $cid );

		if (empty( $cid )) {
			return JError::raiseWarning( 500, JText::_( 'Keine Mannschaft ausgewählt' ) );
		}

		JArrayHelper::toInteger( $cid );
		$cids = implode( ',', $cid );

		$query = 'UPDATE #__ttverein_mannschaften'
		. ' SET published = ' . (int) $publish
		. ' WHERE id IN ( '. $cids .' )'
		;
		$db->setQuery( $query );
		if (!$db->query()) {
			return JError::raiseWarning( 500, $row->getError() );
		}
		$this->setMessage( JText::sprintf( $publish ? 'Items published' : 'Items unpublished', $n ) );
		
		$cache = JFactory::getCache('com_ttverein');
		$cache->clean();
	}

	function cancel()
	{
		$msg = JText::_( 'Aktion Abgebrochen' );
		//JFactory::getApplication()->enqueueMessage($msg);		
		$link = JRoute::_('index.php?option=com_ttverein&controller=teams', false);		
		$this->setRedirect( $link, $msg );		
	}
}
?>