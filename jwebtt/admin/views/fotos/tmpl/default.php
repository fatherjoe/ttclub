<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_webtt
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
 
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
?>
<form action="index.php?option=com_webtt&amp;view=fotos" method="post" id="adminForm" name="adminForm">
	<table class="table table-striped table-hover">
		<thead>
		<tr>
			<th width="1%">
				<?php echo JText::_('COM_WEBTT_NUM'); ?>
			</th>
			<th width="20%">
				Spieler
			</th>
			<th width="10%">
				Altersklasse
			</th>
			<th width="2%">
				Mannschaft
			</th>
			<th width="2%">
				Position
			</th>
			<th width="20%">
				Liste
			</th>
			<th width="20%">
				Foto
			</th>
			<th>
				Beschreibung
			</th>
		</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="5">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<?php if (!empty($this->items)) : ?>
				<?php foreach ($this->items as $i => $row) :
					$link = JRoute::_('administrator/components/com_webtt/fotos/' . $row->name . '.jpg');				
				?>
					<tr>
						<td>
							<?php echo $this->pagination->getRowOffset($i); ?>
						</td>
						<td>
							<?php echo $row->name; ?>
						</td>
						<td align="center">
							<?php echo $row->ak; ?>
						</td>
						<td align="center">
							<?php echo $row->team; ?>
						</td>
						<td align="center">
							<?php echo $row->position; ?>
						</td>
						<td align="center">
								<select type="" id="<?php echo $row->id; ?>" name="filedata[<?php echo $row->clicktt_nr; ?>]">
									<option></option>
								<?php foreach($this->get('Imagelist') as $filename) { ?>
									<option
									<?php if ($row->foto == $filename) { echo ' selected'; } ?>
									>
									<?php echo $filename; ?></option>
								<?php } ?>
								</select>								
						</td>
						<td align="center">
							<?php if ($row->foto) { ?>
							<img src="<?php echo JURI::root() . "images/" . $row->foto; ?>" width="100px" height="100px"/>
							<?php } ?>
						</td>
						<td>
							<textarea name="beschreibungen[<?php echo $row->clicktt_nr; ?>]" cols="150" rows="20">
								<?php echo $row->beschreibung; ?>
							</textarea>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
	<input type="hidden" name="task" value=""/>		
	<?php echo JHtml::_('form.token'); ?>
</form>
