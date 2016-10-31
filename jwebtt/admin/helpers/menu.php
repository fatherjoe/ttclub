<?php

// no direct access
defined('_JEXEC') or die;

JLoader::import('helpers.webtt', JPATH_COMPONENT_ADMINISTRATOR);

class WebttHelperMenu extends WebttHelper
{
		// XML FÜR MENUEPUNKTE ERSTELLEN
		public function createMenupoints()
		{
				// GESPEICHERTE MANNSCHAFTEN ABRUFEN
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('name_webtt')
                    ->from('#__webtt_teams')
                    ->where('typ=' . $db->quote('punkt'));
									
				$db->setQuery($query);

				$teams = $db->loadColumn();


				foreach(array("Aufstellung", "Spielplan", "Tabelle") as $v)
				{
						$title = "$v";
						$message = "$v einer Mannschaft";
						
						$xml = '<?xml version="1.0" encoding="utf-8"?>
<metadata>
	<layout title="' . $title . '">
		<message>' . $message . '</message>
	</layout>
	<fields name="request">
		<fieldset name="request">
			<field
				name="team"
				type="list"
				label="Mannschaft"
				description="">';
				
						foreach ($teams as $team)
						{
								$xml .= '
								<option value="' . $team . '">' . $team . '</option>';
						}
				
						$xml .= '
			</field>
		</fieldset>
	</fields>
</metadata>';

						JFile::write(JPATH_SITE . '/components/com_webtt/views/' . strtolower($v) . '/tmpl/default.xml', $xml);
				}

				// MENÜPUNKTE DER POKALSEITEN
				// GESPEICHERTE MANNSCHAFTEN ABRUFEN
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query
                    ->select('name_webtt')
                    ->from('#__webtt_teams')
                    ->where('typ=' . $db->quote('pokal'));
									
				$db->setQuery($query);

				$teams = $db->loadColumn();


				$title = "Pokal";
				$message = "Pokalseite einer Mannschaft";
						
				$xml = '<?xml version="1.0" encoding="utf-8"?>
<metadata>
	<layout title="' . $title . '">
		<message>' . $message . '</message>
	</layout>
	<fields name="request">
		<fieldset name="request">
			<field
				name="team"
				type="list"
				label="Mannschaft"
				description=""
				default="1">';
				
				foreach ($teams as $team)
				{
						$xml .= '
						<option value="' . $team . '">' . $team . '</option>';
				}
				
				$xml .= '
			</field>
		</fieldset>
	</fields>
</metadata>';

				JFile::write(JPATH_SITE . '/components/com_webtt/views/pokal/tmpl/default.xml', $xml);
		}
}

?>
