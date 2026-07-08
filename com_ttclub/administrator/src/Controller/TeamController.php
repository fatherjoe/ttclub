<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;

class TeamController extends FormController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var string
     */
    protected $text_prefix = 'COM_TTCLUB_TEAM';

    /**
     * Method to check if the user can add a new record.
     *
     * @param array $data An array of input data.
     *
     * @return bool
     */
    protected function allowAdd($data = []): bool
    {
        $user = $this->app->getIdentity();

        if ($user->authorise('core.admin', 'com_ttclub')) {
            return true;
        }

        return $user->authorise('core.create', 'com_ttclub');
    }

    /**
     * Method to check if the user can edit an existing record.
     *
     * @param array  $data An array of input data.
     * @param string $key  The name of the key for the primary key.
     *
     * @return bool
     */
    protected function allowEdit($data = [], $key = 'id'): bool
    {
        $user = $this->app->getIdentity();

        if ($user->authorise('core.admin', 'com_ttclub')) {
            return true;
        }

        return $user->authorise('core.edit', 'com_ttclub');
    }

    /**
     * Method to add a new record.
     *
     * @return bool
     */
    public function add()
    {
        if (!$this->allowAdd()) {
            $this->app->enqueueMessage(
                Text::_('COM_TTCLUB_ERROR_NO_CREATE_PERMISSION'),
                'error'
            );
            $this->setRedirect(Route::_('index.php?option=com_ttclub&view=teams', false));

            return false;
        }

        return parent::add();
    }

    /**
     * Method to edit an existing record.
     *
     * @param string|null $key    The name of the primary key.
     * @param string|null $urlVar The name of the URL variable.
     *
     * @return bool
     */
    public function edit($key = null, $urlVar = null)
    {
        if (!$this->allowEdit()) {
            $this->app->enqueueMessage(
                Text::_('COM_TTCLUB_ERROR_NO_EDIT_PERMISSION'),
                'error'
            );
            $this->setRedirect(Route::_('index.php?option=com_ttclub&view=teams', false));

            return false;
        }

        return parent::edit($key, $urlVar);
    }
}
