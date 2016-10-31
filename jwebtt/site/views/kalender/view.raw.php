<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla view library
jimport('joomla.application.component.view');
 
/**
 * Kalender View
 */
class WebttViewKalender extends JViewLegacy
{
        /**
         * display method of Webtt view
         * @return void
         */
        function display($tpl = null) 
        {

				// get the document
				$document = @JFactory::getDocument();

				// set the MIME type
				$document->setMimeEncoding('text/plain');

				// get the item we want to display
				$item = @$this->get('Kalender');

				// output the text file
				ob_start();
				header("Content-Type: text/x-vCalendar");
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Type: application/download");
				header("Content-Disposition: attachment; filename=" . $item->mannschaft . "." . $item->suffix);
				echo $item->kalender;
        }
 }
