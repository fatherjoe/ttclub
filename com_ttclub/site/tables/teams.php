<?php defined( '_JEXEC' ) or die( 'Restricted access' ); 
 
class TableTeam extends JTable
{                      
  /**
  * Constructor
  *
  * @param object Database connector object
  */
  function __construct( &$db ) {
    parent::__construct('#__ttverein_mannschaften', 'id', $db);
  }
}