<?php
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');


JLoader::import('helpers.webtt', JPATH_COMPONENT);
JLoader::import('helpers.titles', JPATH_COMPONENT);

 
class WebttViewTabelle extends JViewLegacy
{
		function display($tpl = null)
		{
				// HELPER-KLASSE
				$this->WebttHelper = new WebttHelper;
				$this->WebttHelperTitles = new WebttHelperTitles;
				/*
				 * DATEN AUS DEM MODEL HOLEN
				 */
						
				// ANZUZEIGENDE WEBTT-MANNSCHAFT
				$this->team = $this->WebttHelper->getTeam();

				// ZEITPUNKT DER LETZTEN AKTUALISIERUNG
				$this->timestamp = $this->get('row')->timestamp;

				// CLICKTT-HOST
				$this->HostClicktt = $this->WebttHelper->getHostClicktt();

				// XML DER LIGATABELLE
				$this->xml = $this->get('row')->xml;
				
				// XML DER POPUPS
				$model = $this->getModel();

				// XML DER POPUPS
				if (JComponentHelper::getParams('com_webtt')->get('tabelle_anz_mannschaft_popup') == "css")
				{
					$this->popups = $this->get('XMLPopups');
				}

				else if (JComponentHelper::getParams('com_webtt')->get('tabelle_anz_mannschaft_popup') == "milkbox")
				{
					$this->htmlPopupFiles = $this->get('HtmlPopupFiles');
				}

				
				// ÜBERSCHRIFT H1
				$this->title_1 = $this->WebttHelperTitles->getH1();

				// ÜBERSCHRIFT H2
				$this->title_2 = $this->WebttHelperTitles->getH2();

				// ÜBERSCHRIFT H3
				$this->title_3 = $this->WebttHelperTitles->getH3();

				// NEUES OBJEKT FÜR BENÖTIGTE PARAMETER DER WEBTT-CONFIG
				$this->params = new stdClass;
				
				// AKTUALISIERUNGSINTERVALL
				$this->params->akt = JComponentHelper::getParams('com_webtt')->get('tabelle_akt');

				// SPALTENANZEIGE
				$this->params->anz_tendenz 			= JComponentHelper::getParams('com_webtt')->get('tabelle_anz_tendenz');
				$this->params->anz_mann_box 		= JComponentHelper::getParams('com_webtt')->get('tabelle_anz_mann_box');
				$this->params->anz_mann_box_pos 	= JComponentHelper::getParams('com_webtt')->get('tabelle_anz_mann_box_pos');
				$this->params->anz_mann_box_bil 	= JComponentHelper::getParams('com_webtt')->get('tabelle_anz_mann_box_bil');
				$this->params->anz_mann_box_bw 		= JComponentHelper::getParams('com_webtt')->get('tabelle_anz_mann_box_bw');
				$this->params->anz_mann_box_qttr	= JComponentHelper::getParams('com_webtt')->get('tabelle_anz_mann_box_qttr');
				$this->params->anz_begegnungen 		= JComponentHelper::getParams('com_webtt')->get('tabelle_anz_begegnungen');
				$this->params->anz_details 			= JComponentHelper::getParams('com_webtt')->get('tabelle_anz_details');
				$this->params->anz_spiele 			= JComponentHelper::getParams('com_webtt')->get('tabelle_anz_spiele');
				$this->params->anz_diff 			= JComponentHelper::getParams('com_webtt')->get('tabelle_anz_diff');
				$this->params->anz_punkte 			= JComponentHelper::getParams('com_webtt')->get('tabelle_anz_punkte');
				
				$this->params->popups_mannschaften 	= JComponentHelper::getParams('com_webtt')->get('tabelle_anz_mannschaft_popup');
				
		 
				
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
				JHtml::stylesheet('com_webtt/tabelle.css', array(), true);
		 
				// CSS FÜR DIE MOOTOOLS-LIGHTBOX
				if ($this->params->popups_mannschaften == "milkbox")
				{
						JHtml::_('stylesheet', JUri::root() . 'media/com_webtt/css/milkbox/milkbox/milkbox.css');
				}

				// Display the view
				parent::display($tpl);
		}
}
