<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
 

class WebttModelSpielers extends JModelList
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
                    ->from('#__webtt_spieler');
 
                return $query;
        }

		public function getTable($type = 'spielers', $prefix = 'WebttTable', $config = array())
		{
				$table = JTable::getInstance($type, $prefix, $config);
		}

        public function getForm($data = array(), $loadData = true) 
        {
                // Get the form.
                $form = $this->loadForm('com_webtt.spielers', 'spielers',
                                        array('control' => 'jform', 'load_data' => $loadData));
                if (empty($form)) 
                {
                        return false;
                }
                return $form;
        }




		/*
		 * METHODEN ZUM SPEICHERN DER FOTODATEINAMEN IN DER SPIELER-TABELLE
		 * 
		 * 		wird vom Controller aus aufgerufen
		 * 
		 */
		 
		public function saveImagepaths($filenames)
		{
				foreach($filenames as $person => $filename)
				{
						$update = $this->UpdateFilename($person,$filename);

				}		

				return TRUE;
		}

		private function UpdateFilename($person,$filename)
		{
				// Schreiben der Dateinamen in die DB
				$db = JFactory::getDbo();
				 
				$query = $db->getQuery(true);
				 
				// Fields to update.
				$fields = array(
									$db->quoteName('foto') . '=' . $db->quote($filename)
								);
				 
				// Conditions for which records should be updated.
				$conditions = array(
					$db->quoteName('clicktt_nr') . ' = ' . $db->quote($person)
				);
				 
				$query->update($db->quoteName('#__webtt_spieler'))->set($fields)->where($conditions);
				
				$db->setQuery($query);
				 
				$result = $db->execute();
				
				return $result;
		}
}
