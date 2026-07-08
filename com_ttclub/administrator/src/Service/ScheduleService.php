<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Service;

use Joomla\Database\DatabaseInterface;

/**
 * Fetches and caches match schedule data from click-tt.de.
 *
 * Schedule data is NOT stored locally — it is always fetched live with caching.
 * The service POSTs to the clubMeetings endpoint with the team's club_id and
 * season date range, then filters results by team name using ClickTtParser.
 *
 * Cache validity is determined by comparing fetched_at + cache_duration against
 * the current time. On fetch failure, returns stale cache if available, or null
 * for graceful degradation.
 */
class ScheduleService
{
    private const DEFAULT_CACHE_DURATION = 259200; // 3 days in seconds

    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly ClickTtParser $parser,
        private readonly ClickTtUrlBuilder $urlBuilder,
        private readonly int $cacheDurationSeconds = self::DEFAULT_CACHE_DURATION,
    ) {}

    /**
     * Get the match schedule for a team and half-season.
     *
     * Returns cached data if available and not expired.
     * Fetches fresh data from click-tt.de by POSTing to clubMeetings
     * with the team's stored club_id and season date range,
     * filtering results by the team's name.
     *
     * @param int $teamId The team ID to fetch schedule for
     * @param int $halfSeasonId The half-season ID
     * @return array|null Schedule entries or null on fetch failure
     *                    Each entry: ['match_date' => string, 'match_time' => ?string,
     *                                 'home_team' => string, 'guest_team' => string, 'result' => ?string]
     */
    public function getSchedule(int $teamId, int $halfSeasonId): ?array
    {
        // Check cache first
        $cached = $this->loadCachedSchedule($teamId, $halfSeasonId);

        if ($cached !== null && $this->isCacheEntryValid($cached)) {
            return $this->deserializeSchedule($cached->schedule_data);
        }

        // Fetch fresh data
        $scheduleData = $this->fetchScheduleFromClickTt($teamId, $halfSeasonId);

        if ($scheduleData === null) {
            // On fetch failure, return stale cache if available (better than nothing)
            if ($cached !== null) {
                return $this->deserializeSchedule($cached->schedule_data);
            }

            return null;
        }

        // Store in cache
        $this->storeCachedSchedule($teamId, $halfSeasonId, $scheduleData);

        return $scheduleData;
    }

    /**
     * Check if cached schedule data is still valid for the given team and half-season.
     *
     * @param int $teamId The team ID
     * @param int $halfSeasonId The half-season ID
     * @return bool True if cache exists and has not expired
     */
    public function isCacheValid(int $teamId, int $halfSeasonId): bool
    {
        $cached = $this->loadCachedSchedule($teamId, $halfSeasonId);

        if ($cached === null) {
            return false;
        }

        return $this->isCacheEntryValid($cached);
    }

    /**
     * Invalidate cached schedule for a team/half-season.
     *
     * @param int $teamId The team ID
     * @param int $halfSeasonId The half-season ID
     */
    public function invalidateScheduleCache(int $teamId, int $halfSeasonId): void
    {
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__ttclub_schedule_cache'))
            ->where($this->db->quoteName('team_id') . ' = ' . (int) $teamId)
            ->where($this->db->quoteName('half_season_id') . ' = ' . (int) $halfSeasonId);

        $this->db->setQuery($query);
        $this->db->execute();
    }

    // ---------------------------------------------------------------
    // Cache management
    // ---------------------------------------------------------------

    /**
     * Load cached schedule entry from the database.
     */
    private function loadCachedSchedule(int $teamId, int $halfSeasonId): ?object
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__ttclub_schedule_cache'))
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
     * Store or update the schedule cache entry.
     *
     * @param int $teamId The team ID
     * @param int $halfSeasonId The half-season ID
     * @param array $scheduleData The parsed schedule entries
     */
    private function storeCachedSchedule(int $teamId, int $halfSeasonId, array $scheduleData): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $data = json_encode($scheduleData, JSON_UNESCAPED_UNICODE);

        // Check if entry already exists (upsert)
        $existing = $this->loadCachedSchedule($teamId, $halfSeasonId);

        if ($existing !== null) {
            $query = $this->db->getQuery(true)
                ->update($this->db->quoteName('#__ttclub_schedule_cache'))
                ->set($this->db->quoteName('schedule_data') . ' = ' . $this->db->quote($data))
                ->set($this->db->quoteName('fetched_at') . ' = ' . $this->db->quote($now))
                ->where($this->db->quoteName('id') . ' = ' . (int) $existing->id);

            $this->db->setQuery($query);
            $this->db->execute();
        } else {
            $record = (object) [
                'team_id' => $teamId,
                'half_season_id' => $halfSeasonId,
                'schedule_data' => $data,
                'fetched_at' => $now,
            ];

            $this->db->insertObject('#__ttclub_schedule_cache', $record);
        }
    }

    // ---------------------------------------------------------------
    // Data fetching from click-tt.de
    // ---------------------------------------------------------------

    /**
     * Fetch schedule data from click-tt.de for the given team and half-season.
     *
     * POSTs to the clubMeetings endpoint with the team's club_id and season
     * date range, then filters the results by team name.
     *
     * @return array|null Parsed schedule entries or null on failure
     */
    private function fetchScheduleFromClickTt(int $teamId, int $halfSeasonId): ?array
    {
        // Get team info including club_id_source and team_number
        $teamInfo = $this->getTeamInfo($teamId);

        if ($teamInfo === null) {
            return null;
        }

        // Get the club configuration (federation, click_tt_club_id, club_name)
        $clubConfig = $this->getClubConfig($teamInfo->club_id_source);

        if ($clubConfig === null) {
            return null;
        }

        // Get season start year for date range
        $startYear = $this->getSeasonStartYear($teamInfo->season_id);

        if ($startYear === null) {
            return null;
        }

        // Build the team name for filtering (club_name + team number if > 1)
        $teamName = $this->buildTeamName($clubConfig->club_name, (int) $teamInfo->team_number);

        // Build the clubMeetings POST URL and body
        $url = $this->urlBuilder->clubMeetings($clubConfig->federation);
        $postBody = $this->urlBuilder->clubMeetingsBody(
            (int) $clubConfig->click_tt_club_id,
            $startYear
        );

        // POST to click-tt.de
        $html = $this->postPage($url, $postBody);

        if ($html === null) {
            return null;
        }

        // Parse and filter schedule for this team
        $schedule = $this->parser->parseScheduleForTeam($html, $teamName);

        if (empty($schedule)) {
            // Return empty array (valid response) rather than null (fetch failure)
            return [];
        }

        return $schedule;
    }

    /**
     * Build the team's display name for filtering schedule results.
     *
     * click-tt.de uses "{ClubName} {TeamNumber}" format for team numbers > 1,
     * and just "{ClubName}" for team 1 (or with Roman numerals).
     *
     * @param string $clubName The club name from club_ids table
     * @param int $teamNumber The team number
     * @return string The team name for filtering
     */
    private function buildTeamName(string $clubName, int $teamNumber): string
    {
        if ($teamNumber <= 1) {
            return $clubName;
        }

        // click-tt.de typically uses Roman numerals for team numbers
        $romanMap = [
            2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V',
            6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X',
        ];

        $suffix = $romanMap[$teamNumber] ?? (string) $teamNumber;

        return $clubName . ' ' . $suffix;
    }

    /**
     * Deserialize cached schedule data (JSON).
     */
    private function deserializeSchedule(string $scheduleData): ?array
    {
        $data = json_decode($scheduleData, true);

        if (!is_array($data)) {
            return null;
        }

        return $data;
    }

    // ---------------------------------------------------------------
    // Database lookups
    // ---------------------------------------------------------------

    /**
     * Get team information including season_id, team_number, and club_id_source.
     */
    private function getTeamInfo(int $teamId): ?object
    {
        $query = $this->db->getQuery(true)
            ->select([
                't.id',
                't.season_id',
                't.team_number',
                't.club_id_source',
            ])
            ->from($this->db->quoteName('#__ttclub_teams', 't'))
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
     * Get the season start year for a season ID.
     */
    private function getSeasonStartYear(int $seasonId): ?int
    {
        $query = $this->db->getQuery(true)
            ->select('start_year')
            ->from($this->db->quoteName('#__ttclub_seasons'))
            ->where('id = ' . (int) $seasonId);

        $this->db->setQuery($query);
        $result = $this->db->loadResult();

        if ($result === null) {
            return null;
        }

        return (int) $result;
    }

    // ---------------------------------------------------------------
    // HTTP
    // ---------------------------------------------------------------

    /**
     * HTTP POST request. Returns HTML string or null on failure.
     */
    protected function postPage(string $url, string $body): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'User-Agent: Mozilla/5.0 (compatible; TtclubSchedule/1.0)',
                    'Accept: text/html,application/xhtml+xml',
                ],
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false || $httpCode >= 400) {
                return null;
            }

            return $response;
        }

        // Fallback to file_get_contents
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 30,
                'header' => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: Mozilla/5.0 (compatible; TtclubSchedule/1.0)\r\nAccept: text/html\r\n",
                'content' => $body,
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
