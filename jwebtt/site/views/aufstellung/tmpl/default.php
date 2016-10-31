<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_webtt
 *
 */
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// JS FÜR DIE LIGHTBOX
if ($this->params->popups_foto == "lightbox2")
{
		?>
		<script src="<?php echo JUri::base() . 'media/com_webtt/js/lightbox2/lightbox.js'; ?>" type="text/javascript"></script>
		<?php
}

if ($this->params->popups_foto == "milkbox" OR $this->params->popups_spieler == "milkbox")
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
	<div id="aufstellung">

		<?php
		// ÜBERSCHRIFTEN
		echo $this->title_1;
		echo $this->title_2;
		echo $this->title_3;
		
		?>
		<table class="table">
			<thead>
				<tr>
					<?php
					// SPALTE : POSITION
					if ($this->params->anz_pos) { ?>
					<th class="position" title="Position">P</th>
					<?php } ?>
					
					<?php
					// SPALTE : FOTO
					if ($this->params->anz_foto) { ?>
					<th class="foto" title="Foto">Foto</th>
					<?php } ?>
					  
					<?php
					// SPALTEN : SPIELER
					?>
					<th class="spieler">Spieler</th>

					<?php
					// SPALTE : EINSÄTZE
					if ($this->params->anz_eins) { ?>
					<th class="einsaetze">Einsätze</th>
					<?php } ?>

					<?php
					// SPALTE : BILANZ
					if ($this->params->anz_bilanz) { ?>
					<th class="bilanz">Bilanz</th>
					<?php } ?>

					<?php
					// SPALTE : BILANZWERT
					if ($this->params->anz_bw) { ?>
					<th class="bilanzwert" title="Bilanzwert">BW</th>
					<?php } ?>

					<?php
					// SPALTE : QTTR
					if ($this->params->anz_qttr) { ?>
					<th class="qttr">QTTR</th>
					<?php } ?>

					<?php
					// SPALTE : TTR
					if ($this->params->anz_ttr) { ?>
					<th class="ttr">TTR</th>
					<?php } ?>

				</tr>
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
		  <?php foreach ($this->xml as $zeile) {
			  $team = $zeile->team; ?>
			<tr>
				<td class="position"><?php echo $zeile->POSITION; ?></td>

					<?php
					// SPALTE : FOTO
					if ($this->params->anz_foto)
					{
							?>
							<td class="foto" title="Foto">
							<?php
							if (isset($this->spielerfotos["$zeile->SPIELER"]['foto']) && strlen($this->spielerfotos["$zeile->SPIELER"]['foto']))
							{ 
									if ($this->params->popups_foto == "lightbox2")
									{
											include "foto_lightbox_lightbox2.php";
									}
									
									else if ($this->params->popups_foto == "milkbox")
									{
											include "foto_lightbox_milkbox.php";
									}
									
									else if ($this->params->popups_foto == "")
									{
											?>
											<img
												src="<?php echo JURI::base() . 'media/com_webtt/thumbnails/aufstellung/' . $this->spielerfotos["$zeile->SPIELER"]['foto']; ?>"
												height="<?php echo $this->params->thumb_height; ?>"
												width="<?php echo $this->params->thumb_width; ?>"
											/>
											<?php
									}
							}
							?>
							</td>
							<?php
					}
					?>

					<td class="spieler">
						<?php
						if ($this->params->popups_spieler == "" OR $this->params->popups_spieler == "css")
						{
								?>
								<a href="<?php echo $this->HostClicktt . $zeile->SPIELER_PFAD; ?>" target="_blank">
									<?php echo $zeile->SPIELER; ?> 
								</a>
								<?php
						}
						
						// CSS-AUFKLAPPBOX
						if ($this->params->popups_spieler == "css")
						{
								include "spieler_popup_css.php";
						}

						// SPIELERPAARUNGEN IN JAVASCRIPT-LIGHTBOX (nur mootools)
						if ($this->params->popups_spieler == "milkbox")
						{
								include "spieler_popup_milkbox.php";
						}
						?>
					</td>

				<?php
				// SPALTE : ANZAHL DER EINSÄTZE
				if ($this->params->anz_eins) { ?>
				<td class="einsaetze"><?php echo $zeile->EINSAETZE; ?></td>
				<?php } ?>

				<?php
				// SPALTE : BILANZ
				if ($this->params->anz_bilanz) { ?>
				<td class="bilanz"><?php echo $zeile->BILANZ; ?></td>
				<?php } ?>

				<?php
				// SPALTE : BILANZWERT
				if ($this->params->anz_bw) { ?>
				<td class="bilanzwert"><?php echo $zeile->BILANZWERT; ?></td>
				<?php } ?>

				<?php
				// SPALTE : QTTR
				if ($this->params->anz_qttr) { ?>
				<td class="qttr">
				<?php if (isset($this->qttr[trim($zeile->SPIELER)])) { echo $this->qttr[trim($zeile->SPIELER)]; } ?>
				</td>
				<?php } ?>

				<?php
				// SPALTE : TTR
				if ($this->params->anz_ttr) { ?>
				<td class="ttr">
				<?php if (isset($this->ttr[trim($zeile->SPIELER)])) { echo $this->ttr[trim($zeile->SPIELER)]; } ?>
				</td>
				<?php } ?>

			</tr>
		  <?php } ?>
		  </tbody>
		</table>
	</div>
</div>
