<?php

/**
 * @version     1.0.0
 * @package     com_ttclub
 * @copyright   Copyright (C) 2014. Alle Rechte vorbehalten.
 * @license     GNU General Public License Version 2 oder später; siehe LICENSE.txt
 * @author      Thomas Muster <tom.muster@dodgemail.de> - http://
 */
defined('_JEXEC') or die;

JHTML::script('https://code.angularjs.org/2.0.0-beta.15/angular2.dev.js');
JHTML::script('https://cdnjs.cloudflare.com/ajax/libs/restangular/1.4.0/restangular.js');
JHTML::script('https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.7.0/underscore.js');
JHtml::script(Juri::base() . 'components/com_ttclub/assets/js/app.js');

class TtclubFrontendHelper {
    
}
