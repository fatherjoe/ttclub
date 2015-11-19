<?php

defined('_JEXEC') or die();
jimport( 'joomla.application.component.model' );


class PlayersModelPlayers extends JModelLegacy
{

	function getData()
	{
		$query = ' SELECT * '
			. ' FROM #__ttverein_spieler ORDER BY nachname,vorname ASC' ;
		return $this->_getList( $query );
	}

}
