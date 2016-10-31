<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
 
// import Joomla table library
jimport('joomla.database.table');

class WebttTableMannschaftsnamen extends JTable
{
    function __construct( &$db ) {
        parent::__construct('#__webtt_mannsch', 'id', $db);
    }
}
