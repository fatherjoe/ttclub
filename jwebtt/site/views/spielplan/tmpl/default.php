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
	<div id="spielplan">

		<?php
		// ÜBERSCHRIFTEN
		echo $this->title_1;
		echo $this->title_2;
		echo $this->title_3;
		
		?>
		<span><a href="<?php echo JRoute::_('index.php?option=com_webtt&view=kalender&typ=vcal&mannschaft=' . $this->team . '&serie=hin&format=raw'); ?>">VCAL</a></span>		
		<span><a href="<?php echo JRoute::_('index.php?option=com_webtt&view=kalender&typ=ical&mannschaft=' . $this->team . '&serie=hin&format=raw'); ?>">ICAL</a></span>		
		<table class="table">
			<?php
			if (isset($this->xml->BODY->MESSAGE) === FALSE)
			{
					?><thead>
						<tr><?php
					
							// SPALTE : TAG
							if ($this->params->anz_tag)
							{
									?><th class="tag" title="Wochentag">Tag</th><?php
							}
							
							?><th class="datum">Datum</th><?php
							
							
							// SPALTEN : UHRZEIT
							if ($this->params->anz_uhrzeit)
							{
									?><th class="zeit">Uhrzeit</th><?php
							}


							// SPALTE : HALLE
							if ($this->params->anz_halle)
							{
									?><th class="halle">Halle</th><?php
							}


							// SPALTE : HEIM
							?><th class="heim">Heim</th><?php


							// SPALTE : QTTR
							?><th class="gast">Gast</th><?php


							// SPALTE : ERGEBNIS
							if ($this->params->anz_erg)
							{
									?><th class="erg">Ergebnis</th><?php
							}
						?></tr>
					</thead><?php
			}
			
			?>		  
			<tfoot>
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
			<?php
			
			if (isset($this->xml->BODY->MESSAGE) === FALSE)
			{
					?><tbody><?php
						foreach ($this->xml as $zeile)
						{
								?><tr><?php


									// SPALTE : TAG
									if ($this->params->anz_tag)
									{ 
											?><td class="position"><?php echo $zeile->WOCHENTAG; ?></td><?php
									}


									// SPALTE : DATUM
									?><td class="datum"><?php echo $zeile->DATUM; ?></td><?php

									// SPALTE : UHRZEIT
									if ($this->params->anz_uhrzeit)
									{
											?><td class="uhrzeit"><?php echo $zeile->UHRZEIT; ?></td><?php
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
												<a	href="<?php echo 'http://www.google.de/maps/dir/' . urlencode($this->params->route_start) . '/' . urlencode($zeile->HALLE_ADRESSE); ?>"
													title="Zum Routenplaner : <?php echo $zeile->HALLE_TITLE; ?>"
													target="_blank">
													<span class="maps">&#1010<?php echo ($zeile->HALLE + 1); ?></span>
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
										else if ($this->params->popups_hallen == "jquery" && strlen($zeile->HALLE_ADRESSE) === false)
										{
												?>
													<p class="nomaps" title="keine Adresse vorhanden">&#10154;</p>
												<?php
										}

										// HALLE DIREKT NACH GOOGLEMAPS VERLINKEN (KEIN POPUP)
										else if ($this->params->popups_hallen == "" && strlen($zeile->HALLE_ADRESSE))
										{
												?>
												<a	href="<?php echo 'http://www.google.de/maps/dir//' . urlencode($zeile->HALLE_ADRESSE); ?>"
													title="Zum Routenplaner : <?php echo $zeile->HALLE_TITLE; ?>"
													target="_blank">
													<span class="maps">&#10103;</span>
												</a>
												<?php
										}
										
										
										?>
									</td>

								<?php
								// SPALTE : HEIM
								?>
								<td class="heim"><?php echo $zeile->HEIM; ?></td>

								<?php
								// SPALTE : GAST
								?>
								<td class="gast"><?php echo $zeile->GAST; ?></td>

								<?php
								// SPALTE : ERGEBNIS
								if ($this->params->anz_erg)
								{
										?><td class="erg"><?php						
											if ($zeile->ERGEBNIS)
											{
													if ($this->params->popups_erg == "css")
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
														echo $zeile->ERGEBNIS;
													}
											}
										?></td><?php
									}

								?></tr>
						  <?php } ?>
						  </tbody>
		<?php }
				else
				{
					?>
					<tbody><tr><td><?php echo $this->xml->MESSAGE; ?></td></tr></tbody>
					<?php
				}
				?>
		</table>
	</div>
</div>
