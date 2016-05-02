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

jimport('joomla.application.component.controller');

class TtclubController extends JControllerLegacy {

    /**
     * Method to display a view.
     *
     * @param	boolean			$cachable	If true, the view output will be cached
     * @param	array			$urlparams	An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return	JController		This object to support chaining.
     * @since	1.5
     */
    public function display($cachable = false, $urlparams = false) {
        require_once JPATH_COMPONENT . '/helpers/ttclub.php';

        $view = JFactory::getApplication()->input->getCmd('view', 'teams');
        JFactory::getApplication()->input->set('view', $view);

        parent::display($cachable, $urlparams);

        return $this;
    }

}
