<?php
require ('clicktt.php');
$verband = $_GET['verband'];
$vereinsnummer = $_GET['vereinsnummer'];
$clubname = $_GET['clubname'];
$clubid = $_GET['clubid'];
$championship = $_GET['championship'];
$group = $_GET['group'];
$teamtable = $_GET['teamtable'];
$pageState = $_GET['pageState'];

if($verband && $vereinsnummer && $clubname && $clubid) {
	$clicktt = new ClickTT($verband, $vereinsnummer, $clubname, $clubid);
	if($clicktt->clubID > 0) {
		echo $clicktt->getTeamSchedule( $teamtable, $championship, $group, $pageState);
	}
}
?>
