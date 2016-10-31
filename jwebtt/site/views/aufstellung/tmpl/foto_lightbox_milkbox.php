<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_webtt
 *
 */
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');



?>
<a
	href="<?php echo JURI::root() . 'images/' . $this->spielerfotos["$zeile->SPIELER"]['foto']; ?>"
	data-milkbox="gall1"
	title=	"<b><?php echo $zeile->SPIELER; ?></b><br />
			Mannschaft <br /> 
			Position <?php echo $zeile->POSITION; ?> <br /> 
			<br />
			Beschreibung<br />
			<?php echo nl2br($zeile->BESCHREIBUNG); ?>
			">
	<img
		src="<?php echo JURI::base() . 'media/com_webtt/thumbnails/aufstellung/' . $this->spielerfotos["$zeile->SPIELER"]['foto']; ?>"
		height="<?php echo $this->params->thumb_height; ?>"
		width="<?php echo $this->params->thumb_width; ?>"
	 />
</a>
