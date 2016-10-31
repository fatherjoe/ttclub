<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
 
jimport( 'joomla.application.component.modellist' );
 
/**
 * Webtt Model
 *
 * @package    Joomla.Tutorials
 * @subpackage Components
 */
class WebttModelWebtts extends JModelList
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
                    ->select('id,var,val')
                    ->from('#__webtt');
 
                return $query;
        }
}
