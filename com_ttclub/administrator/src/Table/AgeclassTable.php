<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class AgeclassTable extends Table
{
    /**
     * Constructor.
     *
     * @param DatabaseDriver $db A database connector object.
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__ttclub_age_classes', 'id', $db);
    }

    /**
     * Perform pre-save checks.
     *
     * Validates:
     * - Name is not empty and between 1-100 characters
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

        // Trim the name
        $this->name = trim($this->name ?? '');

        // Validate name is not empty
        if ($this->name === '') {
            $this->setError('The age class name is required.');
            return false;
        }

        // Validate name length (max 100 characters)
        if (mb_strlen($this->name) > 100) {
            $this->setError('The age class name must not exceed 100 characters.');
            return false;
        }

        return true;
    }

    /**
     * Override delete to prevent deletion when teams reference this age class.
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
            ->from($db->quoteName('#__ttclub_teams'))
            ->where($db->quoteName('age_class_id') . ' = ' . (int) $pk);

        $db->setQuery($query);
        $teamCount = (int) $db->loadResult();

        if ($teamCount > 0) {
            $this->setError('Cannot delete this age class because it is assigned to one or more teams.');
            return false;
        }

        return parent::delete($pk);
    }
}
