<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
 
 
/**
 * Webtt Model
 *
 * @package    Joomla.Tutorials
 * @subpackage Components
 */
class WebttModelMannschaftsnamens extends JModelList
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
                    ->select('id,clicktt,webtt')
                    ->from('#__webtt_mannsch');
 
                return $query;
        }


		public function getTable($type = 'mannschaftsnamen', $prefix = 'WebttTable', $config = array())
		{
				$table = JTable::getInstance($type, $prefix, $config);
		}

}
