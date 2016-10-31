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
	data-lightbox="hallen"
	data-title=	"<?php echo $zeile->HEIM; ?><br />
					<?php echo $zeile->HALLE_TITLE; ?><br />
					<?php echo $zeile->HALLE_ADRESSE; ?>"
	>
		<span>
			<?php
				if ($zeile->HALLE == "H") { ?>H<?php }
				else { ?>&#1010<?php echo ($zeile->HALLE + 1); }
			?>
		</span>
	</a>
