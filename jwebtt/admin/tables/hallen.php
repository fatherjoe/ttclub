<?php
// No direct access
defined('_JEXEC') or die('Restricted access');

class WebttTableHallen extends JTable
{
    function __construct( &$db ) {
        parent::__construct('#__webtt_hallen', 'id', $db);
    }
}
