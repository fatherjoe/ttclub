<?php
require ('clicktt.php');
$verband = $_GET['verband'];
$vereinsnummer = $_GET['vereinsnummer'];
$clubname = $_GET['clubname'];
$clubid = $_GET['clubid'];

if($verband && $vereinsnummer && $clubname && $clubid) {
	$clicktt = new ClickTT($verband, $vereinsnummer, $clubname, $clubid);
	if($clicktt->clubID > 0) {
		echo '<br />';
		echo 'Die nächsten Spieltermine unserer Mannschaften.';
		echo '<br />';
		echo '<h3 class="contentHeading">Herren</h3>';
		echo '<br />';
		//echo 'Aktuell sind keine Spiele geplant.<br />';
		echo $clicktt->getNextMatches( $clubid, 'Suchen','2','','','0' );
		//echo $clicktt->getNextMatches( $clubid, 'Suchen','1','01.07.2015','10.10.2015','1' );
		echo '<br />';
		echo '<h3 class="contentHeading">Jugend / Schüler (Spielgemeinschaft mit EK Söllingen)</h3>';
		echo '<br />';
		//echo 'Aktuell sind keine Spiele geplant.<br />';
		echo $clicktt->getNextMatches( 30464, 'Suchen','2','','','0' );
		//echo $clicktt->getNextMatches( 30464, 'Suchen','1','01.07.2015','10.10.2015','1' );
	}
}
?>
