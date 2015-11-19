<?php defined('_JEXEC') or die('Restricted access');

function quickiconButton( $controller, $image, $text, $disabled=false ){
	global $mainframe;
	$lang		= JFactory::getLanguage();
	#$template	= $mainframe->getTemplate();
	#$template	= JApplication::getTemplate();
	$template = "hathor";

	if( $disabled ){
		$link = '#';
	}else
		$link = 'index.php?option=com_ttverein&controller=' . $controller;
	?>
	<div style="float:<?php echo ($lang->isRTL()) ? 'right' : 'left'; ?>;">
		<div class="icon">
			<a href="<?php echo $link; ?>">
				<?php echo JHtml::image('administrator/templates/' .$template . '/images/header/' . $image, $text, null , false, false ); ?>
				<span><?php echo $text; ?></span>
			</a>
		</div>
	</div>
    <?php
}
?>


<table class="admintable">
    <tr>
        <td width="370" valign="top" colspan="2">
		<div id="cpanel">
		<?php
		quickiconButton('config', 'icon-48-config.png', 'Konfiguration');
		quickiconButton('altersklassen', 'icon-48-calendar.png', 'Altersklassen');
		quickiconButton('ligen', 'icon-48-levels.png', 'Ligen');
		quickiconButton('teams', 'icon-48-groups.png', 'Mannschaften');
		quickiconButton('players', 'icon-48-user.png', 'Spieler');
		quickiconButton('help', 'icon-48-help_header.png', 'Hilfe');
		?>
		</div>
        <div class="clr"></div>
        <table class="admintable">
        	<!--  <tr>
                <td class="key">
                    <?php echo JText::_( 'Forum' );?>
                </td>
                <td>
                    
                    <a href="http://www.joomlaportal.de/joomla-erweiterungen-komponenten/152088-stelle-komponente-fuer-tischtennis-mannschaften-verwaltung-vor.html" target="_blank">www.joomlaportal.de</a>
                   
                </td>
            </tr> -->
            <tr>
                <td class="key">
                    <?php echo JText::_( 'Projektseite' );?>
                </td>
                <td>
                    <a href="http://debugmode.de/trac/ttverein" target="_blank">http://debugmode.de/trac/ttverein</a>
                </td>
            </tr>
            <tr>
                <td class="key">
                    <?php echo JText::_( 'Demo' );?>
                </td>
                <td>
                    <a href="http://www.aggertalerttc.de/mannschaften.html" target="_blank">www.aggertalerttc.de</a>
                </td>
            </tr>
        </table>
        </td>
        <td valign="top" rowspan="2">
			<b>Version 0.2.7</b>
			<ul>
				<li>[FEATURE] Bayrischer Tischtennis Verband hinzugefügt.</li>
				<li>[FEATURE] Mannschaftsübersicht kann nun nur mit einzelnen Altersklassen angezeigt werden.</li>
				<li>[FIX] Fehler bei der Umlaute konvertierung behoben.</li>
				<li>[FIX] Umlaute Probleme nach Umstellung von click-TT auf UTF-8 behoben.</li>
				<li>[FIX] Auf- und Abstiegspfeile der Tabellen werden wieder angezeigt</li>
			</ul>
			<b>Version 0.2.6</b>
			<ul>
				<li>[FIX] PHP wurde je nach php.ini Einstellung nicht interpretiert.</li>
				<li>[FIX] Bilanzen von Spielern die in Spielklassen mit Umlauten (Schüler/Mädchen) gemeldet sind, werden nun geladen</li> 
				<li>[FEATURE] Höhe des variablen Layout nun einstellbar.</li>
				<li>[FIX] Fehler beim Upload einer Datei werden nun angezeigt</li>
				<li>[FIX] Auswahl einer anderen Saison in der Mannschaftsübersicht hat das Layout geändert</li>
				<li>[FIX] Wenn jemand weniger als ein Jahr im Verein war wird das Fehld "im Verein seit" im Frontend nicht angezeigt</li> 
				<li>[FEATURE] Überprüfung der PHP Version und das vorhandensein der file_get_contents Funktion</li>
				<li>[CHANGE] Mannschaftsführer nun immer unter der Mannschaftsaufstellung</li>
				<li>[CHANGE] Upload Klasse von Colin Verot auf Version 0.27 aktualisiert</li>
				<li>[FIX] Tabelle aus alter Saison wurde angezeigt</li>
				<li>[FIX] Ändern der Spielereigenschaften nachträglich nicht möglich gewesen</li>
				<li>[FIX] kleine HTML Fehler in der Mannschafts- und Spieler- Ansicht behoben. Nun XHTML 1.0 valide - danke Jochen</li>
				<li>[FEATURE] php.ini für 1&1 kunden, da manchmal das laden von fremden URLS abgeschaltet ist.</li>
				<li>[FIX] TTVR Rheinland Url hat sich verändert</li>
				<li>[FEATURE] Email Felder werden mit JavaScript verschleiert - danke Jochen</li>
			</ul>
			<b>Version 0.2.5</b>
			<ul>	
				<li>[FIX] Möglichen SQL Fehler von Update 0.2.0 auf 0.2.1 behoben</li>
				<li>[FIX] Tabellen und Bilanzen werden nach kleiner Änderung von click-tt wieder geladen</li>
				<li>[FIX] Laden der Auf/Abstiebspfeile in der Tabelle vom eigenen Server.</li>
				<li>[FIX] Fehler bei Mannschaften Anzeige und MySQL 4.0 behoben.</li>
				<li>[FIX] Erstes hochladen eines Spieler oder Mannschaftsbild war nicht möglich.</li>
				<li>[FIX] Fehler bei der Spieler speicherung und MySQL 4.0 behoben.</li>
			</ul>
			<b>Version 0.2.4</b>
			<ul>	
				<li>[FIX] Bilanzen teilweise der gesamten Runde berechnet.</li>
				<li>[FIX] Viele Fehler im Zusammenhang mit Tabellen und Altersklasse in clicktt Klasse behoben</li>
				<li>[FIX] Fehler in der Verlinkung einzelner Mannschaften behoben</li>
				<li>[FIX] Kompatibilität zu MySQL 4.0 hergestellt. Einige SQL Anweisungen umgeschrieben. Installationsdatei angepasst.</li>
			</ul>
			<b>Version 0.2.3</b>
			<ul>
				<li>[CHANGE] Hilfe aktualisiert und Bilder werden nun von externen Server geladen um Paketgröße zu minimieren</li>
				<li>[FIX] Clicktt - Alterklassen mit z.B. U18 im Namen gefixed</li>
				<li>[FIX] Fehler mit dem Datumsfeld und PHP4</li>
			</ul>
			<b>Version 0.2.2</b>
			<ul>
				<li>[FIX] Nicht alle Spielerfelder wurden im Backend angezeigt</li>
				<li>[FIX] Ausnahmen in clicktt abgefangen. Mannschaften mit z.B: "(Z)" im Namen.</li>
			</ul>
			<b>Version 0.2.1</b>
			<ul>
				<li>[FIX] click-TT Bilanzen von Spielern, die erst seit der Rückrunde im Verein sind, werden nun geladen</li>
				<li>[CHANGE] Ausgabe des Spielerprofils mit fixer Tabelle</li>
				<li>[FIX] Mannschaftsführer feld bei Neuinstallation nicht erstellt worden</li>
			</ul>
			<b>Version 0.2.0</b>
			<ul>
				<li>[FEATURE] Die Bilder der Spieler und Mannschaften sind nun auf die Bilder in Orginalgröße verlinkt</li>
				<li>[FEATURE] Die Bilanzen und Tabellen werden per Ajax nachgeladen</li>
				<li>[FEATURE] Die Spieler-, Mannschafts- Bilder und die MySQL Tabellen der Komponente werden falls in der Konfiguration angebenen bei der Deeinstallation gelöscht</li>
				<li>[FIX] Die Defaultwerte werden nicht bei einem Update neu geschrieben</li>
				<li>[FEATURE] Ein Spieler kann als Mannschaftsführer markiert werden</li> 
				<li>[FIX] Bilder Upload auf einem Windows Server nun möglich</li>
				<li>[FEATURE] Spieler können frei einstellbare Profilfelder haben</li>
				<li>[FIX] clicktt Klasse nun schneller und nicht so fehleranfällig</li>
				<li>[CHANGE] Es muss nun um clickTT zu nutzen die Vereinsnummer angegeben werden</li> 
				<li>[CHANGE] Update der Uploadklasse von 0.25 auf 0.26</li> 
				<li>[FIX] Standart Sortierung der Spielerliste im Backend nach Nachname</li>
				<li>[FEATURE] Hife und Tooltips im Backend hinzugefügt</li>
				<li>[FEATURE] Einzelne Spieler oder Mannschaften können nun im Menü verlinkt werden</li>
				<li>[FIX] Mehrere Spieler konnten doppelt an einer Position sein</li>
				<li>[CHANGE] Alle Libaries sind nun in einem Ordner</li>
			</ul>
			<b>Version 0.1.6</b>
			<ul>
				<li>[FIX] Fehler bei der Anzeige von Spielern ohne Mannschaft behoben</li>
				<li>[FIX] Doppeltes laden der Bilanzen aus click-TT verhindert. Schnellere ladezeit der Spielerseite</li>
				<li>[FIX] Fehler beim Laden der Tabelle aus click-TT behoben (URL Problem)</li>
			</ul>
			<b>Version 0.1.5</b>
			<ul>
				<li>[FIX] Menüeinträge werden nach Update/Neuinstallation aktualisiert</li>
				<li>[FIX] Verlinkung der Mannschaft in der Tabelle nun in den richtigen Verband</li>
				<li>[CHANGE] Auf- und Abstiegspfeile der Tabelle werden aus Rechtlichen gründen nicht mehr von click-TT geladen</li> 
				<li>[FIX] Nicht anwesende Spieler werden nicht in die Bilanz mit eingerechnet</li>
				<li>[FIX] Die Views Mannschaften (DivBased), Mannschaft und Spieler sind nun XHTML 1.0 Transitional valide</li>
				<li>[FEATURE] Der &lt;title&gt; wird nun dynamisch erzeugt</li>
			</ul>
			<b>Version 0.1.4</b>
			<ul>
				<li>[CHANGE] Anzeige der click-TT Bilanzen wird nun geordnet angezeigt</li>
				<li>[FIX] ClickTT Klasse für andere Vereine und Verbände nutzbar. (Noch nicht genug getestet)</li>
				<li>[FIX] Fehler mit PHP4 im Mannschaften Formular</li>
				<li>[CHANGE] Kleine Designanpassungen für das Spieler und Mannschaftsformular im Backend</li>
				<li>[CHANGE] Im Spieler und Mannschafts Backendformular werden die Pfade zu den Bildern nicht mehr angezeigt.</li>
				<li>[CHANGE] Konfiguration nun wesentlich verständlicher.</li>
				<li>[FEATURE] click-TT Funktionalität abschaltbar.</li>
				<li>[FEATURE] Leistungsindex wird aus den Bilanzen der Spieler errechnet, der die Spieler vergleichbarer macht. Diese Funktion ist auch abschaltbar.</li>
				<li>[FIX] "Div-Based" Mannschaften Seite passt sich besser verschiedenen Joomla Themes an.</li>
			</ul>
			<b>Version 0.1.3</b>
			<ul>
				<li>[FIX] "Originalbild" konnte beim Upload gelöscht werden, wenn das hochgeladene Bild den gleichen Namen wie das "Originalbild" hatte.</li>
				<li>[CHANGE] Die Auswahl der Saison ist im Backend nun mit einem PullDown Menü gelöst</li>
				<li>[FIX] Bei Änderungen im Backend wird der Cache der Komponente gelöscht um die Änderung sofort anzeigen zu lassen.</li>
				<li>[FIX] Link vom Spieler zur Mannschaft konnte in manchen Fällen fehlerhaft sein.</li>
				<li>[FIX] Es werden nun nur noch Bilanzen in einer Saison geladen, in dem der Spieler schon gemeldet war.</li>
				<li>[FIX] Da sich in click-TT die Darstellung der Bilanzen geändert hat, werden sie nun detaillierter geladen und angezeigt.</li>
				<li>[FIX] Können keine Bilanzen aus click-tt geladen werden, wird Überschrift "Bilanzen aus click-TT" ausgeblendet</li>
				<li>[CHANGE] Kleine Verschönerung im Backend einer Mannschaft. Aufstellungen etwas flexibler.</li>
				<li>[FIX] Daten von Mannschaften die in click-tt nicht existieren werden nicht geladen.</li>
				<li>[FIX] Kleiner Fehler in Anzeige der aktuellen Saison in Mannschaften Übersicht behoben.</li>
				<li>[FIX] Bilder werden nun auch angezeigt wenn sich Joomla in einem Unterverzeichnis befindet - Danke Silvo.</li>
				<li>[FIX] Wenn keine Bilanzen eines Spielers vorhanden ist, wird nun früher abgebrochen.</li>
				<li>[FIX] Beim Update der Version 0.0.x auf Versionen höher 0.1.0 konnten Updates übersprungen werden.</li>
			</ul>
			<b>Version 0.1.2</b>
			<ul>
				<li>
					[CHANGE] "Mitglied seit" Feld im Spielerformular nun verständlicher. 
					Werte kleiner 1800 werden nun nicht mehr in die Datenbank gespeichert.
				</li>
				<li>[FIX] Warunungen auf der Mannschaften Seite behoben</li>
				<li>[FIX] Warunungen und Fehler in Altersklasse-, Liga-, Mannschafts- und Spieler Formular behoben</li>
				<li>[FIX] Fehler im Backend Menü - Danke Chraneco</li>
				<li>[FIX] Fehlende Dateien in der ttverein.xml - Danke Chraneco</li>
				<li>[FIX] Fehler in der Datenbankstruktur - Danke Silvo</li>
			</ul>
			<b>Version 0.1.1</b>
			<ul>
				<li>[FIX] Error Ausgabe im Team Model falsch. Fehlermeldungen hinzugefügt - Danke Silvo</li>
				<li>[FIX] Leere Felder im Mannschafts Formular werden all NULL in die Datenbank gespeichert</li>
				<li>[FEATURE] Die Ligen sind nun im Backend verwaltbar</li>
				<li>[FEATURE] Die Altersklassen sind nun im Backend verwaltbar</li>
				<li>[FIX] Wenn der Pfad zu den Bildern nicht mit / beginnt oder endet 
				wird er vorrangestellt oder angehangen - Danke Silvo</li>
				<li>[CHANGE] Joomla internes Caching wird nun für alle Ausgaben in Frondend genutzt</li>
				<li>[FIX] Tabellen von nicht gefundenen Mannschaften werden nicht geladen</li>
			</ul>
			<b>Version 0.1.0</b>
			<ul>
				<li>[FIX] Datum wurde unter PHP4 nicht richtig gespeichert
				<li>
					[FEATURE] Tabelle einer Mannschaft aus der aktuellen Saison 
					wird aus click-TT geladen.
				</li>
				<li>[CHANGE] Bildbeschreibung einer Mannschaft nun mehrzeilig</li>
				<li>[CHANGE] Anzeige der Mannschaftsliste übersichtlicher</li>
				<li>[FIX] Anzeige der Mannschaftsliste im Backend war vollkommen falsch</li>
				<li>[FIX] Falsche Alterklassen Bezeichnung geändert.</li>
				<li>[FIX] <IMG> Tag wird im Frondend/Backend nicht erzeugt wenn keine Bildurl vorhanden ist</li>
				<li>
					[FEATURE] Bilanzen eines Spielers werden versucht aus click-TT zu laden, 
					falls der ClubName in der Konfiguration eingetragen ist
				</li>
				<li>[FIX] einzelne Spielerdaten können gelöscht werden</li>
			</ul>
			<b>Version 0.0.9</b>
			<ul>
				<li>[CHANGE] Geburtsjahr in Geburtsdatum geändert. Es wir im Frondend nun das Alter angezeigt</li>
				<li>[FIX] alt Tags hinzugefügt und &lt;br&gt; Tags in &lt;br /&gt; umgewandelt danke "MrDamage"</li>
			</ul>
        </td>
    </tr>
</table>
