<?php
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JLoader::import('helpers.webtt', JPATH_COMPONENT);
JLoader::import('helpers.vspielplan', JPATH_COMPONENT);
JLoader::import('helpers.qttr', JPATH_COMPONENT);

 
class WebttViewVspielplan extends JViewLegacy
{
		function display($tpl = null)
		{
				// HELPER-KLASSE
				$this->WebttHelper = new WebttHelper;
				$this->WebttHelperQttr = new WebttHelperQttr;

				/*
				 * DATEN AUS DEM MODEL HOLEN
				 */

				// ZEITPUNKT DER LETZTEN AKTUALISIERUNG
				$this->timestamp = $this->WebttHelper->getTimestamp('vspielplan');
				
				// XML DES GESAMTSPIELPLANS
				$this->xml = $this->get('xml');
						
				// STAFFELZUORDNUNGEN
				
				
				// PFADE DER STAFFELN ZU CLICKTT
		//		$this->path_league = $this->get('pathleagueclicktt');
				
				// XML DER POPUPS
				if (JComponentHelper::getParams('com_webtt')->get('vspielplan_anz_erg_popup') == "css")
				{
					$this->popups = $this->get('XMLPopups');
				}

				else if (JComponentHelper::getParams('com_webtt')->get('spielplan_anz_erg_popup') == "mootools")
				{
					$this->htmlPopupFiles = $this->get('HtmlPopupFiles');
				}

				

				// NEUES OBJEKT FÜR BENÖTIGTE PARAMETER DER WEBTT-CONFIG
				$this->params = new stdClass;

				// ÜBERSCHRIFT H1
				$this->params->title = JComponentHelper::getParams('com_webtt')->get('spielpl_title');

				// ANZEIGE DER ÜBERSCHRIFT
				$this->params->title_anz = JComponentHelper::getParams('com_webtt')->get('spielpl_title_anz');
				
				// AKTUALISIERUNGSINTERVALL
				$this->params->akt = JComponentHelper::getParams('com_webtt')->get('spielpl_akt');

				// SPALTENANZEIGE
				$this->params->anz_nr						= JComponentHelper::getParams('com_webtt')->get('spielpl_verein_anz_nr');
				$this->params->anz_tag						= JComponentHelper::getParams('com_webtt')->get('spielpl_verein_anz_tag');
				$this->params->anz_datum 					= JComponentHelper::getParams('com_webtt')->get('spielpl_verein_anz_datum');
				$this->params->anz_uhrzeit					= JComponentHelper::getParams('com_webtt')->get('spielpl_verein_anz_zeit');
				$this->params->anz_staffel 					= JComponentHelper::getParams('com_webtt')->get('spielpl_verein_anz_liga');
				$this->params->anz_halle 					= JComponentHelper::getParams('com_webtt')->get('spielpl_verein_anz_halle');
				$this->params->anz_erg		 				= JComponentHelper::getParams('com_webtt')->get('spielpl_verein_anz_erg');
				$this->params->anz_infotips					= JComponentHelper::getParams('com_webtt')->get('spielpl_verein_anz_infotips');
		 

				$this->params->popups_hallen				= JComponentHelper::getParams('com_webtt')->get('vspielplan_anz_halle_popup'); 				
				$this->params->popups_erg					= JComponentHelper::getParams('com_webtt')->get('vspielplan_anz_erg_popup'); 				

				$this->params->route_start					= JComponentHelper::getParams('com_webtt')->get('hallen_maps_route_start'); 				

				// KALENDERDOWNLOAD
				$this->params->anz_vcal						= JComponentHelper::getParams('com_webtt')->get('vspielplan_anz_vcal');
				$this->params->anz_ical						= JComponentHelper::getParams('com_webtt')->get('vspielplan_anz_ical');
				
				// QTTR-Werte
				if (JComponentHelper::getParams('com_webtt')->get('spielpl_anz_qttr'))
				{
						$this->qttr = $this->WebttHelperQttr->getQTTR();
				}
				

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
				JHtml::stylesheet('com_webtt/vspielplan.css', array(), true);

				// CSS FÜR DIE MOOTOOLS-LIGHTBOX
				if ($this->params->popups_hallen == "jquery" OR $this->params->popups_erg == "jquery")
				{
						JHtml::_('stylesheet', JUri::root() . 'media/com_webtt/css/lightbox2/lightbox.css');
				}

				// CSS FÜR DIE JQUERY-LIGHTBOX
				if ($this->params->popups_hallen == "mootools" OR $this->params->popups_erg == "mootools")
				{
						JHtml::_('stylesheet', JUri::root() . 'media/com_webtt/css/milkbox/milkbox/milkbox.css');
				}


				// Display the view
				parent::display($tpl);
		}
}
