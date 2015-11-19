<?php
defined('_JEXEC') or die('Restricted access');

$title = 'Spieler';
$document=JFactory::getDocument();
$app=JFactory::getApplication();
$document->setTitle( $title . ' - ' . $app->getCfg('sitename') );
$document->setDescription("Fotogalerie mit Bildern und Links zu den Spielern des TTC Wöschbach." );
$width = $this->config['player_thumb_size'];
?>

<div class="page-header clearfix">
	<h1 class="page-title"><?php echo $title;?></h1>
</div>
<div class="category-desc clearfix">
Die aktiven Spieler des TTC Wöschbach:
</div>
</br>
<div class="item-page clearfix">
	<div class="table-responsive">
		<?php

		foreach($this->players as $player) {

			$image = $player->image_thumb;
			if(!$image) 
				$image = "components/com_ttverein/images/no_image_available.png";
			else if(substr($image, 0, 1) == "/")
				$image = substr($image, 1);
			
			$name = $player->vorname . " " . $player->nachname;
			echo '<div class="clicktt_player_box">';
			echo '<a href="index.php?option=com_ttverein&layout=default&view=player&id=' . $player->id . '" class="contentpagetitle" style="white-space:nowrap;width:100px;overflow:hidden;text-overflow:ellipsis;float:left">' . $name . '</a>';
			echo '<img src="' .JURI::root() . $image . '" width="' . $width . '" />'; 
			echo "</div>\n";
		}
		?>
	</div>
</div>
