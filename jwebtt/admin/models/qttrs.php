<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
 
/**
 * Staffelnamen Model
 *
 * @package    Joomla.Tutorials
 * @subpackage Components
 */
class WebttModelQttrs extends JModelList
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
                    ->select('id,datum,typ,werte')
                    ->from('#__webtt_ttr');
 
                return $query;
        }


		public function getTable($type = 'qttr', $prefix = 'WebttTable', $config = array())
		{
				$table = JTable::getInstance($type, $prefix, $config);
		}

}
