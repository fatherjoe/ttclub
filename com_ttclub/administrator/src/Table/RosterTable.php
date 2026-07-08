<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class RosterTable extends Table
{
    /**
     * Constructor.
     *
     * @param DatabaseDriver $db A database connector object.
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__ttclub_rosters', 'id', $db);
    }

    /**
     * Perform pre-save checks.
     *
     * Validates:
     * - player_id is a non-zero integer
     * - team_id is a non-zero integer
     * - half_season_id is a non-zero integer
     * - The combination (player_id, team_id, half_season_id) is unique
     *
     * @return bool True if checks pass.
     */
    public function check(): bool
    {
        if (!parent::check()) {
            return false;
        }

        // Validate player_id is a non-zero integer
        if (empty($this->player_id) || (int) $this->player_id === 0) {
            $this->setError('A valid player is required.');
            return false;
        }

        // Validate team_id is a non-zero integer
        if (empty($this->team_id) || (int) $this->team_id === 0) {
            $this->setError('A valid team is required.');
            return false;
        }

        // Validate half_season_id is a non-zero integer
        if (empty($this->half_season_id) || (int) $this->half_season_id === 0) {
            $this->setError('A valid half-season is required.');
            return false;
        }

        // Enforce unique constraint: (player_id, team_id, half_season_id)
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__ttclub_rosters'))
            ->where($db->quoteName('player_id') . ' = ' . (int) $this->player_id)
            ->where($db->quoteName('team_id') . ' = ' . (int) $this->team_id)
            ->where($db->quoteName('half_season_id') . ' = ' . (int) $this->half_season_id);

        // Exclude the current record when updating
        if ($this->id) {
            $query->where($db->quoteName('id') . ' != ' . (int) $this->id);
        }

        $db->setQuery($query);
        $duplicate = $db->loadResult();

        if ($duplicate) {
            $this->setError('This player is already assigned to this team for the selected half-season.');
            return false;
        }

        return true;
    }
}
