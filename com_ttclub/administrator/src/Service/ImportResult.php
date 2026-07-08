<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Service;

/**
 * Data Transfer Object representing the result of an import operation.
 */
class ImportResult
{
    public function __construct(
        public readonly int $created = 0,
        public readonly int $updated = 0,
        public readonly int $unchanged = 0,
        public readonly bool $success = true,
        public readonly ?string $errorMessage = null,
    ) {}

    /**
     * Get the total number of records processed.
     */
    public function getTotal(): int
    {
        return $this->created + $this->updated + $this->unchanged;
    }
}
