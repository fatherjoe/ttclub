<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class ScheduleTable extends Table
{
    /**
     * Constructor.
     *
     * @param DatabaseDriver $db A database connector object.
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__ttclub_schedules', 'id', $db);
    }

    /**
     * Perform pre-save checks.
     *
     * Validates:
     * - team_id is a non-zero integer
     * - season_id is a non-zero integer
     * - match_date is not empty
     * - opponent is not empty and max 150 characters
     * - venue is not empty and max 200 characters
     * - home_away must be 1 (home) or 2 (away)
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

        // Validate team_id is a non-zero integer
        if (empty($this->team_id) || (int) $this->team_id === 0) {
            $this->setError('A team must be selected.');
            return false;
        }

        // Validate season_id is a non-zero integer
        if (empty($this->season_id) || (int) $this->season_id === 0) {
            $this->setError('A season must be selected.');
            return false;
        }

        // Validate match_date is not empty
        $matchDate = trim($this->match_date ?? '');
        if ($matchDate === '' || $matchDate === '0000-00-00') {
            $this->setError('The match date is required.');
            return false;
        }

        // Trim and validate opponent
        $this->opponent = trim($this->opponent ?? '');

        if ($this->opponent === '') {
            $this->setError('The opponent name is required.');
            return false;
        }

        if (mb_strlen($this->opponent) > 150) {
            $this->setError('The opponent name must not exceed 150 characters.');
            return false;
        }

        // Trim and validate venue
        $this->venue = trim($this->venue ?? '');

        if ($this->venue === '') {
            $this->setError('The venue is required.');
            return false;
        }

        if (mb_strlen($this->venue) > 200) {
            $this->setError('The venue must not exceed 200 characters.');
            return false;
        }

        // Validate home_away must be 1 or 2
        $homeAway = (int) $this->home_away;
        if ($homeAway !== 1 && $homeAway !== 2) {
            $this->setError('Home/Away must be either Home (1) or Away (2).');
            return false;
        }

        return true;
    }
}
