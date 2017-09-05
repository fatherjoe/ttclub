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
		echo $clicktt->getNextMatches( $clubid, 'Suchen','3','','','0' );
		//echo $clicktt->getNextMatches( $clubid, 'Suchen','1','01.08.2017','10.10.2017','1' );
		echo '<br />';
		echo '<h3 class="contentHeading">Jugend / Schüler (Spielgemeinschaft mit EK Söllingen)</h3>';
		echo '<br />';
		echo $clicktt->getNextMatches( 30464, 'Suchen','3','','','0' );
		//echo $clicktt->getNextMatches( 30464, 'Suchen','1','01.08.2017','10.10.2017','1' );
	}
}
?>
