<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Site\View\Team;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Site Team detail view.
 *
 * Displays a single team with its photo, league, roster, match schedule,
 * and league ranking table from click-tt.de.
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The team item.
     *
     * @var object|null
     */
    protected ?object $item = null;

    /**
     * The current/selected half-season.
     *
     * @var object|null
     */
    protected ?object $halfSeason = null;

    /**
     * Half-seasons for the team's season (for switching).
     *
     * @var array
     */
    protected array $halfSeasons = [];

    /**
     * The team photo path for the selected half-season.
     *
     * @var string|null
     */
    protected ?string $teamPhoto = null;

    /**
     * The roster (player list) for the selected half-season.
     *
     * @var array
     */
    protected array $roster = [];

    /**
     * The match schedule for the team's season (from click-tt.de via ScheduleService).
     * Null means the service is unavailable; empty array means no data.
     *
     * @var array|null
     */
    protected ?array $schedule = null;

    /**
     * The league ranking table data from click-tt.de.
     *
     * @var array|null
     */
    protected ?array $ranking = null;

    /**
     * All available seasons for navigation.
     *
     * @var array
     */
    protected array $seasons = [];

    /**
     * Display the view.
     *
     * @param string|null $tpl The name of the template file to parse.
     *
     * @return void
     */
    public function display($tpl = null): void
    {
        /** @var \Fatherjoe\Component\Ttclub\Site\Model\TeamModel $model */
        $model = $this->getModel();

        $this->item = $model->getItem();

        if ($this->item === null) {
            parent::display($tpl);
            return;
        }

        $this->halfSeason = $model->getHalfSeason();
        $halfSeasonId = $this->halfSeason !== null ? (int) $this->halfSeason->id : 0;

        $this->halfSeasons = $model->getHalfSeasons((int) $this->item->season_id);
        $this->teamPhoto = $model->getTeamPhoto((int) $this->item->id, $halfSeasonId);
        $this->roster = $model->getRoster((int) $this->item->id, $halfSeasonId);
        $this->schedule = $model->getScheduleFromService((int) $this->item->id, $halfSeasonId);
        $this->ranking = $model->getRankingTable((int) $this->item->id, $halfSeasonId);
        $this->seasons = $model->getSeasons();

        parent::display($tpl);
    }
}
