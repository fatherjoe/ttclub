<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/**
 * Roster controller.
 *
 * Handles custom roster actions (assign, remove, copy) rather than standard CRUD,
 * so extends BaseController instead of FormController.
 */
class RosterController extends BaseController
{
    /**
     * Assign a player to a team roster for a specific half-season.
     *
     * Expected POST parameters:
     * - player_id: int
     * - team_id: int
     * - half_season_id: int
     *
     * @return void
     */
    public function assign(): void
    {
        Session::checkToken() or die('Invalid Token');

        $user = $this->app->getIdentity();

        if (!$user->authorise('core.admin', 'com_ttclub')
            && !$user->authorise('core.create', 'com_ttclub')
        ) {
            $this->app->enqueueMessage(
                Text::_('COM_TTCLUB_ERROR_NO_CREATE_PERMISSION'),
                'error'
            );
            $this->setRedirect(Route::_('index.php', false));

            return;
        }

        $app = $this->app;
        $input = $app->input;

        $playerId = $input->getInt('player_id', 0);
        $teamId = $input->getInt('team_id', 0);
        $halfSeasonId = $input->getInt('half_season_id', 0);
        $position = $input->getInt('position', 0);
        $position = $position > 0 ? $position : null;

        /** @var \Fatherjoe\Component\Ttclub\Administrator\Model\RosterModel $model */
        $model = $this->getModel('Roster', 'Administrator');

        if ($model->assign($playerId, $teamId, $halfSeasonId, $position)) {
            $app->enqueueMessage('Player successfully assigned to team roster.');
        } else {
            $app->enqueueMessage($model->getError(), 'error');
        }

        $this->setRedirect(
            Route::_(
                'index.php?option=com_ttclub&view=roster&team_id=' . $teamId . '&half_season_id=' . $halfSeasonId,
                false
            )
        );
    }

    /**
     * Remove a player from a team roster.
     *
     * Expected GET/POST parameters:
     * - roster_id: int (the roster entry ID to remove)
     * - team_id: int (for redirect)
     * - half_season_id: int (for redirect)
     *
     * @return void
     */
    public function remove(): void
    {
        Session::checkToken('get') or Session::checkToken() or die('Invalid Token');

        $user = $this->app->getIdentity();

        if (!$user->authorise('core.admin', 'com_ttclub')
            && !$user->authorise('core.delete', 'com_ttclub')
        ) {
            $this->app->enqueueMessage(
                Text::_('COM_TTCLUB_ERROR_NO_DELETE_PERMISSION'),
                'error'
            );
            $this->setRedirect(Route::_('index.php', false));

            return;
        }

        $app = $this->app;
        $input = $app->input;

        $rosterId = $input->getInt('roster_id', 0);
        $teamId = $input->getInt('team_id', 0);
        $halfSeasonId = $input->getInt('half_season_id', 0);

        /** @var \Fatherjoe\Component\Ttclub\Administrator\Model\RosterModel $model */
        $model = $this->getModel('Roster', 'Administrator');

        if ($model->remove($rosterId)) {
            $app->enqueueMessage('Player removed from team roster.');
        } else {
            $app->enqueueMessage($model->getError(), 'error');
        }

        $this->setRedirect(
            Route::_(
                'index.php?option=com_ttclub&view=roster&team_id=' . $teamId . '&half_season_id=' . $halfSeasonId,
                false
            )
        );
    }

    /**
     * Copy roster assignments to the next half-season.
     *
     * Expected POST parameters:
     * - team_id: int
     * - half_season_id: int (source half-season)
     * - copy_mode: string ('merge' or 'replace')
     *
     * @return void
     */
    public function copy(): void
    {
        Session::checkToken() or die('Invalid Token');

        $user = $this->app->getIdentity();

        if (!$user->authorise('core.admin', 'com_ttclub')
            && !$user->authorise('core.create', 'com_ttclub')
        ) {
            $this->app->enqueueMessage(
                Text::_('COM_TTCLUB_ERROR_NO_CREATE_PERMISSION'),
                'error'
            );
            $this->setRedirect(Route::_('index.php', false));

            return;
        }

        $app = $this->app;
        $input = $app->input;

        $teamId = $input->getInt('team_id', 0);
        $halfSeasonId = $input->getInt('half_season_id', 0);
        $copyMode = $input->getCmd('copy_mode', 'merge');

        // Validate copy mode
        if (!in_array($copyMode, ['merge', 'replace'], true)) {
            $copyMode = 'merge';
        }

        /** @var \Fatherjoe\Component\Ttclub\Administrator\Model\RosterModel $model */
        $model = $this->getModel('Roster', 'Administrator');

        if ($model->copyRoster($teamId, $halfSeasonId, $copyMode)) {
            $app->enqueueMessage('Roster successfully copied to the next half-season.');
        } else {
            $app->enqueueMessage($model->getError(), 'error');
        }

        $this->setRedirect(
            Route::_(
                'index.php?option=com_ttclub&view=roster&team_id=' . $teamId . '&half_season_id=' . $halfSeasonId,
                false
            )
        );
    }
}
