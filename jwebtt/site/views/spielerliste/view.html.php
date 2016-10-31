<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
JLoader::import('helpers.webtt', JPATH_COMPONENT);
JLoader::import('helpers.spielerliste', JPATH_COMPONENT);
JLoader::import('helpers.qttr', JPATH_COMPONENT);
JLoader::import('helpers.ttr', JPATH_COMPONENT);
JLoader::import('helpers.fotos', JPATH_COMPONENT);

class WebttViewSpielerliste extends JViewLegacy
{
        function display($tpl = null) 
        {
				// HELPER-KLASSE
				$this->WebttHelper = new WebttHelper;
				$this->WebttHelperQttr = new WebttHelperQttr;
				$this->WebttHelperTtr = new WebttHelperTtr;
				$this->WebttHelperFotos = new WebttHelperFotos;

				// Erstellt Thumbnails, wenn nicht vorhanden bzw. korrigiert die Größe
				$this->WebttHelperFotos->createThumbnail();


				/*
				 * DATEN AUS DEM MODEL HOLEN
				 */
				
				// XML DES GESAMTSPIELPLANS
				$this->xml = $this->get('xml');

                // Get data from the model
                $items = $this->get('Items');
                $pagination = $this->get('Pagination');
				$form = $this->get('Form');

                // Assign data to the view
				$this->state = $this->get('State');
                $this->items = $items;
                $this->pagination = $pagination;
                $this->form = $form;
						
				// ZEITPUNKT DER LETZTEN AKTUALISIERUNG
				$this->timestamp = $this->WebttHelper->getTimestamp('spielerliste');

				// QTTR-Werte
				if (JComponentHelper::getParams('com_webtt')->get('spielerliste_anz_qttr'))
				{
						$this->qttr = $this->WebttHelperQttr->getQTTR();
				}
				
				// TTR-Werte
				if (JComponentHelper::getParams('com_webtt')->get('aufst_anz_attr'))
				{
						$this->ttr = $this->WebttHelperTtr->getTTR();
				}

                // Check for errors.
                if (count($errors = $this->get('Errors'))) 
                {
                        JError::raiseError(500, implode('<br />', $errors));
                        return false;
                }

				// CSS IN <HTML><HEAD> EINBINDEN
				JHtml::stylesheet('com_webtt/webtt.css', array(), true);
				JHtml::stylesheet('com_webtt/spielerliste.css', array(), true);

				$this->params = new StdClass();

				$this->params->anz_foto = JComponentHelper::getParams('com_webtt')->get('spielerliste_anz_foto');


				// FOTO-EINSTELUUNGEN
				$this->params->popups_foto = JComponentHelper::getParams('com_webtt')->get('spielerliste_anz_foto_popup');
				$this->params->thumb_width = JComponentHelper::getParams('com_webtt')->get('foto_spielerliste_width_thumb');
				$this->params->thumb_height = JComponentHelper::getParams('com_webtt')->get('foto_spielerliste_height_thumb');

				// CSS FÜR DIE LIGHTBOX
				if ($this->params->popups_foto == "lightbox2")
				{
						JHtml::_('stylesheet', JUri::root() . 'media/com_webtt/css/lightbox2/lightbox.css');
				}

				if ($this->params->popups_foto == "milkbox")
				{
						JHtml::_('stylesheet', JUri::root() . 'media/com_webtt/css/milkbox/milkbox/milkbox.css');
				}

                // Display the template
                parent::display($tpl);
        }
}
