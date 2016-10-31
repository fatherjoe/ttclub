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
<form action="index.php?option=com_webtt&view=mannschaftens" method="post" id="adminForm" name="adminForm">
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
				<?php echo JText::_('COM_WEBTT_MANNSCHAFTENS_NAME_WEBTT') ;?>
			</th>
			<th width="15%">
				<?php echo JText::_('COM_WEBTT_MANNSCHAFTENS_NAME_CLICKTT') ;?>
			</th>
			<th width="15%">
				<?php echo JText::_('COM_WEBTT_MANNSCHAFTENS_NAME_SP_CLICKTT') ;?>
			</th>
			<th width="15%">
				<?php echo JText::_('COM_WEBTT_MANNSCHAFTENS_PATH_CLICKTT') ;?>
			</th>
			<th width="15%">
				<?php echo JText::_('COM_WEBTT_MANNSCHAFTENS_LEAGUE_CLICKTT'); ?>
			</th>
			<th width="15%">
				<?php echo JText::_('COM_WEBTT_MANNSCHAFTENS_PATH_LEAGUE_CLICKTT') ;?>
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
					$link = JRoute::_('index.php?option=com_webtt&task=mannschaften.edit&id=' . $row->id);				
				?>
					<tr>
						<td>
							<?php echo $this->pagination->getRowOffset($i); ?>
						</td>
						<td>
							<?php echo JHtml::_('grid.id', $i, $row->id); ?>
						</td>
						<td>
							<a href="<?php echo $link; ?>" title="<?php echo JText::_('COM_WEBTT_EDIT_MANNSCHAFTENS'); ?>">
								<?php echo $row->name_webtt; ?>
							</a>
						</td>
						<td align="center">
							<?php echo $row->name_clicktt; ?>
						</td>
						<td align="center">
							<?php echo $row->name_sp_clicktt; ?>
						</td>
						<td align="center">
							<?php echo $row->path_clicktt; ?>
						</td>
						<td align="center">
							<?php echo $row->league_clicktt; ?>
						</td>
						<td align="center">
							<?php echo $row->path_league_clicktt; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
	<input type="hidden" name="task" value="fotos.upload"/>	
	<input type="hidden" name="boxchecked" value="0"/>	
	<?php echo JHtml::_('form.token'); ?>
</form>
