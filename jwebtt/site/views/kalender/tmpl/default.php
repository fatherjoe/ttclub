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
<form action="index.php?option=com_webtt&view=kalender" method="post" id="adminForm" name="adminForm">
	<table class="table table-striped table-hover">
		<thead>
		<tr>
			<th width="1%">
				LNr
			</th>
			<th>
				Datum
			</th>
			<th>
				Mannschaft
			</th>
			<th>
				Hinserie
			</th>
			<th>
				Rückserie
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
					$link_vcal = JRoute::_('index.php?option=com_webtt&view=kalender&typ=vcal&mannschaft=' . $row->mannschaft . '&format=raw');				
					$link_ical = JRoute::_('index.php?option=com_webtt&view=kalender&typ=ical&mannschaft' . $row->mannschaft . '&format=raw');				
				?>
					<tr>
						<td>
							<?php echo $this->pagination->getRowOffset($i); ?>
						</td>
						<td>
							<?php echo $row->datum; ?>
						</td>
						<td align="center">
							<?php echo $row->mannschaft; ?>
						</td>
						<td>
							<a href="<?php echo $link_vcal; ?>" title="<?php echo JText::_('COM_WEBTT_KALENDER_DOWNLOAD_VCAL'); ?>">
								vcal
							</a>

							<a href="<?php echo $link_ical; ?>" title="<?php echo JText::_('COM_WEBTT_KALENDER_DOWNLOAD_ICAL'); ?>">
								ical
							</a>
						</td>
						<td>
							<a href="<?php echo $link_ical; ?>" title="<?php echo JText::_('COM_WEBTT_KALENDER_DOWNLOAD_ICAL'); ?>">
								ical
							</a>
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
