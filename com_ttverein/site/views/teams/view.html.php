<?php
defined('_JEXEC') or die('Restricted access');
jimport( 'joomla.application.component.view');


class TeamsViewteams extends JViewLegacy
{
	function display($tpl = null)
	{
		
		
		$model	= $this->getModel();
		
		$olderSaisons = $model->getSaisons();
		if(!$olderSaisons || count($olderSaisons) <= 0) {
			return parent::display($tpl);
		}
		$this->assignRef('olderSaisons'  , $olderSaisons);
		
		$newestsaison = $model->getNewestSaison();
		$this->assignRef('newestsaison'  , $newestsaison);
		
		/*
		 * Stadart ist die Rückrunde bei der Anzeige
		 */
		if($newestsaison->min_hinrunde == 0)
			$showHin = 0;
		else
			$showHin = 1;
		
		/*
		 * Saisonjahr ist default das Aktuellste.
		 * Wenn 'saison' gesetzt ist wird dies überschrieben.
		 */
		$showSaison = $newestsaison;
		if( ($saison = JRequest::getCmd( 'saison' )) ) {
			/*
			 * Prüfen ob es dies Jahr gibt.
			 */
			foreach ($olderSaisons as $old) {
				if($old->saisonstart == $saison) {
					$showSaison = $old;
					if($old->min_hinrunde == 0)
						$showHin = 0;
					else
						$showHin = 1;
					break;
				}
			}
			
		}
		$this->assignRef('showSaison'  , $showSaison);
		
		/*
		 * Wenn Parameter 'runde' angebeben ist wird der 
		 * Standart überschrieben
		 */
		if(JRequest::getCmd( 'runde' ) == 'hin') {
			$showHin = 1;
		} 
		$this->assignRef('showHin'  , $showHin);
			
		
		$params = JComponentHelper::getParams( 'com_ttverein' );
		$altersklasse = $params->get( 'altersklasse' );
		if($altersklasse == "") {
			$altersklasse = null;
		} else {
			$altersklasse = explode("\n", $altersklasse);
		}
		
		$rows	= $model->getTeamList(
  							array('saisonstart'=>$showSaison->saisonstart, 
  								'hinrunde'=>$showHin,
								'altersklasse'=>$altersklasse
  							)
  							);
		$this->assignRef('rows'  , $rows);

		$config	= $model->getConfig();
		$this->assignRef('config'  , $config);

		parent::display($tpl);
	}
}
?>
