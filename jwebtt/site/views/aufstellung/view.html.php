<?php
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JLoader::import('helpers.webtt', JPATH_COMPONENT);
JLoader::import('helpers.aufstellung', JPATH_COMPONENT);
JLoader::import('helpers.ttr', JPATH_COMPONENT);
JLoader::import('helpers.titles', JPATH_COMPONENT);

 
class WebttViewAufstellung extends JViewLegacy
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
		$this->WebttHelperTtr = new WebttHelperTtr;
		$this->WebttHelperTitles = new WebttHelperTitles;

		/*
		 * DATEN AUS DEM MODEL HOLEN
		 */
				
		// ZEITPUNKT DER LETZTEN AKTUALISIERUNG
		$this->timestamp = $this->WebttHelper->getTimestamp('aufstellung');
		
		// XML DER LIGATABELLE
		$this->xml = $this->get('xml');
		
		// XML DER POPUPS
		if (JComponentHelper::getParams('com_webtt')->get('popups_spieler') == "css")
		{
			$this->popups = $this->get('XMLPopups');
		}

		else if (JComponentHelper::getParams('com_webtt')->get('aufst_anz_spieler_popup') == "milkbox")
		{
			$this->htmlPopupFiles = $this->get('HtmlPopupFiles');
		}

		// SPIELER => SPIELERFOTOS
		$this->spielerfotos = $this->get('ArraySpielerFotos');
		
		$this->HostClicktt = $this->WebttHelper->getHostClicktt();
		
		// NEUES OBJEKT FÜR BENÖTIGTE PARAMETER DER WEBTT-CONFIG
		$this->params = new stdClass;

		// ÜBERSCHRIFT H1
		$this->title_1 = $this->WebttHelperTitles->getH1();

		// ÜBERSCHRIFT H2
		$this->title_2 = $this->WebttHelperTitles->getH2();

		// ÜBERSCHRIFT H3
		$this->title_3 = $this->WebttHelperTitles->getH3();
		
		// AKTUALISIERUNGSINTERVALL
		$this->params->akt = JComponentHelper::getParams('com_webtt')->get('aufst_akt');

		// SPALTENANZEIGE
		$this->params->anz_pos						= JComponentHelper::getParams('com_webtt')->get('aufst_anz_pos');
		$this->params->anz_foto						= JComponentHelper::getParams('com_webtt')->get('aufst_anz_foto');
		$this->params->anz_spieler_box 				= JComponentHelper::getParams('com_webtt')->get('aufst_anz_spieler_box');
		$this->params->anz_spieler_box_datum		= JComponentHelper::getParams('com_webtt')->get('aufst_anz_spieler_box_datum');
		$this->params->anz_spieler_box_paarung		= JComponentHelper::getParams('com_webtt')->get('aufst_anz_spieler_box_paarung');
		$this->params->anz_spieler_box_gegnerteam	= JComponentHelper::getParams('com_webtt')->get('aufst_anz_spieler_box_gegnerteam');
		$this->params->anz_spieler_box_erg			= JComponentHelper::getParams('com_webtt')->get('aufst_anz_spieler_box_erg');
		$this->params->anz_eins 					= JComponentHelper::getParams('com_webtt')->get('aufst_anz_eins');
		$this->params->anz_bilanz					= JComponentHelper::getParams('com_webtt')->get('aufst_anz_bilanz');
		$this->params->anz_bw	 					= JComponentHelper::getParams('com_webtt')->get('aufst_anz_bw');
		$this->params->anz_qttr 					= JComponentHelper::getParams('com_webtt')->get('aufst_anz_qttr');
		$this->params->anz_ttr						= JComponentHelper::getParams('com_webtt')->get('aufst_anz_attr');
 		
 		// FOTO-EINSTELUUNGEN
 		$this->params->popups_foto					= JComponentHelper::getParams('com_webtt')->get('aufstellung_anz_foto_popup');
 		$this->params->thumb_width 					= JComponentHelper::getParams('com_webtt')->get('foto_aufstellung_width_thumb');
 		$this->params->thumb_height 				= JComponentHelper::getParams('com_webtt')->get('foto_aufstellung_height_thumb');


		$this->params->popups_spieler 				= JComponentHelper::getParams('com_webtt')->get('aufst_anz_spieler_popup');
		
		
		// QTTR-Werte
		if (JComponentHelper::getParams('com_webtt')->get('aufst_anz_qttr'))
		{
				$this->qttr = $this->WebttHelperQttr->getQTTR();
		}
		
		// TTR-Werte
		if (JComponentHelper::getParams('com_webtt')->get('aufst_anz_attr'))
		{
				$this->ttr = $this->WebttHelperTtr->getTTR();
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
		JHtml::_('stylesheet', JUri::root() . 'components/com_webtt/css/webtt.css');
		JHtml::_('stylesheet', JUri::root() . 'components/com_webtt/css/aufstellung.css');

		// CSS FÜR DIE MOOTOOLS-LIGHTBOX
		if ($this->params->popups_foto == "lightbox2")
		{
				JHtml::_('stylesheet', JUri::root() . 'media/com_webtt/css/lightbox2/lightbox.css');
		}

		if ($this->params->popups_foto == "milkbox" OR $this->params->popups_spieler == "milkbox")
		{
				JHtml::_('stylesheet', JUri::root() . 'media/com_webtt/css/milkbox/milkbox/milkbox.css');
		}

		// Display the view
		parent::display($tpl);
	}
}
