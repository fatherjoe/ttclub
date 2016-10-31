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
	href="<?php echo JURI::root() . 'images/' . $row->foto; ?>"
	data-lightbox="gall1"
	data-title=	"<b><?php echo $row->name; ?></b><br />
				Mannschaft <br /> 
				Position <?php echo $row->position; ?> <br /> 
				<br />
				Beschreibung<br />
				<?php echo nl2br($row->beschreibung); ?>
				">			
	<img
		src="<?php echo JURI::base() . 'media/com_webtt/thumbnails/aufstellung/' . $row->foto; ?>"
		height="<?php echo $this->params->thumb_height; ?>"
		width="<?php echo $this->params->thumb_width; ?>"
	 />
</a>
