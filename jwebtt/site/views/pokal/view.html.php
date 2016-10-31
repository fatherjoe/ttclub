<?php
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JLoader::import('helpers.webtt', JPATH_COMPONENT);
JLoader::import('helpers.pokal', JPATH_COMPONENT);
JLoader::import('helpers.qttr', JPATH_COMPONENT);

 
class WebttViewPokal extends JViewLegacy
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
				$this->WebttHelperQttr = new WebttHelperQttr;

				/*
				 * DATEN AUS DEM MODEL HOLEN
				 */
						
				// ANZUZEIGENDE WEBTT-MANNSCHAFT
				$this->team = $this->get('team');

				// XML DER LIGATABELLE
				$this->xml = $this->get('xml');
						
				// ZEITPUNKT DER LETZTEN AKTUALISIERUNG
				$this->timestamp = $this->WebttHelper->getTimestamp('pokal');
				
				// NAME DER CLICKTT-STAFFEL
				$this->league = $this->WebttHelper->getPokalLeagueClicktt();
				
				// PFAD DER STAFFEL ZU CLICKTT
				$this->path_league = $this->WebttHelper->getPathLeagueClicktt();
				
				// XML DER POPUPS
				if (JComponentHelper::getParams('com_webtt')->get('pokal_anz_erg_popup') == "css")
				{
						$this->popups = $this->get('XMLPopups');
				}
				
				else if (JComponentHelper::getParams('com_webtt')->get('pokal_anz_erg_popup') == "milkbox")
				{
						// HTML-Dateien der Spielberichte werden erstellt, es wird nichts zurückgeliefert
						$this->htmlPopupFiles = $this->get('HtmlPopupFiles');
				}
				
				// NEUES OBJEKT FÜR BENÖTIGTE PARAMETER DER WEBTT-CONFIG
				$this->params = new stdClass;

				// LANDESVERBAND
				$this->params->verband = JComponentHelper::getParams('com_webtt')->get('verband');

				// ÜBERSCHRIFT H1
				$this->params->title = JComponentHelper::getParams('com_webtt')->get('pokal_title');

				// ANZEIGE DER ÜBERSCHRIFT
				$this->params->title_anz = JComponentHelper::getParams('com_webtt')->get('pokal_title_anz');
				
				// AKTUALISIERUNGSINTERVALL
				$this->params->akt = JComponentHelper::getParams('com_webtt')->get('pokal_akt');

				// SPALTENANZEIGE
				$this->params->anz_tag						= JComponentHelper::getParams('com_webtt')->get('pokal_anz_tag');
				$this->params->anz_datum 					= JComponentHelper::getParams('com_webtt')->get('pokal_anz_datum');
				$this->params->anz_uhrzeit					= JComponentHelper::getParams('com_webtt')->get('pokal_anz_zeit');
				$this->params->anz_liga	 					= JComponentHelper::getParams('com_webtt')->get('pokal_anz_liga');
				$this->params->anz_halle 					= JComponentHelper::getParams('com_webtt')->get('pokal_anz_halle');
				$this->params->anz_erg		 				= JComponentHelper::getParams('com_webtt')->get('pokal_anz_erg');
				$this->params->anz_infotips					= JComponentHelper::getParams('com_webtt')->get('pokal_anz_infotips');

				$this->params->popups_hallen				= JComponentHelper::getParams('com_webtt')->get('pokal_anz_halle_popup'); 				
				$this->params->popups_erg					= JComponentHelper::getParams('com_webtt')->get('pokal_anz_erg_popup'); 				

				$this->params->route_start					= JComponentHelper::getParams('com_webtt')->get('hallen_maps_route_start'); 				


				// QTTR-Werte
				if (JComponentHelper::getParams('com_webtt')->get('pokal_anz_qttr'))
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
				JHtml::stylesheet('com_webtt/pokal.css', array(), true);

				// CSS FÜR DIE MOOTOOLS-LIGHTBOX
				if ($this->params->popups_hallen == "lightbox2" OR $this->params->popups_erg == "lightbox2")
				{
						JHtml::_('stylesheet', JUri::root() . 'media/com_webtt/css/lightbox2/lightbox.css');
				}

				// CSS FÜR DIE JQUERY-LIGHTBOX
				if ($this->params->popups_hallen == "milkbox" OR $this->params->popups_erg == "milkbox")
				{
						JHtml::_('stylesheet', JUri::root() . 'media/com_webtt/css/milkbox/milkbox/milkbox.css');
				}

		 
				// Display the view
				parent::display($tpl);
		}
}
