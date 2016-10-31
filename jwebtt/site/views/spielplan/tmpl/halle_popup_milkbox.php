<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_webtt
 *
 */
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

?>

<a
	href="https://maps.googleapis.com/maps/api/staticmap?center=<?php echo urlencode($zeile->HALLE_ADRESSE); ?>&amp;zoom=13&amp;size=600x300&amp;maptype=roadmap&amp;markers=<?php echo urlencode($zeile->HALLE_ADRESSE); ?>"
	title="Lightbox mit Karte : <?php echo $zeile->HALLE_TITLE; ?>"
	data-milkbox="hallen"
	title=	"<b><?php echo $zeile->HEIM; ?></b><br />
				<?php echo $zeile->HALLE; ?>
				<?php echo $zeile->HALLE_ADRESSE; ?>"
	>
	<?php echo $zeile->HALLE; ?>
	</a>
