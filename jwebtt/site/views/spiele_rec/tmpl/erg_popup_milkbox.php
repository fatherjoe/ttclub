<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_webtt
 *
 */
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');



?>
<a	href="<?php echo JURI::base() . 'media/com_webtt/tmp/meeting_' . $zeile->MEETING . '.html'; ?>" 
	data-milkbox="spielbericht"
	data-milkbox-size="width:1000,height:500">
	<?php echo $zeile->ERGEBNIS; ?>
</a>

