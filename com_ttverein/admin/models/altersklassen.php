<?php

defined('_JEXEC') or die();
jimport( 'joomla.application.component.model' );


class AltersklassenModelAltersklassen extends JModelLegacy
{

	function getData()
	{
		$query = ' SELECT * ' .
				' FROM #__ttverein_altersklassen ' .
				' ORDER BY reihenfolge ASC';
		return $this->_getList( $query );
	}

}
