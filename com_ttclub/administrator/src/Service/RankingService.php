<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Service;

use Joomla\Database\DatabaseInterface;

/**
 * Fetches and caches league ranking tables from click-tt.de.
 *
 * The service maintains a cache in #__ttclub_ranking_cache to avoid
 * excessive requests to click-tt.de. Cache validity is determined by
 * comparing fetched_at + cache_duration against the current time.
 *
 * On fetch failure, returns null for graceful degradation — the calling
 * page can still render without the ranking table.
 */
class RankingService
{
    private const DEFAULT_CACHE_DURATION = 3600;

    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly ClickTtParser $parser,
        private readonly int $cacheDurationSeconds = self::DEFAULT_CACHE_DURATION,
    ) {}

    /**
     * Get ranking table for a team's league and half-season.
     *
     * Returns cached data if available and not expired.
     * Fetches fresh data from click-tt.de if cache is expired or missing.
     * The own team is identified by matching the club name from the club_ids table
     * and highlighted with a CSS class `ttclub-own-team`.
     *
     * @param int $teamId The team ID to fetch ranking for
     * @param int $halfSeasonId The half-season ID
     * @return array|null Ranking rows or null on fetch failure
     *                    Each row: ['position' => int, 'team_name' => string, 'matches' => int,
     *                               'wins' => int, 'draws' => int, 'losses' => int,
     *                               'points' => string, 'is_own_team' => bool]
     */
    public function getRanking(int $teamId, int $halfSeasonId): ?array
    {
        // Check cache first
        $cached = $this->loadCachedRanking($teamId, $halfSeasonId);

        if ($cached !== null && $this->isCacheEntryValid($cached)) {
            return $this->deserializeRanking($cached->ranking_html, $teamId);
        }

        // Fetch fresh data
        $rankingData = $this->fetchRankingFromClickTt($teamId, $halfSeasonId);

        if ($rankingData === null) {
            // On fetch failure, return stale cache if available (better than nothing)
            if ($cached !== null) {
                return $this->deserializeRanking($cached->ranking_html, $teamId);
            }

            return null;
        }

        // Store in cache
        $this->storeCachedRanking($teamId, $halfSeasonId, $rankingData);

        return $this->markOwnTeam($rankingData, $teamId);
    }

    /**
     * Check if cached ranking data is still valid for the given team and half-season.
     *
     * @param int $teamId The team ID
     * @param int $halfSeasonId The half-season ID
     * @return bool True if cache exists and has not expired
     */
    public function isCacheValid(int $teamId, int $halfSeasonId): bool
    {
        $cached = $this->loadCachedRanking($teamId, $halfSeasonId);

        if ($cached === null) {
            return false;
        }

        return $this->isCacheEntryValid($cached);
    }

    /**
     * Invalidate cached ranking for a team/half-season.
     *
     * @param int $teamId The team ID
     * @param int $halfSeasonId The half-season ID
     */
    public function invalidateCache(int $teamId, int $halfSeasonId): void
    {
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__ttclub_ranking_cache'))
            ->where($this->db->quoteName('team_id') . ' = ' . (int) $teamId)
            ->where($this->db->quoteName('half_season_id') . ' = ' . (int) $halfSeasonId);

        $this->db->setQuery($query);
        $this->db->execute();
    }

    // ---------------------------------------------------------------
    // Cache management
    // ---------------------------------------------------------------

    /**
     * Load cached ranking entry from the database.
     */
    private function loadCachedRanking(int $teamId, int $halfSeasonId): ?object
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__ttclub_ranking_cache'))
            ->where($this->db->quoteName('team_id') . ' = ' . (int) $teamId)
            ->where($this->db->quoteName('half_season_id') . ' = ' . (int) $halfSeasonId);

        $this->db->setQuery($query);
        $result = $this->db->loadObject();

        return $result ?: null;
    }

    /**
     * Determine if a cache entry is still valid based on fetched_at + cache duration.
     */
    private function isCacheEntryValid(object $cacheEntry): bool
    {
        $fetchedAt = strtotime($cacheEntry->fetched_at);

        if ($fetchedAt === false) {
            return false;
        }

        return ($fetchedAt + $this->cacheDurationSeconds) > time();
    }

    /**
     * Store or update the ranking cache entry.
     *
     * @param int $teamId The team ID
     * @param int $halfSeasonId The half-season ID
     * @param array $rankingData The parsed ranking rows
     */
    private function storeCachedRanking(int $teamId, int $halfSeasonId, array $rankingData): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $html = json_encode($rankingData, JSON_UNESCAPED_UNICODE);

        // Check if entry already exists (upsert)
        $existing = $this->loadCachedRanking($teamId, $halfSeasonId);

        if ($existing !== null) {
            $query = $this->db->getQuery(true)
                ->update($this->db->quoteName('#__ttclub_ranking_cache'))
                ->set($this->db->quoteName('ranking_html') . ' = ' . $this->db->quote($html))
                ->set($this->db->quoteName('fetched_at') . ' = ' . $this->db->quote($now))
                ->where($this->db->quoteName('id') . ' = ' . (int) $existing->id);

            $this->db->setQuery($query);
            $this->db->execute();
        } else {
            $record = (object) [
                'team_id' => $teamId,
                'half_season_id' => $halfSeasonId,
                'ranking_html' => $html,
                'fetched_at' => $now,
            ];

            $this->db->insertObject('#__ttclub_ranking_cache', $record);
        }
    }

    // ---------------------------------------------------------------
    // Data fetching from click-tt.de
    // ---------------------------------------------------------------

    /**
     * Fetch ranking data from click-tt.de for the given team and half-season.
     *
     * Determines the team's league URL from the team/season configuration,
     * fetches the ranking page, and parses it.
     *
     * @return array|null Parsed ranking rows or null on failure
     */
    private function fetchRankingFromClickTt(int $teamId, int $halfSeasonId): ?array
    {
        // Get team info including league and associated club ID source
        $teamInfo = $this->getTeamInfo($teamId);

        if ($teamInfo === null) {
            return null;
        }

        // Get the club configuration (federation, club_id)
        $clubConfig = $this->getClubConfig($teamInfo->club_id_source);

        if ($clubConfig === null) {
            return null;
        }

        // Determine half number for the display type
        $half = $this->getHalfNumber($halfSeasonId);
        $displayTyp = ($half === 1) ? 'vorrunde' : 'rueckrunde';

        // Get season name
        $seasonName = $this->getSeasonName($teamInfo->season_id);

        if ($seasonName === null) {
            return null;
        }

        // Build the ranking URL
        // click-tt.de ranking URL pattern:
        // https://{federation}.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/groupPage?championship={championship}&group={league}
        $federation = strtolower($clubConfig->federation);
        $url = sprintf(
            'https://%s.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/groupPage?championship=%s&group=%s',
            $federation,
            urlencode($federation . ' ' . $seasonName),
            urlencode($teamInfo->league_name)
        );

        $html = $this->fetchPage($url);

        if ($html === null) {
            return null;
        }

        $ranking = $this->parser->parseRankingTable($html);

        if (empty($ranking)) {
            return null;
        }

        return $ranking;
    }

    /**
     * Mark the own team in ranking data with a flag.
     *
     * Identifies the own team by matching club names from the club_ids table.
     *
     * @param array $rankingData The ranking rows
     * @param int $teamId The team ID to identify
     * @return array Ranking rows with is_own_team flag set
     */
    private function markOwnTeam(array $rankingData, int $teamId): array
    {
        $clubNames = $this->getClubNames();

        foreach ($rankingData as &$row) {
            $row['is_own_team'] = false;
            $teamNameLower = mb_strtolower($row['team_name'] ?? '');

            foreach ($clubNames as $clubName) {
                if ($clubName !== '' && str_contains($teamNameLower, mb_strtolower($clubName))) {
                    $row['is_own_team'] = true;
                    break;
                }
            }
        }

        return $rankingData;
    }

    /**
     * Deserialize cached ranking HTML (JSON) and mark own team.
     */
    private function deserializeRanking(string $rankingHtml, int $teamId): ?array
    {
        $data = json_decode($rankingHtml, true);

        if (!is_array($data)) {
            return null;
        }

        return $this->markOwnTeam($data, $teamId);
    }

    // ---------------------------------------------------------------
    // Database lookups
    // ---------------------------------------------------------------

    /**
     * Get team information including league name.
     */
    private function getTeamInfo(int $teamId): ?object
    {
        $query = $this->db->getQuery(true)
            ->select([
                't.id',
                't.season_id',
                't.league_id',
                't.team_number',
                't.club_id_source',
                'l.name AS league_name',
            ])
            ->from($this->db->quoteName('#__ttclub_teams', 't'))
            ->innerJoin(
                $this->db->quoteName('#__ttclub_leagues', 'l')
                . ' ON l.id = t.league_id'
            )
            ->where('t.id = ' . (int) $teamId);

        $this->db->setQuery($query);
        $result = $this->db->loadObject();

        return $result ?: null;
    }

    /**
     * Get club configuration for a given club_id_source.
     * Falls back to the first configured club ID if source is null.
     */
    private function getClubConfig(?int $clubIdSource): ?object
    {
        if ($clubIdSource !== null && $clubIdSource > 0) {
            $query = $this->db->getQuery(true)
                ->select('*')
                ->from($this->db->quoteName('#__ttclub_club_ids'))
                ->where('id = ' . (int) $clubIdSource);

            $this->db->setQuery($query);
            $result = $this->db->loadObject();

            if ($result) {
                return $result;
            }
        }

        // Fallback: use first configured club ID
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__ttclub_club_ids'))
            ->order('ordering ASC');

        $this->db->setQuery($query, 0, 1);
        $result = $this->db->loadObject();

        return $result ?: null;
    }

    /**
     * Get the half number (1 or 2) for a given half_season_id.
     */
    private function getHalfNumber(int $halfSeasonId): int
    {
        $query = $this->db->getQuery(true)
            ->select('half')
            ->from($this->db->quoteName('#__ttclub_half_seasons'))
            ->where('id = ' . (int) $halfSeasonId);

        $this->db->setQuery($query);

        return (int) ($this->db->loadResult() ?? 1);
    }

    /**
     * Get the season display name (e.g., "2025/26") for a season ID.
     */
    private function getSeasonName(int $seasonId): ?string
    {
        $query = $this->db->getQuery(true)
            ->select('start_year')
            ->from($this->db->quoteName('#__ttclub_seasons'))
            ->where('id = ' . (int) $seasonId);

        $this->db->setQuery($query);
        $startYear = $this->db->loadResult();

        if ($startYear === null) {
            return null;
        }

        $startYear = (int) $startYear;

        return sprintf('%d/%02d', $startYear, ($startYear + 1) % 100);
    }

    /**
     * Get all configured club names for own-team matching.
     *
     * @return string[]
     */
    private function getClubNames(): array
    {
        $query = $this->db->getQuery(true)
            ->select('club_name')
            ->from($this->db->quoteName('#__ttclub_club_ids'));

        $this->db->setQuery($query);
        $results = $this->db->loadColumn();

        return is_array($results) ? $results : [];
    }

    // ---------------------------------------------------------------
    // HTTP
    // ---------------------------------------------------------------

    /**
     * Fetch a page via HTTP GET. Returns HTML string or null on failure.
     */
    protected function fetchPage(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 15,
                'header' => "User-Agent: Mozilla/5.0 (compatible; TtclubRanking/1.0)\r\nAccept: text/html\r\n",
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        // Check HTTP status from response headers
        if (isset($http_response_header) && is_array($http_response_header)) {
            $statusLine = $http_response_header[0] ?? '';
            if (preg_match('/\s(\d{3})\s/', $statusLine, $matches)) {
                $statusCode = (int) $matches[1];
                if ($statusCode >= 400) {
                    return null;
                }
            }
        }

        return $response;
    }
}
