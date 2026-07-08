<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\View\Roster;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Roster management view.
 *
 * Displays the roster for a selected team and half-season, with controls to
 * assign/remove players and copy the roster to the next half-season.
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The list of roster entries (players assigned to the team for the selected half-season).
     *
     * @var array
     */
    protected array $rosterEntries = [];

    /**
     * The roster form (for adding new players).
     *
     * @var Form|null
     */
    protected ?Form $form = null;

    /**
     * The selected team ID.
     *
     * @var int
     */
    protected int $teamId = 0;

    /**
     * The selected half-season ID.
     *
     * @var int
     */
    protected int $halfSeasonId = 0;

    /**
     * Team display name.
     *
     * @var string
     */
    protected string $teamName = '';

    /**
     * Half-season display name.
     *
     * @var string
     */
    protected string $halfSeasonName = '';

    /**
     * Whether the target half-season already has assignments (for copy confirmation).
     *
     * @var bool
     */
    protected bool $targetHasAssignments = false;

    /**
     * Display the view.
     *
     * @param string $tpl The name of the template file to parse.
     *
     * @return void
     */
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $input = $app->input;

        $this->teamId = $input->getInt('team_id', 0);
        $this->halfSeasonId = $input->getInt('half_season_id', 0);

        // Auto-resolve half_season_id from team's season if not provided
        if ($this->teamId > 0 && $this->halfSeasonId === 0) {
            $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select('hs.id')
                ->from($db->quoteName('#__ttclub_half_seasons', 'hs'))
                ->innerJoin($db->quoteName('#__ttclub_teams', 't') . ' ON t.season_id = hs.season_id')
                ->where($db->quoteName('t.id') . ' = ' . $this->teamId)
                ->order('hs.half ASC')
                ->setLimit(1);
            $db->setQuery($query);
            $this->halfSeasonId = (int) ($db->loadResult() ?? 0);
        }

        /** @var \Fatherjoe\Component\Ttclub\Administrator\Model\RosterModel $model */
        $model = $this->getModel();

        // Load the roster form for adding players
        $this->form = $model->getForm();

        // Load roster entries if both team and half-season are selected
        if ($this->teamId > 0 && $this->halfSeasonId > 0) {
            $this->rosterEntries = $model->getRosterEntries($this->teamId, $this->halfSeasonId);
            $this->targetHasAssignments = $model->targetHasExistingAssignments($this->teamId, $this->halfSeasonId);

            // Load team and half-season names for display
            $this->loadDisplayNames();
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Load the team and half-season display names.
     *
     * @return void
     */
    protected function loadDisplayNames(): void
    {
        $db = Factory::getContainer()->get('DatabaseDriver');

        // Load team name
        $query = $db->getQuery(true)
            ->select(['t.team_number', 's.start_year AS season_start_year'])
            ->from($db->quoteName('#__ttclub_teams', 't'))
            ->join('LEFT', $db->quoteName('#__ttclub_seasons', 's') . ' ON s.id = t.season_id')
            ->where($db->quoteName('t.id') . ' = ' . $this->teamId);

        $db->setQuery($query);
        $team = $db->loadObject();

        if ($team) {
            $sy = (int) $team->season_start_year;
            $this->teamName = sprintf('%d/%02d - Team %s', $sy, ($sy + 1) % 100, $team->team_number);
        }

        // Load half-season name
        $query = $db->getQuery(true)
            ->select(['hs.half', 's.start_year AS season_start_year'])
            ->from($db->quoteName('#__ttclub_half_seasons', 'hs'))
            ->join('LEFT', $db->quoteName('#__ttclub_seasons', 's') . ' ON s.id = hs.season_id')
            ->where($db->quoteName('hs.id') . ' = ' . $this->halfSeasonId);

        $db->setQuery($query);
        $halfSeason = $db->loadObject();

        if ($halfSeason) {
            $sy = (int) $halfSeason->season_start_year;
            $this->halfSeasonName = sprintf('%d/%02d - Half %d', $sy, ($sy + 1) % 100, $halfSeason->half);
        }
    }

    /**
     * Add the page title and toolbar.
     *
     * @return void
     */
    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_TTCLUB_ROSTER_TITLE'), 'users');
    }
}
