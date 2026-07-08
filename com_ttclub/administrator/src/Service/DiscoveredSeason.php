<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Service;

/**
 * Data Transfer Object representing a season discovered during historical import.
 */
readonly class DiscoveredSeason
{
    public function __construct(
        public string $name,        // e.g. "2019/20"
        public string $archiveUrl,  // URL to the season archive page
        public string $dataSource,  // 'mytischtennis' or 'clicktt'
    ) {}
}
