<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class PlayerImageTable extends Table
{
    /**
     * Constructor.
     *
     * @param DatabaseDriver $db A database connector object.
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__ttclub_player_images', 'id', $db);
    }

    /**
     * Perform pre-save checks.
     *
     * Validates:
     * - player_id is set and positive
     * - half_season_id is set and positive
     * - image_path is not empty
     *
     * @return bool True if checks pass.
     */
    public function check(): bool
    {
        if (!parent::check()) {
            return false;
        }

        if (empty($this->player_id) || (int) $this->player_id <= 0) {
            $this->setError('A valid player ID is required.');
            return false;
        }

        if (empty($this->half_season_id) || (int) $this->half_season_id <= 0) {
            $this->setError('A valid half-season ID is required.');
            return false;
        }

        $this->image_path = trim($this->image_path ?? '');

        if ($this->image_path === '') {
            $this->setError('The image path is required.');
            return false;
        }

        return true;
    }
}
