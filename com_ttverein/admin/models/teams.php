<?php

defined('_JEXEC') or die();

jimport( 'joomla.application.component.model' );


class TeamsModelTeams extends JModelLegacy
{

	function _buildMannschaftenQuery()
	{
		$query = ' SELECT mannschaften.id, mannschaften.nummer, mannschaften.saisonstart, ' .
					' mannschaften.hinrunde, ' .
					' mannschaften.image_text, mannschaften.image_orginal, ' .
					' mannschaften.image_resize, mannschaften.image_thumb,' .
					' mannschaften.published, mannschaften.liga, ' .
					' altersklassen.name AS altersklasse '
			. 	' FROM #__ttverein_mannschaften AS mannschaften, ' .
					'#__ttverein_altersklassen AS altersklassen ' .
				' WHERE altersklassen.id = mannschaften.altersklasse ' .
				' ORDER BY mannschaften.saisonstart DESC, altersklassen.reihenfolge ASC, ' .
					'mannschaften.hinrunde ASC, mannschaften.nummer ASC ';

		return $query;
	}


	function getData()
	{
		return $this->_getList( $this->_buildMannschaftenQuery() );;
	}

}
