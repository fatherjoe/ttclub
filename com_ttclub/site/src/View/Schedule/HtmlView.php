<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Site\View\Schedule;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Site Schedule HTML view.
 *
 * Displays the schedule for a team, grouped into upcoming and past matches.
 */
class HtmlView extends BaseHtmlView
{
    /**
     * Upcoming match entries (date >= today, ascending).
     *
     * @var array
     */
    protected array $upcomingMatches = [];

    /**
     * Past match entries (date < today, descending).
     *
     * @var array
     */
    protected array $pastMatches = [];

    /**
     * Available seasons for the selector.
     *
     * @var array
     */
    protected array $seasons = [];

    /**
     * The currently selected season ID.
     *
     * @var int|null
     */
    protected ?int $seasonId = null;

    /**
     * The team ID.
     *
     * @var int|null
     */
    protected ?int $teamId = null;

    /**
     * The team record.
     *
     * @var object|null
     */
    protected ?object $team = null;

    /**
     * Display the view.
     *
     * @param string $tpl The template name.
     *
     * @return void
     */
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $input = $app->getInput();

        /** @var \Fatherjoe\Component\Ttclub\Site\Model\ScheduleModel $model */
        $model = $this->getModel();

        // Get team ID from request
        $this->teamId = $input->getInt('team_id', 0);

        // Get season ID from request, or default to current season
        $this->seasonId = $input->getInt('season_id', 0);

        if ($this->seasonId === 0) {
            $this->seasonId = $model->getCurrentSeasonId();
        }

        // Load team info
        if ($this->teamId > 0) {
            $this->team = $model->getTeam($this->teamId);
        }

        // Load schedule data if we have valid team and season
        if ($this->teamId > 0 && $this->seasonId !== null && $this->seasonId > 0) {
            $this->upcomingMatches = $model->getUpcomingMatches($this->teamId, $this->seasonId);
            $this->pastMatches = $model->getPastMatches($this->teamId, $this->seasonId);
        }

        // Load seasons for selector
        $this->seasons = $model->getSeasons();

        parent::display($tpl);
    }
}
