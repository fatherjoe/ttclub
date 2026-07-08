<?php

declare(strict_types=1);

namespace Joomla\Database;

/**
 * Stub for Joomla\Database\DatabaseDriver used in property tests.
 */
class DatabaseDriver
{
    public function getQuery(bool $new = false): DatabaseQuery
    {
        return new DatabaseQuery();
    }

    public function quoteName(string $name): string
    {
        return '`' . $name . '`';
    }

    public function quote(string $text): string
    {
        return "'" . $text . "'";
    }

    public function setQuery($query): self
    {
        return $this;
    }

    public function loadResult(): ?string
    {
        return null;
    }

    public function execute(): bool
    {
        return true;
    }

    public function insertObject(string $table, object &$object, ?string $key = null): bool
    {
        return true;
    }

    public function loadObject(?string $class = null): ?object
    {
        return null;
    }

    public function loadColumn(int $offset = 0): array
    {
        return [];
    }

    public function insertid(): int
    {
        return 0;
    }

    public function loadAssoc(): ?array
    {
        return null;
    }
}
