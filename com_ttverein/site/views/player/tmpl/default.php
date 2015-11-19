<?php
defined('_JEXEC') or die('Restricted access');

$name = $this->player->vorname . " " . $this->player->nachname;
$title = "Spielerportrait " . $name;

$document=JFactory::getDocument();
$app=JFactory::getApplication();
$document->setTitle($title . ' - ' . $app->getCfg('sitename') );
$document->setDescription("Steckbrief " . $name . ". Mannschaftszugehörigkeit, Bilanzen, Q-TTR Wert, Spielerfoto");
// remove incorrect canonical URL
foreach ( $document->_links as $k => $array ) {
	if ( $array['relation'] == 'canonical' ) {
		unset($document->_links[$k]);
	}
}
?>

<div class="page-header clearfix">
	<h1 class="page-title"><?php echo $name;?> - Steckbrief</h1>
</div>
<div class="item-page clearfix">

<?php
$image = $this->player->image_thumb;
if(!$image) 
	$image = "components/com_ttverein/images/no_image_available.png";

$imageOrginal = $this->player->image_orginal;
if(!$imageOrginal ) 
	$imageOrginal = "components/com_ttverein/images/no_image_available.png";


if($image) {
	if(substr($image, 0, 1) == "/")
		$image = substr($image, 1);
	if(substr($imageOrginal, 0, 1) == "/")
		$imageOrginal = substr($imageOrginal, 1);
		
	if($imageOrginal)
		echo '<a href="' . JURI::root() . $imageOrginal . '" target="_blank">';
	echo "<img src=\"" . JURI::root() . $image . "\" style=\"float:left; margin-right:10px;\" alt=\"$name\" />";
	if($imageOrginal)
		echo "</a>";
}
?>

<div class="table-responsive">
	<table class="table table-condensed table-striped" style="width: calc(100% - 210px);">
		<?php printFelder($this->player->felder);?>
	</table>
</div>
<?php
if(is_array($this->aufstellungen)) {
	echo '<br clear="all"/>';
	echo "<h2>Mannschaften</h2>";

	$tab = "&nbsp;&nbsp;&nbsp;&nbsp;";
	$oldSaison = null;
	foreach($this->aufstellungen as $mannschaft) {
		if($oldSaison != $mannschaft->saisonstart) {
			echo "<b>Saison " . $mannschaft->saisonstart . "/" .  substr(($mannschaft->saisonstart+1),2,2) . "</b>\n<br />\n";
			$oldSaison = $mannschaft->saisonstart;
		}
		
		$runde = ($mannschaft->hinrunde == 1) ? "Hinrunde&nbsp;&nbsp;&nbsp;&nbsp;" : "R&uuml;ckrunde&nbsp;";
		echo  $tab . $runde;
		$url = "index.php?option=com_ttverein&layout=default&view=team&id=" . $mannschaft->id;
		$linkText = $mannschaft->nummer . '. ' . $mannschaft->altersklasse;
		if($mannschaft->liga)
			$linkText .= ' - ' . $mannschaft->liga;
		echo ' <a href="'. $url . '">' . $linkText . '</a>';
		echo ' ( Pos. ' . $mannschaft->position . ' )<br />';
		
	}
	
}
?>
</div>
<?php
$rootpath = JURI::root().'administrator/components/com_ttverein/lib/clicktt/ajaxbilanz.php';
$ajaxurl = '?verband=' .urlencode($this->ajax['clicktt_verband']) 
	.'&vereinsnummer='.urlencode($this->ajax['clicktt_club_nummer']) 
	.'&clubname='.urlencode($this->ajax['clicktt_club_name'])
	.'&clubid='.urlencode($this->ajax['clicktt_club_id'])
	.'&spielerid='.urlencode($this->ajax['personid'])
	.'&calc_leistungsindex='.urlencode($this->ajax['clicktt_calc_leistungsindex']);
	
function printFelder($felder) {

	foreach($felder as $feld) {
		$title = $feld->name;
		$text = trim($feld->kurz_text);
		
		if($feld->typ == "jahre seit") {
			if(trim($feld->datum) == "0000-00-00")
				continue;
			
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
			
			if($alter == 0 && $v > 0) {
				$text = $v . " Monate"; //Ungenau
			} else {		
				$text = $alter . " Jahre";
			}	
			
		}
		if ($feld->typ == "email") {
			$text = JHTML::_('email.cloak', $text);	
		} 
		
		if($text == null || $text == '')
			continue;
		?>
		<tr>
			<th><?php echo $title;?></th>
			<td><?php echo $text;?></td>
		</tr>
		<?php
		
	}	
}	
?>

<span id="bilanz"></span>

<script type="text/javascript">
/* <![CDATA[ */
	var xmlHttp = false;

	if (!xmlHttp && typeof XMLHttpRequest != 'undefined') {
  		xmlHttp = new XMLHttpRequest();
  	}
  	getBilanzTable();
	function getBilanzTable(){
		if (xmlHttp) {
			xmlHttp.open('GET', '<?php echo $rootpath.$ajaxurl ?>', true);
			xmlHttp.onreadystatechange = function () {
        		if (xmlHttp.readyState == 4) {
        			document.getElementById("bilanz").innerHTML = xmlHttp.responseText;
				allocateSidebarHeightForPlayer();
         		}
     		};
     		xmlHttp.send(null);
 		}
	}
	
	function allocateSidebarHeightForPlayer() {
		var contentDom = document.getElementById("wrapper2");
		var sidebarDom = document.getElementById("nav");
		contentDom.style.height = null;
		if((contentDom.offsetHeight) > sidebarDom.offsetHeight) {
			sidebarDom.style.height = (contentDom.offsetHeight - 25)+"px"; 
		} else {
			contentDom.style.height = (sidebarDom.offsetHeight - 25)+"px"; 
		}
		
	}
/* ]]> */	
</script>
