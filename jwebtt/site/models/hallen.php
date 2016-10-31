<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();


class WebttModelHallen extends JModelList
{
        public function getHallenVerein()
        {
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);
                
                $query
                    ->select('*')
                    ->from('#__webtt_hallen')
                    ->where('verein='. $db->quote(JComponentHelper::getParams('com_webtt')->get('verein')));
 
				$db->setQuery($query);

				$results = $db->loadObject();

                return $results;
        }
}
