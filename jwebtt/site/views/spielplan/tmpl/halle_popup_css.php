<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_webtt
 *
 */
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

?>

<div class="box">
	
	<a href="<?php echo 'http://www.google.de/maps/dir//' . urlencode($zeile->HALLE_ADRESSE); ?>"
	   title="Zum Routenplaner : <?php echo $zeile->HALLE_TITLE; ?>"
	   target="_blank">
		
		<img src="https://maps.googleapis.com/maps/api/staticmap
					?center=<?php echo urlencode($zeile->HALLE_ADRESSE); ?>
					&amp;zoom=13
					&amp;size=600x300
					&amp;maptype=roadmap" />
	</a>
	
	<br />

	<a href="" title="<?php echo $zeile->HALLE_TITLE; ?>" target="_blank">
		<img src="https://maps.googleapis.com/maps/api/streetview
					?size=600x300
					&amp;location=<?php echo urlencode($zeile->HALLE_ADRESSE); ?>
					&amp;fov=120
					&amp;heading=235
					&amp;pitch=0"
		/>
	</a>

</div>
