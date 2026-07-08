<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Service;

/**
 * Data Transfer Object representing the result of a full historical import.
 */
readonly class HistoricalImportResult
{
    /**
     * @param SeasonImportResult[] $perSeasonResults
     */
    public function __construct(
        public int $seasonsCreated,
        public int $teamsCreated,
        public int $playersCreated,
        public int $rosterEntriesCreated,
        public int $scheduleEntriesCreated,
        public array $perSeasonResults = [],
    ) {}
}
