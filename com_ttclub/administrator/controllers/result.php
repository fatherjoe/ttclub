<?php
/**
 * @version     1.0.0
 * @package     com_ttclub
 * @copyright   Copyright (C) 2014. Alle Rechte vorbehalten.
 * @license     GNU General Public License Version 2 oder später; siehe LICENSE.txt
 * @author      Thomas Muster <tom.muster@dodgemail.de> - http://
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

/**
 * Result controller class.
 */
class TtclubControllerResult extends JControllerForm
{

    function __construct() {
        $this->view_list = 'results';
        parent::__construct();
    }

}