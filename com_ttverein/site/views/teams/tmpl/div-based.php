<?php
defined('_JEXEC') or die('Restricted access');

require(JPATH_COMPONENT_SITE . '/' . "views" . '/' . "teams" . '/' . "lib.tmpl.php");

$document=JFactory::getDocument();
$app=JFactory::getApplication();
$saisonString = $this->showSaison->saisonstart . '/' . substr( ($this->showSaison->saisonstart+1), 2, 2 );
//$document->setTitle( "Mannschaften " . $saisonString . " - " . $app->getCfg('sitename') );
//$document->setDescription( "Unsere Mannschaften in der Saison " . $saisonString . ".");
$document->setMetaData( 'keywords', 'Mannschaft, Aufstellung, Klasseneinteilung, Oberliga' );
/*
 * Sind keine Mannschaften vorhanden wird abgebrochen bzw. nichts angezeigt
 */
$vars = get_object_vars( $this );
if(!array_key_exists( "olderSaisons", $vars )) 
	return;


echo getHead($this->showHin, $this->showSaison, $this->olderSaisons);
echo "<br /><br />";

$styleTag = '
<style type="text/css">
	div.clicktt_team_box {
		width: ' . $this->config['team_thumb_size'] . 'px;
		margin-right: 1em;
		margin-bottom: 1em;
		
		float: left;
	
		height: ' . $this->config['div_team_height'] . 'px;
	}
	h1.clicktt_clear {
	clear:left;
		width: auto;
	}
	</style>';
?>
<table><tr><td>
	<?php
	$alte_altersklasse = "";
	if(is_array($this->rows)) {
		foreach($this->rows as $row) {
			if($row->altersklasse != $alte_altersklasse) {
				echo '<br clear="all">';			
				echo '<h1 class="clicktt_clear">' . $row->altersklasse . "</h1>";
				$alte_altersklasse = $row->altersklasse;
			}
			printEntry($row, $this->config['team_thumb_size']);
		}
	}
	?>
</td></tr></table>

<?php

function printEntry(&$row, $width) {
	$title = $row->nummer .  '. ' . $row->altersklasse;
	if($row->liga)
		$title .= ' - ' . $row->liga;
	
	$link = JRoute::_('index.php?option=com_ttverein&layout=default&view=team&id=' . $row->id);
	$content = "";
	$image = $row->image_thumb;
		
	if(!$image) 
		$image = "components/com_ttverein/images/no_image_available_group.jpg";
	else if(substr($image, 0, 1) == "/")
		$image = substr($image, 1);
	
	$content = '<a href="' . $link . '"><img width="' . $width . 'px" src="' . JURI::root() . $image . '" alt="' . $title . '" /></a>';
	?>
	<div class="clicktt_team_box">
		<h2>
		<a href="<?php echo $link;?>" class="contentpagetitle">
			<?php echo $title;?>
		</a>
		</h2>
		<?php echo $content;?>
	</div>
<?php
}
?>
