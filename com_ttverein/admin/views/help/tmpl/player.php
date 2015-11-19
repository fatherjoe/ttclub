<?php defined('_JEXEC') or die('Restricted access'); 
include(JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'views'.'/'. 'help'.'/'. 'header.php' );
$path = 'http://www.svennissel.de/ttverein/0.2/'; 
?>
<h2>Spieler verwalten</h2>
<img src="<?php echo $path;?>spielermanagerhilfe.jpg" alt="" /> 
<ol>
	<li>Wählen sie den Spielermanager aus.</li>
	<li>Wenn sie einen neuen Spieler erstellen möchten klicken sie oben rechts auf den Neu Button.</li>
	<li>Zum bearbeiten eines Spielers können sie direkt auf seinen Namen klicken.</li>
</ol>
<h2>Spieler Profil</h2>
<img src="<?php echo $path;?>spielerprofilhilfe.jpg" alt="" />
<ol>
	<li>Der Name des Spieler muss im Verein eindeutig sein</li>
	<li>Für den Spieler kann ein Bild hochgeladen werden. Dies wird automatisch nach den Vorgaben in der Konfiguration skaliert. Besetehende Bilder werden überschrieben.</li>
	<li>Falls man schon Mannschaften erstellt hat kann man den Spieler eine Mannschaft und Position zuordnen.</li>
	<li>Zum Schluß das Speichern nicht vergessen</li>
</ol>