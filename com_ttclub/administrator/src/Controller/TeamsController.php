<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;

class TeamsController extends AdminController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var string
     */
    protected $text_prefix = 'COM_TTCLUB_TEAMS';

    /**
     * Proxy for getModel.
     *
     * @param string $name   The model name. Optional.
     * @param string $prefix The class prefix. Optional.
     * @param array  $config Configuration array for model. Optional.
     *
     * @return \Joomla\CMS\MVC\Model\BaseDatabaseModel The model.
     */
    public function getModel($name = 'Team', $prefix = 'Administrator', $config = ['ignore_request' => true])
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
            $this->setRedirect(Route::_('index.php?option=com_ttclub&view=teams', false));

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
            $this->setRedirect(Route::_('index.php?option=com_ttclub&view=teams', false));

            return;
        }

        parent::publish();
    }
}
