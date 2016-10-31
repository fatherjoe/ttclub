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
	<table>
		<caption>
			<?php echo $zeile->TEAM; ?>
		</caption>
		<thead>
			<tr>
				<?php if ($this->params->anz_mann_box_pos) { ?>
				<th class="pos">
					Pos
				</th>
				<?php } ?>
				<th class="spieler">
					Spieler
				</th>
				<?php if ($this->params->anz_mann_box_bil) { ?>
				<th class="bilanz">
					Bilanz
				</th>
				<?php } ?>
				<?php if ($this->params->anz_mann_box_bw) { ?>
				<th class="bw" title="Bilanzwert">
					BW
				</th>
				<?php } ?>
				<?php if ($this->params->anz_mann_box_qttr) { ?>
				<th class="qttr">
					QTTR
				</th>
				<?php } ?>
			</tr>
		</thead>
		
		<tbody>
			<?php foreach ($this->popups->{$zeile->TEAM}->xml as $spielerdaten) { ?>
			<tr>
				<?php if ($this->params->anz_mann_box_pos) { ?>
				<td class="pos">
					<?php echo $spielerdaten->POSITION; ?>
				</td>
				<?php } ?>
				<td class="spieler">
					<?php echo $spielerdaten->SPIELER; ?>
				</td>
				<?php if ($this->params->anz_mann_box_bil) { ?>
				<td class="bilanz">
					<?php echo $spielerdaten->BILANZ; ?>
				</td>
				<?php } ?>
				<?php if ($this->params->anz_mann_box_bw) { ?>
				<th class="bw">
					<?php echo $spielerdaten->BILANZWERT; ?>
				</th>
				<?php } ?>
				<?php if ($this->params->anz_mann_box_qttr) { ?>
				<th class="qttr">
					<?php echo $spielerdaten->QTTR; ?>
				</th>
				<?php } ?>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</div>

