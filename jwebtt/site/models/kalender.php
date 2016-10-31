<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
 
jimport( 'joomla.application.component.modellist' );
 
/**
 * Kalender Model
 *
 * @package    Joomla.Site
 * @subpackage com_webtt
 */
class WebttModelKalender extends JModelList
{
        /**
         * Method to build an SQL query to load the list data.
         *
         * @return      string  An SQL query
         */
        protected function getListQuery()
        {
                // Create a new query object.           
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);
                
                // Select some fields from the hello table
                $query
                    ->select('*')
                    ->from('#__webtt_kalender');
 
                return $query;
        }


		public function getTable($type = 'kalender', $prefix = 'WebttTable', $config = array())
		{
				$table = JTable::getInstance($type, $prefix, $config);
		}
		
		public function getKalender()
		{
				// GET-Variablen abfragen
				$jinput = JFactory::getApplication()->input;
				$typ = $jinput->get('typ', null, null);
				$mannschaft = $jinput->get('mannschaft', null, null);
				
				if ($typ == "vcal")
				{
						$suffix = "vcs";
				}
				
				else if ($typ == "ical")
				{
						$suffix = "ics";
				}



                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select($typ)
                    ->from('#__webtt_kalender')
                    ->where($db->quoteName('mannschaft') . '=' . $db->quote($mannschaft));

				$db->setQuery($query);
								 
				$result->kalender = $db->loadObject()->{$typ};
				$result->mannschaft = $mannschaft;
				$result->typ = $typ;
				$result->suffix = $suffix;
				
				return $result;
		}

}
