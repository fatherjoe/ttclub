<?php
 
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

// JS FÜR DIE LIGHTBOX
if ($this->params->popups_foto == "lightbox2")
{
		?>
		<script src="<?php echo JUri::base() . 'media/com_webtt/js/lightbox2/lightbox.js'; ?>" type="text/javascript"></script>
		<?php
}

if ($this->params->popups_foto == "milkbox")
{
		?>
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
	<div id="spielerliste">
	<table class="table table-striped table-hover">
		<thead>
		<tr>
			<th class="lnr">
				LNr
			</th>
			<th class="spielerfoto">
				Foto
			</th>
			<th class="name">
				Spieler
			</th>
			<th class="altersklasse">
				Altersklasse
			</th>
			<th class="mannschaft">
				Mannschaft
			</th>
			<th class="position">
				Position
			</th>
			<th class="qttr">
				QTTR
			</th>
			<th class="ttr">
				TTR
			</th>
		</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="5">

				</td>
			</tr>
		</tfoot>
		<tbody>
			<?php if (!empty($this->items)) : ?>
				<?php foreach ($this->items as $i => $row) { ?>
					<tr>
						<td class="lnr">
							<?php echo $i + 1; ?>
						</td>
						
						<?php // SPALTE : SPIELERFOTO
						if ($this->params->anz_foto)
						{
						?>
						<td class="spielerfoto">
								<?php
								if (isset($row->foto) && strlen($row->foto))
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
													src="<?php echo JURI::base() . 'media/com_webtt/thumbnails/aufstellung/' . $row->foto; ?>"
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
						
						<td class="name">
							<?php echo $row->name; ?>
						</td>
						<td class="altersklasse">
							<?php echo $row->ak; ?>
						</td>
						<td class="mannschaft">
							<?php echo $row->team; ?>
						</td>
						<td class="position">
							<?php echo $row->position; ?>
						</td>
						<td class="qttr">
							<?php if (isset($this->qttr[trim($row->name)])) { echo $this->qttr[trim($row->name)]; } ?>
						</td>
						<td class="ttr">
							<?php if (isset($this->ttr[trim($row->name)])) { echo $this->ttr[trim($row->name)]; } ?>
						</td>
					</tr>
				<?php } ?>
			<?php endif; ?>
		</tbody>
	</table>
	</div>
</div>
