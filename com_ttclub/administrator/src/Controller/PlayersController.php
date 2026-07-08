<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;

class PlayersController extends AdminController
{
    public function getModel($name = 'Player', $prefix = 'Administrator', $config = ['ignore_request' => true]): mixed
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Method to delete one or more records.
     *
     * @return void
     */
    public function delete(): void
    {
        $user = $this->app->getIdentity();

        if (!$user->authorise('core.admin', 'com_ttclub')
            && !$user->authorise('core.delete', 'com_ttclub')
        ) {
            $this->app->enqueueMessage(
                Text::_('COM_TTCLUB_ERROR_NO_DELETE_PERMISSION'),
                'error'
            );
            $this->setRedirect(Route::_('index.php?option=com_ttclub&view=players', false));

            return;
        }

        parent::delete();
    }

    /**
     * Method to change the published state of one or more records.
     *
     * @return void
     */
    public function publish(): void
    {
        $user = $this->app->getIdentity();

        if (!$user->authorise('core.admin', 'com_ttclub')
            && !$user->authorise('core.edit.state', 'com_ttclub')
        ) {
            $this->app->enqueueMessage(
                Text::_('COM_TTCLUB_ERROR_NO_EDITSTATE_PERMISSION'),
                'error'
            );
            $this->setRedirect(Route::_('index.php?option=com_ttclub&view=players', false));

            return;
        }

        parent::publish();
    }
}
