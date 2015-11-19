<?php
defined('_JEXEC') or die('Restricted access');

$unknown = "<strong>Unbekannte Version.</strong>";
$VERSION_MAIN = 0;
$VERSION_SUB = 2;
$VERSION_PATCH = 8;
$VERSION_BUILD = 5;
//Erzeugt Update auf Verion $VERSION_MAIN.$VERSION_SUB.$VERSION_PATCH
$update = new Update($VERSION_MAIN,$VERSION_SUB,$VERSION_PATCH, $VERSION_BUILD);


/*
 * Aktuallisieren der Datenbankstruktur
 */
if($update->isUpdateRequired()) {
	$version = $update->getOldVersion();
	switch($version['main']) {
		default:
			echo $unknown;
			break;
		case 0: //0.x.x
			switch($version['sub']) {
				default:
					echo $unknown;
					break;
				case 0: //0.0.x
					switch($version['patch']) {
						default:
							echo $unknown;
							break;
							
						case 5: //Version 0.0.5 -> 0.0.6
							$update->query('ALTER TABLE #__ttverein_mannschaften ' .
											'ADD image_text TEXT NULL');
							$update->query('ALTER TABLE #__ttverein_mannschaften ' .
											'ADD liga VARCHAR( 255 ) NULL');

						case 6: //Version 0.0.6 -> 0.0.7
							$update->query('ALTER TABLE #__ttverein_mannschaften ' .
											'DROP rueckrunde');
						
						case 7: //Version 0.0.7 -> 0.0.8
						case 8: //Version 0.0.8 -> 0.0.9
							$result = $update->getObejectList("SELECT id, geburtsjahr " .
																" FROM #__ttverein_spieler");
							$update->query('ALTER TABLE #__ttverein_spieler ' .
											'CHANGE geburtsjahr geburtsdatum DATE  NULL DEFAULT NULL');
							foreach($result as $spieler) {		
								$update->query('UPDATE #__ttverein_spieler ' .
												" SET geburtsdatum='" . $spieler->geburtsjahr ."-01-01' " .
												" WHERE id=" . $spieler->id);
							}
							
						
						case 9: //Version 0.0.9 -> 0.1.0
							$update->query("UPDATE #__ttverein_altersklassen " .
											" SET name = 'Jungen' WHERE id =6");
											
							$update->query('ALTER TABLE #__ttverein_spieler' .
											' CHANGE hoechste_spielklasse hoechste_spielklasse VARCHAR( 255 )  NULL');	
											
							$update->query('ALTER TABLE #__ttverein_spieler ' .
											'ADD clicktt_person_id INT(11) NULL');
											
							$update->query('DELETE FROM #__ttverein_config ' .
											"WHERE name='clicktt_club_id'");
							
							$update->query('ALTER TABLE #__ttverein_mannschaften ' .
											' ADD clicktt_championship VARCHAR( 127 ) NULL , ' .
											' ADD clicktt_group INT NULL');
											 
							$version['patch'] = 0;

					}
				case 1: //0.1.x
					switch($version['patch']) {
						default:
							echo $unknown;
							break;
						case 0: //Version 0.1.0 -> 0.1.1
							$update->query("UPDATE #__ttverein_mannschaften " .
											" SET image_orginal = NULL WHERE image_orginal =''");
							$update->query("UPDATE #__ttverein_mannschaften " .
											" SET image_resize = NULL WHERE image_resize =''");
							$update->query("UPDATE #__ttverein_mannschaften " .
											" SET image_thumb = NULL WHERE image_thumb =''");
							$update->query("UPDATE #__ttverein_mannschaften " .
											" SET image_text = NULL WHERE image_text =''");
							$update->query("UPDATE #__ttverein_mannschaften " .
											" SET liga = NULL WHERE liga =''");			
							$update->query("UPDATE #__ttverein_mannschaften " .
											" SET liga = '1' WHERE liga ='1. Bundesliga'");
							$update->query("UPDATE #__ttverein_mannschaften " .
											" SET liga = '2' WHERE liga ='2. Bundesliga'");
							$update->query("UPDATE #__ttverein_mannschaften " .
											" SET liga = '3' WHERE liga ='Regionalliga'");
							$update->query("UPDATE #__ttverein_mannschaften " .
											" SET liga = '4' WHERE liga ='Oberliga'");
							$update->query("UPDATE #__ttverein_mannschaften " .
											" SET liga = '5' WHERE liga ='Verbandsliga'");
							$update->query("UPDATE #__ttverein_mannschaften " .
											" SET liga = '6' WHERE liga ='Landesliga'");
							$update->query("UPDATE #__ttverein_mannschaften " .
											" SET liga = '7' WHERE liga ='Bezirksliga'");
							$update->query("UPDATE #__ttverein_mannschaften " .
											" SET liga = '8' WHERE liga ='Bezirksklasse'");
							$update->query("UPDATE #__ttverein_mannschaften " .
											" SET liga = '9' WHERE liga ='Kreisliga'");
							$update->query("UPDATE #__ttverein_mannschaften " .
											" SET liga = '10' WHERE liga ='1. Kreisklasse'");
							$update->query("UPDATE #__ttverein_mannschaften " .
											" SET liga = '11' WHERE liga ='2. Kreisklasse'");
							$update->query("UPDATE #__ttverein_mannschaften " .
											" SET liga = '12' WHERE liga ='3. Kreisklasse'");
							
							$update->query("ALTER TABLE #__ttverein_mannschaften " .
											" CHANGE liga liga INT( 11 ) NULL DEFAULT NULL");
							   
			
						case 1: //Version 0.1.1 -> 0.1.2
							/*
							 * Bug seit Version 0.1.0 - In der install.sql fehlten zwei Felder
							 */
							if(!$update->fieldExists('#__ttverein_mannschaften','clicktt_championship'))
								$update->query('ALTER geschrieben TABLE #__ttverein_mannschaften ' .
											' ADD clicktt_championship VARCHAR( 127 ) NULL ');
							if(!$update->fieldExists('#__ttverein_mannschaften','clicktt_group'))
								$update->query('ALTER TABLE #__ttverein_mannschaften ' .
											' ADD clicktt_group INT NULL ');					   

							
						case 2: //Version 0.1.2 -> 0.1.3
						case 3: //Version 0.1.3 -> 0.1.4
							//Feld wird nicht mehr benötigt
							$update->query("DELETE FROM #__ttverein_config " .
											"WHERE name = 'clicktt_calc_leistungsindex' " .
											"LIMIT 1 ");
						case 4:  //Version 0.1.4 -> 0.1.5
						case 5:  //Version 0.1.5 -> 0.1.6
						case 6:  //Version 0.1.6 -> 0.2.0
							$update->query("INSERT IGNORE INTO `#__ttverein_felder` (`id`, `name_backend`, `name_frontend`, `typ`, `tooltip`, `zeige_in_uebersicht`, `reihenfolge`) " .
								" VALUES
									(1, 'Email', 'Email', 'email', 'Die Emailadresse wird im Spielerprofil und in der Mannschaftsdeteil- Seite angezeigt.', 1, 1),
									(2, 'Geburtstag', 'Alter', 'jahre seit', 'Mögliches Format: tt.mm.JJJJ', 0, 2),
									(3, 'Telefon', 'Telefon', 'telefon', 'Die Telefonnummer wird im Spielerprofil und in der Mannschaftsdeteil- Seite angezeigt.', 1, 3),
									(4, 'Geburtsort', 'Geburtsort', 'text', '', 0, 4),
									(5, 'Wohnort', 'Wohnort', 'text', '', 0, 5),
									(6, 'Mitglied seit', 'Jahre im Verein', 'jahre seit', 'Mögliches Format: tt.mm.JJJJ', 0, 6),
									(7, 'Größte Erfolge', 'Größte Erfolge', 'text', 'Größten Tischtennis Erfolge. z.B. Vereinsmeisterschaften oder Turniersiege', 0, 7),
									(8, 'Höchste Spielklasse', 'Höchste Spielklasse', 'text', 'Höchste Klasse in der der Spieler jemals gespielt hat.', 0, 8),
									(9, 'Bisherige Vereine', 'Bisherige Vereine', 'text', 'Vereine indem der Spieler vorher Spielberechtigt war.', 0, 9)
								");							
							
							$query = "SELECT id, geburtsdatum, fon, geburtsort, " .
											" wohnort, mitglied_seit, erfolge, " .
											" alte_vereine, hoechste_spielklasse " .
									" FROM #__ttverein_spieler ";
							$spieler = $update->getObejectList( $query );
							foreach($spieler as $row) {
								//Geburtstag - ID 2
								$update->insertSpielerFeld($row->id, 2, "datum", $row->geburtsdatum);
								//Telefon - ID 3
								$update->insertSpielerFeld($row->id, 3, "kurz_text", $row->fon);
								//Geburtsort - ID 4
								$update->insertSpielerFeld($row->id, 4, "kurz_text", $row->geburtsort);
								//Wohnort - ID 5
								$update->insertSpielerFeld($row->id, 5, "kurz_text", $row->wohnort);
								//Mitglied seit - ID 6
								$update->insertSpielerFeld($row->id, 6, "datum", $row->mitglied_seit . "-01-01");
								//Größte Erfolge - ID 7
								$update->insertSpielerFeld($row->id, 7, "kurz_text", $row->erfolge);
								//Höchste Spielklasse - ID 8
								$update->insertSpielerFeld($row->id, 8, "kurz_text", $row->hoechste_spielklasse);
								//Bisherige Vereine - ID 9
								$update->insertSpielerFeld($row->id, 9, "kurz_text", $row->alte_vereine);
							}
							
							$query = "ALTER TABLE `#__ttverein_spieler`
										  DROP `geburtsdatum`,
										  DROP `fon`,
										  DROP `geburtsort`,
										  DROP `wohnort`,
										  DROP `mitglied_seit`,
										  DROP `erfolge`,
										  DROP `alte_vereine`,
										  DROP `hoechste_spielklasse`";
							$update->query($query);
							
							$query = "ALTER TABLE #__ttverein_mannschaften " .
										" ADD mannschaftsfuehrer INT NULL " .
											" AFTER hinrunde ";
							$update->query($query);
					}
				case 2: //Version 0.2.x -> [0.2.x|0.3.0]
					switch($version['patch']) {
						default:
							echo $unknown;
							break;
						case 0:// Version 0.2.0 -> 0.2.1
							//Neuinstallationen machen einen Fehler
							if(!$update->fieldExists("#__ttverein_mannschaften","mannschaftsfuehrer")) {
								$query = "ALTER IGNORE TABLE #__ttverein_mannschaften " .
											" ADD mannschaftsfuehrer INT NULL " .
												" AFTER hinrunde ";
								$update->query($query);
							}
						
						case 1:// Version 0.2.1 -> 0.2.2
						case 2:// Version 0.2.2 -> 0.2.3	
						case 3:// Version 0.2.3 -> 0.2.4
						case 4:// Version 0.2.4 -> 0.2.5
						case 5:// Version 0.2.5 -> 0.2.6
						case 6:// Version 0.2.6 -> 0.2.7
						case 7:// Version 0.2.7 -> 0.2.8
							if($version['build'] < 3) {
								if(!$update->fieldExists("#__ttverein_spieler","published_gebutstag")) {
									$query = "ALTER TABLE  #__ttverein_spieler " .
												"ADD  published_gebutstag TINYINT( 1 ) NOT NULL DEFAULT  '1' AFTER  nachname";
									$update->query($query);
								}
							}
					}
				
			}
		//case 1:
		
	}
	$update->updateVersionString();
	echo $update->success();
	
	//$update->clearClickttCache();
	//echo "<br />clicktt Tabellen Cache gelöscht<br />";
	
//Keine ältere Version gefunden
} else if(!$update->isSameVersion()) {
	echo "<strong>Keine ältere Version gefunden</strong>";
	
	/*
	 * Defaultwerte einfügen 
	 */
	echo "<br><strong>Installiere Default Daten</strong>";
	//Altersklassen
	$update->query("INSERT IGNORE INTO #__ttverein_altersklassen VALUES (1, 'Herren', NULL, NULL, 0)");
	$update->query("INSERT IGNORE INTO #__ttverein_altersklassen VALUES (2, 'Damen', NULL, NULL, 10)");
	$update->query("INSERT IGNORE INTO #__ttverein_altersklassen VALUES (3, 'Senioren 40', NULL, 40, 20)");
	$update->query("INSERT IGNORE INTO #__ttverein_altersklassen VALUES (4, 'Senioren 50', NULL, 50, 21)");
	$update->query("INSERT IGNORE INTO #__ttverein_altersklassen VALUES (5, 'Senioren 60', NULL, 60, 22)");
	$update->query("INSERT IGNORE INTO #__ttverein_altersklassen VALUES (6, 'Jungen', 17, NULL, 30)");
	$update->query("INSERT IGNORE INTO #__ttverein_altersklassen VALUES (7, 'Schüler', 14, NULL, 31)");

	//Ligen
	$update->query("INSERT IGNORE INTO #__ttverein_ligen (id, name, reihenfolge) VALUES(1, '1. Bundesliga', 0)");
	$update->query("INSERT IGNORE INTO #__ttverein_ligen (id, name, reihenfolge) VALUES(2, '2. Bundesliga', 1)");
	$update->query("INSERT IGNORE INTO #__ttverein_ligen (id, name, reihenfolge) VALUES(3, 'Regionalliga', 2)");
	$update->query("INSERT IGNORE INTO #__ttverein_ligen (id, name, reihenfolge) VALUES(4, 'Oberliga', 3)");
	$update->query("INSERT IGNORE INTO #__ttverein_ligen (id, name, reihenfolge) VALUES(5, 'Verbandsliga', 4)");
	$update->query("INSERT IGNORE INTO #__ttverein_ligen (id, name, reihenfolge) VALUES(6, 'Landesliga', 5)");
	$update->query("INSERT IGNORE INTO #__ttverein_ligen (id, name, reihenfolge) VALUES(7, 'Bezirksliga', 6)");
	$update->query("INSERT IGNORE INTO #__ttverein_ligen (id, name, reihenfolge) VALUES(8, 'Bezirksklasse', 7)");
	$update->query("INSERT IGNORE INTO #__ttverein_ligen (id, name, reihenfolge) VALUES(9, 'Kreisliga', 8)");
	$update->query("INSERT IGNORE INTO #__ttverein_ligen (id, name, reihenfolge) VALUES(10, '1. Kreisklasse', 9)");
	$update->query("INSERT IGNORE INTO #__ttverein_ligen (id, name, reihenfolge) VALUES(11, '2. Kreisklasse', 10)");
	$update->query("INSERT IGNORE INTO #__ttverein_ligen (id, name, reihenfolge) VALUES(12, '3. Kreisklasse', 11)");
		
	//Felder
	$update->query("INSERT IGNORE INTO `#__ttverein_felder` (`id`, `name_backend`, `name_frontend`, `typ`, `tooltip`, `zeige_in_uebersicht`, `reihenfolge`) " .
					" VALUES
						(1, 'Email', 'Email', 'email', 'Die Emailadresse wird im Spielerprofil und in der Mannschaftsdeteil- Seite angezeigt.', 1, 1),
						(2, 'Geburtstag', 'Alter', 'jahre seit', 'Mögliches Format: tt.mm.JJJJ', 0, 2),
						(3, 'Telefon', 'Telefon', 'telefon', 'Die Telefonnummer wird im Spielerprofil und in der Mannschaftsdeteil- Seite angezeigt.', 1, 3),
						(4, 'Geburtsort', 'Geburtsort', 'text', '', 0, 4),
						(5, 'Wohnort', 'Wohnort', 'text', '', 0, 5),
						(6, 'Mitglied seit', 'Jahre im Verein', 'jahre seit', 'Mögliches Format: tt.mm.JJJJ', 0, 6),
						(7, 'Größte Erfolge', 'Größte Erfolge', 'text', 'Größten Tischtennis Erfolge. z.B. Vereinsmeisterschaften oder Turniersiege', 0, 7),
						(8, 'Höchste Spielklasse', 'Höchste Spielklasse', 'text', 'Höchste Klasse in der der Spieler jemals gespielt hat.', 0, 8),
						(9, 'Bisherige Vereine', 'Bisherige Vereine', 'text', 'Vereine indem der Spieler vorher Spielberechtigt war.', 0, 9)
					");
	
//Gleiche Version war vorher Installiert
} else {
	//$update->clearClickttCache();
	//echo "<br />clicktt Tabellen Cache gelöscht<br />";
		
	echo "<strong>Gleiche Version war vorher installiert</strong>";
}

/*
 * Konfigurationswerte müssen immer vorhanden sein!
 */
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('version_main', '$VERSION_MAIN', 0)");
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('version_sub', '$VERSION_SUB', 0)");
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('version_patch', '$VERSION_PATCH', 0)");
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('version_build', '$VERSION_BUILD', 0)");
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('player_image_path', '/images/stories/ttverein/player/', 1)");
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('team_image_path', '/images/stories/ttverein/team/', 1)");
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('team_thumb_size', '250', 1)");
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('team_image_size', '700', 1)");
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('player_thumb_size', '250', 1)");
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('player_image_size', '700', 1)");
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('delete_database', '0', 1)");
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('delete_pictures', '0', 1)");
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('clicktt_use', '0', 1)");
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('clicktt_verband', 'WTTV Westdeutschland', 1)");
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('clicktt_club_name', NULL, 1)");
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('clicktt_club_nummer', NULL, 1)");
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('clicktt_calc_leistungsindex', 0, 1)");
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('clicktt_club_id', NULL, 0)");
//Layout
$update->query("INSERT IGNORE INTO #__ttverein_config VALUES('div_team_height', '230', 1)");


//Die Komponenten ID im Menü muss aktualisiert werden.
$update->updateMenu();

if(!$update->checkPHPVersion(50100)) {
	echo "<h1>Ihre PHP Version ist zu alt. Sie benötigen PHP 5.1 oder höher</h1>";
}
if(!$update->checkFileGetContents()) {
	echo '<h1>Ihr Provider hat die Funktion: "file_get_contents()" nicht freigeschaltet!</h1>';
}


class com_ttvereinInstallerScript {
	var $_db;
	var $_new_version = array();
	var $_old_version = array();

	/**
	 * Dem Konstruktor wird die neue Versionsnummer übergeben. 
	 * Es wird eine Datenbankverbindung geladen und gespeichert, 
	 * die neue Version gespeichert und die Version der vorher 
	 * installierten Komponente herrausgefunden unf gespeichert. 
	 *  
	 * @access	public
	 * @param int $main Hauptnummer. Bei der Version 1.5.6 wäre $main = 1
	 * @param int $sub Unternummer. Bei der Version 1.5.6 wäre $sub = 5
	 * @param int $patch Patchnummer. Bei der Version 1.5.6 wäre $patch = 6
	 */
	function update($main, $sub, $patch, $build) {
		$this->_db = &JFactory::getDBO();

		$this->_new_version['main'] = intval($main);
		$this->_new_version['sub'] = intval($sub);
		$this->_new_version['patch'] = intval($patch);
		$this->_new_version['build'] = intval($build);
		
		//Speichert alte Version in $this->_old_version
		$this->getOldVersion();
	}

	/**
	 * Diese Methode kann benutzt werden um ein 
	 * UPDATE, ALTER, INSERT, CREATE, DELETE oder DROP Befehl abzusetzen.
	 * Es gibt keinen Rückgabewert.
	 * 
	 * @access	public
	 * @param $query Einen Gültigen MySQL Query String. 
	 */
	function query($query) {
		$this->_db->setQuery( $query );
		$this->_db->query();
	}
	
	/**
	 * Führt das übergebene Query aus und gibt das Ergebnis als "ObjectList" zurück.
	 * 
	 * @access public
	 * @param string $query Gültiger MySQL Query
	 * @return array Array mit Objekten wie die Methode JDatabase::loadObjectList();
	 */
	function getObejectList($query){
		$this->_db->setQuery( $query );
		return $this->_db->loadObjectList();
	}
	
	/**
	 * Prüft ob eine bestimmte Tabelle ein bestimmtes Feld besitzt.
	 * 
	 * @access public
	 * @param string $table Tabellennamen wie die Joomla API ihn erwartet (z.B. #__ttverein_spieler).
	 * @param string $field Name des Feldes (Spalte) die gesucht wird
	 * @return bool True wenn das Feld vorhanden ist. Ansonsten False.
	 */
	function fieldExists($table, $field) {

		$result = $this->_db->getTableFields($table);
		if(is_array($result) && array_key_exists($field, $result[$table]))
			return true;
		return false;
	}

	/**
	 * @access	public
	 * @return string Gibt Meldung zurück von welcher nach welcher Version geupdatet wurde 
	 */
	function success() {
		return "<br><strong>Update durchgeführt von Version " .
				$this->_old_version['main'] . "." .
				$this->_old_version['sub'] . "." .
				$this->_old_version['patch'] . " Build " . 
				$this->_old_version['build'] . " nach " .
				$this->_new_version['main'] . "." .
				$this->_new_version['sub'] . "." .
				$this->_new_version['patch'] . " Build " .
				$this->_new_version['build'] . "</strong>";
	}

	/**
	 * Überprüft ob ein Update überhaut notwendig ist.
	 * 
	 * @access	public
	 * @return bool true wenn ein Update nötig ist, 
	 * 				false wenn keine ältere Version gefunden wurde 
	 * 				oder eine neuere Version gefunden wurde.
	 */
	function isUpdateRequired() {
		if($this->_old_version == null || $this->isOldVersionLower() <= 0)
			return false;
		return true;
	}
	
	/**
	 * Überprüft ob die Vorherige Version die Selbe war.
	 * 
	 * @access public
	 * @return bool true wenn die Versionen gleich sind, ansonsten false.
	 */
	function isSameVersion($new=null, $old=null) {
		if($old == null)
			$old = $this->_old_version;
		if($new == null)
			$new = 	$this->_new_version;
		
		//Es wurde keine alte Version gefunden
		if(!is_array($old) || count($old) < 3)
			return false;
				
		if($old['main'] == $new['main']
			&& $old['sub'] == $new['sub']
			&& $old['patch'] == $new['patch']
			&& $old['build'] == $new['build'])
			return true;
		return false;
	}

	/**
	 * Überprüft ob es die alte Version älter ist.
	 * 
	 * @access	public
	 * @return int -1 alte Komponente hat eine neuere Version
	 * 				0 alte Komponente hat die gleiche Version
	 * 				1 alte Komponente hat eine ältere Versin
	 */
	function isOldVersionLower() {
		if( $this->_old_version['main'] > $this->_new_version['main'] )
			return -1;
		else if($this->_old_version['main'] < $this->_new_version['main'])
			return 1;

		if( $this->_old_version['sub'] > $this->_new_version['sub'] )
			return -1;
		else if($this->_old_version['sub'] < $this->_new_version['sub'])
			return 1;

		if( $this->_old_version['patch'] > $this->_new_version['patch'] )
			return -1;
		else if( $this->_old_version['patch'] < $this->_new_version['patch'] )
			return 1;

		if( $this->_old_version['build'] > $this->_new_version['build'] )
			return -1;
		else if( $this->_old_version['build'] < $this->_new_version['build'] )
			return 1;
		

		return 0;
	}

	/**
	 * Läd die alte Verion aus der Datenbank und speichert diese in $this->_old_version
	 * 
	 * @access	public
	 * @return array 	Bei Erfolg ein array mit 'main', 'sub', 'patch' Version oder 
	 * 					null wenn keine alte version existiert. 
	 */
	function getOldVersion() {
		if(count($this->_old_version) == 4)
			return $this->_old_version;

		$query = "SELECT name,value " .
			"FROM #__ttverein_config " .
			"WHERE name='version_main' " .
			" OR name='version_sub' " .
			" OR name='version_patch' " .
			" OR name='version_build' ";
		$this->_db->setQuery( $query );
		$result = $this->_db->loadObjectList();
		if($result == null)
			return null;

		foreach($result as $row) {
			$this->_old_version[substr($row->name,8)] = intval($row->value);
		}
		//Ist die Buildversion noch nicht vorhanden muss sie eingeführt werden Version >0.2.7RC3
		if(!array_key_exists("build", $this->_old_version)) {
			$this->_old_version['build'] = 0;
		}		
		return $this->_old_version;
	}

	/**
	 * Getter für $this->_new_version
	 * 
	 * @access	public
	 * @return array
	 */
	function getNewVersion() {
		return $this->_new_version;
	}

	/**
	 * Stellt den Versionsstring in der Datenbank auf die neue Version
	 * @access public
	 */
	function updateVersionString() {

		$query = "UPDATE #__ttverein_config " .
				"SET value='" . intval($this->_new_version['main']) ."' " .
				"WHERE name='version_main' ";
		$this->_db->setQuery( $query );
		$this->_db->query();


		$query = "UPDATE #__ttverein_config " .
				"SET value='" . intval($this->_new_version['sub']) ."' " .
				"WHERE name='version_sub' ";
		$this->_db->setQuery( $query );
		$this->_db->query();


		$query = "UPDATE #__ttverein_config " .
				"SET value='" . intval($this->_new_version['patch']) ."' " .
				"WHERE name='version_patch' ";
		$this->_db->setQuery( $query );
		$this->_db->query();

		$query = "UPDATE #__ttverein_config " .
				"SET value='" . intval($this->_new_version['build']) ."' " .
				"WHERE name='version_build' ";
		$this->_db->setQuery( $query );
		$this->_db->query();
	}
	
	/**
	* Verlinkung der Menüeinträge aktualisieren. 
	* Bei einer Neuistallation der Komponente werden schon existierende Menüeintrage nicht aktualliesiert. 
	* @access public
	*/
	function updateMenu () {
		/*
		 * ID der neuen Komponente laden
		 */
		$query = "SELECT id " .
				" FROM #__components " .
				" WHERE name='TT-Verein'";
		$this->_db->setQuery( $query );
		$row = $this->_db->loadObject();
				
		/*
		 * Menüeinträge auf neue Komponente verlinken.
		 */
		$query = "UPDATE #__menu " .
				" SET componentid=" . $row->id .
				" WHERE link LIKE 'index.php?option=com_ttverein%'";
		$this->_db->setQuery( $query );
		$this->_db->query();
	}
	
	/**
	 * Hilfsfunktion um einen Spieler einzufügen. 
	 * Wird nur für das Update von Version 0.1.x nach 0.2.x benötigt.
	 * @access public
	 * @param int $spielerID Die ID des Spielers
	 * @param int $feldID Die ID des Feldes aus #__ttverein_felder
	 * @param string $feldTyp Der Typ des Feldes - siehe #__ttverein_spieler_felder
	 * @param string $wert Der Wert des Feldes
	 */
	function insertSpielerFeld($spielerID, $feldID, $feldTyp, $wert) {
			if($wert == null || trim($wert) == "")
				return;
			$query = "INSERT INTO #__ttverein_spieler_felder " .
					" SET spieler_id=$spielerID, " .
						" felder_id=$feldID, " .
						" $feldTyp='$wert' ";	
			$this->_db->setQuery($query);
			$this->_db->query();
	}
	
	function clearClickttCache() {
		$query = "UPDATE #__ttverein_mannschaften " .
				" SET clicktt_championship=null, " .
					" clicktt_group=null";
		$this->_db->setQuery($query);
		$this->_db->query();
	}
	
	function checkPHPVersion($min = 50200) {
		//Versionen kleiner 5.2.7 haben keine PHP_VERSION_ID
		if(!defined('PHP_VERSION_ID')) {
		    $version = PHP_VERSION;
		
		    define('PHP_VERSION_ID', ($version{0} * 10000 + $version{2} * 100 + $version{4}));
		}

		if(PHP_VERSION_ID < $min) {
		    return false;
		}
		return true;
	}
	
	function checkFileGetContents() {
		if(!function_exists('file_get_contents') || !ini_get('allow_url_fopen')) {
			return false;
		}
		return true;
	}


}
?>
