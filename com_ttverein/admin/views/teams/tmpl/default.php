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
			<th width="5%" align="center">
				<?php echo JHTML::_('grid.sort',   'Altersklasse', 'altersklassen.name', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
			</th>
			<th  class="title">
				<?php echo JHTML::_('grid.sort',   'Mannschaft', 'mannschaften.nummer', @$lists['order_Dir'], @$this->lists['order'] ); ?>
			</th>
			
			
			<th width="5%" align="center">
				<?php echo JHTML::_('grid.sort',   'Published', 'mannschaften.published', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
			</th>
			<th width="1%" nowrap="nowrap">
				<?php echo JHTML::_('grid.sort',   'ID', 'mannschaften.id', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
			</th>
		</tr>

	</thead>
	<?php
	$lastSaison = "";
	$k = 0;
	for ($i=0, $n=count( $this->items ); $i < $n; $i++)
	{
		$row = $this->items[$i];

		if($lastSaison != $row->saisonstart) {
			$lastSaison = $row->saisonstart;
			?>
			<tr>
			<th colspan="6" align="left">
				<h2> Saison <?php echo $row->saisonstart . '/' . substr(($row->saisonstart + 1),2) ;?></h2>
			</th>			
			</tr>
			<?php
		}

		$published  = JHTML::_('grid.published', $row, $i );
		$checked    = JHTML::_('grid.id',   $i, $row->id );
		$link       = JRoute::_( 'index.php?option=com_ttverein&controller=teams&task=edit&cid[]='. $row->id, false );

		if($row->hinrunde == 1)
			$runde = "Hinrunde";
		else if($row->hinrunde == 0)
			$runde = "R&uuml;ckrunde";	
		?>
		<tr class="<?php echo "row$k"; ?>">
			<td>
				<?php echo $i+1; ?>
			</td>
			<td>
				<?php echo $checked; ?>
			</td>
			<td align="center">
				<?php echo $row->altersklasse;?>
			</td>
			<td>
				<a href="<?php echo $link; ?>"><?php echo $row->nummer; ?>. Mannschaft (<?php echo $runde;?>)</a>
			</td>
			
			
			<td align="center">
				<?php echo $published;?>
			</td>
			<td align="center">
				<?php echo $row->id; ?>
			</td>
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
	<input type="hidden" name="controller" value="teams" />
	<?php echo JHtml::_('form.token'); ?>
</div>
</form>
