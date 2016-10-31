<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
 
jimport( 'joomla.application.component.modellist' );
 
/**
 * Staffelnamen Model
 *
 * @package    Joomla.Tutorials
 * @subpackage Components
 */
class WebttModelStaffelnamens extends JModelList
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
                    ->select('id,clicktt_kurz,clicktt_lang')
                    ->from('#__webtt_staffeln');
 
                return $query;
        }


		public function getTable($type = 'staffelnamen', $prefix = 'WebttTable', $config = array())
		{
				$table = JTable::getInstance($type, $prefix, $config);
		}

}
