<?php
// No direct access
defined('_JEXEC') or die('Restricted access');

class WebttTableFotos extends JTable
{
    function __construct( &$db ) {
        parent::__construct('#__webtt_spieler', 'id', $db);
    }
}
