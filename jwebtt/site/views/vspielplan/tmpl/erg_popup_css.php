<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_webtt
 *
 */
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');



echo $zeile->ERGEBNIS;
?>

<div class="box">
	<table>
		
		<caption>
			<?php echo $zeile->HEIM . " - " . $zeile->GAST; ?>
		</caption>
		
		<thead>
			<tr>
				<?php // if ($this->params->anz_erg_box_paarung) { ?>
				<th class="paarung">
					Paarung
				</th>
				<?php // } ?>

				<th class="satz">
					1
				</th>

				<th class="satz">
					2
				</th>

				<th class="satz">
					3
				</th>

				<th class="satz">
					4
				</th>

				<th class="satz">
					5
				</th>

				<th class="saetze">
					Sätze
				</th>

				<th class="punkte">
					Punkte
				</th>

				<?php // if ($this->params->anz_erg_box_erg) { ?>
				<th class="ergebnis">
					Ergebnis
				</th>
				<?php // } ?>
			</tr>
		</thead>
		
		<tbody>
			<?php foreach ($this->popups->{$zeile->MEETING}->xml as $spielbericht) { ?>
			
			<?php if (isset($spielbericht->STAFFEL)) { ?>
			<tr>
				<th colspan="10" class="staffel">
					<?php echo $spielbericht->STAFFEL; ?>										
				</th>
			</tr>
			<?php } ?>
			
			<tr>
				<?php // if ($this->params->anz_erg_box_paarung) { ?>
				<td class="datum">
					<?php echo $spielbericht->PAARUNG; ?>
				</td>
				<?php // } ?>

				<td class="spieler">
					<?php echo $spielbericht->HEIMSPIELER; ?>
				</td>

				<td class="spieler">
					<?php echo $spielbericht->GASTSPIELER; ?>
				</td>

				<?php // if ($this->params->anz_erg_box_satzerg) { ?>
				<td class="datum">
					<?php echo $spielbericht->SATZERGEBNISSE->SATZ_1; ?>
				</td>
				<td class="datum">
					<?php echo $spielbericht->SATZERGEBNISSE->SATZ_2; ?>
				</td>
				<td class="datum">
					<?php echo $spielbericht->SATZERGEBNISSE->SATZ_3; ?>
				</td>
				<td class="datum">
					<?php echo $spielbericht->SATZERGEBNISSE->SATZ_4; ?>
				</td>
				<td class="datum">
					<?php echo $spielbericht->SATZERGEBNISSE->SATZ_5; ?>
				</td>
				<?php // } ?>

				<td class="saetze">
					<?php echo $spielbericht->SAETZE; ?>
				</td>

				<td class="punkte">
					<?php echo $spielbericht->PUNKTE; ?>
				</td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</div>

