<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_webtt
 *
 */
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');


if ($this->params->popups_mannschaften == "milkbox")
{
		?>
		<script src="<?php echo JUri::base() . 'media/com_webtt/js/milkbox/mootools-core.js'; ?>" type="text/javascript"></script>
		<script src="<?php echo JUri::base() . 'media/com_webtt/js/milkbox/mootools-more.js'; ?>" type="text/javascript"></script>
		<script src="<?php echo JUri::base() . 'media/com_webtt/js/milkbox/milkbox.js'; ?>" type="text/javascript"></script>
		<?php
}

// MENÜTITEL ALS CONTENT TITLE
if (isset(JFactory::getApplication()->getMenu()->getActive()->title))
{
		?>
		<div class="page-header">
			<h1><?php echo JFactory::getApplication()->getMenu()->getActive()->title; ?></h1>
		</div>
		<?php
}

?>
<div id="webtt">	
	<div id="tabelle">

		<?php
		// ÜBERSCHRIFTEN
		echo $this->title_1;
		echo $this->title_2;
		echo $this->title_3;
		
		?>

		<table class="table hover">
			<thead>
				<tr>
					<?php
					// SPALTE : TENDENZ
					if ($this->params->anz_tendenz)
					{
							?><th class="tendenz" title="Tendenz">T</th><?php
					}
					
					?>					
					<th class="rang">Rang</th>
					<th class="mann">Mannschaft</th>
					<?php

					// SPALTE : ANZAHL DER BEGEGNUNGEN
					if ($this->params->anz_begegnungen)
					{
							?><th class="begegnungen" title="Anzahl der Begegnungen">Beg</th><?php
					}
					  

					// SPALTEN : DETAILS
					if ($this->params->anz_details)
					{
							?><th class="siege" title="Siege">S</th>
							<th class="unentschieden" title="Unentschieden">U</th>
							<th class="niederlagen" title="Niederlagen">N</th><?php
					}


					// SPALTE : SPIELVERHÄLTNIS
					if ($this->params->anz_spiele)
					{
							?><th class="spiele">Spiele</th><?php
					}


					// SPALTE : SPIELEDIFFERENZ
					if ($this->params->anz_diff)
					{
							?><th class="differenz" title="Differenz">Diff</th><?php
					}


					// SPALTE : PUNKTENVERHÄLTNIS
					if ($this->params->anz_punkte)
					{
							?><th class="punkte">Punkte</th><?php
					}
					
				?></tr>
			</thead>
		  
			<tfoot>
				<tr>
					<td colspan="10">
						<span class="backlink">
							Erstellt mit <a href="http://webtt.de/" title="Lassen Sie Ihre Homepage mit den Daten von clicktt füttern">Web-TT</a> | 
						</span>
						<span title="Nächste Aktualisierung: <?php echo date("d.m. H:i", strtotime($this->timestamp) + ( $this->params->akt * 3600)); ?>">
							Stand: <?php echo date("d.m. H:i", strtotime($this->timestamp)); ?>
						</span>
					</td>
				</tr>	
		  </tfoot>
		  
		  <tbody>
		  <?php
		  foreach ($this->xml as $zeile)
		  {
				$team = $zeile->team;
				?><tr><?php

				// SPALTE : TENDENZ
				if ($this->params->anz_tendenz)
				{
						?><td class="tendenz"><?php

						if ($zeile->TENDENZ == "up")
						{
							echo "&uArr;";
						}
						if ($zeile->TENDENZ == "down")
						{
							echo "&dArr;";
						}
						if ($zeile->TENDENZ == "rel_up")
						{
							echo "&uarr;";
						}
						if ($zeile->TENDENZ == "rel_down")
						{
							echo "&darr;";
						}
						
						?></td><?php
				}
				
				// SPALTE : RANG
				?><td class="rang"><?php echo $zeile->RANG; ?></td><?php


				// SPALTE : MANNSCHAFT
				?><td class="mann"><?php
				
				if ($this->params->popups_mannschaften == "" OR $this->params->popups_mannschaften == "css")
				{
						?>
						<a href="<?php echo $this->HostClicktt . $zeile->TEAM_PATH; ?>" target="_blank">
							<?php echo $zeile->TEAM; ?>
						</a>
						<?php
				}

				// AUFKLAPPBOX CSS
				if ($this->params->popups_mannschaften == "css")
				{
						include "mannschaft_popup_css.php";
				}

				// MANNSCHAFTSAUFSTELLUNGEN IN JAVASCRIPT-LIGHTBOX (nur mootools)
				if ($this->params->popups_mannschaften == "milkbox")
				{
						include "mannschaft_popup_milkbox.php";
				}
				
				?></td><?php


				// SPALTE : ANZAHL DER BEGEGNUNGEN
				if ($this->params->anz_begegnungen)
				{
						?><td class="begegnungen"><?php echo $zeile->BEGEGNUNGEN; ?></td><?php
				}


				// SPALTEN : DETAILS
				if ($this->params->anz_details)
				{
						?>
						<td class="siege"><?php echo $zeile->DETAILS->SIEGE; ?></td>
						<td class="unentschieden"><?php echo $zeile->DETAILS->UNENTSCHIEDEN; ?></td>
						<td class="niederlagen"><?php echo $zeile->DETAILS->NIEDERLAGEN; ?></td>
						<?php
				}


				// SPALTE : SPIELVERHÄLTNIS
				if ($this->params->anz_spiele)
				{
						?><td class="spiele"><?php echo $zeile->SPIELE; ?></td><?php
				}


				// SPALTE : SPIELEDIFFERENZ
				if ($this->params->anz_diff)
				{
						?><td class="differenz"><?php echo $zeile->DIFFERENZ; ?></td><?php
				}


				// SPALTE : PUNKTEVERHÄLTNIS
				if ($this->params->anz_punkte)
				{
						?><td class="punkte"><?php echo $zeile->PUNKTE; ?></td><?php
				}
				
				?></tr><?php
			}
			
			?></tbody>
		</table>
	</div>
</div>
