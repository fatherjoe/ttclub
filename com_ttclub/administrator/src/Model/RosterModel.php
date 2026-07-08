<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

class RosterModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var string
     */
    public $typeAlias = 'com_ttclub.roster';

    /**
     * Get the form for this model.
     *
     * @param array $data     Data for the form.
     * @param bool  $loadData True if the form is to load its own data.
     *
     * @return Form|false A Form object on success, false on failure.
     */
    public function getForm($data = [], $loadData = true): Form|false
    {
        $form = $this->loadForm(
            'com_ttclub.roster',
            'roster',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Load the data for the form.
     *
     * @return array|object The data for the form.
     */
    protected function loadFormData(): array|object
    {
        $data = Factory::getApplication()->getUserState('com_ttclub.edit.roster.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Get the table for this model.
     *
     * @param string $name    The table name. Optional.
     * @param string $prefix  The class prefix. Optional.
     * @param array  $options Configuration array for model. Optional.
     *
     * @return Table A Table object.
     */
    public function getTable($name = 'Roster', $prefix = 'Administrator', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Assign a player to a team for a specific half-season.
     *
     * Creates a new roster entry linking the player, team, and half-season.
     * Validates uniqueness via the RosterTable::check() method.
     *
     * @param int $playerId     The player ID to assign.
     * @param int $teamId       The team ID to assign to.
     * @param int $halfSeasonId The half-season ID for this assignment.
     *
     * @return bool True on success, false on failure.
     */
    public function assign(int $playerId, int $teamId, int $halfSeasonId, ?int $position = null): bool
    {
        $table = $this->getTable();

        $data = [
            'id'             => 0,
            'player_id'      => $playerId,
            'team_id'        => $teamId,
            'half_season_id' => $halfSeasonId,
            'position'       => $position,
            'created'        => Factory::getDate()->toSql(),
        ];

        if (!$table->bind($data)) {
            $this->setError($table->getError());
            return false;
        }

        if (!$table->check()) {
            $this->setError($table->getError());
            return false;
        }

        if (!$table->store()) {
            $this->setError($table->getError());
            return false;
        }

        return true;
    }

    /**
     * Remove a roster entry by its ID.
     *
     * @param int $rosterId The roster entry ID to remove.
     *
     * @return bool True on success, false on failure.
     */
    public function remove(int $rosterId): bool
    {
        $table = $this->getTable();

        if (!$table->load($rosterId)) {
            $this->setError('Roster entry not found.');
            return false;
        }

        if (!$table->delete($rosterId)) {
            $this->setError($table->getError());
            return false;
        }

        return true;
    }

    /**
     * Copy a roster to the next half-season.
     *
     * Duplicates all player assignments from the source team and half-season
     * to the subsequent half-season:
     * - If source is the first half (half=1), target is the second half of the same season.
     * - If source is the second half (half=2), target is the first half of the next season.
     *
     * @param int    $teamId           The team ID whose roster to copy.
     * @param int    $sourceHalfSeasonId The source half-season ID to copy from.
     * @param string $mode             Either 'merge' or 'replace'. Merge keeps existing assignments,
     *                                 replace removes all existing assignments in the target first.
     *
     * @return bool True on success, false on failure.
     */
    public function copyRoster(int $teamId, int $sourceHalfSeasonId, string $mode = 'merge'): bool
    {
        $db = $this->getDatabase();

        // Determine target half-season
        $targetHalfSeasonId = $this->getNextHalfSeasonId($sourceHalfSeasonId);

        if ($targetHalfSeasonId === null) {
            $this->setError('Cannot determine the next half-season. Please ensure the target season exists.');
            return false;
        }

        // If replace mode, remove all existing assignments in the target half-season for this team
        if ($mode === 'replace') {
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__ttclub_rosters'))
                ->where($db->quoteName('team_id') . ' = ' . $teamId)
                ->where($db->quoteName('half_season_id') . ' = ' . $targetHalfSeasonId);

            $db->setQuery($query);
            $db->execute();
        }

        // Get all player assignments from the source half-season for this team
        $query = $db->getQuery(true)
            ->select($db->quoteName('player_id'))
            ->from($db->quoteName('#__ttclub_rosters'))
            ->where($db->quoteName('team_id') . ' = ' . $teamId)
            ->where($db->quoteName('half_season_id') . ' = ' . $sourceHalfSeasonId);

        $db->setQuery($query);
        $playerIds = $db->loadColumn();

        if (empty($playerIds)) {
            $this->setError('No roster entries found in the source half-season to copy.');
            return false;
        }

        // Copy each player assignment to the target half-season
        $copiedCount = 0;
        $skippedCount = 0;

        foreach ($playerIds as $playerId) {
            $table = $this->getTable();

            $data = [
                'id'             => 0,
                'player_id'      => (int) $playerId,
                'team_id'        => $teamId,
                'half_season_id' => $targetHalfSeasonId,
                'created'        => Factory::getDate()->toSql(),
            ];

            if (!$table->bind($data)) {
                continue;
            }

            if (!$table->check()) {
                // Skip duplicates silently during merge
                $skippedCount++;
                continue;
            }

            if (!$table->store()) {
                $this->setError($table->getError());
                return false;
            }

            $copiedCount++;
        }

        return true;
    }

    /**
     * Get the players assigned to a team for a specific half-season.
     *
     * @param int $teamId       The team ID.
     * @param int $halfSeasonId The half-season ID.
     *
     * @return array List of roster entries with player details.
     */
    public function getRosterEntries(int $teamId, int $halfSeasonId): array
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select([
                'r.' . $db->quoteName('id') . ' AS roster_id',
                'r.' . $db->quoteName('player_id'),
                'r.' . $db->quoteName('position'),
                'r.' . $db->quoteName('created'),
                'p.' . $db->quoteName('first_name'),
                'p.' . $db->quoteName('last_name'),
                'p.' . $db->quoteName('published'),
            ])
            ->from($db->quoteName('#__ttclub_rosters', 'r'))
            ->join('LEFT', $db->quoteName('#__ttclub_players', 'p') . ' ON p.id = r.player_id')
            ->where($db->quoteName('r.team_id') . ' = ' . $teamId)
            ->where($db->quoteName('r.half_season_id') . ' = ' . $halfSeasonId)
            ->order('r.position ASC, p.last_name ASC, p.first_name ASC');

        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /**
     * Determine whether the target half-season already has assignments for the given team.
     *
     * @param int $teamId              The team ID.
     * @param int $sourceHalfSeasonId  The source half-season ID (used to determine target).
     *
     * @return bool True if the target half-season has existing assignments.
     */
    public function targetHasExistingAssignments(int $teamId, int $sourceHalfSeasonId): bool
    {
        $targetHalfSeasonId = $this->getNextHalfSeasonId($sourceHalfSeasonId);

        if ($targetHalfSeasonId === null) {
            return false;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__ttclub_rosters'))
            ->where($db->quoteName('team_id') . ' = ' . $teamId)
            ->where($db->quoteName('half_season_id') . ' = ' . $targetHalfSeasonId);

        $db->setQuery($query);

        return ((int) $db->loadResult()) > 0;
    }

    /**
     * Determine the next half-season ID from a given source half-season.
     *
     * - If source half=1, target is half=2 of the same season.
     * - If source half=2, target is half=1 of the next season (by name).
     *
     * @param int $sourceHalfSeasonId The source half-season ID.
     *
     * @return int|null The target half-season ID, or null if not found.
     */
    public function getNextHalfSeasonId(int $sourceHalfSeasonId): ?int
    {
        $db = $this->getDatabase();

        // Load source half-season details
        $query = $db->getQuery(true)
            ->select(['hs.season_id', 'hs.half', 's.start_year AS season_start_year'])
            ->from($db->quoteName('#__ttclub_half_seasons', 'hs'))
            ->join('LEFT', $db->quoteName('#__ttclub_seasons', 's') . ' ON s.id = hs.season_id')
            ->where($db->quoteName('hs.id') . ' = ' . $sourceHalfSeasonId);

        $db->setQuery($query);
        $source = $db->loadObject();

        if (!$source) {
            return null;
        }

        if ((int) $source->half === 1) {
            // Target is the second half of the same season
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__ttclub_half_seasons'))
                ->where($db->quoteName('season_id') . ' = ' . (int) $source->season_id)
                ->where($db->quoteName('half') . ' = 2');

            $db->setQuery($query);
            $result = $db->loadResult();

            return $result ? (int) $result : null;
        }

        // Source is half=2, target is half=1 of the next season
        $nextStartYear = (int) $source->season_start_year + 1;

        $query = $db->getQuery(true)
            ->select('hs.id')
            ->from($db->quoteName('#__ttclub_half_seasons', 'hs'))
            ->join('INNER', $db->quoteName('#__ttclub_seasons', 's') . ' ON s.id = hs.season_id')
            ->where($db->quoteName('s.start_year') . ' = ' . $nextStartYear)
            ->where($db->quoteName('hs.half') . ' = 1');

        $db->setQuery($query);
        $result = $db->loadResult();

        return $result ? (int) $result : null;
    }
}
