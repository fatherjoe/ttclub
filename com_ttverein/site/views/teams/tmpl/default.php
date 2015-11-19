<?php
defined('_JEXEC') or die('Restricted access');

require(JPATH_COMPONENT_SITE . '/' . "views" . '/' . "teams" . '/' . "lib.tmpl.php");

/*
 * Sind keine Mannschaften vorhanden wird abgebrochen bzw. nichts angezeigt
 */
$vars = get_object_vars( $this );
if(!array_key_exists( "olderSaisons", $vars )) 
	return;

$saisonString = $this->showSaison->saisonstart . '/' . substr( ($this->showSaison->saisonstart+1), 2, 2 );

echo getHead($this->showHin, $this->showSaison, $this->olderSaisons);
?>
<table align="center" width="100%">
	<tr>
		<td valign="top">
			<table width="100%" cellpadding="0" cellspacing="0">
				<tr>
					<td valign="top" width="50%" class="article_column">
						<?php
						// Auslesen der Datensätze im Array
						for($i=0; $i < count($this->rows); $i+=2) {
							printEntry($this->rows[$i], $this->config['team_thumb_size']);
						}
						?>
					</td>
					<td valign="top" width="50%" class="article_column column_separator">
						<?php
						for($i=1; $i < count($this->rows); $i+=2) {
							printEntry($this->rows[$i], $this->config['team_thumb_size']);
						}
						?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
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
	
	
	$content = '<a href="' . $link . '"><img width="' . $width . 'px" src="' . JURI::root() . $image . '" alt="Mannschaftsaufstellung ' . $title . '"></a>';
	?>
	<table class="contentpaneopen">
		<tr>
			<td class="contentheading" width="100%">
				<a href="<?php echo $link;?>" class="contentpagetitle">
					<?php echo $title;?>
				</a>
				</td>
		</tr>
	</table>

	<table class="contentpaneopen">
		<tr>
			<td valign="top" colspan="2">
				<?php echo $content;?>
			</td>
		</tr>
	</table>

	<span class="article_separator">&nbsp;</span>
	</br></br></br>


<?php
}
?>




