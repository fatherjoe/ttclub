<?php defined('_JEXEC') or die('Restricted access'); 
include(JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'views'.'/'. 'help'.'/'. 'header.php' );
$path = 'http://www.svennissel.de/ttverein/0.2/'; 
?>
<h2>Einrichtung von click-TT</h2>
Für die Aktivierung der click-TT Funktionalität muss die Vereinsnummer und der Vereinsname bekannt sein.<BR><BR>
<img src="<?php echo $path;?>clicktthilfe.jpg" alt="" />
<ol>
	<li>Dies ist die sechstellige Vereinsnummer</li>
	<li>Als Vereinsname muss der in den Tabellen benutze Kurzname verwendet werden.</li>
</ol>
Diese Daten müssen müssen in die <a href="index.php?option=com_ttverein&controller=config" >Konfiguration</a> eingetragen werden.
<br />
<h2>Altersklassen</h2>
Die Alterklassen in ClickTT m&uuml;ssen mit denen im Backend eingestellten Altersklassen &uuml;bereinstimmen. 
Gehen Sie dazu bei xxx.click-tt.de auf die "Mannschaften und Ligeneinteilung" des Vereins. 
<br />
<img src="<?php echo $path;?>clickttmannschaften.png" alt="" />
<br />
In diesem Beispiel hat der Verein die Altersklassen:
<ul>
	<li>M&auml;dchen U18</li>
	<li>Herren</li>
	<li>Jungen U18</li>
	<li>Jungen U15</li>
	<li>Nachwuchsklasse U12</li>
</ul>
Diese Alterklassen müssen im Backend erstellt werden. 
Dazu k&ouml;nnen neue Alterklassen erstellt oder vorhandene umbenannt werden.

