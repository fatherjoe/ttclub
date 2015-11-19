<?php defined('_JEXEC') or die('Restricted access'); ?>

<form id="adminForm" action="index.php" method="post" name="adminForm">
<div id="editcell">
	<table class="adminlist">
	<tr>
		<td colspan="3"><h2>Pfade</h2></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td width="120">Spielerbilder</td>
		<td width="200">
			<input class="inputbox" type="text" name="player_image_path" id="player_image_path" size="40" value="<?php echo$this->config['player_image_path'];?>" />
		</td>
		<td width="410">Pfad vom Instalationsverzeichnis ausgehend. Der Ordner "/images/stories/" ist der Standart Ordner f&uuml;r Bilder in Joomla.</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>Mannschaftsbilder</td>
		<td>
			<input class="inputbox" type="text" name="team_image_path" id="team_image_path" size="40" value="<?php echo$this->config['team_image_path'];?>" />
		</td>
		<td>Pfad vom Instalationsverzeichnis ausgehend.  Der Ordner "/images/stories/" ist der Standart Ordner f&uuml;r Bilder in Joomla.</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="3"><h2>Design</h2></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>Variables Layout</td>
		<td>
			<table>
				<tr>
					<td>H&ouml;he eine Mannschaft</td>
					<td><input class="inputbox" type="text" name="div_team_height" id="div_team_height" size="5" value="<?php echo$this->config['div_team_height'];?>" />
						 Pixel hoch
					</td>
			
				</tr>
			</table>
		</td>
		<td>Wird für die Mannschaftsübersicht das Variable Layout eingestellt, so muss die H&ouml;he einer Mannschaft (Name + Bild) angegeben werden.</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>Mannschaftsbilder</td>
		<td>
			<table>
				<tr>
					<td>Vorschau</td>
					<td><input class="inputbox" type="text" name="team_thumb_size" id="team_thumb_size" size="5" value="<?php echo$this->config['team_thumb_size'];?>" />
						 Pixel breit
						<input type="hidden" name="team_thumb_size_old" value="<?php echo$this->config['team_thumb_size'];?>" />
					</td>
			
				</tr>
				<tr>
					<td>Detail</td>
					<td><input class="inputbox" type="text" name="team_image_size" id="team_image_size" size="5" value="<?php echo$this->config['team_image_size'];?>" />
						Pixel breit
						<input type="hidden" name="team_image_size_old" value="<?php echo$this->config['team_image_size'];?>" />
					</td>
			
				</tr>
			
			</table>
		</td>
		<td>Das Vorschaubild wird in der Mannschaften Übersicht angeteigt. Das Detailbild wird bei den Details der Mannschaft angezeigt.</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>Spielerbilder</td>
		<td>
			<table>
				<tr>
					<td>Vorschau</td>
					<td><input class="inputbox" type="text" name="player_thumb_size" id="player_thumb_size" size="5" value="<?php echo$this->config['player_thumb_size'];?>" />
						 Pixel breit
						 <input type="hidden" name="player_thumb_size_old" value="<?php echo$this->config['player_thumb_size'];?>" />
					</td>
			
				</tr>
				<tr>
					<td>Detail</td>
					<td><input class="inputbox" type="text" name="player_image_size" id="player_image_size" size="5" value="<?php echo$this->config['player_image_size'];?>" />
						Pixel breit
						<input type="hidden" name="player_image_size_old" value="<?php echo$this->config['player_image_size'];?>" />
					</td>
			
				</tr>
			
			</table>
		</td>
		<td>Das Detailbild wird derzeit noch nicht genutzt. Das Vorschaubild wird im Spielerprofil angezeigt.</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="3"><h2>Deinstallation</h2></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>Löschen aller Daten aus Datenbank</td>
		<td>
			<input type="radio" name="delete_database" id="delete_database" value="1"<?php if($this->config['delete_database'] == "1")echo " checked";?>> Ja
    		<input type="radio" name="delete_database" id="delete_database" value="0"<?php if($this->config['delete_database'] == "0")echo " checked";?>> Nein
		</td>
		<td>Wenn man hier "Ja" auswählt, werden bei der Deinstallation der Komponente alle Daten aus der Datenbank gel&ouml;scht</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>Löschen aller Bilder</td>
		<td>
			<input type="radio" name="delete_pictures" id="delete_pictures" value="1"<?php if($this->config['delete_pictures'] == "1")echo " checked";?>> Ja
    		<input type="radio" name="delete_pictures" id="delete_pictures" value="0"<?php if($this->config['delete_pictures'] == "0")echo " checked";?>> Nein
		</td>
		<td>Wenn man hier "Ja" auswählt, werden bei der Deinstallation alle Spieler und Mannschaften Bilder gel&ouml;scht</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="3"><h2>Click-TT</h2></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>Daten aus click-TT laden</td>
		<td>
			<input type="radio" name="clicktt_use" id="clicktt_use" value="1"<?php if($this->config['clicktt_use'] == "1")echo " checked";?>> Ja
    		<input type="radio" name="clicktt_use" id="clicktt_use" value="0"<?php if($this->config['clicktt_use'] == "0")echo " checked";?>> Nein
		</td>
		<td>Das laden aus click-TT kann einige Sekunden bei jedem Seitenaufruf beanspruchen. Der Cache sollte aktiviert sein.</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>ClickTT Verband</td>
		<td>
			<select name="clicktt_verband" id="clicktt_verband">
		   	<?php
		   	foreach($this->clickttVerbaende as $verband) {
		   		echo "<option";
		   		if($verband->name == $this->config['clicktt_verband'])
		   			echo ' selected="selected"';
		   		echo ">" . $verband->name . "</option>\n";
		   	}
		    ?>
		    </select>
		</td>
		<td>W&auml;hlen Sie den Verband in dem Ihr Verein gemeldet ist. Andere Verb&auml;nde werden nicht unterst&uuml;tzt.</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>Vereinsnummer</td>
		<td>
			<input class="inputbox" type="text" name="clicktt_club_nummer" id="clicktt_club_nummer" size="6" value="<?php echo$this->config['clicktt_club_nummer'];?>" />
		</td>
		<td>Die sechs stellige Vereinsnummer. Zu finden auf der Vereinsübersichtsseite in clickTT</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>Vereinsname</td>
		<td>
			<input class="inputbox" type="text" name="clicktt_club_name" id="clicktt_club_name" size="40" value="<?php echo$this->config['clicktt_club_name'];?>" />
		</td>
		<td>Der Name des Vereins genauso wie der in den Tabellen auftaucht. 
		z.B. der "Aggertaler TTC Gummersbach 1957 e.V." wird in den Tabellen "Aggertaler TTC" geschrieben.
		Dann müsste in diesen Fall hier Aggertaler TTC angegeben werden.</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>Leistungsindex Berechnen</td>
		<td>
			<input type="radio" name="clicktt_calc_leistungsindex" id="clicktt_calc_leistungsindex" value="1"<?php if($this->config['clicktt_calc_leistungsindex'] == "1")echo " checked";?>> Ja
    		<input type="radio" name="clicktt_calc_leistungsindex" id="clicktt_calc_leistungsindex" value="0"<?php if($this->config['clicktt_calc_leistungsindex'] == "0")echo " checked";?>> Nein
		</td>
		<td>Gibt zusätlich zur Bilanz den Leistungsindex an. Dieser wurde für den WTTV entwickelt. Diese Funktion ist auch noch nicht ausreichend getestet.</td>
		<td>&nbsp;</td>
	</tr>
	</table>
</div>

<input type="hidden" name="option" value="com_ttverein" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="controller" value="config" />
</form>
