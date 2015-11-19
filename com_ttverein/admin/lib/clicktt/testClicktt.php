<?php
header('Content-type: text/html; charset=utf-8');
require('clicktt.php');

?>
<html>
<head>
	<title>clicktt Testseite</title>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>
<body>

<?php

$test = new TestClicktt();
//$test->testBergischGladbach(true);
//$test->testAggertalerTTC(false);
//$test->testFuerstengrund(false);
//$test->testOkriftel(false);
//$test->testPfrondorf(false);
//$test->testDettingen(false);
//$test->testBernloch(false);
//$test->testKesselstadt(false);
//$test->testOker(false);
//$test->testEschbach(false);
//$test->testWestercelle(false);
//$test->testLuehnde(false);
//$test->testKapellenErft(false);
//$test->testNagold(false);
//$test->testAlsdorf(false);
//$test->testStetten(false);
$test->testNortrup(true);
?>

</body>
</html>


<?php

class TestClicktt {
	
	var $time = 0;
	var $clicktt = null;
	
	function testBergischGladbach($kurz=true) {
		echo "<h1>TTV Berg. Gladbach (WTTV)</h1>";
		$this->doTest($kurz, "WTTV Westdeutschland", 153041, "TTV Bergisch-Gladbach", 2008, true);
	}
	
	function testAggertalerTTC($kurz=true) {
		echo "<h1>Aggertaler TTC (WTTV)</h1>";
		$this->doTest($kurz, "WTTV Westdeutschland", 152009, "Aggertaler TTC", 2008, false);
	}
	
	function testFuerstengrund($kurz=true) {
		echo "<h1>RV-TTC Fürstengrund (HTTV)</h1>";
		$this->doTest($kurz, "HTTV Hessen", 45009, "RV-TTC Fürstengrund", 2008, false);
	}
	
	function testOkriftel($kurz=true) {
		echo "<h1>TV Okriftel (HTTV)</h1>";
		$this->doTest($kurz, "HTTV Hessen", 34035, "TV Okriftel", 2008, false);
	}
	
	function testPfrondorf($kurz=true) {
		echo "<h1>SV Pfrondorf (TTVBW)</h1>";
		$this->doTetestKapellenErftst($kurz, "TTVBW Württemberg-Hohenzollern", 1041, "SV Pfrondorf", 2008, false);
	}
	
	function testDettingen($kurz=true) {
		echo "<h1>TTV Dettingen (TTVBW)</h1>";
		$this->doTest($kurz, "TTVBW Württemberg-Hohenzollern", "05011", "TTV Dettingen", 2008, false);
	}
	
	function testBernloch($kurz=true) {
		echo "<h1>SSV Bernloch (TTVBW)</h1>";
		$this->doTest($kurz, "TTVBW Württemberg-Hohenzollern", "01005", "SSV Bernloch", 2008, false);
	}
	
	function testKesselstadt($kurz=true) {
		echo "<h1>TV 1860 Kesselstadt (HTTV)</h1>";
		$this->doTest($kurz, "HTTV Hessen", "23028", "TV 1860 Kesselstadt", 2008, false);
	}
	
	function testOker($kurz=true) {
		echo "<h1>VfL Oker (TTVN)</h1>";
		$this->doTest($kurz, "TTVN Niedersachsen", "1072745", "VfL Oker", 2008, false);
	}
	
	function testEschbach($kurz=true) {
		echo "<h1>TTC Eschbach (TTVBW)</h1>";
		$this->doTest($kurz, "TTVBW Südbaden", "22015", "TTC Eschbach", 2008, false);
	}
	
	function testWestercelle($kurz=true) {
		echo "<h1>VfL Westercelle (TTVN)</h1>";
		$this->doTest($kurz, "TTVN Niedersachsen", "3256750", "VfL Westercelle", 2008, false);
	}	
	
	function testLuehnde($kurz=true) {
		echo "<h1>TuS Lühnde (TTVN)</h1>";
		$this->doTest($kurz, "TTVN Niedersachsen", "2214100", "TuS Lühnde", 2008, false);
	}
	
	function testKapellenErft($kurz=true) {
		echo "<h1>TTC Kapellen-Erft (WTTV)</h1>";
		$this->doTest($kurz, "WTTV Westdeutschland", "142005", "TTC Kapellen-Erft", 2008, false);
	}
	
	function testNagold($kurz=true) {
		echo "<h1>VFL Nagold (TTVBW)</h1>";
		$this->doTest($kurz, "TTVBW Württemberg-Hohenzollern", "12031", "VFL Nagold", 2008, true);
	}
	
	function testAlsdorf($kurz=true) {
		echo "<h1>SV 09 Alsdorf (TTVR)</h1>";
		$this->doTest($kurz, "TTVR Rheinland", "000001", "SV 09 Alsdorf", 2009, true);
	}
	
	function testStetten($kurz=true) {
		echo "<h1>TSV Stetten (TTVBW)</h1>";
		$this->doTest($kurz, "TTVBW Württemberg-Hohenzollern", "06056", "TSV Stetten", 2008, true);
	}
	
	function testNortrup($kurz=true) {
		echo "<h1>SV Nortrup (TTVN)</h1>";
		$this->doTest($kurz, "TTVN Niedersachsen", "4473300", "SV Nortrup", 2009, true);
	}
	
	
	function doTest($kurz=true, $verband, $vereinsNummer, $vereinsName, $jahr=2008, $hinrunde=true) {
		if($kurz)
			$this->shortTest($verband, $vereinsNummer, $vereinsName, $jahr, $hinrunde);
		else
			$this->testAll($verband, $vereinsNummer, $vereinsName, $jahr, $hinrunde);
	}
	
	function testAll($verband, $vereinsNummer, $vereinsName, $jahr=2008, $hinrunde=true) {
		$clubID = $this->newClicktt($verband, $vereinsNummer, $vereinsName);
		
		$teams = $this->getTeams(null, null, $hinrunde);
		
		foreach($teams as $klassen=>$mannschaft) {
			$persons = $this->getPersons($jahr, $klassen, $hinrunde);
			foreach($persons as $person) {
				$this->getBilanz($jahr, $person->id, $person->vorname . " " . $person->nachname);
				if($verband == "WTTV Westdeutschland")
					$this->getLeistungsIndex($jahr, $hinrunde, $person->id);
			}
			foreach($mannschaft as $nummer=>$data) {
				$this->getTable($data['championship'], $data['group']);
			}
		}
	}
	
	function shortTest($verband, $vereinsNummer, $vereinsName, $jahr=2008, $hinrunde=true) {
		$clubID = $this->newClicktt($verband, $vereinsNummer, $vereinsName);
		
		$teams = $this->getTeams("Herren",1, $hinrunde);

		foreach($teams as $klassen=>$mannschaft) {
			$persons = $this->getPersons($jahr, $klassen, $hinrunde);

			for($i=0; $i < 4; $i++) {
				$this->getBilanz($jahr, $persons[$i]->id, $persons[$i]->vorname . " " . $persons[$i]->nachname);
				if($verband == "WTTV Westdeutschland")
					$this->getLeistungsIndex($jahr, $hinrunde, $persons[$i]->id);
			}
			foreach($mannschaft as $nummer=>$data) {
				$this->getTable($data['championship'], $data['group']);
			}
		}
	}	
		
	function newClicktt($verband, $vereinsNummer, $vereinsName, $clubID=null) {
		$this->getTime();
		$this->clicktt = new Clicktt($verband, $vereinsNummer, $vereinsName, $clubID);
		if($clubID == null)
			$clubID = "null";
		$title = "new Clicktt(verband=\"$verband\", vereinsNummer=$vereinsNummer, vereinsName=\"$vereinsName\", clubID=$clubID)";
		
		$clubID = $this->clicktt->getCache("clubID");
		
		$error = false;
		if(!is_int($clubID) || $clubID < 1)
			$error = true;
		
		$html = "<b>clubID:</b> " . $this->clicktt->clubID;
		$html .= "<br><b>clubName:</b> " . $this->clicktt->clubName;
		$html .= "<br><b>Verbandname:</b> " . $this->clicktt->verband->name;
		$html .= "<br><b>imageUrl:</b> " . $this->clicktt->imageUrl;
		
		$this->debugPrint($title, $html, $this->clicktt, $this->getTime(true), $error);
		return $clubID;
	}
	
	function getLeistungsIndex($jahr, $hinrunde, $id) {
		$this->getTime();
		$index = $this->clicktt->getLeistungsIndex($jahr, $hinrunde, $id);
		
		$error = false;
		if(!is_int($index) && !is_float($index))
			$error = true;
			
		if($hinrunde)
			$hinrunde = "true";
		else
			$hinrunde = "false";	
		$title = "getLeistungsIndex(jahr=$jahr, hinrunde=$hinrunde, id=$id)";
		$this->debugPrint($title, "", $index, $this->getTime(true), $error);
		return $index;
	}
	
	function getBilanz($jahr, $id, $name="") {
		$this->getTime();
		$bilanz = $this->clicktt->getBilanz($jahr, $id);
		
		$error = false;
		if(!is_array($bilanz))
			$error = true;
			
		$title = "$name - getBilanz(jahr=$jahr, id=$id)";
		$this->debugPrint($title, "", $bilanz, $this->getTime(true), $error);
		return $bilanz;
	}
	
	function getPersons($jahr, $altersKlasse="Herren", $hinrunde=true) {
		$this->getTime();
		$persons = $this->clicktt->getPersons($jahr, $altersKlasse, $hinrunde);
				
		
		$error = false;
		if(!is_array($persons) || count($persons) < 4)
			$error = true;
		
		$html = "<ol>";
		foreach($persons as $person) {
			$html .=  "<li>" . $person->vorname . " " . $person->nachname . " - " . $person->id . "</li>";
		}
		$html .= "</ol>";
		if($hinrunde)
			$hinrunde = "true";
		else
			$hinrunde = "false";
		$title = "getPersons(jahr=$jahr, altersKlasse=\"$altersKlasse\", hinrunde=$hinrunde)";
		$this->debugPrint($title, $html, $persons, $this->getTime(true), $error);
		return $persons;
	}
	
	function getTeams($altersKlasse=null, $nummer=null, $hinrunde=true) {
		$this->getTime();
		$teams = $this->clicktt->getTeams($altersKlasse,$nummer, $hinrunde);
		
		if($altersKlasse == null)
			$altersKlasse = "null";
		if($nummer == null)
			$nummer = "null";
		
		$error = false;
		if(!$teams || !is_array($teams)) 
			$error = true;	
		
		$html = "";
		foreach($teams as $klasse=>$data) {
			if(!is_array($data)) 
				$error = true;
			foreach($data as $num=>$ids) {
				if(!is_array($ids)) 
					$error = true;
				$html .= "<h3>$num. $klasse</h3>";
				foreach($ids as $key=>$value) {
					if(!$value) 
						$error = true;
					$html .= "<b>$key:</b> $value<br />";
				}
				$html .= "<hr />";
			}
		}
		if($hinrunde)
			$hin = "true";
		else
			$hin = "false";
		$this->debugPrint("getTeams(altersKlasse=\"$altersKlasse\", nummer=$nummer, hinrunde=$hin)", $html, $teams, $this->getTime(true), $error);
		return $teams;
	}
	
	function getTable($championship, $group) {
		$this->getTime();
		$table = $this->clicktt->getTeamTable($championship, $group);
		$error = false;
		if(strlen($table) < 1000) 
			$error = true;
		$this->debugPrint("getTeamTable(championship=\"$championship\", group=\"$group\")", $table, $table, $this->getTime(true), $error);
	}	
	
	function debugPrint($title, $html, $debug, $time="", $error=true) {
		if($error)
			$color = "red";
		else
			$color = "55BB00";
		
		$time = substr($time, 0, strpos($time, ".")+4);
		
		$rows = 15;
		if($debug === null || $debug === ""
			|| (is_array($debug) && count($debug) == 0)
			)
			$rows = 3;
		
		echo "\n<table bgcolor=\"$color\" width=\"100%\">";
		echo "\n<tr><td colspan=\"2\"><h2>$title</h2><b>$time Sekunden<b></td></tr>";
		echo "\n<tr>\n\t<td valign=\"top\" width=\"65%\">$html</td>";
		echo "\n\t<td valign=\"top\">";
		echo '<div style="width:600px; height:180px; overflow:auto">';
		var_dump($debug);	
        echo "</div>"; 
		echo "</td>";		
		echo "\n</tr></table>\n<br><br>";
		
	}	
	
	function getTime($calcDiff = false) {
		$new=microtime();
		$temp=explode(" ",$new);
		$newTime = $temp[0]+$temp[1];
		
		if($calcDiff)	
			$result = $newTime-$this->time;
		else
			$result = $newTime;
			
		$this->time = $newTime;
		
		return $result;
	}
	
}
?>