<?php
// No direct access
defined('_JEXEC') or die('Restricted access');

class WebttTableQttr extends JTable
{
    function __construct( &$db ) {
        parent::__construct('#__webtt_ttr', 'id', $db);
    }
}
