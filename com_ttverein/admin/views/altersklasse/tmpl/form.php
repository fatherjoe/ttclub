<?php defined('_JEXEC') or die('Restricted access');?>

<script language="javascript" type="text/javascript">
	function submitbutton(pressbutton) {
		var form = document.adminForm;
		if (pressbutton == 'cancel') {
			submitform( pressbutton );
			return;
		}

		if (form.name.value == "") {
			alert( "<?php echo JText::_( 'Geben Sie eine Bezeichnung für die Altersklasse an', true ); ?>" );
		} else {
			submitform( pressbutton );
		}
	}
</script>

<form action="index.php" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
<div>
	<fieldset class="adminform">
	<legend><?php echo JText::_( 'Details' ); ?></legend>
	
	<table class="admintable">
		<tr>
			<td class="key">
				<label for="title">
					<?php echo JText::_( 'Bezeichnung' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="name" id="name" size="40" value="<?php echo $this->altersklasse->name; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="alias">
					<?php echo JText::_( 'Min alter' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="minalter" id="minalter" size="4" value="<?php echo $this->altersklasse->minalter; ?>" />
				<?php echo JHTML::tooltip("In den Seniorenklassen muss hier das Mindestalter eingeragen werden."); ?>
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="alias">
					<?php echo JText::_( 'Max alter' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="maxalter" id="maxalter" size="4" value="<?php echo $this->altersklasse->maxalter; ?>" />
				<?php echo JHTML::tooltip("In den Schüler, Jungen und Mädchen Klassen muss die das Höchstalter eingetragen werden."); ?>
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="alias">
					<?php echo JText::_( 'Sortier Reihenfolge' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="reihenfolge" id="reihenfolge" size="4" value="<?php echo $this->altersklasse->reihenfolge; ?>" />
				<?php echo JHTML::tooltip("In dieser Reihenfolge werden die Mannschaften im Frontend angezeigt (Aufsteigend sortiert). "); ?>
			</td>
		</tr>
		
		

	</table>
	</fieldset>
</div>
<div class="clr"></div>
<div class="clr"></div>

<input type="hidden" name="option" value="com_ttverein" />
<input type="hidden" name="id" value="<?php echo $this->altersklasse->id; ?>" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="controller" value="altersklassen" />
</form>
