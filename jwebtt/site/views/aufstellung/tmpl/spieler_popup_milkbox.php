<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_webtt
 *
 */
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');



?>
<a	href="<?php echo JURI::base() . 'media/com_webtt/tmp/person_' . $zeile->SPIELER . '.html'; ?>" 
	data-milkbox="spielerdetails"
	data-milkbox-size="width:750,height:550">
	<?php echo $zeile->SPIELER; ?>
</a>

