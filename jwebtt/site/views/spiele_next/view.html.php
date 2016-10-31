<?php
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JLoader::import('helpers.webtt', JPATH_COMPONENT);
JLoader::import('helpers.titles', JPATH_COMPONENT);

 
class WebttViewSpiele_next extends JViewLegacy
{
		/**
		 * Display the WebTT view
		 *
		 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
		 *
		 * @return  void
		 */
		function display($tpl = null)
		{
				// HELPER-KLASSE
				$this->WebttHelper = new WebttHelper;
				$this->WebttHelperTitles = new WebttHelperTitles;

				/*
				 * DATEN AUS DEM MODEL HOLEN
				 */

				// XML DES GESAMTSPIELPLANS
				$this->xml = $this->get('xml');
						
				// ZEITPUNKT DER LETZTEN AKTUALISIERUNG
				$this->timestamp = $this->WebttHelper->getTimestampVerein('spiele_next');


				// NEUES OBJEKT FÜR BENÖTIGTE PARAMETER DER WEBTT-CONFIG
				$this->params = new stdClass;

				// ÜBERSCHRIFT H1
				$this->title_1 = $this->WebttHelperTitles->getH1();
				
				// AKTUALISIERUNGSINTERVALL
				$this->params->akt = JComponentHelper::getParams('com_webtt')->get('spiele_next_akt');

				// SPALTENANZEIGE
				$this->params->anz_nr						= JComponentHelper::getParams('com_webtt')->get('spiele_next_anz_nr');
				$this->params->anz_tag						= JComponentHelper::getParams('com_webtt')->get('spiele_next_anz_tag');
				$this->params->anz_datum 					= JComponentHelper::getParams('com_webtt')->get('spiele_next_anz_datum');
				$this->params->anz_uhrzeit					= JComponentHelper::getParams('com_webtt')->get('spiele_next_anz_zeit');
				$this->params->anz_staffel 					= JComponentHelper::getParams('com_webtt')->get('spiele_next_anz_liga');
				$this->params->anz_halle 					= JComponentHelper::getParams('com_webtt')->get('spiele_next_anz_halle');
				$this->params->anz_halle_box				= JComponentHelper::getParams('com_webtt')->get('spiele_next_anz_halle_box'); 

				$this->params->popups_hallen				= JComponentHelper::getParams('com_webtt')->get('spiele_next_anz_halle_popup'); 				
				$this->params->popups_erg					= JComponentHelper::getParams('com_webtt')->get('spiele_next_anz_erg_popup'); 				

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
				JHtml::stylesheet('com_webtt/spiele_next.css', array(), true);
		 
				// CSS FÜR DIE JQUERY-LIGHTBOX
				if ($this->params->popups_hallen == "lightbox2")
				{
						JHtml::_('stylesheet', JUri::root() . 'media/com_webtt/css/lightbox2/lightbox.css');
				}

				// CSS FÜR DIE MOOTOOLS-LIGHTBOX
				if ($this->params->popups_hallen == "milkbox")
				{
						JHtml::_('stylesheet', JUri::root() . 'media/com_webtt/css/milkbox/milkbox/milkbox.css');
				}
				
				// Display the view
				parent::display($tpl);
		}
}
