<?php
defined('_JEXEC') or die('Restricted access');

function getHead($showHin, $showSaison, $olderSaisons) {
	$document=JFactory::getDocument();
	$app=JFactory::getApplication();
	
	$layout = "";
	if(isset($_REQUEST['layout']))
		$layout = "&layout=" . $_REQUEST['layout'];
	$teamsUrl = 'index.php?option=com_ttverein&view=teams' . $layout;
	$teamsUrlHin = JRoute::_($teamsUrl . '&runde=hin&saison=' . $showSaison->saisonstart);
	$teamsUrlRueck = JRoute::_($teamsUrl . '&runde=rueck&saison=' . $showSaison->saisonstart);
	
	$rundeLink = "";
	if(!$showHin) {
		$title = 'R&uuml;ckrunde';
		if($showSaison->max_hinrunde)
			$rundeLink = '<a href="' . $teamsUrlHin . '">Zur Hinrunde</a>';
	} else {
		$title = 'Hinrunde';
		if($showSaison->min_hinrunde == 0)
			$rundeLink = '<a href="' . $teamsUrlRueck . '">Zur R&uuml;ckrunde</a>';
	}
	$saisonTitle = "";
	if ($showSaison->saisonstart)
		$saisonTitle = $showSaison->saisonstart . '/' . substr(($showSaison->saisonstart+1),2) . " ($title)";

	$head = "<h1>Mannschaften $saisonTitle</h1>\n" . $rundeLink . "<br />\n";
	
	/*
	 * setzen des <title> Tag.
	 * Da die Methode setPageTitle() einen nicht URL codierten String benötigt, 
	 * wird dieser mit html_entity_decode() umgewandelt. Da es zu Fehlern bei PHP4 kommen kann
	 * wenn man die Kodierung UTF-8 angibt, muss der String noch in UTF-8 umgewandelt werden.
	 */
	$document->setTitle("Mannschaften " . html_entity_decode($saisonTitle) . ' - ' .$app->getCfg('sitename'));
	$document->setDescription("Mannschaften des TTC Wöschbach in der Saison " . html_entity_decode($saisonTitle) . ".");
	
	for($i=0; $i < count($olderSaisons); $i++) {
		$saisonstart = $olderSaisons[$i]->saisonstart;
		if($i > 0)
				$head .= ', ';
		if($showSaison->saisonstart == $saisonstart)
			$head .= '<b>Saison ' . $saisonstart . '/' . substr(($saisonstart+1),2) . "</b>";
		else {
			$url = JRoute::_($teamsUrl . '&saison=' . $saisonstart);
			$head .= '<a href="' . $url . '">Saison ' . $saisonstart . '/' . substr(($saisonstart+1),2) . '</a>';
		}
	}
	
	return $head;
	
}
?>
