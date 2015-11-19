<?php
defined('_JEXEC') or die('Restricted access');
require( JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'lib'.'/'. 'config'.'/'. 'config.php' );
require( JPATH_ADMINISTRATOR .'/'. 'components' .'/'. 'com_ttverein' .'/'. 'lib'.'/'. 'upload'.'/'. 'image.php' );

$db = &JFactory::getDBO();

$config = Config::getConfig(array("delete_database","delete_pictures"));

/*
 * Löschen der Spieler und Mannschaftsbilder
 */
if($config['delete_pictures'] == "1") {
	$query = "SELECT image_orginal, image_resize, image_thumb 
				FROM #__ttverein_spieler";
	$db->setQuery( $query );
	$images = $db->loadObjectList();
	Image::deleteImages($images);
	
	
	$query = "SELECT image_orginal, image_resize, image_thumb 
				FROM #__ttverein_mannschaften";
	$db->setQuery( $query );
	$images = $db->loadObjectList();
	Image::deleteImages($images);
}

if($config['delete_database'] == "1") {
	$query = "DROP TABLE IF EXISTS #__ttverein_altersklassen, " . 
				" #__ttverein_aufstellungen, " . 
				" #__ttverein_config, " . 
				" #__ttverein_ligen, " . 
				" #__ttverein_mannschaften, " . 
				" #__ttverein_spieler," .
				" #__ttverein_felder," .
				" #__ttverein_spieler_felder ";
	$db->setQuery( $query );
	$db->query();
}
?>