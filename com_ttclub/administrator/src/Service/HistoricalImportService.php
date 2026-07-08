<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Service;

use Fatherjoe\Component\Ttclub\Administrator\Table\ImportLogTable;
use Joomla\Database\DatabaseInterface;

/**
 * Service for one-time bulk import of all historical seasons from mytischtennis.de or click-tt.de.
 *
 * Discovers all available seasons for a configured club, then imports teams, rosters,
 * and schedules for each season. Uses SeasonParserInterface implementations selected
 * by dataSource parameter. Player matching uses first_name + last_name as unique identifier.
 *
 * Per-season commit and logging via ImportLogTable ensures progress is not lost on failure.
 */
class HistoricalImportService
{
    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly ImportLogTable $importLog,
    ) {}

    /**
     * Discover all available seasons from the club's archive pages.
     *
     * Fetches the club archive page and uses the appropriate parser to extract
     * all season links (name + URL).
     *
     * @param string $clubUrl  Base club URL on mytischtennis.de or click-tt.de
     * @param string $dataSource 'mytischtennis' or 'clicktt'
     * @return DiscoveredSeason[] Array of discovered season descriptors
     */
    public function discoverSeasons(string $clubUrl, string $dataSource): array
    {
        $parser = $this->createParser($dataSource);
        $archiveUrl = rtrim($clubUrl, '/');
        $html = $this->fetchPage($archiveUrl);

        if ($html === null) {
            return [];
        }

        $rawSeasons = $parser->parseSeasonArchive($html);
        $discovered = [];

        foreach ($rawSeasons as $season) {
            $name = trim($season['name'] ?? '');
            $url = trim($season['url'] ?? '');

            if ($name !== '' && $url !== '') {
                $discovered[] = new DiscoveredSeason(
                    name: $name,
                    archiveUrl: $url,
                    dataSource: $dataSource,
                );
            }
        }

        return $discovered;
    }

    /**
     * Execute the full historical import for all discovered seasons.
     *
     * Discovers seasons, deduplicates against existing data, then imports each new season.
     * Each season is committed independently so progress is preserved on failure.
     *
     * @param string $clubUrl    Base club URL
     * @param string $dataSource 'mytischtennis' or 'clicktt'
     * @param bool   $confirmed  Whether the admin has confirmed the operation
     * @return HistoricalImportResult Summary of all created records
     */
    public function executeFullImport(
        string $clubUrl,
        string $dataSource,
        bool $confirmed = false,
    ): HistoricalImportResult {
        if (!$confirmed && $this->hasExistingData()) {
            return new HistoricalImportResult(
                seasonsCreated: 0,
                teamsCreated: 0,
                playersCreated: 0,
                rosterEntriesCreated: 0,
                scheduleEntriesCreated: 0,
                perSeasonResults: [],
            );
        }

        $discoveredSeasons = $this->discoverSeasons($clubUrl, $dataSource);

        if ($discoveredSeasons === []) {
            return new HistoricalImportResult(
                seasonsCreated: 0,
                teamsCreated: 0,
                playersCreated: 0,
                rosterEntriesCreated: 0,
                scheduleEntriesCreated: 0,
                perSeasonResults: [],
            );
        }

        $totalSeasonsCreated = 0;
        $totalTeamsCreated = 0;
        $totalPlayersCreated = 0;
        $totalRosterEntries = 0;
        $totalScheduleEntries = 0;
        $perSeasonResults = [];

        foreach ($discoveredSeasons as $season) {
            // Season deduplication: skip existing seasons by name
            if ($this->seasonExistsByName($season->name)) {
                $perSeasonResults[] = new SeasonImportResult(
                    seasonName: $season->name,
                    teamsCreated: 0,
                    rosterEntriesCreated: 0,
                    scheduleEntriesCreated: 0,
                    playersCreated: 0,
                    success: true,
                    errorMessage: 'Skipped — season already exists.',
                );
                continue;
            }

            $result = $this->importSeason($season, $dataSource);
            $perSeasonResults[] = $result;

            if ($result->success) {
                $totalSeasonsCreated++;
                $totalTeamsCreated += $result->teamsCreated;
                $totalPlayersCreated += $result->playersCreated;
                $totalRosterEntries += $result->rosterEntriesCreated;
                $totalScheduleEntries += $result->scheduleEntriesCreated;
            }
        }

        return new HistoricalImportResult(
            seasonsCreated: $totalSeasonsCreated,
            teamsCreated: $totalTeamsCreated,
            playersCreated: $totalPlayersCreated,
            rosterEntriesCreated: $totalRosterEntries,
            scheduleEntriesCreated: $totalScheduleEntries,
            perSeasonResults: $perSeasonResults,
        );
    }

    /**
     * Check whether the database already contains season or team records.
     *
     * Used to trigger the "initial setup" warning before proceeding.
     */
    public function hasExistingData(): bool
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__ttclub_seasons'));

        $this->db->setQuery($query);
        $seasonCount = (int) $this->db->loadResult();

        if ($seasonCount > 0) {
            return true;
        }

        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__ttclub_teams'));

        $this->db->setQuery($query);
        $teamCount = (int) $this->db->loadResult();

        return $teamCount > 0;
    }

    /**
     * Import a single season's data (creates season record, teams, rosters, schedules).
     *
     * Fetches the season page, parses teams, then for each team fetches rosters and schedules.
     * Players are matched by first_name + last_name or created if not found.
     * The entire season is committed at the end and logged via ImportLogTable.
     *
     * @param DiscoveredSeason $season     The season to import
     * @param string           $dataSource 'mytischtennis' or 'clicktt'
     * @return SeasonImportResult Per-season result with counts
     */
    public function importSeason(DiscoveredSeason $season, string $dataSource): SeasonImportResult
    {
        $parser = $this->createParser($dataSource);
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        // Create the season record
        $seasonId = $this->createSeasonRecord($season->name, $now);

        if ($seasonId === null) {
            $this->logSeasonImport($season->name, 0, false, 'Failed to create season record.');

            return new SeasonImportResult(
                seasonName: $season->name,
                teamsCreated: 0,
                rosterEntriesCreated: 0,
                scheduleEntriesCreated: 0,
                playersCreated: 0,
                success: false,
                errorMessage: 'Failed to create season record.',
            );
        }

        // Create half-season records for this season
        $halfSeasonIds = $this->createHalfSeasonRecords($seasonId, $season->name);

        // Fetch and parse teams for this season
        $seasonHtml = $this->fetchPage($season->archiveUrl);

        if ($seasonHtml === null) {
            $this->logSeasonImport($season->name, 0, false, 'Failed to fetch season page.');

            return new SeasonImportResult(
                seasonName: $season->name,
                teamsCreated: 0,
                rosterEntriesCreated: 0,
                scheduleEntriesCreated: 0,
                playersCreated: 0,
                success: false,
                errorMessage: 'Failed to fetch season page: ' . $season->archiveUrl,
            );
        }

        $parsedTeams = $parser->parseTeams($seasonHtml);

        $teamsCreated = 0;
        $rosterEntriesCreated = 0;
        $scheduleEntriesCreated = 0;
        $playersCreated = 0;

        foreach ($parsedTeams as $teamData) {
            $teamNumber = (int) ($teamData['team_number'] ?? 0);
            $leagueName = trim($teamData['league'] ?? '');
            $ageClassName = trim($teamData['age_class'] ?? 'Herren');

            if ($teamNumber === 0) {
                continue;
            }

            // Match-or-create league
            $leagueId = $this->matchOrCreateLeague($leagueName, $now);

            // Match-or-create age class
            $ageClassId = $this->matchOrCreateAgeClass($ageClassName, $now);

            // Create team record
            $teamId = $this->createTeamRecord($seasonId, $leagueId, $ageClassId, $teamNumber, $now);
            $teamsCreated++;

            // Import roster for this team (try to parse from same page or sub-page)
            $rosterNames = $parser->parseRoster($seasonHtml);

            foreach ($rosterNames as $playerName) {
                $nameParts = $this->splitPlayerName($playerName);
                $firstName = $nameParts['first_name'];
                $lastName = $nameParts['last_name'];

                if ($firstName === '' || $lastName === '') {
                    continue;
                }

                // Player match-or-create using first_name + last_name
                $playerId = $this->findPlayerByName($firstName, $lastName);

                if ($playerId === null) {
                    $playerId = $this->insertPlayer($firstName, $lastName, $now);
                    $playersCreated++;
                }

                // Create roster entries for both half-seasons
                foreach ($halfSeasonIds as $halfSeasonId) {
                    $this->insertRosterEntry($playerId, $teamId, $halfSeasonId, $now);
                    $rosterEntriesCreated++;
                }
            }

            // Import schedule for this team
            $scheduleEntries = $parser->parseSchedule($seasonHtml);

            foreach ($scheduleEntries as $matchData) {
                $matchDate = $matchData['match_date'] ?? '';
                $opponent = trim($matchData['opponent'] ?? '');

                if ($matchDate === '' || $opponent === '') {
                    continue;
                }

                $this->insertScheduleEntry(
                    teamId: $teamId,
                    seasonId: $seasonId,
                    matchDate: $matchDate,
                    matchTime: $matchData['match_time'] ?? null,
                    opponent: $opponent,
                    venue: trim($matchData['venue'] ?? ''),
                    homeAway: (int) ($matchData['home_away'] ?? 1),
                    result: $matchData['result'] ?? null,
                    now: $now,
                );
                $scheduleEntriesCreated++;
            }
        }

        // Log per-season result
        $totalRecords = $teamsCreated + $rosterEntriesCreated + $scheduleEntriesCreated + $playersCreated;
        $this->logSeasonImport(
            $season->name,
            $totalRecords,
            true,
            sprintf(
                'Season "%s": %d teams, %d players, %d roster entries, %d schedules created.',
                $season->name,
                $teamsCreated,
                $playersCreated,
                $rosterEntriesCreated,
                $scheduleEntriesCreated,
            ),
        );

        return new SeasonImportResult(
            seasonName: $season->name,
            teamsCreated: $teamsCreated,
            rosterEntriesCreated: $rosterEntriesCreated,
            scheduleEntriesCreated: $scheduleEntriesCreated,
            playersCreated: $playersCreated,
            success: true,
        );
    }

    // ---------------------------------------------------------------
    // Parser factory
    // ---------------------------------------------------------------

    /**
     * Create the appropriate parser based on data source.
     */
    protected function createParser(string $dataSource): SeasonParserInterface
    {
        return match ($dataSource) {
            'clicktt' => new ClickTtParser(),
            default => new MyTischtennisParser(),
        };
    }

    // ---------------------------------------------------------------
    // HTTP fetching
    // ---------------------------------------------------------------

    /**
     * Fetch a page via HTTP GET. Returns HTML string or null on failure.
     */
    protected function fetchPage(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'header' => "Accept: text/html\r\nUser-Agent: TtclubImport/1.0\r\n",
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

    // ---------------------------------------------------------------
    // Season record management
    // ---------------------------------------------------------------

    /**
     * Check if a season already exists by name.
     */
    protected function seasonExistsByName(string $seasonName): bool
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__ttclub_seasons'))
            ->where($this->db->quoteName('start_year') . ' = ' . (int) $seasonName);

        $this->db->setQuery($query);

        return (int) $this->db->loadResult() > 0;
    }

    /**
     * Create a season record.
     *
     * @return int|null The new season ID, or null on failure.
     */
    protected function createSeasonRecord(string $name, string $now): ?int
    {
        $record = (object) [
            'name' => $name,
            'published' => 1,
            'created' => $now,
            'modified' => $now,
            'created_by' => 0,
            'modified_by' => 0,
        ];

        try {
            $this->db->insertObject('#__ttclub_seasons', $record, 'id');

            return (int) $record->id;
        } catch (\RuntimeException) {
            return null;
        }
    }

    /**
     * Create half-season records for a season.
     *
     * Derives approximate dates from the season name (e.g., "2019/20" →
     * first half: 2019-09-01 to 2019-12-31, second half: 2020-01-01 to 2020-06-30).
     *
     * @return int[] Array of half-season IDs [firstHalfId, secondHalfId]
     */
    protected function createHalfSeasonRecords(int $seasonId, string $seasonName): array
    {
        // Parse year from season name (format: "YYYY/YY")
        $startYear = (int) substr($seasonName, 0, 4);

        if ($startYear === 0) {
            $startYear = (int) date('Y');
        }

        $endYear = $startYear + 1;

        $ids = [];

        // First half-season
        $firstHalf = (object) [
            'season_id' => $seasonId,
            'half' => 1,
            'start_date' => $startYear . '-09-01',
            'end_date' => $startYear . '-12-31',
        ];
        $this->db->insertObject('#__ttclub_half_seasons', $firstHalf, 'id');
        $ids[] = (int) $firstHalf->id;

        // Second half-season
        $secondHalf = (object) [
            'season_id' => $seasonId,
            'half' => 2,
            'start_date' => $endYear . '-01-01',
            'end_date' => $endYear . '-06-30',
        ];
        $this->db->insertObject('#__ttclub_half_seasons', $secondHalf, 'id');
        $ids[] = (int) $secondHalf->id;

        return $ids;
    }

    // ---------------------------------------------------------------
    // League and age class management
    // ---------------------------------------------------------------

    /**
     * Find or create a league by name. Returns the league ID.
     */
    protected function matchOrCreateLeague(string $name, string $now): int
    {
        if ($name === '') {
            $name = 'Unbekannt';
        }

        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__ttclub_leagues'))
            ->where('LOWER(' . $this->db->quoteName('name') . ') = LOWER(' . $this->db->quote($name) . ')');

        $this->db->setQuery($query);
        $existingId = $this->db->loadResult();

        if ($existingId !== null) {
            return (int) $existingId;
        }

        $record = (object) [
            'name' => $name,
            'published' => 1,
            'created' => $now,
            'modified' => $now,
            'created_by' => 0,
            'modified_by' => 0,
        ];

        $this->db->insertObject('#__ttclub_leagues', $record, 'id');

        return (int) $record->id;
    }

    /**
     * Find or create an age class by name. Returns the age class ID.
     */
    protected function matchOrCreateAgeClass(string $name, string $now): int
    {
        if ($name === '') {
            $name = 'Herren';
        }

        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__ttclub_age_classes'))
            ->where('LOWER(' . $this->db->quoteName('name') . ') = LOWER(' . $this->db->quote($name) . ')');

        $this->db->setQuery($query);
        $existingId = $this->db->loadResult();

        if ($existingId !== null) {
            return (int) $existingId;
        }

        $record = (object) [
            'name' => $name,
            'max_age' => null,
            'published' => 1,
            'created' => $now,
            'modified' => $now,
        ];

        $this->db->insertObject('#__ttclub_age_classes', $record, 'id');

        return (int) $record->id;
    }

    // ---------------------------------------------------------------
    // Team management
    // ---------------------------------------------------------------

    /**
     * Create a team record. Returns the new team ID.
     */
    protected function createTeamRecord(
        int $seasonId,
        int $leagueId,
        int $ageClassId,
        int $teamNumber,
        string $now,
    ): int {
        $record = (object) [
            'season_id' => $seasonId,
            'league_id' => $leagueId,
            'age_class_id' => $ageClassId,
            'team_number' => $teamNumber,
            'published' => 1,
            'created' => $now,
            'modified' => $now,
            'created_by' => 0,
            'modified_by' => 0,
        ];

        $this->db->insertObject('#__ttclub_teams', $record, 'id');

        return (int) $record->id;
    }

    // ---------------------------------------------------------------
    // Player management
    // ---------------------------------------------------------------

    /**
     * Find a player by first name + last name (case-insensitive match).
     *
     * @return int|null The player ID, or null if not found.
     */
    protected function findPlayerByName(string $firstName, string $lastName): ?int
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__ttclub_players'))
            ->where('LOWER(' . $this->db->quoteName('first_name') . ') = LOWER(' . $this->db->quote($firstName) . ')')
            ->where('LOWER(' . $this->db->quoteName('last_name') . ') = LOWER(' . $this->db->quote($lastName) . ')');

        $this->db->setQuery($query);
        $result = $this->db->loadResult();

        return $result !== null ? (int) $result : null;
    }

    /**
     * Insert a new player record.
     *
     * @return int The new player's ID.
     */
    protected function insertPlayer(string $firstName, string $lastName, string $now): int
    {
        $record = (object) [
            'first_name' => mb_substr($firstName, 0, 50),
            'last_name' => mb_substr($lastName, 0, 50),
            'published' => 1,
            'created' => $now,
            'modified' => $now,
            'created_by' => 0,
            'modified_by' => 0,
        ];

        $this->db->insertObject('#__ttclub_players', $record, 'id');

        return (int) $record->id;
    }

    // ---------------------------------------------------------------
    // Roster management
    // ---------------------------------------------------------------

    /**
     * Insert a roster entry linking a player to a team for a half-season.
     */
    protected function insertRosterEntry(int $playerId, int $teamId, int $halfSeasonId, string $now): void
    {
        $record = (object) [
            'player_id' => $playerId,
            'team_id' => $teamId,
            'half_season_id' => $halfSeasonId,
            'created' => $now,
        ];

        $this->db->insertObject('#__ttclub_rosters', $record);
    }

    // ---------------------------------------------------------------
    // Schedule management
    // ---------------------------------------------------------------

    /**
     * Insert a schedule entry for a team in a season.
     */
    protected function insertScheduleEntry(
        int $teamId,
        int $seasonId,
        string $matchDate,
        ?string $matchTime,
        string $opponent,
        string $venue,
        int $homeAway,
        ?string $result,
        string $now,
    ): void {
        $record = (object) [
            'team_id' => $teamId,
            'season_id' => $seasonId,
            'match_date' => $matchDate,
            'match_time' => $matchTime,
            'opponent' => mb_substr($opponent, 0, 150),
            'venue' => mb_substr($venue, 0, 200),
            'home_away' => $homeAway,
            'result' => $result !== null ? mb_substr($result, 0, 20) : null,
            'published' => 1,
            'created' => $now,
            'modified' => $now,
            'created_by' => 0,
            'modified_by' => 0,
        ];

        $this->db->insertObject('#__ttclub_schedules', $record);
    }

    // ---------------------------------------------------------------
    // Name parsing utilities
    // ---------------------------------------------------------------

    /**
     * Split a full player name into first and last name parts.
     * Handles "Last, First" and "First Last" formats.
     *
     * @return array{first_name: string, last_name: string}
     */
    protected function splitPlayerName(string $fullName): array
    {
        $fullName = trim($fullName);

        // Handle "Last, First" format (common on mytischtennis.de)
        if (str_contains($fullName, ',')) {
            $parts = explode(',', $fullName, 2);

            return [
                'first_name' => mb_substr(trim($parts[1] ?? ''), 0, 50),
                'last_name' => mb_substr(trim($parts[0]), 0, 50),
            ];
        }

        // Handle "First Last" format
        $parts = explode(' ', $fullName);

        if (count($parts) >= 2) {
            $lastName = array_pop($parts);
            $firstName = implode(' ', $parts);

            return [
                'first_name' => mb_substr(trim($firstName), 0, 50),
                'last_name' => mb_substr(trim($lastName), 0, 50),
            ];
        }

        return [
            'first_name' => '',
            'last_name' => mb_substr($fullName, 0, 50),
        ];
    }

    // ---------------------------------------------------------------
    // Logging
    // ---------------------------------------------------------------

    /**
     * Log a per-season import operation via ImportLogTable.
     */
    protected function logSeasonImport(
        string $seasonName,
        int $recordsCreated,
        bool $success,
        ?string $message = null,
    ): void {
        $this->importLog->logImport(
            importType: 'historical_season',
            recordsCreated: $recordsCreated,
            recordsUpdated: 0,
            recordsUnchanged: 0,
            success: $success,
            message: $message,
        );
    }
}
