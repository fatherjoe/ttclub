<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();


class WebttModelSpieler extends JModelAdmin
{
		public function getTable($type = 'spieler', $prefix = 'WebttTable', $config = array())
		{
				return $table = JTable::getInstance($type, $prefix, $config);
		}
		
		public function getForm($data = array(), $loadData = true)
		{
				// Get the form.
				$form = $this->loadForm(
					'com_webtt.spieler',
					'spieler',
					array(
						'control' => 'jform',
						'load_data' => $loadData
					)
				);
		 
				if (empty($form))
				{
					return false;
				}
		 
				return $form;
		}
	 
		/**
		 * Method to get the data that should be injected in the form.
		 *
		 * @return  mixed  The data for the form.
		 *
		 * @since   1.6
		 */
		protected function loadFormData()
		{
			// Check the session for previously entered form data.
			$data = JFactory::getApplication()->getUserState('com_webtt.edit.spieler.data', array());
	 
			if (empty($data))
			{
				$data = $this->getItem();
			}
	 
			return $data;
		}
}
