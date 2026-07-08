<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class HalfSeasonTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__ttclub_half_seasons', 'id', $db);
    }

    public function check(): bool
    {
        if (!parent::check()) {
            return false;
        }

        if (empty($this->season_id) || (int) $this->season_id <= 0) {
            $this->setError('A season must be selected for the half-season.');
            return false;
        }

        $half = (int) $this->half;

        if ($half !== 1 && $half !== 2) {
            $this->setError('The half value must be 1 (first half) or 2 (second half).');
            return false;
        }

        return true;
    }
}
