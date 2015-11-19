<?php defined('_JEXEC') or die('Restricted access'); ?>

<script language="javascript" type="text/javascript">
	function submitbutton(pressbutton) {
		var form = document.adminForm;
		if (pressbutton == 'cancel') {
			submitform( pressbutton );
			return;
		}

		if (form.nummer.value == "") {
			alert( "<?php echo JText::_( 'Geben Sie an die Nummer der Mannschaft an', true ); ?>" );
		} else {
			submitform( pressbutton );
		}
	}
</script>

<form action="index.php" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
<div>
	<fieldset class="adminform">
	<legend><?php echo JText::_( 'Details' ); ?></legend>
	<?php 
		$image = $this->team->image_resize;
		if($image) {
			if(substr($image,0,1) == "/")
				$image = substr($image, 1);
	?>
		<img src="<?php echo JURI::root() . $image;?>" width="380" align="right" alt="Mannschaftsfoto">
	<?php } ?>
	<table class="admintable">
		<tr>
			<td width="110" class="key">
				<label for="title">
					<?php echo JText::_( 'Mannschafts Nummer' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="nummer" id="nummer" size="2" value="<?php echo $this->team->nummer; ?>" />
				<?php echo JHTML::tooltip("Zur Eingabe der 3.Mannschaft muss hier eine 3 eingetragen werden."); ?>
			</td>
		</tr>
		<tr>
			<td class="key">
				<?php echo JText::_( 'Altersklasse' ); ?>:
			</td>
			<td>
				<?php
				echo JHTML::_( 'select.genericlist',$this->team->altersklassen,  'altersklasse','class="inputbox"', 'id', 'name', $this->team->altersklasse );
				echo " " . JHTML::tooltip("Zugehörige Altersklasse.");
				 ?>
			</td>
		</tr>
		<tr>
			<td class="key">
				<?php echo JText::_( 'Liga' ); ?>:
			</td>
			<td>
				<?php
					$tmp = new stdClass();
					$tmp->id = "";
					$tmp->name = " - ";
					array_unshift($this->team->ligen, $tmp);
					echo JHTML::_( 'select.genericlist',$this->team->ligen,  'liga','class="inputbox"', 'id', 'name', $this->team->liga );
					echo " " . JHTML::tooltip("Leistungsklasse"); 
				 ?>
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="alias">
					<?php echo JText::_( 'Saison' ); ?>:
				</label>
			</td>
			<td>
				<?php
				$saisonstart = "";
				if($this->team->saisonstart > 1900)
					$saisonstart = $this->team->saisonstart;
					
				echo "\n" . '<select name="saisonstart" id="saisonstart" class="inputbox">';
				$date = JFactory::getDate();
				$now = intval( $date->format("Y") );
				echo '<option value=""> - </option>';
				for($i=$now+1; $i > 1900; $i--) {
					echo "\n<option value=\"$i\"";
					if($saisonstart == $i)
						echo ' selected="selected"';
					echo '>' . $i . "/"  . substr(($i+1),2) .'</option>';
				}
				echo "\n</select>\n&nbsp;";
				echo JHTML::tooltip("Jeder Mannschaft muss eine Saison zugewiesen werden. Eine Mannschaft kann nicht in mehreren Saison sein.");
				?>
					
				
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="alias">
					<?php echo JText::_( 'Runde' ); ?>:
				</label>
			</td>
			<td>
				
				<?php
				$hinrunde = new stdClass(); 
				$hinrunde->id = 1;
				$hinrunde->name = 'Hinrunde';
				$rueckrunde = new stdClass();
				$rueckrunde->id = 0;
				$rueckrunde->name = 'R&uuml;ckrunde';
				
				$options = array($hinrunde, $rueckrunde);
				echo JHTML::_( 'select.genericlist',$options,  'hinrunde','class="inputbox"', 'id', 'name', $this->team->hinrunde );
				
				echo " " . JHTML::tooltip("Jede Mannschaft kann nur in einer Hin oder Rückrunde sein.");
				 ?>
			</td>
		</tr>
		


		<tr>
			<td class="key">
				<label for="alias">
					<?php echo JText::_( 'Neues Bild hochladen' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="file" name="image" id="image" size="30" />
				<?php 
				$upload = get_cfg_var('upload_max_filesize');
				$post = get_cfg_var('post_max_size');
				if($post < $upload)
					$upload = $post;
				$upload = str_replace("M", " MegaByte", $upload);
				$upload = str_replace("K", " KilloByte", $upload);
				
				echo JHTML::tooltip("Sie können nur maximal $upload große Dateien hochladen."); ?>
			</td>
		</tr>
				<tr>
			<td class="key">
				<label for="alias">
					<?php echo JText::_( 'Bildbeschreibung' ); ?>:
				</label>
			</td>
			<td>
				<textarea class="inputbox" name="image_text" id="image_text" cols="40" rows="3"><?php echo $this->team->image_text; ?></textarea>
				<?php echo JHTML::tooltip("Dieser Text wird unter dem Bild in der Mannschafdsdetail- Seite angezeigt."); ?>
			</td>
		</tr>
		
		<tr>
			<td class="key">
				<?php echo JText::_( 'Published' ); ?>:
			</td>
			<td>
				<?php echo JHTML::_( 'select.booleanlist',  'published', 'class="inputbox"', $this->team->published ); ?>
				&nbsp;&nbsp;<?php echo JHTML::tooltip("Anzeige in Frontend freischalten."); ?>
			</td>
		</tr>
		<tr>
			<td class="key">
				<?php echo JText::_( 'Aufstellung' ); ?>:
			</td>
			<td>
				<?php 
				
				if(count($this->team->spieler) == 0)
					echo "Keine Spieler angelegt.";
				
				//Standart Anzeige von möglichen einsetzbaren Spielern in der Mannschaft
				$maxSpieler = 10;
				/*
				 * Es können immer 3 Spieler mehr als schon aufgestellt zusätlich in die 
				 * Mannschaft hinzugefügt werden. Dies soll auch Mannschaften mit mehr als 10 Spielern ermöglichen.
				 */ 
				if(count($this->team->aufstellungen)+3 >= $maxSpieler)
				$maxSpieler = count($this->team->aufstellungen) + 3;
				
				if(count($this->team->spieler) < $maxSpieler)
					$maxSpieler = count($this->team->spieler);
				
				//Um eine Aufstellung löschen zu können wird der Spieler " - " mit der ID 0 ausgewählt
				$tmpSpieler = new stdClass();
				$tmpSpieler->id = 0;
				$tmpSpieler->name = ' - ';
				array_unshift($this->team->spieler, $tmpSpieler);
				
				echo "\n<table>";
				echo "\n<tr>" .
						"<th>Position</th>" .
						"<th>Spieler</th>" .
					"</tr>";
				for($i=1; $i <= $maxSpieler; $i++) {
					$select = 0;
					foreach($this->team->aufstellungen as $aufstellung) {
						if($aufstellung->position == $i) {
							$select = $aufstellung->id;				
							break;
						}
					}
					echo "<tr>";
					echo "<td align=\"right\">$i</td>";
					echo "<td>" . JHTML::_( 'select.genericlist',$this->team->spieler,  "spieler[$i]",'class="inputbox"', 'id', 'name', $select ) . "</td>";
					echo "</tr>\n";
				}
				
				 ?>
				</table>
			</td>
		</tr>
		
		<tr>
			<td class="key">
				<?php echo JText::_( 'Mannschaftsführer' ); ?>:
			</td>
			<td>
				<?php
				echo JHTML::_( 'select.genericlist',$this->team->spieler,  "mannschaftsfuehrer",'class="inputbox"', 'id', 'name', $this->team->mannschaftsfuehrer );
				?>
			</td>
		</tr>

		<tr>
			<td width="110" class="key">
				<label for="title">
					<?php echo JText::_( 'click-TT teamtable' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="clicktt_teamtable" id="clicktt_teamtable" size="2" value="<?php echo $this->team->clicktt_teamtable; ?>" />
				<?php echo JHTML::tooltip("Nummer in der Click-TT URL der Mannschaftsseite (?teamtable=xxx"); ?>
			</td>
		</tr>
		
	</table>
	</fieldset>
</div>
<div class="clr"></div>
<div class="clr"></div>


<input type="hidden" name="image_orginal" value="<?php echo $this->team->image_orginal; ?>" />
<input type="hidden" name="image_resize" value="<?php echo $this->team->image_resize; ?>" />
<input type="hidden" name="image_thumb" value="<?php echo $this->team->image_thumb; ?>" />
<input type="hidden" name="clicktt_championship" value="<?php echo $this->team->clicktt_championship; ?>" />
<input type="hidden" name="clicktt_group" value="<?php echo $this->team->clicktt_group; ?>" />
<input type="hidden" name="option" value="com_ttverein" />
<input type="hidden" name="id" value="<?php echo $this->team->id; ?>" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="controller" value="teams" />
</form>
