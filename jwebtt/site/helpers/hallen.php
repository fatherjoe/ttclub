<?php

// no direct access
defined('_JEXEC') or die;


class WebttHelperHallen extends WebttHelper
{

		// LIEFERT DIE HALLEN ALLER MANNSCHAFTEN ZURÜCK
		public function getHallen()
		{
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('*')
                    ->from('#__webtt_hallen');
                    
				$db->setQuery($query);
								 
				$result = $db->loadAssocList('verein');
				
				return $result;
		}

		public function createLightbox()
		{
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('*')
                    ->from('#__webtt_spieler');
                    
				$db->setQuery($query);
								 
				$result = $db->loadAssocList('foto');

				foreach ($result as $filename => $array)
				{
						// PRÜFEN, OB DEM SPIELER EIN BILD ZUGEORDNET WURDE
						if ($filename)
						{
								// Prüfen, ob Thumbnail existiert
								if (file_exists(JPATH_SITE . '/images/webtt/lightbox/' . $filename))
								{
										// Prüfen, ob Thumbnail in der richtigen Größe vorhanden
										$thumb = new JImage(JPATH_SITE . '/images/webtt/lightbox/' . $filename);

										if (
													$thumb->getWidth() != JComponentHelper::getParams('com_webtt')->get('foto_width_lb')
												&&	$thumb->getHeight() != JComponentHelper::getParams('com_webtt')->get('foto_height_lb')
											)
										{
												// Thumbnail erstellen
												$image = new JImage(JPATH_SITE . '/images/' . $filename);
												$image->resize(100, 150, true, JImage::SCALE_INSIDE)->toFile(JPATH_SITE . '/images/webtt/lightbox/' . $filename);
										}

								}

								else
								{
										// Thumbnail erstellen
										$image = new JImage(JPATH_SITE . '/images/' . $filename);
										$image->resize(100, 150, true, JImage::SCALE_INSIDE)->toFile(JPATH_SITE . '/images/webtt/lightbox/' . $filename);
								}

								chmod(JPATH_SITE . '/images/webtt/lightbox/' . $filename, 0666);
						}
				}
		}
}

?>
