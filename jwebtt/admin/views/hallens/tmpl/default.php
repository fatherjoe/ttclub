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
<form action="index.php?option=com_webtt&view=hallens" method="post" id="adminForm" name="adminForm">
	<table class="table table-striped table-hover">
		<thead>
		<tr>
			<th width="1%">
				<?php echo JText::_('COM_WEBTT_NUM'); ?>
			</th>
			<th width="2%">
				<?php echo JHtml::_('grid.checkall'); ?>
			</th>
			<th width="15%">
				<?php echo JText::_('COM_WEBTT_HALLENS_VEREIN') ;?>
			</th>
			<th width="15%">
				<?php echo JText::_('COM_WEBTT_HALLENS_HALLE_1') ;?>
			</th>
			<th width="15%">
				<?php echo JText::_('COM_WEBTT_HALLENS_ADDR_1') ;?>
			</th>
			<th width="15%">
				<?php echo JText::_('COM_WEBTT_HALLENS_HALLE_2') ;?>
			</th>
			<th width="15%">
				<?php echo JText::_('COM_WEBTT_HALLENS_ADDR_2'); ?>
			</th>
			<th width="15%">
				<?php echo JText::_('COM_WEBTT_HALLENS_HALLE_3') ;?>
			</th>
			<th width="15%">
				<?php echo JText::_('COM_WEBTT_HALLENS_ADDR_3') ;?>
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
					$link = JRoute::_('index.php?option=com_webtt&task=hallen.edit&id=' . $row->id);				
				?>
					<tr>
						<td>
							<?php echo $this->pagination->getRowOffset($i); ?>
						</td>
						<td>
							<?php echo JHtml::_('grid.id', $i, $row->id); ?>
						</td>
						<td>
							<a href="<?php echo $link; ?>" title="<?php echo JText::_('COM_WEBTT_EDIT_HALLENS'); ?>">
								<?php echo $row->verein; ?>
							</a>
						</td>
						<td align="center">
							<?php echo $row->halle_1; ?>
						</td>
						<td align="center">
							<?php echo $row->addr_1; ?>
						</td>
						<td align="center">
							<?php echo $row->halle_2; ?>
						</td>
						<td align="center">
							<?php echo $row->addr_2; ?>
						</td>
						<td align="center">
							<?php echo $row->halle_3; ?>
						</td>
						<td align="center">
							<?php echo $row->addr_3; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
	<input type="hidden" name="task" value="hallen.edit"/>	
	<input type="hidden" name="boxchecked" value="0"/>	
	<?php echo JHtml::_('form.token'); ?>
</form>
