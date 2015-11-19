<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require( JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'lib'.'/'. 'upload'.'/'. 'image.php' );

class PlayersControllerPlayers extends AbstractController
{
	var $redirect = "index.php?option=com_ttverein&controller=players";
	function __construct()
	{
		parent::__construct();

		// Register Extra tasks
		$this->registerTask( 'add', 'edit' );
		$this->registerTask( 'unpublish', 'publish');
	}

	function edit()
	{
		JRequest::setVar( 'view', 'player' );
		JRequest::setVar( 'layout', 'form'  );
		JRequest::setVar( 'hidemainmenu', 1);

		parent::display();
	}

	function save()
	{
		$model = $this->getModel('player');
		$post = JRequest::get( 'post') ;

		$image = &$_FILES['image'];
		if(is_uploaded_file($image['tmp_name'])) {
			$config = $model->getConfig();
			$name = $post['vorname'] . '_' . $post['nachname'];
			$imageManager = new Image();
			$files = $imageManager->saveImage($image, $name,
						$config['player_image_path'],
						$config['player_image_size'],
						$config['player_thumb_size']);
			if($files == null) {
				JError::raiseWarning( 551, JText::_( $imageManager->error ) );
				$this->setRedirect($this->redirect, "Spieler nicht gespeichert");
				return false;
			}

			if($files != null) {
				$post['image_orginal'] = $files['image_orginal'];
				$post['image_resize'] = $files['image_resize'];
				$post['image_thumb'] = $files['image_thumb'];
			}
		}

		if ($model->store($post)) {
			$msg = JText::_( 'Spieler gespeichert!' );
		} else {
			$msg = JText::_( 'Spieler konnte nicht gespeichert werden' );
		}

		$cache = JFactory::getCache('com_ttverein');
		$cache->clean();

		$link = JRoute::_('index.php?option=com_ttverein&controller=players', false);		
		$this->setRedirect( $link, $msg );
	}

	function remove()
	{
		$model = $this->getModel('player');
		if(!$model->delete()) {
			$msg = JText::_( 'Spieler konnte nicht gelöscht werden' );
		} else {
			$msg = JText::_( 'Spieler Gelöscht' );
		}
		
		$cache = JFactory::getCache('com_ttverein');
		$cache->clean();

		$link = JRoute::_('index.php?option=com_ttverein&controller=players', false);		
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
			return JError::raiseWarning( 500, JText::_( 'Kein Spieler ausgewählt' ) );
		}

		JArrayHelper::toInteger( $cid );
		$cids = implode( ',', $cid );

		$query = 'UPDATE #__ttverein_spieler'
		. ' SET published = ' . (int) $publish
		. ' WHERE id IN ( '. $cids .' )'
		;

		$db->setQuery( $query );
		if (!$db->query()) {
			return JError::raiseWarning( 500, $row->getError() );
		}
		$this->setMessage( JText::sprintf( $publish ? 'Spieler veröffentlicht' : 'Spieler nun unveröffentlicht', $n ) );
		
		$cache = JFactory::getCache('com_ttverein');
		$cache->clean();
		$link = JRoute::_('index.php?option=com_ttverein&controller=players', false);		
		$this->setRedirect( $link, $msg );		
	}

	function cancel()
	{
		$msg = JText::_( 'Bearbeiten abgebrochen' );
		$link = JRoute::_('index.php?option=com_ttverein&controller=players', false);		
		$this->setRedirect( $link, $msg );
	}
}
?>
