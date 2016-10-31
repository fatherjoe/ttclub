<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
JLoader::import('helpers.webtt', JPATH_COMPONENT);
JLoader::import('helpers.pokaluebersicht', JPATH_COMPONENT);


class WebttViewPokaluebersicht extends JViewLegacy
{
        function display($tpl = null) 
        {
				// HELPER-KLASSE
				$this->WebttHelper = new WebttHelper;

				/*
				 * DATEN AUS DEM MODEL HOLEN
				 */
				
				// XML DER TABELLENSTÄNDE
				$this->xml = $this->get('xml');
						
				// ZEITPUNKT DER LETZTEN AKTUALISIERUNG
				$this->timestamp = $this->WebttHelper->getTimestampVerein('pokaluebersicht');


                // Check for errors.
                if (count($errors = $this->get('Errors'))) 
                {
                        JError::raiseError(500, implode('<br />', $errors));
                        return false;
                }

				// CSS IN <HTML><HEAD> EINBINDEN
				JHtml::stylesheet('com_webtt/webtt.css', array(), true);
				JHtml::stylesheet('com_webtt/pokaluebersicht.css', array(), true);

                // Display the template
                parent::display($tpl);
        }
}
