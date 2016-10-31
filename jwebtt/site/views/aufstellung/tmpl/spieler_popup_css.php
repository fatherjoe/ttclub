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

	<?php
		// Tabelle mit Einzel
		if ($this->popups->{$zeile->SPIELER}->xml)
		{
				?>
				<table>
					<caption>
						<?php echo $zeile->SPIELER; ?>
					</caption>
					<thead>
						<tr>
							<?php
							if ($this->params->anz_spieler_box_datum)
							{
									?>
									<th class="datum">
										Datum
									</th>
									<?php
							}

							if ($this->params->anz_spieler_box_paarung)
							{
									?>
									<th class="paarung">
										Paarung
									</th>
									<?php
							}
							?>

							<th class="gegner">
								Gegner
							</th>

							<th class="saetze">
								Sätze
							</th>

							<?php
							if ($this->params->anz_spieler_box_gegnerteam)
							{
									?>
									<th class="gegnerteam">
										Gegner-Team
									</th>
									<?php
							}

							if ($this->params->anz_spieler_box_erg)
							{
									?>
									<th class="ergebnis">
										Ergebnis
									</th>
									<?php
							}
							?>

						</tr>
					</thead>
					
					<tbody>
						<?php foreach ($this->popups->{$zeile->SPIELER}->xml as $spielerdaten) { ?>
						
						<?php if (isset($spielerdaten->STAFFEL)) { ?>
						<tr>
							<th colspan="10" class="staffel">
								<?php echo $spielerdaten->STAFFEL; ?>										
							</th>
						</tr>
						<?php } ?>
						
						<tr>
							<?php if ($this->params->anz_spieler_box_datum) { ?>
							<td class="datum">
								<?php echo $spielerdaten->DATUM; ?>
							</td>
							<?php } ?>

							<?php if ($this->params->anz_spieler_box_paarung) { ?>
							<td class="paarung">
								<?php echo $spielerdaten->PAARUNG; ?>
							</td>
							<?php } ?>

							<td class="saetze">
								<?php echo $spielerdaten->SAETZE; ?>
							</td>

							<td class="gegner">
								<?php echo $spielerdaten->GEGNER; ?>
							</td>

							<?php if ($this->params->anz_spieler_box_gegnerteam) { ?>
							<th class="bw">
								<?php echo $spielerdaten->GEGNER_TEAM; ?>
							</th>
							<?php } ?>

							<?php if ($this->params->anz_spieler_box_erg) { ?>
							<th class="qttr">
								<?php echo $spielerdaten->ERGEBNIS; ?>
							</th>
							<?php } ?>
						</tr>
						<?php } ?>
					</tbody>
				</table>
	<?php }
	
	// Noch keine Einzel
	else { ?>
		<div class="">
			Am <?php echo date("d.m.y", strtotime($this->popups->{$zeile->SPIELER}->datum)); ?> noch keine gespielten Einzel
		</div>
		<?php } ?>
</div>


