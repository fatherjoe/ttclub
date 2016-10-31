<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_webtt
 *
 */
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');



// JS FÜR DIE JQUERY-LIGHTBOX
if ($this->params->popups_hallen == "jquery" OR $this->params->popups_erg == "jquery")
{
		?>
		<script src="<?php echo JUri::base() . 'media/com_webtt/js/lightbox2/lightbox.js'; ?>" type="text/javascript"></script>
		<?php
}

// JS FÜR DIE MOOTOOLS-LIGHTBOX
if ($this->params->popups_hallen == "mootools" OR $this->params->popups_erg == "mootools")
{
		?>
		<script src="<?php echo JUri::base() . 'media/com_webtt/js/milkbox/mootools-core.js'; ?>" type="text/javascript"></script>
		<script src="<?php echo JUri::base() . 'media/com_webtt/js/milkbox/mootools-more.js'; ?>" type="text/javascript"></script>
		<script src="<?php echo JUri::base() . 'media/com_webtt/js/milkbox/milkbox.js'; ?>" type="text/javascript"></script>
		<?php
}

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
<div id="webtt">	
	<div id="spielpl_verein">

		<?php
		// ÜBERSCHRIFTEN
		if ($this->params->title_anz)
		{ ?>
		<h1>
			<?php echo $this->params->title; ?>
		</h1>
		<?php } ?>		
		
		<table class="table">
			<thead>
				<tr>
					<?php
					// SPALTE : LAUFNUMMER
					if ($this->params->anz_nr) { ?>
					<th class="lnr" title="Laufnummer">LNr</th>
					<?php } ?>

					<?php
					// SPALTE : TAG
					if ($this->params->anz_tag) { ?>
					<th class="tag" title="Wochentag">Tag</th>
					<?php } ?>
					
					<?php
					// SPALTE : DATUM
					?>
					<th class="datum">Datum</th>
					  
					<?php
					// SPALTEN : UHRZEIT
					if ($this->params->anz_uhrzeit) { ?>
					<th class="zeit">Uhrzeit</th>
					<?php } ?>

					<?php
					// SPALTE : HALLE
					if ($this->params->anz_halle) { ?>
					<th class="halle">Halle</th>
					<?php } ?>

					<?php
					// SPALTE : STAFFEL
					if ($this->params->anz_staffel) { ?>
					<th class="staffel">Staffel</th>
					<?php } ?>

					<?php
					// SPALTE : HEIM
					?>
					<th class="heim">Heim</th>

					<?php
					// SPALTE : QTTR
					?>
					<th class="gast">Gast</th>

					<?php
					// SPALTE : ERGEBNIS
					if ($this->params->anz_erg) { ?>
					<th class="erg">Ergebnis</th>
					<?php } ?>

				</tr>
			</thead>
		  
			<tfoot>
				<tr>
					<td colspan="10" class="kalender">
							<span >
								<a href="<?php echo JURI::base(); ?>index.php?option=com_webtt&view=kalender&mannschaft=verein&typ=vcal&format=raw">
									VCAL
								</a>
							</span>		
							<span class="kalender">
								<a href="<?php echo JURI::base(); ?>index.php?option=com_webtt&view=kalender&mannschaft=verein&typ=ical&format=raw">
									ICAL
								</a>
							</span>		
					</td>
				</tr>
				
				<tr>
					<td colspan="10">
						<span class="backlink">
							Erstellt mit <a href="http://webtt.de/" title="Lassen Sie Ihre Homepage mit den Daten von clicktt füttern">Web-TT</a> | 
						</span>
						<span title="Nächste Aktualisierung: <?php echo date("d.m. H:i", strtotime($this->timestamp) + ( $this->params->akt * 3600)); ?>">
							Stand: <?php echo date("d.m. H:i", strtotime($this->timestamp)); ?>
						</span>
					</td>
				</tr>	
		  </tfoot>
		  
		  <tbody>
			<?php
			$i="" ;
			foreach ($this->xml as $zeile)
			{
					?><tr><?php
					
					// SPALTE : LAUFNUMMER
					if ($this->params->anz_nr)
					{
							?><td class="laufnummer"><?php $i++ ; echo $i ; ?></td><?php
					}

					// SPALTE : WOCHENTAG
					if ($this->params->anz_tag)
					{
							?><td class="wochentag"><?php echo $zeile->WOCHENTAG; ?></td><?php
					}

					// SPALTE : DATUM
					?><td class="datum"><?php echo $zeile->DATUM; ?></td><?php

					// SPALTE : UHRZEIT
					if ($this->params->anz_uhrzeit)
					{
							?><td class="uhrzeit"><?php echo $zeile->UHRZEIT;
							if (strlen($zeile->UHRZEIT_ALT))
							{
									?>
									<div class="infotip">
									<p>Die Uhrzeit wurde verlegt.</p>
									<p>Ursprünglicher Spielbeginn war <?php echo $zeile->UHRZEIT; ?></p>
									</div>
									<?php
							}
							
							?></td><?php
					}

					// SPALTE : HALLE
					?><td class="halle"><?php

					// AUKLAPPBOX MIT CSS
					if ($this->params->popups_hallen == "css" && strlen($zeile->HALLE_ADRESSE))
					{							
							include "halle_popup_css.php";
					}

					// LINK (MIT HALLENZEICHEN) ZU GOOGLEMAPS 
					if ($this->params->popups_hallen == "css" && strlen($zeile->HALLE_ADRESSE))
					{ 
							?>
							<a	href="<?php echo 'http://www.google.de/maps/dir//' . urlencode($zeile->HALLE_ADRESSE); ?>"
								title="Zum Routenplaner : <?php echo $zeile->HALLE_TITLE; ?>"
								target="_blank">
								<span class="maps">
									<?php
										if ($zeile->HALLE == "H") { ?>H<?php }
										else { ?>&#1010<?php echo ($zeile->HALLE + 1); }
									?>
								</span>
							</a>
							<?php
					}


					// HALLE IN JAVASCRIPT LIGHTBOX
					if ($this->params->popups_hallen == "jquery" && strlen($zeile->HALLE_ADRESSE))
					{
							include "halle_popup_lightbox2.php";							
					}
					// ENDE LIGHTBOX


					// LINK (MIT PFEIL) ZU GOOGLEMAPS HINTER POPUP-LINK 
					if ($this->params->popups_hallen == "jquery" && strlen($zeile->HALLE_ADRESSE))
					{ 
							?>
							<a	href="<?php echo 'http://www.google.de/maps/dir//' . urlencode($zeile->HALLE_ADRESSE); ?>"
								title="Zum Routenplaner : <?php echo $zeile->HALLE_TITLE; ?>"
								target="_blank">
								<span class="maps">&#10154;</span>
							</a>
							<?php
					}
					
					// KEINE ADRESSE VORHANDEN
					else if ($this->params->popups_hallen == "jquery" && strlen($zeile->HALLE_ADRESSE) == "")
					{
							?>
							<p class="nomaps" title="keine Adresse vorhanden">
								<?php
									if ($zeile->HALLE == "H") { ?>H<?php }
									else { ?>&#1010<?php echo ($zeile->HALLE + 1); ?>;<?php }
								?>
							</p>
							<?php
					}

					// HALLE DIREKT NACH GOOGLEMAPS VERLINKEN (KEIN POPUP)
					else if ($this->params->popups_hallen == "" && strlen($zeile->HALLE_ADRESSE))
					{
							?>
							<a	href="<?php echo 'http://www.google.de/maps/dir//' . urlencode($zeile->HALLE_ADRESSE); ?>"
								title="Zum Routenplaner : <?php echo $zeile->HALLE_TITLE; ?>"
								target="_blank">
								<span class="maps">
									<?php
										if ($zeile->HALLE == "H") { ?>H<?php }
										else { ?>&#1010<?php echo ($zeile->HALLE + 1);  }
									?>
								</span>
							</a>
							<?php
					}

					?>
					</td>

					<?php
					// SPALTE : STAFFEL
					if ($this->params->anz_staffel)
					{
							?><td class="uhrzeit"><?php echo $zeile->STAFFEL; ?></td><?php
					}

					// SPALTE : HEIM
					?><td class="heim"><?php echo $zeile->HEIM; ?></td><?php

					// SPALTE : GAST
					?><td class="gast"><?php echo $zeile->GAST; ?></td><?php

					// SPALTE : ERGEBNIS
					if ($this->params->anz_erg)
					{
							?><td class="erg"><?php
					}

					if ($this->params->popups_erg == "css" && strlen($zeile->ERGEBNIS_PFAD))
					{
							include "erg_popup_css.php";
					}

					// SPIELERPAARUNGEN IN JAVASCRIPT-LIGHTBOX (nur mootools)
					else if ($this->params->popups_erg == "mootools")
					{
							include "erg_popup_milkbox.php";
					}

					else
					{
							if (strlen($zeile->ERGEBNIS_PFAD)) { ?><a href="<?php echo $zeile->ERGEBNIS_PFAD; ?>"><?php }
							if (strlen($zeile->ERGEBNIS)) { echo $zeile->ERGEBNIS; }
							if (strlen($zeile->ERGEBNIS_PFAD)) { ?></a><?php }
					}

						
					if ($this->params->anz_erg)
					{
							?></td><?php
					}
					
					?></tr><?php
			} ?>
		  </tbody>
		</table>
	</div>
</div>
