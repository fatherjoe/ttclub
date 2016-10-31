<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
 

class WebttTableKalender extends JTable
{
    function __construct( &$db ) {
        parent::__construct('#__webtt_kalender', 'id', $db);
    }
}
