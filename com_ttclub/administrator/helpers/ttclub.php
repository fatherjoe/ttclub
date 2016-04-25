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

/**
 * Ttclub helper.
 */
class TtclubHelper {

    /**
     * Configure the Linkbar.
     */
    public static function addSubmenu($vName = '') {
        		JHtmlSidebar::addEntry(
			JText::_('COM_TTCLUB_TITLE_TEAMS'),
			'index.php?option=com_ttclub&view=teams',
			$vName == 'teams'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_TTCLUB_TITLE_SCHEDULE'),
			'index.php?option=com_ttclub&view=schedule',
			$vName == 'schedule'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_TTCLUB_TITLE_RESULTS'),
			'index.php?option=com_ttclub&view=results',
			$vName == 'results'
		);

    }

    /**
     * Gets a list of the actions that can be performed.
     *
     * @return	JObject
     * @since	1.6
     */
    public static function getActions() {
        $user = JFactory::getUser();
        $result = new JObject;

        $assetName = 'com_ttclub';

        $actions = array(
            'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
        );

        foreach ($actions as $action) {
            $result->set($action, $user->authorise($action, $assetName));
        }

        return $result;
    }


}
