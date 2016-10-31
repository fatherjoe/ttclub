<?php
// No direct access to this file
defined('_JEXEC') or die;
jimport('joomla.database.table');
class WebttTableObjects extends JTable
{
  var $id = null;
  var $var = null;
  var $val = null;

  function __construct(&$db)
  {
    parent::__construct('#__cocoaterealestate_objects', 'id', $db);
  }
}
?>
