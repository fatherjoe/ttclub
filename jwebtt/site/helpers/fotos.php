<?php

// no direct access
defined('_JEXEC') or die;

JLoader::import('helpers.webtt', JPATH_COMPONENT);

class WebttHelperFotos extends WebttHelper
{
		// ERSTELLT THUMBNAILS FÜR AUFSTELLUNGEN UND SPIELERLISTE
		public function createThumbnail()
		{
				$view = parent::getView();
				$path = JPATH_SITE . '/media/com_webtt/thumbnails/' . $view;
				
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
								if (file_exists($path . '/' . $filename))
								{
										// Prüfen, ob Thumbnail in der richtigen Größe vorhanden
										$thumb = new JImage($path . '/' . $filename);

										if (
													$thumb->getWidth() != JComponentHelper::getParams('com_webtt')->get('foto_width_thumb')
												&&	$thumb->getHeight() != JComponentHelper::getParams('com_webtt')->get('foto_height_thumb')
											)
										{
												// Thumbnail erstellen
												$image = new JImage(JPATH_SITE . '/images/' . $filename);
												$image->resize(100, 150, true, JImage::SCALE_INSIDE)->toFile($path . '/' . $filename);
										}

								}

								else
								{
										// Thumbnail erstellen
										$image = new JImage(JPATH_SITE . '/images/' . $filename);
										$image->resize(100, 150, true, JImage::SCALE_INSIDE)->toFile($path . '/' . $filename);
								}

								chmod($path . '/' . $filename, 0666);
						}
				}
		}
}

?>
