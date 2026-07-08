<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Service;

/**
 * Data Transfer Object representing the result of a full import across all configured club IDs.
 *
 * Contains aggregate totals and per-club-ID breakdown of import results.
 */
readonly class FullImportResult
{
    /**
     * @param int $totalCreated Total records created across all club IDs
     * @param int $totalUpdated Total records updated across all club IDs
     * @param int $totalUnchanged Total records unchanged across all club IDs
     * @param bool $success Whether all club ID imports completed without fatal errors
     * @param string|null $errorMessage Combined error messages (if any)
     * @param array<array{label: string, club_id: int, config_id: ?int, result: ImportResult}> $perClubResults Per-club-ID results
     */
    public function __construct(
        public int $totalCreated = 0,
        public int $totalUpdated = 0,
        public int $totalUnchanged = 0,
        public bool $success = true,
        public ?string $errorMessage = null,
        public array $perClubResults = [],
    ) {}

    /**
     * Get the total number of records processed across all club IDs.
     */
    public function getTotal(): int
    {
        return $this->totalCreated + $this->totalUpdated + $this->totalUnchanged;
    }

    /**
     * Get the number of club IDs that were processed.
     */
    public function getClubIdCount(): int
    {
        return count($this->perClubResults);
    }

    /**
     * Get a human-readable summary string.
     */
    public function getSummary(): string
    {
        $lines = [];
        $lines[] = sprintf(
            'Full import complete: %d created, %d updated, %d unchanged across %d club ID(s)',
            $this->totalCreated,
            $this->totalUpdated,
            $this->totalUnchanged,
            $this->getClubIdCount()
        );

        foreach ($this->perClubResults as $pcr) {
            $result = $pcr['result'];
            $lines[] = sprintf(
                '  %s (ID %d): %d created, %d updated, %d unchanged%s',
                $pcr['label'],
                $pcr['club_id'],
                $result->created,
                $result->updated,
                $result->unchanged,
                !$result->success ? ' [ERROR]' : ''
            );
        }

        return implode("\n", $lines);
    }
}
