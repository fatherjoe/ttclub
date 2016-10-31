<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_webtt
 *
 */
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');


?>
<a	href="<?php echo JURI::base() . 'media/com_webtt/tmp/mannschaft_' . $zeile->TEAM . '.html'; ?>" 
	data-milkbox="aufstellungen"
	data-milkbox-size="width:750,height:400">
	<?php echo $zeile->TEAM; ?>
</a>
