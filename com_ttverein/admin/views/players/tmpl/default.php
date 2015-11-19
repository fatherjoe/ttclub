<?php defined('_JEXEC') or die('Restricted access'); ?>

<form id="adminForm" action="index.php" method="post" name="adminForm">
<div id="editcell">
	<table class="adminlist">
	<thead>
		<tr>
		<tr>
			<th width="5">
				<?php echo JText::_( 'NUM' ); ?>
			</th>
			<th width="20">
				<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
			</th>
			<th width="200">
				<?php echo JHTML::_('grid.sort',   'Name', 'spieler.nachname', @$lists['order_Dir'], @$this->lists['order'] ); ?>
			</th>
			<th width="5%" align="center">
				<?php echo JHTML::_('grid.sort',   'Published', 'spieler.published', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
			</th>
			<th width="1%" nowrap="nowrap">
				<?php echo JHTML::_('grid.sort',   'ID', 'spieler.id', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
			</th>
			<th>&nbsp;</th>
		</tr>

	</thead>
	<?php
	$k = 0;
	for ($i=0, $n=count( $this->items ); $i < $n; $i++)
	{
		$row = $this->items[$i];

		$published	= JHTML::_('grid.published', $row, $i );
		$checked 	= JHTML::_('grid.id',   $i, $row->id );
		$link 		= JRoute::_( 'index.php?option=com_ttverein&controller=players&task=edit&cid[]='. $row->id , false);

		?>
		<tr class="<?php echo "row$k"; ?>">
			<td>
				<?php echo $i+1; ?>
			</td>
			<td>
				<?php echo $checked; ?>
			</td>
			<td>
				<a href="<?php echo $link; ?>"><?php echo $row->nachname . ", " . $row->vorname; ?></a>
			</td>
			<td align="center">
				<?php echo $published;?>
			</td>
			<td align="center">
				<?php echo $row->id; ?>
			</td>
			<td>&nbsp;</td>
		</tr>
		<?php
		$k = 1 - $k;
	}
	?>
	</table>	
</div>
<div>
	<input type="hidden" name="option" value="com_ttverein" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="controller" value="players" />
	<?php echo JHtml::_('form.token'); ?>
</div>
</form>
