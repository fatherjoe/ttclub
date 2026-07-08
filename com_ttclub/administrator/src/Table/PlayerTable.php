<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class PlayerTable extends Table
{
    /**
     * Constructor.
     *
     * @param DatabaseDriver $db A database connector object.
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__ttclub_players', 'id', $db);
    }

    /**
     * Perform pre-save checks.
     *
     * Validates:
     * - first_name is not empty/whitespace-only and between 1–50 characters
     * - last_name is not empty/whitespace-only and between 1–50 characters
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

        // Trim whitespace from first_name
        $this->first_name = trim($this->first_name ?? '');

        // Validate first_name is not empty
        if ($this->first_name === '') {
            $this->setError('The first name is required.');
            return false;
        }

        // Validate first_name length (max 50 characters)
        if (mb_strlen($this->first_name) > 50) {
            $this->setError('The first name must not exceed 50 characters.');
            return false;
        }

        // Trim whitespace from last_name
        $this->last_name = trim($this->last_name ?? '');

        // Validate last_name is not empty
        if ($this->last_name === '') {
            $this->setError('The last name is required.');
            return false;
        }

        // Validate last_name length (max 50 characters)
        if (mb_strlen($this->last_name) > 50) {
            $this->setError('The last name must not exceed 50 characters.');
            return false;
        }

        return true;
    }

    /**
     * Override delete to prevent deletion when roster entries exist for this player.
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
            ->where($db->quoteName('player_id') . ' = ' . (int) $pk);

        $db->setQuery($query);
        $rosterCount = (int) $db->loadResult();

        if ($rosterCount > 0) {
            $this->setError('Cannot delete this player because they are still assigned to a team roster.');
            return false;
        }

        return parent::delete($pk);
    }
}
