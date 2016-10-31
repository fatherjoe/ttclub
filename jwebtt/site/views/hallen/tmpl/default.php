<?php
 
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
<div id="webtt">
	<div id="hallen">
		<?php if ($this->params->title_anz && $this->params->title) { ?>
			<h1>
				<?php echo $this->params->title; ?>
			</h1>
		<?php } ?>
			
		<?php for ($i = 1; $i <= 3 ; $i++) { 
			if (strlen($this->hallen->{'halle_'.$i})) { ?>
			<table>
				<caption>
					<?php echo "Halle $i"; ?>
				</caption>
				<thead>
					<tr>
						<th>
							<?php echo $this->hallen->{'halle_'.$i} . "<br />" . $this->hallen->{'addr_'.$i}; ?>
						</th>
					</tr>
				</thead>
				
				<tfoot>
					<tr>
						<td>

						</td>
					</tr>
				</tfoot>

				<tbody>
					<tr>
					  <?php if ($this->params->karte) { ?>
						<td>
						  <?php
						  
						  // DYNAMISCHE KARTE
						  if ($this->params->karte_type == "dyn") { ?>
							<iframe
							  width="<?php echo $this->params->karte_width; ?>"
							  height="<?php echo $this->params->karte_height; ?>"
							  frameborder="0" style="border:0"
							  src="https://www.google.com/maps/embed/v1/place
								?key=<?php echo $this->params->api; ?>
								&amp;q=<?php echo urlencode($this->hallen->{'addr_'.$i}); ?>"
							  allowfullscreen>
							</iframe>
						  <?php }
						  
						  // STATISCHE KARTE
						  else if ($this->params->karte_type == "stat" OR $this->params->api = "") { ?>
							<a href="http://maps.google.de/?f=q&amp;source=s_q&amp;hl=de&amp;daddr=<?php echo urlencode($this->hallen->{'addr_'.$i}); ?>" target="_blank">
								<img src="http://maps.google.com/maps/api/staticmap
											?zoom=<?php echo $this->params->karte_zoom; ?>
											&amp;size=<?php echo $this->params->karte_width; ?>x<?php echo $this->params->karte_height; ?>
											&amp;maptype=roadmap
											&amp;markers=color:blue|label:Halle|=<?php echo urlencode($this->hallen->{'addr_'.$i}); ?>
											&amp;sensor=false) center center no-repeat transparent;" />
							</a>
						  <?php } ?>
						</td>
					  <?php } ?>
					</tr>
					<tr>
					  <?php if ($this->params->streetview) { ?>
						<td>
						  <?php if ($this->params->streetview_type == "dyn") { ?>
							<iframe
							  width="<?php echo $this->params->streetview_width; ?>"
							  height="<?php echo $this->params->streetview_height;?>"
							  frameborder="0" style="border:0"
							  src="https://www.google.com/maps/embed/v1/streetview?key=<?php echo $this->params->api; ?>&amp;location=<?php echo $this->params->lat; ?>,<?php echo $this->params->lng; ?>&amp;pitch=10&amp;heading=210&amp;fov=35" allowfullscreen>
							</iframe>
						  <?php }

						  else if ($this->params->streetview_type == "stat" OR $this->params->api = "") { ?>
							<a href="" target="_blank">
								<img src="https://maps.googleapis.com/maps/api/streetview
									?size=<?php echo $this->params->streetview_width; ?>x<?php echo $this->params->streetview_height;?>
									&amp;location=<?php echo urlencode($this->hallen->{'addr_'.$i}); ?>
									&amp;fov=120
									&amp;heading=235
									&amp;pitch=0"
								/>
							</a>
						  <?php } ?>
						</td>
					  <?php } ?>
					</tr>
				</tbody>

			</table>
	<?php }} ?>
	</div>
</div>
