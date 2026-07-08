<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Service;

/**
 * Data Transfer Object representing the result of importing a single season.
 */
readonly class SeasonImportResult
{
    public function __construct(
        public string $seasonName,
        public int $teamsCreated,
        public int $rosterEntriesCreated,
        public int $scheduleEntriesCreated,
        public int $playersCreated,
        public bool $success,
        public ?string $errorMessage = null,
    ) {}
}
