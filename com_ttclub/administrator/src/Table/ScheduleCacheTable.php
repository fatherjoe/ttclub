<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

/**
 * Schedule Cache table class.
 *
 * Stores cached match schedule data fetched from click-tt.de,
 * keyed by team and half-season with a timestamp for cache expiry.
 */
class ScheduleCacheTable extends Table
{
    /**
     * Constructor.
     *
     * @param DatabaseDriver $db A database connector object.
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__ttclub_schedule_cache', 'id', $db);
    }

    /**
     * Perform pre-save checks.
     *
     * Validates:
     * - team_id is set and positive
     * - half_season_id is set and positive
     * - schedule_data is not empty
     * - fetched_at is not empty
     *
     * @return bool True if checks pass.
     */
    public function check(): bool
    {
        if (!parent::check()) {
            return false;
        }

        if (empty($this->team_id) || (int) $this->team_id < 1) {
            $this->setError('A valid team ID is required.');
            return false;
        }

        if (empty($this->half_season_id) || (int) $this->half_season_id < 1) {
            $this->setError('A valid half-season ID is required.');
            return false;
        }

        if (empty($this->schedule_data)) {
            $this->setError('Schedule data content is required.');
            return false;
        }

        if (empty($this->fetched_at)) {
            $this->setError('The fetched_at timestamp is required.');
            return false;
        }

        return true;
    }
}
