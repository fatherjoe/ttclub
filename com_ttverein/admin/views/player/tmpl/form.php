<?php defined('_JEXEC') or die('Restricted access');?>

<script language="javascript" type="text/javascript">
	function submitbutton(pressbutton) {
		var form = document.adminForm;
		if (pressbutton == 'cancel') {
			submitform( pressbutton );
			return;
		}

		if (form.vorname.value == "") {
			alert( "<?php echo JText::_( 'Geben Sie den Vornamen des Spielers an', true ); ?>" );
		} else if(form.nachname.value == "") {
			alert( "<?php echo JText::_( 'Geben Sie den Nachnamen des Spielers an', true ); ?>" );
		}else {
			submitform( pressbutton );
		}
	}
</script>

<form action="index.php" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
<div>
	<fieldset class="adminform">
	<legend><?php echo JText::_( 'Details' ); ?></legend>
	<?php 
		$image = $this->player->image_resize;
		if($image) {
			if(substr($image,0,1) == "/")
				$image = substr($image, 1);
	?>
		<img src="<?php echo JURI::root() . $image;?>" width="350" align="right" alt="<?php echo $this->player->vorname . " " . $this->player->nachname; ?>">
	<?php }?>
	<table class="admintable">
		<tr>
			<td class="key">
				<label for="title">
					<?php echo JText::_( 'Vorname' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="vorname" id="vorname" size="20" value="<?php echo $this->player->vorname; ?>" />
				<?php echo JHTML::tooltip("Vorname und Nachname zusammen müssen eindeutig im Verein sein"); ?>
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="alias">
					<?php echo JText::_( 'Nachname' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="nachname" id="nachname" size="20" value="<?php echo $this->player->nachname; ?>" />
				<?php echo JHTML::tooltip("Vorname und Nachname zusammen müssen eindeutig im Verein sein"); ?>
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="alias">
					<?php echo JText::_( 'Neues Bild hochladen' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="file" name="image" id="image" size="35" />
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
		
		<?php
		
		foreach($this->player->felder as $id=>$feld) {
			?>
		<tr>
			<td class="key">
				<label for="alias">
					<?php echo JText::_( $feld->name ); ?>:
				</label>
			</td>
			<td>
			<?php

			switch($feld->typ) {
				case "jahre seit":
					$datum = "";
					if($feld->wert && $feld->wert != '0000-00-00')
						$datum = strftime("%d.%m.%Y",strtotime($feld->wert));
					?>
					<input class="inputbox" type="text" name="felder[<?php echo $id;?>]" id="felder[<?php echo $id;?>]" size="10" value="<?php echo $datum;?>" />
					<?php
					JHTML::calendar(date("%Y-%m-%d"),"felder[$id]","felder[$id]","%d.%m.%Y");
					
					break;
					
				case "text":
				case "email":
					?>
					<input class="inputbox" type="text" name="felder[<?php echo $id;?>]" id="felder[<?php echo $id;?>]" size="40" value="<?php echo $feld->wert; ?>" />
					<?php
					break;
				case "telefon":
					?>
					<input class="inputbox" type="text" name="felder[<?php echo $id;?>]" id="felder[<?php echo $id;?>]" size="15" value="<?php echo $feld->wert; ?>" />
					<?php
					break;	
			}
			if($feld->tooltip)
					echo JHTML::tooltip($feld->tooltip);
			?>
			<input class="inputbox" type="hidden" name="typen[<?php echo $id;?>]" value="<?php echo $feld->typ; ?>" />
			</td>
		</tr>
			
			<?php
		}
		
		?>
		<tr>
			<td width="120" class="key">
				<?php echo JText::_( 'Im Geburtstag-Modul anzeigen' ); ?>:
			</td>
			<td>
				<?php echo JHTML::_( 'select.booleanlist',  'published_gebutstag', 'class="inputbox"', $this->player->published_gebutstag ); ?>
				&nbsp;&nbsp;<?php echo JHTML::tooltip("Anzeige für das Geburtstag-Modul freischalten, falls installiert."); ?>
			</td>
		</tr>
		<tr>
			<td width="120" class="key">
				<?php echo JText::_( 'Published' ); ?>:
			</td>
			<td>
				<?php echo JHTML::_( 'select.booleanlist',  'published', 'class="inputbox"', $this->player->published ); ?>
				&nbsp;&nbsp;<?php echo JHTML::tooltip("Anzeige in Forntend freischalten."); ?>
			</td>
		</tr>

		<tr>
			<td class="key">
				<?php echo JText::_( 'Mannschaft' ); ?>:
			</td>
			<td>
				<?php

				$options = array();
				
				
				$lastSaison = "";
				$lastRunde = "";
				
				/*
				 * Aufstellungen in einen Array ordnen.
				 * array[saison][hinrunde/rueckrunde][mannschafts ID] = Mannschafts Name
				 */
				$mannschaften = array();
				for($i=0, $n=count( $this->player->mannschaften ); $i < $n; $i++) {
					$mannschaft = $this->player->mannschaften[$i];
					$mannschaften[$mannschaft->saisonstart][$mannschaft->hinrunde][$mannschaft->id] = $mannschaft->mannschaft;
				}
				
				$tab = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
				foreach($mannschaften as $saisonstart=>$runden) {
					echo "<strong>Saison $saisonstart/" . ($saisonstart+1) . "</strong><br />";
					
					foreach($runden as $hinrunde=>$mannschaft) {
						if($hinrunde == 0)
							echo $tab . "R&uuml;ckrunde";
						else if($hinrunde == 1)
							echo $tab . "Hinrunde&nbsp;&nbsp;&nbsp;";		
						
													
						echo "\n" . '<select name="mannschaften[' . $saisonstart . '-' . $hinrunde . ']" id="mannschaft" class="inputbox">';
						echo "\n\t" . '<option value="0"> - </option>';
						foreach($mannschaft as $id=>$name) {
							echo "\n\t" . '<option value="' . $id . '"';
							foreach($this->player->aufstellungen as $value) {
								if($value->mannschafts_id == $id) {
									echo ' selected="selected"';
									break;
								}
							}
							
							echo '>' . $name .'</option>';
						}
						echo "\n</select>\n&nbsp;";
						
						$selected = 0;
						foreach($this->player->aufstellungen as $value) {
							if(array_key_exists($value->mannschafts_id, $mannschaft))
								$selected = $value->position;
						}
						echo "\n" . '<select name="aufstellungen[' . $saisonstart . '-' . $hinrunde . ']" id="position" class="inputbox">';
						echo "\n\t" . '<option value="0"> - </option>';
						//TODO Anzahl Konfigurierbar
						for($j=1; $j<=15; $j++) {
							echo "\n\t<option";
							if($j == $selected)
								echo ' selected="selected"';
							echo ">$j</option>";
						}
						echo "\n</select>\n<br />";
					}
				}
			
				
				
				
				
				 ?>
			</td>
		</tr>

	</table>
	</fieldset>
</div>
<div class="clr"></div>
<div class="clr"></div>

<input type="hidden" name="image_orginal" value="<?php echo $this->player->image_orginal; ?>" />
<input type="hidden" name="image_resize" value="<?php echo $this->player->image_resize; ?>" />
<input type="hidden" name="image_thumb" value="<?php echo $this->player->image_thumb; ?>" />
<input type="hidden" name="clicktt_person_id" value="<?php echo $this->player->clicktt_person_id; ?>" />
<input type="hidden" name="option" value="com_ttverein" />
<input type="hidden" name="id" value="<?php echo $this->player->id; ?>" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="controller" value="players" />
</form>
