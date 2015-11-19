<?php
defined('_JEXEC') or die('Restricted access');

$saisonString = $this->team->saisonstart . '/' . substr( ($this->team->saisonstart+1), 2, 2 );
if($this->team->hinrunde == 1)
	$rundenString =  "Hinrunde";
else
	$rundenString = "R&uuml;ckrunde";

if($this->team->hinrunde == 1)
	$pageState =  "vorrunde";
else
	$pageState = "rueckrunde";

$title = $this->team->nummer . ". " . $this->team->altersklasse . " Mannschaft";
/*
	 * setzen des <title> Tag.
	 * Da die Methode setPageTitle() einen nicht URL codierten String benötigt, 
	 * wird dieser mit html_entity_decode() umgewandelt. Da es zu Fehlern bei PHP4 kommen kann
	 * wenn man die Kodierung UTF-8 angibt, muss der String noch in UTF-8 umgewandelt werden.
	 */
	
$document=JFactory::getDocument();
$app=JFactory::getApplication();
$document->setTitle( $title . ' ' . " " . $saisonString . " (" .  html_entity_decode($rundenString) . ") - " . $app->getCfg('sitename') );
$document->setDescription("Mannschaftsportrait " . $title . " - Aufstellung und Tabellenstand in der Saison " . $saisonString . " (" . html_entity_decode($rundenString) . ")." );
// remove incorrect canonical URL
foreach ( $document->_links as $k => $array ) {
	if ( $array['relation'] == 'canonical' ) {
		unset($document->_links[$k]);
	}
}

if($this->team->liga)
	$title .= " - " . $this->team->liga;
?>
<div class="item-page clearfix">

<!-- article itemscope itemtype="http://schema.org/SportsTeam" --!>
<header class="article-header clearfix">
	<h1 class="article-title" itemprop="name"><?php echo $title;?></h1>
	<h2>Saison <?php echo $saisonString . " - " . $rundenString;?></h2>
</header>

<section class="article-content clearfix" itemprop="articleBody">
	<?php
	$image = $this->team->image_resize;
	$imageOrginal = $this->team->image_orginal;
	if($image) {
		if(substr($image, 0, 1) == "/")
			$image = substr($image, 1);
		if(substr($imageOrginal, 0, 1) == "/")
			$imageOrginal = substr($imageOrginal, 1);
		
		if($imageOrginal)
			echo '<a href="' . JURI::root() . $imageOrginal . '" target="_blank">';
		?>
		<div class="clicktt_team_image">
			<img src="<?php echo JURI::root() . $image;?>" alt="<?php echo $app->getCfg('sitename'). ' - ' . $title;?>" />
			<br />
			<div style="margin-top: 0px; margin-bottom: 0px; width: 100%; overflow: hidden; max-width: <?php echo $this->config['team_image_size'];?>px;"><?php echo nl2br($this->team->image_text);?></div>
		</div>
			<?php
			if($imageOrginal)
				echo "</a>";
	}  
	?>
</section>

<section class="article-content clearfix" itemprop="articleBody">
	<?php if(is_array($this->team->players)) {?>
	<h3>Aufstellung</h3>
	<div id="aufstellung">
		<table class="aufstellung table table-condensed table-striped">
			<tr>
				<th style="width: 30px;">Pos.</th>
				<th>Name</th>
				
				<?php
				foreach($this->team->spielerFelder as $feld) {
					echo "\n<th>" . $feld->name . "</th>";
				}
				
				?>
			</tr>
			<?php
			$mannschaftsFuehrerAusgegeben = false;
			foreach($this->team->players as $player) {
			?>
			
			<tr>
				<td><div style="width: 90%"><?php echo $player->position;?></div></td>
				<td>
					<?php if($player->published ) { ?>
						<a href="index.php?option=com_ttverein&layout=default&view=player&id=<?php echo $player->id;?>">
					<?php } ?>
							<?php echo $player->vorname . " " . $player->nachname;?>
					<?php if($player->published ) { ?>
						</a>
					<?php } ?>
				</td>
				<?php
				foreach($this->team->spielerFelder as $feldUeberschrift) {
					$ausgabe = "&nbsp;";
					//TODO wofür die Schleife? Funktion wird auch in Spieler View benötigt
					foreach($player->felder as $feld) {
						if($feld->name != $feldUeberschrift->name)
							continue;
						$ausgabe = $feld->kurz_text;
						
						if($feld->typ == "jahre seit") {
						
							$akt_jahr = date("Y");
							$akt_monat = date("m");
							$akt_tag = date("d");
							
							$gebdat = explode("-", trim($feld->datum));
							$geb_jahr = $gebdat[0];
							$geb_monat = $gebdat[1];
							$geb_tag = $gebdat[2];
							
							$alter = $akt_jahr - $geb_jahr;
							$v = $akt_monat - $geb_monat;
							
							// Geb-Monat in der Zukunft
							if ($v < 0) {
								$alter = $alter - 1;
						
							// aktuelles Monat ist Geb-Monat
							} elseif ($v == 0) {
								$d = $akt_tag - $geb_tag;
								if ($d < 0) 
									$alter = $alter - 1;
							}
							
							$ausgabe = $alter;
							
						}
						if ($feld->typ == "email") {
							$ausgabe = JHTML::_('email.cloak', $ausgabe);	
						} 
						//break;							
					}
					echo "\n<td>$ausgabe</td>";
				}
				?>
			
			</tr>
			

			<?php
			}
			?>

		</table>
	</div>
	<?php }
		if($this->team->mannschaftsfuehrerName) {
			echo "<strong>Mannschaftsf&uuml;hrer:</strong> ";
			echo $this->team->mannschaftsfuehrerName;
			echo "\n";
		}
		?>
</section>
<section class="article-content clearfix" itemprop="articleBody">
	<span id="teamschedule"></span>
</section>
<section class="article-content clearfix" itemprop="articleBody">
	<span id="teamtabelle"></span>
</section>
<!-- /article -->
</div>
<?php 
$teamtablepath = JURI::root().'administrator/components/com_ttverein/lib/clicktt/ajaxtabelle.php';
$teamtableparams = '?verband=' .urlencode($this->ajax->verband) 
	.'&vereinsnummer='.urlencode($this->ajax->vereinsnummer) 
	.'&clubname='.urlencode($this->ajax->clubname)
	.'&clubid='.urlencode($this->ajax->clubid)
	.'&championship='.urlencode($this->ajax->championship)
	.'&group='.urlencode($this->ajax->group)
	.'&imageurl='.urlencode(substr(JURI::root(),7) . "components/com_ttverein/images/");

$teamschedulepath = JURI::root().'administrator/components/com_ttverein/lib/clicktt/ajaxteamschedule.php';
$teamscheduleparams = '?verband=' .urlencode($this->ajax->verband) 
	.'&vereinsnummer='.urlencode($this->ajax->vereinsnummer) 
	.'&clubname='.urlencode($this->ajax->clubname)
	.'&clubid='.urlencode($this->ajax->clubid)
	.'&championship='.urlencode($this->ajax->championship)
	.'&pageState='.$pageState
	.'&group='.urlencode($this->ajax->group)
	.'&teamtable='.$this->ajax->teamtable
	;

?>
<script type="text/javascript">
/* <![CDATA[ */
	
	getTeamTable();
	getTeamSchedule();
	
	function getTeamTable(){
		var wrapperDom = document.getElementById("wrapper2");
		var xmlHttp = new XMLHttpRequest();

		if (xmlHttp) {
			xmlHttp.open('GET', '<?php echo $teamtablepath.$teamtableparams ?>', true);
			xmlHttp.onreadystatechange = function () {
			if (xmlHttp.readyState == 4) {
				if (wrapperDom != null) {
					wrapperDom.style.height = null;
				}
				document.getElementById("teamtabelle").innerHTML = xmlHttp.responseText;
				allocateSidebarHeightForTeamTable();
			}
		};
		xmlHttp.send(null);
 		}
	}

	function getTeamSchedule(){
		var wrapperDom = document.getElementById("wrapper2");
		var xmlHttp = new XMLHttpRequest();

		if (xmlHttp) {
			xmlHttp.open('GET', '<?php echo $teamschedulepath.$teamscheduleparams ?>', true);
			xmlHttp.onreadystatechange = function () {
			if (xmlHttp.readyState == 4) {
				if (wrapperDom != null) {
					wrapperDom.style.height = null;
				}
				document.getElementById("teamschedule").innerHTML = xmlHttp.responseText;
				allocateSidebarHeightForTeamTable();
			}
		};
		xmlHttp.send(null);
 		}
	}
	
	function allocateSidebarHeightForTeamTable() {
		var wrapperDom = document.getElementById("wrapper2");
		var contentDom = document.getElementById("contenttable");
		var sidebarDom = document.getElementById("nav");

		contentDom.style.height = contentDom.offsetHeight+"px";
		if((contentDom.offsetHeight) > sidebarDom.offsetHeight) {
			sidebarDom.style.height = (contentDom.offsetHeight)+"px"; 
		}
		
		wrapperDom.style.height = wrapperDom.offsetHeight+"px";
		if((wrapperDom.offsetHeight) > sidebarDom.offsetHeight) {
			sidebarDom.style.height = (wrapperDom.offsetHeight)+"px"; 
		}		
	}

/* ]]> */
</script>