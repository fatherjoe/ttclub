<?php defined('_JEXEC') or die('Restricted access'); 
include(JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'views'.'/'. 'help'.'/'. 'header.php' );
$path = 'http://www.svennissel.de/ttverein/0.2/'; 
?>
<h2>Mannschaften verwalten</h2>
Für jede Saison und jede Hin- und Rückrunde muss eine Mannschaft neu erstellt werden. <BR>
<img src="<?php echo $path; ?>mannschaftenhilfe.jpg" alt="" />
<ol>
	<li>Wählen sie den Mannschaftenrmanager aus.</li>
	<li>Wenn sie eine neue Mannschaft erstellen möchten klicken sie oben rechts auf den Neu Button.</li>
	<li>Zum bearbeiten einer Mannschaft können sie direkt auf ihren Namen klicken.</li>
</ol>
<h2>Mannschaft erstellen/bearbeiten</h2>
<img src="<?php echo $path;?>mannschafthilfe.jpg" alt="" />
<ol>
	<li>Möchte man zum Beispiel die 3. Mannschaft erstellen so muss man hier 3 eintragen.</li>
	<li>Die Felder Altersklasse, Saison und Runde sind Pflichtfelder.</li>
	<li>Für die Mannschaft kann ein Bild hochgeladen werden. Dies wird automatisch nach den Vorgaben in der Konfiguration skaliert. Besetehende Bilder werden überschrieben.</li>
	<li>Falls man schon Spieler erstellt hat kann man die Mannschaftsaufstellung hier eingeben. </li>
	<li>Zum Schluß das Speichern nicht vergessen</li>
</ol>