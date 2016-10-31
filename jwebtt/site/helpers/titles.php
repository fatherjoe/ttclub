<?php

// no direct access
defined('_JEXEC') or die;

JLoader::import('helpers.webtt', JPATH_COMPONENT);

/*
 * Klasse Überschriften
 * 
 */

class WebttHelperTitles extends WebttHelper
{
		// LIEST DIE BILANZWERTE VON CLICKTT AUS UND GIBT SIE ALS ASS. ARRAY ZURÜCK
		public function getH1()
		{
				$var_title = parent::getView() . '_title';
				$var_anz =  parent::getView() . '_title_anz';
				$h1 = "";

				if (JComponentHelper::getParams('com_webtt')->get($var_anz))
				{
						$h1 = "<h1>" . JComponentHelper::getParams('com_webtt')->get($var_title) . "</h1>";

				}

				return $h1;
		}

		// LIEST DIE BILANZWERTE VON CLICKTT AUS UND GIBT SIE ALS ASS. ARRAY ZURÜCK
		public function getH2()
		{
				$var_anz = parent::getView() . '_team_anz';
				$h2 = "";

				if (JComponentHelper::getParams('com_webtt')->get($var_anz))
				{
						$h2 = "<h2>" . parent::getTeam() . "</h2>";
				}

				return $h2;
		}

		// LIEST DIE BILANZWERTE VON CLICKTT AUS UND GIBT SIE ALS ASS. ARRAY ZURÜCK
		public function getH3()
		{
				$var_anz = parent::getView() . '_staffel_anz';
				$h3 = "";
				
				if (JComponentHelper::getParams('com_webtt')->get($var_anz))
				{
						$h3 = '<h3><a href="' . parent::getHostClicktt() . parent::getPathLeagueClicktt() . '" target="_blank" title="--> zu Clicktt">' . parent::getLeagueClicktt() . '</a></h3>';
				}

				return $h3;
		}
}

?>
