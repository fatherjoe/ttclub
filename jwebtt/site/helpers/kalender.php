<?php

// no direct access
defined('_JEXEC') or die;

JLoader::import('helpers.webtt', JPATH_COMPONENT);

/*
 * Klasse zum Speichern und Auslesen der Kalender
 * 
 */

class WebttHelperKalender extends WebttHelper
{
		public function storeKalender($mannschaft,$vcal,$ical,$serie='gesamt')
		{		
				// Überprüfen, ob die Tabelle schon existiert --> insert oder update           
				$db = JFactory::getDBO();
				$query = $db->getQuery(true);

				$query
					->select('datum')
					->from('#__webtt_kalender')
					->where(array(
									'mannschaft=' . $db->quote($mannschaft),
									$db->quoteName('serie') . ' = ' . $db->quote($serie)
									)
							);
 
				$db->setQuery($query);
				
				// UPDATE
				if (isset($db->loadObject()->datum))
				{
						$db = JFactory::getDbo();
						$query = $db->getQuery(true);
						 
						$fields = array(
							$db->quoteName('vcal') . ' = ' . $db->quote($vcal),
							$db->quoteName('ical') . ' = ' . $db->quote($ical),
							$db->quoteName('datum') . ' = ' . $db->quote(date("Y-m-d H:i:s"))
						);
						 
						$conditions = array(
												$db->quoteName('mannschaft') . ' = ' . $db->quote($mannschaft),
												$db->quoteName('serie') . ' = ' . $db->quote($serie)
											);
						 
						$query->update($db->quoteName('#__webtt_kalender'))->set($fields)->where($conditions);
						 
						$db->setQuery($query);
						 
						$result = $db->execute();
				}
				
				// INSERT
				else
				{
						$db = JFactory::getDbo();
						$query = $db->getQuery(true);
						 
						// Fields to update.
						$fields = array(
							$db->quoteName('datum') . ' = ' . $db->quote(date("Y-m-d H:i:s")),
							$db->quoteName('mannschaft') . ' = ' . $db->quote($mannschaft),
							$db->quoteName('serie') . ' = ' . $db->quote($serie),
							$db->quoteName('vcal') . ' = ' . $db->quote($vcal),
							$db->quoteName('ical') . ' = ' . $db->quote($ical)
						);
						 
						$query->insert($db->quoteName('#__webtt_kalender'))->set($fields);
						 
						$db->setQuery($query);
						 
						$result = $db->execute();
				}
				
						return $result;
		}
}

?>
