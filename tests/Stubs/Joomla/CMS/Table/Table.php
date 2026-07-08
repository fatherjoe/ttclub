<?php

declare(strict_types=1);

namespace Joomla\CMS\Table;

use Joomla\Database\DatabaseDriver;

/**
 * Stub for Joomla\CMS\Table\Table used in property tests.
 */
class Table
{
    /** @var int|null */
    public $id;

    /** @var string */
    protected string $_tbl;

    /** @var string */
    protected string $_tbl_key;

    /** @var DatabaseDriver */
    protected DatabaseDriver $_db;

    /** @var string */
    protected string $_error = '';

    public function __construct(string $table, string $key, DatabaseDriver $db)
    {
        $this->_tbl = $table;
        $this->_tbl_key = $key;
        $this->_db = $db;
    }

    public function getDbo(): DatabaseDriver
    {
        return $this->_db;
    }

    public function check(): bool
    {
        return true;
    }

    public function delete($pk = null): bool
    {
        return true;
    }

    public function setError(string $error): void
    {
        $this->_error = $error;
    }

    public function getError(): string
    {
        return $this->_error;
    }
}
