<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
 
jimport( 'joomla.application.component.modellist' );
jimport('joomla.filesystem.folder');
 
class WebttModelFotos extends JModelList
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

		public function getTable($type = 'fotos', $prefix = 'WebttTable', $config = array())
		{
				$table = JTable::getInstance($type, $prefix, $config);
		}

        public function getForm($data = array(), $loadData = true) 
        {
                // Get the form.
                $form = $this->loadForm('com_webtt.fotos', 'fotos',
                                        array('control' => 'jform', 'load_data' => $loadData));
                if (empty($form)) 
                {
                        return false;
                }
                return $form;
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

		public function saveBeschreibung($beschreibungen)
		{
				foreach($beschreibungen as $person => $beschreibung)
				{
						$beschreibung = htmlspecialchars($beschreibung);

						$update = $this->UpdateBeschreibung($person,$beschreibung);

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

		private function UpdateBeschreibung($person,$beschreibung)
		{
				// Schreiben der Dateinamen in die DB
				$db = JFactory::getDbo();
				 
				$query = $db->getQuery(true);
				 
				// Fields to update.
				$fields = array(
									$db->quoteName('beschreibung') . '=' . $db->quote($beschreibung)
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
