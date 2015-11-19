<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
jimport('joomla.application.component.model');
require( JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'lib'.'/'. 'config'.'/'. 'config.php' );

class TeamsModelTeams extends JModelLegacy
{
	function getTeamList( $options=array() )
	{
		
		$select = 'mannschaften.id, mannschaften.nummer, mannschaften.saisonstart, ' .
				' mannschaften.hinrunde, ' .
				' mannschaften.image_thumb, #__ttverein_ligen.name AS liga, ' .
				' altersklassen.name AS altersklasse';
		$from	= 	'#__ttverein_altersklassen AS altersklassen, ' . 
					'#__ttverein_mannschaften AS mannschaften ';

		
		$wheres[] = 'mannschaften.published = 1';
		$wheres[] = 'altersklassen.id = mannschaften.altersklasse';
		if(array_key_exists('saisonstart',$options))
			$wheres[] = 'mannschaften.saisonstart = ' . intval($options['saisonstart']);
		if( array_key_exists('hinrunde',$options) ) {
			$wheres[] = 'mannschaften.hinrunde = ' . intval($options['hinrunde']);
		}
		if(array_key_exists('altersklasse',$options) && $options['altersklasse'] != null) {
			$wheresOR = array();
			for($i=0; $i < count($options['altersklasse']); $i++) {
				$wheresOR[] = "altersklassen.name = '" . $options['altersklasse'][$i] . "'";
			}
			$wheres[] = "(" . implode( "\n  OR ", $wheresOR ) . ")";
			
		} 
		
		$orderby = 'altersklassen.reihenfolge ASC, mannschaften.nummer ASC, ' .
					' mannschaften.hinrunde DESC';

		$query = "SELECT " . $select .
				"\n FROM " . $from .
				"\n LEFT JOIN #__ttverein_ligen ON (#__ttverein_ligen.id=mannschaften.liga)" .
				"\n WHERE " . implode( "\n  AND ", $wheres ) .
				"\n ORDER BY " . $orderby;

		return $this->_getList( $query );
	}

	public static function getConfig() {
		
		return Config::getConfig(array('team_thumb_size', 'div_team_height'));		
	}
	
	function getLigaName($id) {
		$maxSaison = $this->getMaxSaison();
		$query = "SELECT name " .
					" FROM #__ttverein_ligen " .
					" WHERE saisonstart = $maxSaison " .
					" GROUP BY(saisonstart) ";
		$this->_db->setQuery($query);
		return $this->_db->loadObject();
	}
	
	function getMaxSaison() {
		$query = "SELECT max(saisonstart) AS maximal" .
				" FROM #__ttverein_mannschaften";
		$this->_db->setQuery($query);
		$saison = $this->_db->loadObject();
		if($saison != null)
			return $saison->maximal;
		else
			return 0;
	}
	
	function getNewestSaison() {
		$maxSaison = $this->getMaxSaison();
		$query = "SELECT saisonstart, " .
						" min(hinrunde) AS min_hinrunde, " .
						" max(hinrunde) AS max_hinrunde " .
					" FROM #__ttverein_mannschaften " .
					" WHERE saisonstart = $maxSaison " .
					" GROUP BY(saisonstart) ";
		$this->_db->setQuery($query);
		return $this->_db->loadObject();
	}
	
	function getSaisons() {
		$query = "SELECT saisonstart, " .
						" min(hinrunde) AS min_hinrunde, " .
						" max(hinrunde) AS max_hinrunde " .
					" FROM #__ttverein_mannschaften " .
					" GROUP BY (saisonstart) " .
					" ORDER BY saisonstart DESC ";
		$this->_db->setQuery($query);
		return $this->_db->loadObjectList();
	}
}
?>
