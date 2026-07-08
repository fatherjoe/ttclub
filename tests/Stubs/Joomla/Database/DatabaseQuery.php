<?php

declare(strict_types=1);

namespace Joomla\Database;

/**
 * Stub for Joomla\Database\DatabaseQuery used in property tests.
 */
class DatabaseQuery
{
    public function select($columns): self
    {
        return $this;
    }

    public function from($table): self
    {
        return $this;
    }

    public function where($conditions): self
    {
        return $this;
    }

    public function innerJoin($table): self
    {
        return $this;
    }

    public function join($type, $conditions): self
    {
        return $this;
    }

    public function order($columns): self
    {
        return $this;
    }

    public function setLimit(int $limit, int $offset = 0): self
    {
        return $this;
    }

    public function bind($key, &$value = null, $dataType = 'string', int $length = 0, array $driverOptions = []): self
    {
        return $this;
    }

    public function delete(?string $table = null): self
    {
        return $this;
    }

    public function insert(string $table): self
    {
        return $this;
    }

    public function columns(array $columns): self
    {
        return $this;
    }

    public function values(string $values): self
    {
        return $this;
    }

    public function update(string $table): self
    {
        return $this;
    }

    public function set($conditions, string $glue = ','): self
    {
        return $this;
    }
}
