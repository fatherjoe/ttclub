<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Table;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

/**
 * Import Log table class.
 *
 * Logs every import operation with timestamp, type, record counts, and status.
 */
class ImportLogTable extends Table
{
    /**
     * Constructor.
     *
     * @param DatabaseDriver $db A database connector object.
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__ttclub_import_logs', 'id', $db);
    }

    /**
     * Perform pre-save checks.
     *
     * Validates:
     * - import_date is not empty
     * - import_type is not empty
     *
     * @return bool True if checks pass.
     */
    public function check(): bool
    {
        if (!parent::check()) {
            return false;
        }

        // Validate import_date is not empty
        if (empty($this->import_date)) {
            $this->setError('The import date is required.');
            return false;
        }

        // Validate import_type is not empty
        $this->import_type = trim($this->import_type ?? '');

        if ($this->import_type === '') {
            $this->setError('The import type is required.');
            return false;
        }

        return true;
    }

    /**
     * Log an import operation.
     *
     * @param string $importType     The type of import (e.g., 'players', 'rosters', 'schedules', 'historical').
     * @param int    $recordsCreated Number of records created during the import.
     * @param int    $recordsUpdated Number of records updated during the import.
     * @param int    $recordsUnchanged Number of records unchanged during the import.
     * @param bool   $success        Whether the import was successful.
     * @param string|null $message   Optional message or error details.
     *
     * @return bool True on success, false on failure.
     */
    public function logImport(
        string $importType,
        int $recordsCreated,
        int $recordsUpdated,
        int $recordsUnchanged,
        bool $success,
        ?string $message = null
    ): bool {
        $this->reset();

        $this->import_date = Factory::getDate()->toSql();
        $this->import_type = $importType;
        $this->records_created = $recordsCreated;
        $this->records_updated = $recordsUpdated;
        $this->records_unchanged = $recordsUnchanged;
        $this->status = $success ? 1 : 0;
        $this->message = $message;

        if (!$this->check()) {
            return false;
        }

        return $this->store();
    }
}
