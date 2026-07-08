<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class ClubidTable extends Table
{
    /**
     * Constructor.
     *
     * @param DatabaseDriver $db A database connector object.
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__ttclub_club_ids', 'id', $db);
    }

    /**
     * Perform pre-save checks.
     *
     * Validates:
     * - label is required
     * - click_tt_club_id is required
     * - federation is required
     *
     * @return bool True if checks pass.
     */
    public function check(): bool
    {
        if (!parent::check()) {
            return false;
        }

        // Trim string fields
        $this->label = trim($this->label ?? '');
        $this->federation = trim($this->federation ?? '');
        $this->club_name = trim($this->club_name ?? '');

        // Validate label is not empty
        if ($this->label === '') {
            $this->setError('The label is required.');
            return false;
        }

        // Validate click_tt_club_id is set and positive
        if (empty($this->click_tt_club_id) || (int) $this->click_tt_club_id <= 0) {
            $this->setError('The click-tt.de Club ID is required.');
            return false;
        }

        // Validate federation is not empty
        if ($this->federation === '') {
            $this->setError('The federation is required.');
            return false;
        }

        // Ensure ordering has a default
        if (empty($this->ordering)) {
            $this->ordering = 0;
        }

        return true;
    }
}
