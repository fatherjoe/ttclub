<?php
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JLoader::import('helpers.webtt', JPATH_COMPONENT);

 
class WebttViewHallen extends JViewLegacy
{
		function display($tpl = null)
		{
				/*
				 * DATEN AUS DEM MODEL HOLEN
				 */
				
				// XML DER LIGATABELLE
				$this->hallen = $this->get('HallenVerein');

				
				// NEUES OBJEKT FÜR BENÖTIGTE PARAMETER DER WEBTT-CONFIG
				$this->params = new stdClass;

				// ÜBERSCHRIFT H1
				$this->params->title = JComponentHelper::getParams('com_webtt')->get('hallen_title');

				// ANZEIGE DER ÜBERSCHRIFT
				$this->params->title_anz = JComponentHelper::getParams('com_webtt')->get('hallen_title_anz');
				
				// SPALTENANZEIGE
				$this->params->streetview					= JComponentHelper::getParams('com_webtt')->get('hallen_maps_streetview');
				$this->params->streetview_type				= JComponentHelper::getParams('com_webtt')->get('hallen_maps_streetview_type');
				$this->params->streetview_width				= JComponentHelper::getParams('com_webtt')->get('hallen_maps_streetview_width');
				$this->params->streetview_height			= JComponentHelper::getParams('com_webtt')->get('hallen_maps_streetview_height');
				$this->params->karte						= JComponentHelper::getParams('com_webtt')->get('hallen_maps_karte');
				$this->params->karte_type					= JComponentHelper::getParams('com_webtt')->get('hallen_maps_karte_type');
				$this->params->karte_zoom					= JComponentHelper::getParams('com_webtt')->get('hallen_maps_karte_zoom');
				$this->params->karte_width					= JComponentHelper::getParams('com_webtt')->get('hallen_maps_karte_width');
				$this->params->karte_height					= JComponentHelper::getParams('com_webtt')->get('hallen_maps_karte_height');
				$this->params->api			 				= JComponentHelper::getParams('com_webtt')->get('hallen_maps_api');
				$this->params->lat			 				= JComponentHelper::getParams('com_webtt')->get('hallen_maps_lat');
				$this->params->lng			 				= JComponentHelper::getParams('com_webtt')->get('hallen_maps_lng');
		 
		 
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
				JHtml::stylesheet('com_webtt/hallen.css', array(), true);
		 
				// Display the view
				parent::display($tpl);
		}
}
