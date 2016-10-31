<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();



class WebttModelSpielerliste extends JModelList
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

                $query
                    ->select('*')
                    ->from('#__webtt_spieler');
 
                return $query;
        }

		public function getImagelist()
		{
				$path = JPATH_SITE . '/images';
				$recurse = FALSE;
				$fullpath = FALSE;
				$exclude = "";
				$filter = ".jpg|.png";
				
				$filelist = JFolder::files($path, $filter, $recurse, $fullpath , $exclude);
				
				return $filelist;
		}
}
