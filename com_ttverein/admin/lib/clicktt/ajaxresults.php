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
		echo 'Die Ergebnisse der letzten Spiele unserer Mannschaften:';
		echo '<br />';
		echo '<h3 class="contentHeading">Herren</h3>';
		echo '<br />';
		echo $clicktt->getLatestResults( $clubid, 'Suchen','2','','','0' );
		//echo $clicktt->getLatestResults( $clubid, 'Suchen','2','09.05.2015','30.04.2016','0' );		
		echo '<br />';
		echo '<h3 class="contentHeading">Jugend / Schüler (Spielgemeinschaft mit EK Söllingen)</h3>';
		echo '<br />';
		echo $clicktt->getLatestResults( 30464, 'Suchen','2','','','0' );
		//echo $clicktt->getLatestResults( 30464, 'Suchen','2','15.03.2013','30.04.2013','0' );		
	}
}

?>
