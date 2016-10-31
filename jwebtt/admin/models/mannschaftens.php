<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
 
/**
 * Mannschaften Model
 *
 * @package    Joomla.Tutorials
 * @subpackage Components
 */
class WebttModelMannschaftens extends JModelList
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
                    ->from('#__webtt_teams');
 
                return $query;
        }


		public function getTable($type = 'mannschaften', $prefix = 'WebttTable', $config = array())
		{
				$table = JTable::getInstance($type, $prefix, $config);
		}

}
