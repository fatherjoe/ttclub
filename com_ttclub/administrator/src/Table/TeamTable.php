<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class TeamTable extends Table
{
    /**
     * Constructor.
     *
     * @param DatabaseDriver $db A database connector object.
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__ttclub_teams', 'id', $db);
    }

    /**
     * Perform pre-save checks.
     *
     * Validates:
     * - season_id, league_id, age_class_id, team_number are all non-zero integers
     * - League immutability: league_id cannot be changed after initial creation
     *
     * @return bool True if checks pass.
     */
    public function check(): bool
    {
        if (!parent::check()) {
            return false;
        }

        // Set timestamps
        $now = \Joomla\CMS\Factory::getDate()->toSql();

        if (empty($this->created)) {
            $this->created = $now;
        }

        $this->modified = $now;

        $errors = [];

        // Validate required fields are non-zero integers
        if (empty($this->season_id) || (int) $this->season_id === 0) {
            $errors[] = 'season';
        }

        if (empty($this->league_id) || (int) $this->league_id === 0) {
            $errors[] = 'league';
        }

        if (empty($this->age_class_id) || (int) $this->age_class_id === 0) {
            $errors[] = 'age class';
        }

        if (empty($this->team_number) || (int) $this->team_number === 0) {
            $errors[] = 'team number';
        }

        if (!empty($errors)) {
            $this->setError('The following fields are required: ' . implode(', ', $errors) . '.');
            return false;
        }

        // Enforce league immutability on existing records
        if (!empty($this->id)) {
            $db = $this->getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName('league_id'))
                ->from($db->quoteName('#__ttclub_teams'))
                ->where($db->quoteName('id') . ' = ' . (int) $this->id);

            $db->setQuery($query);
            $originalLeagueId = (int) $db->loadResult();

            if ($originalLeagueId > 0 && (int) $this->league_id !== $originalLeagueId) {
                $this->setError('League changes are not permitted after creation. The league assignment is fixed for the entire season.');
                return false;
            }
        }

        return true;
    }

    /**
     * Override delete to prevent deletion when roster entries exist for this team.
     *
     * @param int|null $pk Primary key value to delete. If null, uses the current record's primary key.
     *
     * @return bool True on success, false on failure.
     */
    public function delete($pk = null): bool
    {
        $pk = $pk ?? $this->id;

        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__ttclub_rosters'))
            ->where($db->quoteName('team_id') . ' = ' . (int) $pk);

        $db->setQuery($query);
        $rosterCount = (int) $db->loadResult();

        if ($rosterCount > 0) {
            $this->setError('Cannot delete this team because it still has assigned players in the roster.');
            return false;
        }

        return parent::delete($pk);
    }
}
