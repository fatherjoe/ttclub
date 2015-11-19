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
			<th width="200" class="title">
				<?php echo JText::_( "Altersklasse" ); ?>
			</th>
			<th width="90px" align="center">
				<?php echo JText::_( "Min alter" ); ?>
			</th>
			<th width="90px" align="center">
				<?php echo JText::_( "Max alter" ); ?>
			</th>
			<th width="90px" align="center">
				<?php echo JText::_( "Reihenfolge" ); ?>	
			</th>
			<th>&nbsp;</th>
		</tr>

	</thead>
	<?php
	$k = 0;
	for ($i=0, $n=count( $this->items ); $i < $n; $i++)
	{
		$row = $this->items[$i];

		
		$checked 	= JHTML::_('grid.id',   $i, $row->id );
		$link 		= JRoute::_( 'index.php?option=com_ttverein&controller=altersklassen&task=edit&cid[]='. $row->id, false );

		?>
		<tr class="<?php echo "row$k"; ?>">
			<td>
				<?php echo $row->id; ?>
			</td>
			<td>
				<?php echo $checked; ?>
			</td>
			<td>
				<a href="<?php echo $link; ?>"><?php echo $row->name; ?></a>
			</td>
			<td>
				<?php echo $row->minalter; ?>
			</td>
			<td>
				<?php echo $row->maxalter; ?>
			</td>
			<td align="center">
				<?php echo $row->reihenfolge; ?>
			</td>
			<td>&nbsp;</td>
		</tr>
		<?php
		$k = 1 - $k;
	}
	?>
	</table>
</div>

<input type="hidden" name="option" value="com_ttverein" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="controller" value="altersklassen" />
<?php echo JHtml::_('form.token'); ?>
</form>
