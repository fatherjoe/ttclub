<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
 
// load tooltip behavior
JHtml::_('behavior.tooltip');
?>

<form action="index.php?option=com_webtt&view=staffelnamens" method="post" id="adminForm" name="adminForm">
	<table class="table table-striped table-hover">
		<thead>
		<tr>
			<th width="1%">
				<?php echo JText::_('COM_WEBTT_NUM'); ?>
			</th>
			<th width="2%">
				<?php echo JHtml::_('grid.checkall'); ?>
			</th>
			<th width="5%">
				Typ
			</th>
			<th width="30%">
				<?php echo JText::_('COM_WEBTT_QTTRS_DATUM') ;?>
			</th>
			<th width="30%">
				<?php echo JText::_('COM_WEBTT_QTTRS_WERTE'); ?>
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
					$link = JRoute::_('index.php?option=com_webtt&task=qttr.edit&id=' . $row->id);				
				?>
					<tr>
						<td>
							<?php echo $this->pagination->getRowOffset($i); ?>
						</td>
						<td>
							<?php echo JHtml::_('grid.id', $i, $row->id); ?>
						</td>
						<td>
							<?php echo strtoupper($row->typ); ?>
						</td>
						<td>
							<a href="<?php echo $link; ?>" title="<?php echo JText::_('COM_WEBTT_EDIT_QTTR'); ?>">
								<?php echo $row->datum; ?>
							</a>
						</td>
						<td align="center">
							<textarea rows="5" cols="50" readonly>
								<?php echo $row->werte; ?>
							</textarea>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
	<input type="hidden" name="task" value=""/>	
	<input type="hidden" name="boxchecked" value="0"/>	
	<?php echo JHtml::_('form.token'); ?>
</form>
