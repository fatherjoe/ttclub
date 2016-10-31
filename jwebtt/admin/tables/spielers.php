<?php
// No direct access
defined('_JEXEC') or die('Restricted access');

class WebttTableSpielers extends JTable
{
    function __construct( &$db ) {
        parent::__construct('#__webtt_spieler', 'id', $db);
    }
}
