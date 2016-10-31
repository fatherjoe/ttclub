<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
 
// import Joomla table library
jimport('joomla.database.table');

class WebttTableWebtt extends JTable
{
    var $id = null;
    var $var = null;
    var $val = null;
    var $descr = null;
 
    function __construct( &$db ) {
        parent::__construct('#__webtt', 'id', $db);
    }

    public function bind($array, $ignore = '')
    {
        if (isset($array['jform']) && is_array($array['jform']))
        {
            // Convert the params field to a string.
            $parameter = new JRegistry;
            $parameter->loadArray($array['jform']);
            $array['jform'] = (string) $parameter;
        }
 
        return parent::bind($array, $ignore);
    }
}
