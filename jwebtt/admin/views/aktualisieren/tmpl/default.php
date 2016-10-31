<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
 
// load tooltip behavior
JHtml::_('behavior.tooltip');
?>
<form action="<?php echo JRoute::_('index.php?option=com_webtt&view=aktualisieren'); ?>" method="post" name="adminForm" id="adminForm">
		<input type="submit" value="Mannschaften aktualisieren" />
        <div>
                <input type="hidden" name="task" value="aktualisieren.get_teams" />
                <?php echo JHtml::_('form.token'); ?>
        </div>
</form>

<form action="<?php echo JRoute::_('index.php?option=com_webtt&view=aktualisieren'); ?>" method="post" name="adminForm" id="adminForm">
		<input type="submit" value="Spieler aktualisieren" />
        <div>
                <input type="hidden" name="task" value="aktualisieren.get_spieler" />
                <?php echo JHtml::_('form.token'); ?>
        </div>
</form>

<form action="<?php echo JRoute::_('index.php?option=com_webtt&view=aktualisieren'); ?>" method="post" name="adminForm" id="adminForm">
		<input type="submit" value="Spiellokale (auswärts) aktualisieren" />
        <div>
                <input type="hidden" name="task" value="aktualisieren.get_hallen_ausw" />
                <?php echo JHtml::_('form.token'); ?>
        </div>
</form>
