<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Service;

/**
 * Interface for parsing season-related HTML pages from data sources.
 *
 * Different HTML structures exist on mytischtennis.de vs click-tt.de.
 * Implementations handle source-specific DOM structures using DOMDocument/DOMXPath.
 */
interface SeasonParserInterface
{
    /**
     * Parse season archive HTML to discover available season links.
     *
     * @param string $html Raw HTML content of the season archive page
     * @return array<int, array{name: string, url: string}> Array of season descriptors with name and URL
     */
    public function parseSeasonArchive(string $html): array;

    /**
     * Parse a season page to extract team listings.
     *
     * @param string $html Raw HTML content of the season/teams page
     * @return array<int, array{team_number: int, league: string, age_class: string}> Array of team data
     */
    public function parseTeams(string $html): array;

    /**
     * Parse a team roster page to extract player assignments.
     *
     * @param string $html Raw HTML content of the roster page
     * @return array<int, string> Array of player names (format: "Last, First" or "First Last")
     */
    public function parseRoster(string $html): array;

    /**
     * Parse a schedule page to extract match entries.
     *
     * @param string $html Raw HTML content of the schedule page
     * @return array<int, array{match_date: string, match_time: ?string, opponent: string, venue: string, home_away: int, result: ?string}> Array of match entries
     */
    public function parseSchedule(string $html): array;
}
