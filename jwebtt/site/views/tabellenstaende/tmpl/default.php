<?php
 
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');



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
	<div id="tabst">
	<table class="table table-striped table-hover">
		<thead>
		<tr>
			<th>
				Mannschaft
			</th>
			<th>
				Staffel
			</th>
			<th>
				Punkte
			</th>
			<th>
				Platz
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
				<?php foreach ($this->xml->ZEILE as $row) { ?>
					<tr>
						<td>
							<?php echo $row->TEAM; ?>
						</td>
						<td>
							<?php echo $row->STAFFEL; ?>
						</td>
						<td align="center">
							<?php echo $row->PUNKTE; ?>
						</td>
						<td align="center">
							<?php echo $row->PLATZ; ?>
						</td>
					</tr>
				<?php } ?>
		</tbody>
	</table>
	</div>
</div>
