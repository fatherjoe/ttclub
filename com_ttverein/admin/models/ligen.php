<?php

defined('_JEXEC') or die();
jimport( 'joomla.application.component.model' );


class LigenModelLigen extends JModelLegacy
{

	function getData()
	{
		$query = ' SELECT * ' .
				' FROM #__ttverein_ligen ' .
				' ORDER BY reihenfolge ASC';
		return $this->_getList( $query );
	}

}
