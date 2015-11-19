<?php
defined('_JEXEC') or die();
jimport( 'joomla.application.component.model' );

class FelderModelFelder extends JModelLegacy
{

	function getData()
	{		
		$query = ' SELECT id, name_backend, name_frontend, typ, zeige_in_uebersicht, tooltip ' .
				' FROM #__ttverein_felder ' .
				' ORDER BY reihenfolge, id ASC';
		$result = $this->_getList( $query );
		
		
		
		return $result;
	}
	
	
function getTypen() {
		$typen = array();
		
		$typ = new stdClass();
		$typ->typ = "text";
		$typen[] = $typ;		
		
		$typ = new stdClass();
		$typ->typ = "email";
		$typen[] = $typ;
		
		$typ = new stdClass();
		$typ->typ = "telefon";
		$typen[] = $typ;
		
		$typ = new stdClass();
		$typ->typ = "datum";
		$typen[] = $typ;
		
		$typ = new stdClass();
		$typ->typ = "jahre seit";
		$typen[] = $typ;
		
		return $typen;
	}
	
	function store($post=null)
	{
		foreach($post['alte_namen_backend'] as $id=>$name_backend) {
			$typ = $post['alte_typen'][$id];
			$name_frondend = $post['alte_namen_frondend'][$id];
			$zeige_in_uebersicht = $post['alte_zeige_in_uebersicht'][$id];
			$tooltip = $post['alte_tooltip'][$id];
			$query = "UPDATE #__ttverein_felder " .
					" SET name_backend='$name_backend', " .
						" name_frontend='$name_frondend', " .
						" zeige_in_uebersicht='$zeige_in_uebersicht', " .
						" tooltip='$tooltip', " .
						" typ='$typ' " .
					" WHERE id=$id ";
			$this->_db->setQuery($query);
			if(!$this->_db->query()) {
				JError::raiseError(801, $this->_db->getErrorMsg());
				return false;
			}
		}
		for($i=0; $i < count($post['neue_namen_backend']); $i++){
			$name_backend = $post['neue_namen_backend'][$i];
			$name_frondend = $post['neue_namen_frontend'][$i];
			$zeige_in_uebersicht = $post['neue_zeige_in_uebersicht'][$i];
			$tooltip = $post['neue_tooltip'][$i];
			$typ = $post['neue_typen'][$i];
			if(!$typ || !$name_backend)
				continue;			
			$query = "INSERT INTO #__ttverein_felder(" .
							" name_backend, name_frontend, " .
							" zeige_in_uebersicht, typ, tooltip" .
						" ) " .
						" VALUES(" .
							" '$name_backend', '$name_frondend', '$zeige_in_uebersicht', " .
							" '$typ', '$tooltip') ";
			$this->_db->setQuery($query);
			if(!$this->_db->query()) {
				JError::raiseError(802, $this->_db->getErrorMsg());
				return false;
			}
		}
		return true;
	}

}
