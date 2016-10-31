<?php
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JLoader::import('helpers.webtt', JPATH_COMPONENT);
JLoader::import('helpers.spiele_rec', JPATH_COMPONENT);
JLoader::import('helpers.titles', JPATH_COMPONENT);
JLoader::import('helpers.qttr', JPATH_COMPONENT);

 
class WebttViewSpiele_rec extends JViewLegacy
{
		function display($tpl = null)
		{
				// HELPER-KLASSE
				$this->WebttHelper = new WebttHelper;
				$this->WebttHelperTitles = new WebttHelperTitles;
				$this->WebttHelperQttr = new WebttHelperQttr;

				/*
				 * DATEN AUS DEM MODEL HOLEN
				 */

				// XML DES GESAMTSPIELPLANS
				$this->xml = $this->get('xml');
						
				// ZEITPUNKT DER LETZTEN AKTUALISIERUNG
				$this->timestamp = $this->WebttHelper->getTimestampVerein('spiele_rec');

				
				// XML DER POPUPS
				if (JComponentHelper::getParams('com_webtt')->get('spiele_rec_anz_erg_popup') == "css")
				{
					$this->popups = $this->get('XMLPopups');
				}

				else if (JComponentHelper::getParams('com_webtt')->get('spiele_rec_anz_erg_popup') == "milkbox")
				{
					$this->htmlPopupFiles = $this->get('HtmlPopupFiles');
				}

				

				// NEUES OBJEKT FÜR BENÖTIGTE PARAMETER DER WEBTT-CONFIG
				$this->params = new stdClass;

				// ÜBERSCHRIFT H1
				$this->params->title = JComponentHelper::getParams('com_webtt')->get('spiele_rec_title');

				// ANZEIGE DER ÜBERSCHRIFT
				$this->params->title_anz = JComponentHelper::getParams('com_webtt')->get('spiele_rec_title_anz');
				
				// AKTUALISIERUNGSINTERVALL
				$this->params->akt = JComponentHelper::getParams('com_webtt')->get('spiele_rec_akt');

				// SPALTENANZEIGE
				$this->params->anz_tag						= JComponentHelper::getParams('com_webtt')->get('spiele_rec_anz_tag');
				$this->params->anz_datum 					= JComponentHelper::getParams('com_webtt')->get('spiele_rec_anz_datum');
				$this->params->anz_uhrzeit					= JComponentHelper::getParams('com_webtt')->get('spiele_rec_anz_zeit');
				$this->params->anz_staffel 					= JComponentHelper::getParams('com_webtt')->get('spiele_rec_anz_liga');
				$this->params->anz_halle 					= JComponentHelper::getParams('com_webtt')->get('spiele_rec_anz_halle');
				$this->params->anz_halle_box				= JComponentHelper::getParams('com_webtt')->get('spiele_rec_anz_halle_box');
				$this->params->anz_erg		 				= JComponentHelper::getParams('com_webtt')->get('spiele_rec_anz_erg');
				$this->params->anz_erg_box	 				= JComponentHelper::getParams('com_webtt')->get('spiele_rec_anz_erg_box');
				$this->params->anz_infotips					= JComponentHelper::getParams('com_webtt')->get('spiele_rec_anz_infotips');
				

				$this->params->popups_hallen				= JComponentHelper::getParams('com_webtt')->get('spiele_rec_anz_halle_popup'); 				
				$this->params->popups_erg					= JComponentHelper::getParams('com_webtt')->get('spiele_rec_anz_erg_popup'); 				

				$this->params->route_start					= JComponentHelper::getParams('com_webtt')->get('hallen_maps_route_start'); 				


				/*
				 *	AUF FEHLER ÜBERPRÜFEN
				 */
				 
				if (count($errors = $this->get('Errors')))
				{
					JLog::add(implode('<br />', $errors), JLog::WARNING, 'jerror');
		 
					return false;
				}
		 
				// CSS IN <HTML><HEAD> EINBINDEN
				JHtml::stylesheet('com_webtt/webtt.css', array(), true);
				JHtml::stylesheet('com_webtt/spiele_rec.css', array(), true);

				// CSS FÜR DIE JQUERY-LIGHTBOX
				if ($this->params->popups_hallen == "lightbox2" OR $this->params->popups_erg == "lightbox2")
				{
						JHtml::_('stylesheet', JUri::root() . 'media/com_webtt/css/lightbox2/lightbox.css');
				}

				// CSS FÜR DIE MOOTOOLS-LIGHTBOX
				if ($this->params->popups_hallen == "milkbox" OR $this->params->popups_erg == "milkbox")
				{
						JHtml::_('stylesheet', JUri::root() . 'media/com_webtt/css/milkbox/milkbox/milkbox.css');
				}

		 
				// Display the view
				parent::display($tpl);
		}
}
