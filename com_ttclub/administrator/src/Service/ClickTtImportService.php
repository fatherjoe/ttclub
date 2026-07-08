<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Service;

use Joomla\Database\DatabaseInterface;

/**
 * Import service that fetches data from click-tt.de (battv.click-tt.de).
 *
 * URL pattern: https://{federation}.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/{action}?club={clubId}&championship={federation}+{season}
 */
class ClickTtImportService
{
    private const BASE_URL_TEMPLATE = 'https://%s.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/';

    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly string $federation,
        private readonly int $clubId,
        private readonly ?int $clubIdConfigId = null,
    ) {}

    /**
     * Import teams from click-tt.de for the given season.
     * Also discovers and creates the season + half-seasons if they don't exist.
     * Creates separate parallel seasons with label "Pokal" for cup competitions
     * identified by championship name via ClickTtParser::isCupCompetition().
     *
     * @param int $seasonStartYear The start year of the season (e.g., 2025 for "25/26")
     * @param int $seasonId The local season record ID (used for non-cup teams)
     * @return ImportResult
     */
    public function importTeams(int $seasonStartYear, int $seasonId): ImportResult
    {
        $championship = $this->buildChampionship($seasonStartYear);
        $url = $this->buildUrl('clubTeams') . '&championship=' . urlencode($championship);

        $html = $this->fetchPage($url);

        if ($html === null) {
            return new ImportResult(success: false, errorMessage: 'Failed to fetch teams page from click-tt.de. URL: ' . $url);
        }

        $teams = $this->parseTeamsHtml($html);

        if (empty($teams)) {
            return new ImportResult(success: true, errorMessage: 'No teams found on the page.');
        }

        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $parser = new ClickTtParser();

        foreach ($teams as $team) {
            $teamNumber = $team['number'];
            $leagueName = $team['league'];
            $ageClass = $team['age_class'];
            $teamChampionship = $team['championship'] ?? '';

            // Determine target season: create parallel "Pokal" season for cup competitions
            $targetSeasonId = $seasonId;
            if ($teamChampionship !== '' && $parser->isCupCompetition($teamChampionship)) {
                $targetSeasonId = $this->ensureSeasonExists($seasonStartYear, $now, 'Pokal');
                // Ensure half-seasons exist for the Pokal season too
                $this->ensureHalfSeasonExists($targetSeasonId, 1);
                $this->ensureHalfSeasonExists($targetSeasonId, 2);
            }

            // Match or create league
            $leagueId = $this->matchOrCreateLeague($leagueName, $now);

            // Match or create age class
            $ageClassId = $this->matchOrCreateAgeClass($ageClass, $now);

            // Check if team already exists for this season + number + age class
            $existing = $this->findTeam($targetSeasonId, $teamNumber, $ageClassId);

            if ($existing === null) {
                $this->createTeam($targetSeasonId, $leagueId, $ageClassId, $teamNumber, $now);
                $created++;
            } else {
                $unchanged++;
            }
        }

        return new ImportResult(created: $created, updated: $updated, unchanged: $unchanged);
    }

    /**
     * Import roster (player assignments) for teams in the given season.
     *
     * Fetches from: clubPools?displayTyp=vorrunde|rueckrunde&club=X&contestType=Erwachsene&seasonName=YYYY/YY
     */
    public function importRosters(int $seasonStartYear, int $seasonId, int $halfSeasonId): ImportResult
    {
        // Determine vorrunde/rueckrunde from half_season_id
        // click-tt.de uses displayTyp=vorrunde for Hinrunde (first half) and rueckrunde for Rückrunde (second half)
        $half = $this->getHalfNumber($halfSeasonId);
        $displayTyp = ($half === 1) ? 'vorrunde' : 'rueckrunde';

        $seasonName = sprintf('%d/%02d', $seasonStartYear, ($seasonStartYear + 1) % 100);
        $url = sprintf(
            '%sclubPools?displayTyp=%s&club=%d&contestType=Erwachsene&seasonName=%s',
            sprintf(self::BASE_URL_TEMPLATE, strtolower($this->federation)),
            $displayTyp,
            $this->clubId,
            urlencode($seasonName)
        );

        $html = $this->fetchPage($url);

        if ($html === null) {
            return new ImportResult(success: false, errorMessage: 'Failed to fetch roster page from click-tt.de. URL: ' . $url);
        }

        $rosterEntries = $this->parseRosterHtml($html);

        if (empty($rosterEntries)) {
            return new ImportResult(success: true, errorMessage: 'No roster entries found on the page.');
        }

        $created = 0;
        $unchanged = 0;
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($rosterEntries as $entry) {
            $teamNumber = $entry['team_number'];
            $position = $entry['position'];
            $lastName = $entry['last_name'];
            $firstName = $entry['first_name'];

            // Find the team
            $teamId = $this->findTeam($seasonId, $teamNumber);

            if ($teamId === null) {
                // Team doesn't exist yet — skip (import teams first)
                continue;
            }

            // Match or create the player
            $playerId = $this->matchOrCreatePlayer($firstName, $lastName, $now);

            // Record the player-to-club-ID association
            $this->ensurePlayerClubAssociation($playerId, $this->clubIdConfigId);

            // Create roster entry if it doesn't exist
            if (!$this->rosterEntryExists($playerId, $teamId, $halfSeasonId)) {
                $this->createRosterEntry($playerId, $teamId, $halfSeasonId, $position, $now);
                $created++;
            } else {
                $unchanged++;
            }
        }

        return new ImportResult(created: $created, updated: 0, unchanged: $unchanged);
    }

    /**
     * Parse roster data from the clubPools HTML page.
     *
     * Each player row has a position like "1.3" meaning team 1, position 3.
     * Player names are in "Last, First" format.
     *
     * @return array<array{team_number: int, position: int, last_name: string, first_name: string}>
     */
    private function parseRosterHtml(string $html): array
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($doc);

        $entries = [];
        $rows = $xpath->query("//table[contains(@class,'result-set')]//tr");

        if ($rows === false || $rows->length === 0) {
            return [];
        }

        for ($i = 0; $i < $rows->length; $i++) {
            $row = $rows->item($i);
            $cells = $xpath->query('.//td', $row);

            if ($cells === false || $cells->length < 3) {
                continue; // Skip header rows and section dividers
            }

            // First cell should contain position like "1.1", "2.3", etc.
            $posText = trim($cells->item(0)?->textContent ?? '');

            if (!preg_match('/^(\d+)\.(\d+)$/', $posText, $posMatch)) {
                continue; // Not a player data row
            }

            $teamNumber = (int) $posMatch[1];
            $position = (int) $posMatch[2];

            // Name is in cell 1 (teamPortrait) or cell 2 (clubPools — older format)
            // Try cell 1 first, then cell 2
            $nameText = '';
            for ($cellIdx = 1; $cellIdx <= min(3, $cells->length - 1); $cellIdx++) {
                $candidate = trim($cells->item($cellIdx)?->textContent ?? '');
                // Remove non-breaking spaces (U+00A0)
                $candidate = str_replace("\xC2\xA0", '', $candidate);
                $candidate = trim($candidate);
                // Check if it looks like a name (contains a comma for "Last, First" format)
                if ($candidate !== '' && str_contains($candidate, ',') && !is_numeric($candidate)) {
                    $nameText = $candidate;
                    break;
                }
            }

            if ($nameText === '') {
                continue;
            }

            // Split "Last, First" 
            if (str_contains($nameText, ',')) {
                $parts = explode(',', $nameText, 2);
                $lastName = trim($parts[0]);
                $firstName = trim($parts[1] ?? '');
            } else {
                $nameParts = explode(' ', $nameText);
                $lastName = array_pop($nameParts);
                $firstName = implode(' ', $nameParts);
            }

            if ($lastName === '') {
                continue;
            }

            $entries[] = [
                'team_number' => $teamNumber,
                'position' => $position,
                'last_name' => mb_substr($lastName, 0, 50),
                'first_name' => mb_substr($firstName, 0, 50),
            ];
        }

        return $entries;
    }

    /**
     * Get the half number (1 or 2) for a given half_season_id.
     */
    private function getHalfNumber(int $halfSeasonId): int
    {
        $query = $this->db->getQuery(true)
            ->select('half')
            ->from($this->db->quoteName('#__ttclub_half_seasons'))
            ->where('id = ' . $halfSeasonId);
        $this->db->setQuery($query);
        return (int) ($this->db->loadResult() ?? 1);
    }

    /**
     * Import schedule/meetings for the club.
     */
    public function importSchedule(int $seasonStartYear, int $seasonId): ImportResult
    {
        // Try the clubMeetings page first
        $url = $this->buildUrl('clubMeetings');
        $html = $this->fetchPage($url);

        if ($html === null) {
            return new ImportResult(success: false, errorMessage: 'Failed to fetch schedule from click-tt.de.');
        }

        $matches = $this->parseScheduleHtml($html, $seasonId);

        if (empty($matches)) {
            return new ImportResult(success: true, unchanged: 0, errorMessage: 'No schedule entries found. The schedule page may require a different URL.');
        }

        $created = 0;
        $unchanged = 0;
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($matches as $match) {
            $teamId = $this->findTeam($seasonId, $match['team_number']);

            if ($teamId === null) {
                continue;
            }

            if (!$this->scheduleEntryExists($teamId, $seasonId, $match['date'], $match['opponent'])) {
                $this->createScheduleEntry($teamId, $seasonId, $match, $now);
                $created++;
            } else {
                $unchanged++;
            }
        }

        return new ImportResult(created: $created, updated: 0, unchanged: $unchanged);
    }

    // ---------------------------------------------------------------
    // HTML Parsing
    // ---------------------------------------------------------------

    /**
     * Parse teams from the clubTeams HTML page.
     *
     * Structure:
     * - Rows with class containing championship headers (spans multiple sections)
     * - Column headers row
     * - Data rows with: team name, league, contact, rank, points
     *
     * Each team entry includes a 'championship' field extracted from the section header,
     * which is used to detect cup competitions (e.g., "Pokal Bezirk Karlsruhe 2025/26").
     */
    private function parseTeamsHtml(string $html): array
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($doc);

        $teams = [];
        $currentAgeClass = 'Erwachsene';
        $currentChampionship = '';

        // Find all rows in result-set tables
        $rows = $xpath->query("//table[contains(@class,'result-set')]//tr");

        if ($rows === false || $rows->length === 0) {
            return [];
        }

        for ($i = 0; $i < $rows->length; $i++) {
            $row = $rows->item($i);
            $cells = $xpath->query('.//td', $row);
            $ths = $xpath->query('.//th', $row);

            // Skip header rows (th elements)
            if ($ths !== false && $ths->length > 0) {
                // Check if it's a championship section header (single cell spanning)
                $headerText = trim($row->textContent);
                if (str_contains($headerText, 'Mannschaft') && str_contains($headerText, 'Liga')) {
                    continue; // Column header row
                }
                // Championship group header — capture the championship name
                if (!empty($headerText) && !str_contains($headerText, 'Mannschaft')) {
                    $currentChampionship = $headerText;
                    continue;
                }
                continue;
            }

            if ($cells === false || $cells->length < 2) {
                // Could be a section header in a td with colspan
                $text = trim($row->textContent);
                if (!empty($text) && $cells !== false && $cells->length === 1) {
                    // This might be a group header — capture as championship name
                    // e.g., "Pokal Bezirk Karlsruhe 2025/26"
                    $currentChampionship = $text;
                    continue;
                }
                continue;
            }

            // Data row: team name | league | contact | rank | points
            $teamName = trim($cells->item(0)?->textContent ?? '');
            $league = trim($cells->item(1)?->textContent ?? '');
            $contact = $cells->length >= 3 ? trim($cells->item(2)?->textContent ?? '') : '';

            if ($teamName === '' || $league === '') {
                continue;
            }

            // Extract team number from name
            $teamNumber = $this->extractTeamNumber($teamName);

            // Determine age class from team name
            if (str_contains(strtolower($teamName), 'jugend') || str_contains(strtolower($teamName), 'jungen') || str_contains(strtolower($teamName), 'mädchen')) {
                $currentAgeClass = $teamName;
            } else {
                $currentAgeClass = 'Erwachsene';
            }

            $teams[] = [
                'name' => $teamName,
                'number' => $teamNumber,
                'league' => $league,
                'age_class' => $currentAgeClass,
                'contact' => $contact,
                'championship' => $currentChampionship,
            ];
        }

        return $teams;
    }

    /**
     * Parse schedule entries from HTML (basic implementation).
     */
    private function parseScheduleHtml(string $html, int $seasonId): array
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($doc);

        $matches = [];
        $rows = $xpath->query("//table[contains(@class,'result-set')]//tr[td]");

        if ($rows === false) {
            return [];
        }

        for ($i = 0; $i < $rows->length; $i++) {
            $row = $rows->item($i);
            $cells = $xpath->query('.//td', $row);

            if ($cells === false || $cells->length < 5) {
                continue;
            }

            // Typical schedule columns: Date | Time | Home | Away | Result
            $dateText = trim($cells->item(0)?->textContent ?? '');
            $timeText = trim($cells->item(1)?->textContent ?? '');
            $homeTeam = trim($cells->item(2)?->textContent ?? '');
            $awayTeam = trim($cells->item(3)?->textContent ?? '');
            $result = trim($cells->item(4)?->textContent ?? '');

            $matchDate = $this->parseDate($dateText);
            if ($matchDate === null) {
                continue;
            }

            // Determine if we're home or away
            $isHome = $this->isOwnClub($homeTeam);
            $opponent = $isHome ? $awayTeam : $homeTeam;
            $teamNumber = $this->extractTeamNumber($isHome ? $homeTeam : $awayTeam);

            $matches[] = [
                'team_number' => $teamNumber,
                'date' => $matchDate,
                'time' => $this->parseTime($timeText),
                'opponent' => $opponent,
                'venue' => '',
                'home_away' => $isHome ? 1 : 2,
                'result' => $result !== '' ? $result : null,
            ];
        }

        return $matches;
    }

    // ---------------------------------------------------------------
    // URL building
    // ---------------------------------------------------------------

    private function buildUrl(string $action): string
    {
        $base = sprintf(self::BASE_URL_TEMPLATE, strtolower($this->federation));
        return $base . $action . '?club=' . $this->clubId;
    }

    private function buildChampionship(int $startYear): string
    {
        $startShort = $startYear % 100;
        $endShort = ($startYear + 1) % 100;
        return sprintf('%s %02d/%02d', $this->federation, $startShort, $endShort);
    }

    // ---------------------------------------------------------------
    // HTTP
    // ---------------------------------------------------------------

    protected function fetchPage(string $url): ?string
    {
        // Use cURL if available (more reliable)
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Mozilla/5.0 (compatible; TtclubImport/1.0)',
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
                'method' => 'GET',
                'timeout' => 15,
                'header' => "User-Agent: Mozilla/5.0 (compatible; TtclubImport/1.0)\r\nAccept: text/html\r\n",
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        if (isset($http_response_header) && is_array($http_response_header)) {
            $statusLine = $http_response_header[0] ?? '';
            if (preg_match('/\s(\d{3})\s/', $statusLine, $m) && (int) $m[1] >= 400) {
                return null;
            }
        }

        return $response;
    }

    // ---------------------------------------------------------------
    // Utility
    // ---------------------------------------------------------------

    private function extractTeamNumber(string $teamName): int
    {
        // Roman numerals at end
        $roman = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5,
                  'VI' => 6, 'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10];

        if (preg_match('/\b([IVX]+)\s*$/', $teamName, $m) && isset($roman[$m[1]])) {
            return $roman[$m[1]];
        }

        // Arabic number at end
        if (preg_match('/\b(\d+)\s*$/', $teamName, $m)) {
            return (int) $m[1];
        }

        return 1;
    }

    private function parseDate(string $text): ?string
    {
        if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})/', $text, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $text, $m)) {
            return $m[0];
        }
        return null;
    }

    private function parseTime(string $text): ?string
    {
        if (preg_match('/(\d{1,2}):(\d{2})/', $text, $m)) {
            return str_pad($m[1], 2, '0', STR_PAD_LEFT) . ':' . $m[2] . ':00';
        }
        return null;
    }

    private function isOwnClub(string $teamName): bool
    {
        // Simple heuristic: check if the name contains our club name fragments
        $lower = strtolower($teamName);
        return str_contains($lower, 'wöschbach') || str_contains($lower, 'woeschbach');
    }

    private function splitName(string $fullName): array
    {
        $fullName = trim($fullName);
        if (str_contains($fullName, ',')) {
            $parts = explode(',', $fullName, 2);
            return ['last_name' => trim($parts[0]), 'first_name' => trim($parts[1] ?? '')];
        }
        $parts = explode(' ', $fullName);
        if (count($parts) >= 2) {
            $lastName = array_pop($parts);
            return ['first_name' => implode(' ', $parts), 'last_name' => $lastName];
        }
        return ['first_name' => '', 'last_name' => $fullName];
    }

    // ---------------------------------------------------------------
    // Database operations
    // ---------------------------------------------------------------

    private function matchOrCreateLeague(string $name, string $now): int
    {
        if ($name === '') $name = 'Unbekannt';

        $query = $this->db->getQuery(true)
            ->select('id')->from($this->db->quoteName('#__ttclub_leagues'))
            ->where('LOWER(name) = LOWER(' . $this->db->quote($name) . ')');
        $this->db->setQuery($query);
        $id = $this->db->loadResult();

        if ($id) return (int) $id;

        $rec = (object) ['name' => $name, 'published' => 1, 'created' => $now, 'modified' => $now, 'created_by' => 0, 'modified_by' => 0];
        $this->db->insertObject('#__ttclub_leagues', $rec, 'id');
        return (int) $rec->id;
    }

    private function matchOrCreateAgeClass(string $name, string $now): int
    {
        if ($name === '') $name = 'Erwachsene';

        $query = $this->db->getQuery(true)
            ->select('id')->from($this->db->quoteName('#__ttclub_age_classes'))
            ->where('LOWER(name) = LOWER(' . $this->db->quote($name) . ')');
        $this->db->setQuery($query);
        $id = $this->db->loadResult();

        if ($id) return (int) $id;

        $rec = (object) ['name' => $name, 'max_age' => null, 'published' => 1, 'created' => $now, 'modified' => $now];
        $this->db->insertObject('#__ttclub_age_classes', $rec, 'id');
        return (int) $rec->id;
    }

    private function matchOrCreatePlayer(string $firstName, string $lastName, string $now): int
    {
        $query = $this->db->getQuery(true)
            ->select('id')->from($this->db->quoteName('#__ttclub_players'))
            ->where('LOWER(first_name) = LOWER(' . $this->db->quote($firstName) . ')')
            ->where('LOWER(last_name) = LOWER(' . $this->db->quote($lastName) . ')');
        $this->db->setQuery($query);
        $id = $this->db->loadResult();

        if ($id) return (int) $id;

        $rec = (object) ['first_name' => $firstName, 'last_name' => $lastName, 'published' => 1, 'created' => $now, 'modified' => $now, 'created_by' => 0, 'modified_by' => 0];
        $this->db->insertObject('#__ttclub_players', $rec, 'id');
        return (int) $rec->id;
    }

    /**
     * Ensure a player-to-club-ID association exists in the junction table.
     *
     * Inserts the association if it does not already exist. Duplicate-key
     * conditions are treated as a successful no-op. Other failures are
     * logged but do not abort the import.
     */
    private function ensurePlayerClubAssociation(int $playerId, ?int $clubIdConfigId): void
    {
        if ($clubIdConfigId === null || $clubIdConfigId <= 0) {
            return;
        }

        try {
            $query = $this->db->getQuery(true)
                ->select('id')
                ->from($this->db->quoteName('#__ttclub_player_club_ids'))
                ->where($this->db->quoteName('player_id') . ' = ' . $playerId)
                ->where($this->db->quoteName('club_id') . ' = ' . $clubIdConfigId);
            $this->db->setQuery($query);

            if ($this->db->loadResult() !== null) {
                return; // Already exists
            }

            $record = (object) [
                'player_id' => $playerId,
                'club_id' => $clubIdConfigId,
            ];
            $this->db->insertObject('#__ttclub_player_club_ids', $record);
        } catch (\Exception $e) {
            // Log but don't abort — duplicate key or other errors are non-fatal
            $this->logDebug('ensurePlayerClubAssociation failed: ' . $e->getMessage());
        }
    }

    private function findTeam(int $seasonId, int $teamNumber, ?int $ageClassId = null): ?int
    {
        $query = $this->db->getQuery(true)
            ->select('id')->from($this->db->quoteName('#__ttclub_teams'))
            ->where('season_id = ' . $seasonId)
            ->where('team_number = ' . $teamNumber);

        // Scope by club_id_source to distinguish teams from different club registrations
        if ($this->clubIdConfigId !== null && $this->clubIdConfigId > 0) {
            $query->where('club_id_source = ' . (int) $this->clubIdConfigId);
        }

        // Scope by age_class_id to distinguish teams from different age classes
        if ($ageClassId !== null && $ageClassId > 0) {
            $query->where('age_class_id = ' . (int) $ageClassId);
        }

        $this->db->setQuery($query);
        $id = $this->db->loadResult();
        return $id ? (int) $id : null;
    }

    private function createTeam(int $seasonId, int $leagueId, int $ageClassId, int $teamNumber, string $now): void
    {
        $rec = (object) [
            'season_id' => $seasonId, 'league_id' => $leagueId, 'age_class_id' => $ageClassId,
            'club_id_source' => $this->clubIdConfigId,
            'team_number' => $teamNumber, 'published' => 1, 'created' => $now, 'modified' => $now,
            'created_by' => 0, 'modified_by' => 0,
        ];
        $this->db->insertObject('#__ttclub_teams', $rec);
    }

    private function rosterEntryExists(int $playerId, int $teamId, int $halfSeasonId): bool
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')->from($this->db->quoteName('#__ttclub_rosters'))
            ->where('player_id = ' . $playerId)
            ->where('team_id = ' . $teamId)
            ->where('half_season_id = ' . $halfSeasonId);
        $this->db->setQuery($query);
        return (int) $this->db->loadResult() > 0;
    }

    private function createRosterEntry(int $playerId, int $teamId, int $halfSeasonId, ?int $position, string $now): void
    {
        $rec = (object) ['player_id' => $playerId, 'team_id' => $teamId, 'half_season_id' => $halfSeasonId, 'position' => $position, 'created' => $now];
        try {
            $this->db->insertObject('#__ttclub_rosters', $rec);
        } catch (\Exception $e) {
            $this->logDebug("createRosterEntry FAILED: player=$playerId team=$teamId hs=$halfSeasonId pos=$position error=" . $e->getMessage());
        }
    }

    private function scheduleEntryExists(int $teamId, int $seasonId, string $date, string $opponent): bool
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')->from($this->db->quoteName('#__ttclub_schedules'))
            ->where('team_id = ' . $teamId)
            ->where('season_id = ' . $seasonId)
            ->where('match_date = ' . $this->db->quote($date))
            ->where('LOWER(opponent) = LOWER(' . $this->db->quote($opponent) . ')');
        $this->db->setQuery($query);
        return (int) $this->db->loadResult() > 0;
    }

    private function createScheduleEntry(int $teamId, int $seasonId, array $match, string $now): void
    {
        $rec = (object) [
            'team_id' => $teamId, 'season_id' => $seasonId,
            'match_date' => $match['date'], 'match_time' => $match['time'],
            'opponent' => mb_substr($match['opponent'], 0, 150),
            'venue' => mb_substr($match['venue'] ?? '', 0, 200),
            'home_away' => $match['home_away'], 'result' => $match['result'],
            'published' => 1, 'created' => $now, 'modified' => $now,
            'created_by' => 0, 'modified_by' => 0,
        ];
        $this->db->insertObject('#__ttclub_schedules', $rec);
    }

    /**
     * Create from component params.
     */
    public static function fromParams(\Joomla\Registry\Registry $params, DatabaseInterface $db): ?self
    {
        $federation = $params->get('clicktt_federation', '');
        $clubId = (int) $params->get('mytischtennis_club_id', 0);

        if ($federation === '' || $clubId === 0) {
            return null;
        }

        return new self($db, $federation, $clubId);
    }

    /**
     * Create instances for all configured club IDs.
     *
     * @return array<array{service: self, label: string}>
     */
    public static function allFromParams(\Joomla\Registry\Registry $params, DatabaseInterface $db): array
    {
        // Prefer loading from the #__ttclub_club_ids table
        $instances = self::allFromDatabase($db);

        if (!empty($instances)) {
            return $instances;
        }

        // Parse from config param: format is "FEDERATION|CLUB_ID|LABEL" per line
        $clubIdsRaw = $params->get('clicktt_club_ids', '');
        $lines = array_filter(array_map('trim', explode("\n", (string) $clubIdsRaw)));

        $instances = [];

        foreach ($lines as $line) {
            $parts = explode('|', $line);

            if (count($parts) >= 2) {
                // New format: FEDERATION|CLUB_ID|LABEL
                $federation = trim($parts[0]);
                $id = (int) trim($parts[1]);
                $label = trim($parts[2] ?? 'Club ' . $id);
            } else {
                // Legacy format: CLUB_ID (federation from global param)
                $id = (int) trim($parts[0]);
                $federation = $params->get('clicktt_federation', '');
                $label = 'Club ' . $id;
            }

            if ($id > 0 && $federation !== '') {
                // Auto-create DB entry so we have a proper clubIdConfigId for associations
                $configId = self::ensureClubIdEntry($db, $federation, $id, $label);
                $instances[] = [
                    'service' => new self($db, $federation, $id, $configId),
                    'label' => $label,
                    'club_id' => $id,
                    'config_id' => $configId,
                ];
            }
        }

        return $instances;
    }

    /**
     * Ensure a club_ids DB entry exists for the given federation + click_tt_club_id.
     * Returns the config ID (primary key) of the entry.
     */
    private static function ensureClubIdEntry(DatabaseInterface $db, string $federation, int $clickTtClubId, string $label): int
    {
        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__ttclub_club_ids'))
            ->where($db->quoteName('click_tt_club_id') . ' = ' . $clickTtClubId)
            ->where($db->quoteName('federation') . ' = ' . $db->quote($federation));
        $db->setQuery($query);
        $existing = $db->loadResult();

        if ($existing) {
            return (int) $existing;
        }

        $record = (object) [
            'click_tt_club_id' => $clickTtClubId,
            'federation' => $federation,
            'label' => $label,
            'club_name' => '',
            'legacy_club_id' => null,
            'ordering' => 0,
        ];
        $db->insertObject('#__ttclub_club_ids', $record, 'id');

        return (int) $record->id;
    }

    /**
     * Create instances for all configured club IDs from the #__ttclub_club_ids database table.
     *
     * Each entry has its own federation field, so no global federation setting is needed.
     * The entry's primary key (id) is passed as clubIdConfigId to track the source
     * of imported teams (team.club_id_source).
     *
     * @return array<array{service: self, label: string, club_id: int, config_id: int}>
     */
    public static function allFromDatabase(DatabaseInterface $db): array
    {
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__ttclub_club_ids'))
            ->order($db->quoteName('ordering') . ' ASC');

        $db->setQuery($query);
        $entries = $db->loadObjectList();

        if (empty($entries)) {
            return [];
        }

        $instances = [];

        foreach ($entries as $entry) {
            $federation = trim($entry->federation ?? '');
            $clubId = (int) ($entry->click_tt_club_id ?? 0);
            $configId = (int) $entry->id;
            $label = trim($entry->label ?? '');

            if ($federation === '' || $clubId <= 0) {
                continue;
            }

            $instances[] = [
                'service' => new self($db, $federation, $clubId, $configId),
                'label' => $label ?: 'Club ' . $clubId,
                'club_id' => $clubId,
                'config_id' => $configId,
            ];
        }

        return $instances;
    }

    /**
     * Discover all available seasons from the clubPools overview page and import everything.
     *
     * Entry point: clubPools?club={clubId}
     * Discovers season links, creates seasons + half-seasons, then imports rosters for each.
     * Creates separate parallel seasons with label "Pokal" for cup competitions
     * identified by championship name via ClickTtParser::isCupCompetition().
     *
     * @return ImportResult Summary of all created records
     */
    public function discoverAndImportAll(): ImportResult
    {
        $url = sprintf(self::BASE_URL_TEMPLATE, strtolower($this->federation)) . 'clubPools?&club=' . $this->clubId;
        $html = $this->fetchPage($url);

        if ($html === null) {
            return new ImportResult(success: false, errorMessage: 'Failed to fetch clubPools overview. URL: ' . $url);
        }

        // Parse all clubPools links from the page
        $doc = new \DOMDocument();
        @$doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($doc);

        $links = $xpath->query("//a[contains(@href,'clubPools')]");

        if ($links === false || $links->length === 0) {
            return new ImportResult(success: true, errorMessage: 'No season links found on the overview page.');
        }

        // Collect unique season+half combinations
        $rosterPages = [];
        $baseHost = sprintf('https://%s.click-tt.de', strtolower($this->federation));
        $parser = new ClickTtParser();

        for ($i = 0; $i < $links->length; $i++) {
            $href = $links->item($i)->getAttribute('href');

            if (!preg_match('/displayTyp=(vorrunde|hinrunde|rueckrunde)/', $href, $dtMatch)) {
                continue;
            }
            if (!preg_match('/seasonName=([^&]+)/', $href, $snMatch)) {
                continue;
            }

            $displayTyp = $dtMatch[1];
            $seasonName = urldecode($snMatch[1]); // e.g., "2025/26"

            // Extract contestType (age class)
            $contestType = 'Erwachsene';
            if (preg_match('/contestType=([^&]+)/', $href, $ctMatch)) {
                $contestType = urldecode($ctMatch[1]);
            }

            // Extract championship name from the link URL (if present)
            $championshipName = '';
            if (preg_match('/championship=([^&]+)/', $href, $chMatch)) {
                $championshipName = urldecode($chMatch[1]);
            }

            // If no championship in URL, try to get it from the link text or parent context
            if ($championshipName === '') {
                $linkText = trim($links->item($i)->textContent ?? '');
                // The link text on the overview page may itself contain championship info
                if ($linkText !== '' && $parser->isCupCompetition($linkText)) {
                    $championshipName = $linkText;
                }
            }

            // Determine season label: "Pokal" for cup competitions, empty for league
            $seasonLabel = '';
            if ($championshipName !== '' && $parser->isCupCompetition($championshipName)) {
                $seasonLabel = 'Pokal';
            }

            // Parse start year from season name
            if (!preg_match('/^(\d{4})\//', $seasonName, $yrMatch)) {
                continue;
            }

            $startYear = (int) $yrMatch[1];
            $half = ($displayTyp === 'vorrunde' || $displayTyp === 'hinrunde') ? 1 : 2;
            $key = $startYear . '_' . $half . '_' . $contestType . '_' . $seasonLabel;

            if (!isset($rosterPages[$key])) {
                $fullUrl = str_starts_with($href, 'http') ? $href : $baseHost . $href;
                $rosterPages[$key] = [
                    'url' => $fullUrl,
                    'start_year' => $startYear,
                    'half' => $half,
                    'age_class' => $contestType,
                    'season_label' => $seasonLabel,
                ];
            }
        }

        if (empty($rosterPages)) {
            return new ImportResult(success: true, errorMessage: 'No valid roster page links found.');
        }

        $totalCreated = 0;
        $totalUnchanged = 0;
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        // Collect all seasons we process for post-import league resolution
        $processedSeasons = [];

        // Process each season/half combination
        foreach ($rosterPages as $page) {
            $startYear = $page['start_year'];
            $half = $page['half'];
            $ageClassName = $page['age_class'];
            $seasonLabel = $page['season_label'];

            // Ensure season exists (with label for cup competitions)
            $seasonId = $this->ensureSeasonExists($startYear, $now, $seasonLabel);

            // Ensure half-season exists
            $halfSeasonId = $this->ensureHalfSeasonExists($seasonId, $half);

            // Ensure age class exists
            $ageClassId = $this->matchOrCreateAgeClass($ageClassName, $now);

            // Track for post-import league resolution
            $processedSeasons[$startYear] = $seasonId;

            // Fetch and parse the roster page (skip league lookup for now — resolved later via clubMeetings)
            $rosterHtml = $this->fetchPage($page['url']);

            if ($rosterHtml === null) {
                continue;
            }

            $entries = $this->parseRosterHtml($rosterHtml);

            foreach ($entries as $entry) {
                $teamNumber = $entry['team_number'];
                $positionInTeam = $entry['position']; // Y from "X.Y"

                // Ensure team exists with placeholder league (will be resolved after)
                $teamId = $this->findTeam($seasonId, $teamNumber, $ageClassId);
                if ($teamId === null) {
                    $leagueId = $this->matchOrCreateLeague('Unbekannt', $now);
                    $this->createTeam($seasonId, $leagueId, $ageClassId, $teamNumber, $now);
                    $teamId = $this->findTeam($seasonId, $teamNumber, $ageClassId);
                }

                if ($teamId === null) {
                    continue;
                }

                // Match or create player
                $playerId = $this->matchOrCreatePlayer($entry['first_name'], $entry['last_name'], $now);

                // Record the player-to-club-ID association
                $this->ensurePlayerClubAssociation($playerId, $this->clubIdConfigId);

                // Store position as composite: X * 100 + Y (e.g., "2.3" → 203, "3.1" → 301)
                // This ensures natural ordering: team's own players first, then substitutes
                $compositePosition = $teamNumber * 100 + ($positionInTeam ?? 0);

                // Create roster entry for the player's primary team
                if (!$this->rosterEntryExists($playerId, $teamId, $halfSeasonId)) {
                    $this->createRosterEntry($playerId, $teamId, $halfSeasonId, $compositePosition, $now);
                    $totalCreated++;
                } else {
                    $totalUnchanged++;
                }
            }
        }

        // Post-import: resolve league names via clubMeetings for each processed season
        foreach ($processedSeasons as $startYear => $seasonId) {
            $this->resolveLeaguesViaClubMeetings($startYear, $seasonId, $now);
        }

        // Fetch teamPortrait pages for accurate roster data (Requirement 6.11)
        $this->importFromTeamPortraits($processedSeasons, $now, $totalCreated, $totalUnchanged);

        return new ImportResult(created: $totalCreated, updated: 0, unchanged: $totalUnchanged);
    }

    /**
     * Fetch team-to-league mapping from the clubPortraitTT page.
     *
     * Parses h2 headings like "Erwachsene II - Kreisliga Staffel 1"
     * to extract team number → league name mapping.
     *
     * @return array<int, string> team_number => league_name
     */
    /**
     * Fetch team-to-league mapping from the clubTeams page.
     *
     * The clubTeams page has championship dropdown(s). We need to:
     * 1. Fetch the default page to get all championship options
     * 2. For each championship option, fetch clubTeams?club={id}&championship={value}
     * 3. Parse groupPage links whose text contains the full league name
     *
     * @return array<int, string> team_number => league_name
     */
    private function fetchTeamLeagues(string $url): array
    {
        // Build the clubTeams base URL
        $baseUrl = sprintf(
            '%sclubTeams?club=%d',
            sprintf(self::BASE_URL_TEMPLATE, strtolower($this->federation)),
            $this->clubId
        );

        $html = $this->fetchPage($baseUrl);

        if ($html === null) {
            return [];
        }

        $doc = new \DOMDocument();
        @$doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($doc);

        $leagues = [];

        // First, extract leagues from the default page (shows first championship)
        $this->extractLeaguesFromHtml($xpath, $leagues);

        // Parse all championship option values from the select dropdowns
        $options = $xpath->query("//select[@name='championship']/option[@value!='0']");
        $championships = [];

        if ($options !== false) {
            for ($i = 0; $i < $options->length; $i++) {
                $value = trim($options->item($i)->getAttribute('value'));
                if ($value !== '' && $value !== '0') {
                    $championships[] = $value;
                }
            }
        }

        // Fetch clubTeams for each championship to find our teams
        foreach ($championships as $championship) {
            $champUrl = $baseUrl . '&championship=' . urlencode($championship);
            $champHtml = $this->fetchPage($champUrl);

            if ($champHtml === null) {
                continue;
            }

            $champDoc = new \DOMDocument();
            @$champDoc->loadHTML($champHtml, LIBXML_NOERROR | LIBXML_NOWARNING);
            $champXpath = new \DOMXPath($champDoc);

            $this->extractLeaguesFromHtml($champXpath, $leagues);
        }

        return $leagues;
    }

    /**
     * Extract team-to-league mappings from a clubTeams page HTML.
     * Looks for groupPage links and associates them with team numbers
     * based on team name links found in the same table.
     *
     * @param \DOMXPath $xpath The XPath instance for the page
     * @param array<int, string> $leagues Reference to leagues array to populate
     */
    private function extractLeaguesFromHtml(\DOMXPath $xpath, array &$leagues): void
    {
        // Find all table rows in result-set tables
        $rows = $xpath->query("//table[contains(@class,'result-set')]//tr");

        if ($rows === false || $rows->length === 0) {
            // Fallback: just get groupPage links
            $links = $xpath->query("//a[contains(@href, 'groupPage')]");
            if ($links !== false) {
                for ($i = 0; $i < $links->length; $i++) {
                    $text = trim($links->item($i)->textContent);
                    if ($text !== '') {
                        // Try to find the team number from context
                        $teamNum = count($leagues) + 1;
                        $leagues[$teamNum] = $text;
                    }
                }
            }
            return;
        }

        // Parse rows looking for team name + league pattern
        for ($i = 0; $i < $rows->length; $i++) {
            $row = $rows->item($i);
            $cells = $xpath->query('.//td', $row);

            if ($cells === false || $cells->length < 2) {
                continue;
            }

            // Look for a groupPage link in this row — its text is the league name
            $groupLinks = $xpath->query('.//a[contains(@href, "groupPage")]', $row);
            if ($groupLinks === false || $groupLinks->length === 0) {
                continue;
            }

            $leagueName = trim($groupLinks->item(0)->textContent);
            if ($leagueName === '') {
                continue;
            }

            // Look for the team name in this row (typically contains our club name)
            $rowText = trim($row->textContent);

            // Extract team number from our club's team name in the row
            if (str_contains(strtolower($rowText), 'wöschbach') || str_contains(strtolower($rowText), 'woeschbach')) {
                $teamNumber = $this->extractTeamNumber($rowText);
                $leagues[$teamNumber] = $leagueName;
            }
        }
    }

    /**
     * Resolve league names for teams in a season by fetching clubMeetings and groupPage.
     *
     * Steps:
     * 1. POST to clubMeetings to get all matches for the season
     * 2. Extract championship_id and group_id from match report links per team
     * 3. Fetch groupPage?championship={}&group={} to get the full league name from the page title
     * 4. Update the team's league_id to the resolved league
     *
     * Only updates teams that currently have the "Unbekannt" league.
     */
    private function resolveLeaguesViaClubMeetings(int $startYear, int $seasonId, string $now): void
    {
        // $this->clubId IS already the click-tt internal club ID (from #__ttclub_club_ids.click_tt_club_id)
        $clickTtClubId = $this->clubId;

        // Build the POST URL and body manually for debugging
        $url = sprintf(
            'https://%s.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubMeetings',
            strtolower($this->federation)
        );
        $postBody = http_build_query([
            'searchTimeRange' => '13-6976',
            'searchType' => '1',
            'searchTimeRangeFrom' => sprintf('01.08.%d', $startYear),
            'searchTimeRangeTo' => sprintf('31.07.%d', $startYear + 1),
            'selectedTeamId' => 'WONoSelectionString',
            'club' => $clickTtClubId,
            'searchMeetings' => 'Suchen',
        ]);

        $this->logDebug("resolveLeagues: POSTing to $url with body: $postBody");

        $html = $this->postPage($url, $postBody);

        if ($html === null) {
            $this->logDebug("resolveLeagues: postPage returned NULL for season $startYear club $clickTtClubId");
            return;
        }

        $this->logDebug("resolveLeagues: Got HTML response, length=" . strlen($html) . " for season $startYear");

        // Check if response contains match data
        if (!str_contains($html, 'clubMeetingReport')) {
            $this->logDebug("resolveLeagues: HTML does not contain clubMeetingReport links. First 500 chars: " . substr($html, 0, 500));
            return;
        }

        $teamMatches = $this->parseClubMeetingsHtml($html, $clickTtClubId);

        if (empty($teamMatches)) {
            $this->logDebug("resolveLeagues: parseClubMeetingsHtml returned empty for season $startYear");
            return;
        }

        $this->logDebug('resolveLeagues: Found ' . count($teamMatches) . ' teams for season ' . $startYear);

        // Collect all championship/group combinations per team number
        // Then pick the best one: VOL (Verbands) > SK (Spielklassen) > Pokal
        $teamGroups = []; // team_number => [{championship_id, group_id, priority}]

        foreach ($teamMatches as $teamInfo) {
            $teamNumber = $teamInfo['team_number'];
            $championshipId = $teamInfo['championship_id'] ?? '';
            $groupId = $teamInfo['group_id'] ?? '';

            if ($championshipId === '' || $groupId === '') {
                continue;
            }

            // Determine priority: VOL (highest level) = 1, SK = 2, Pokal = 3, other = 4
            $decoded = urldecode($championshipId);
            $priority = 4;
            if (str_starts_with($decoded, 'VOL') || str_starts_with($decoded, 'VSK')) {
                $priority = 1;
            } elseif (str_starts_with($decoded, 'SK')) {
                $priority = 2;
            } elseif (str_contains(strtolower($decoded), 'pokal')) {
                $priority = 3;
            }

            if (!isset($teamGroups[$teamNumber]) || $priority < $teamGroups[$teamNumber]['priority']) {
                $teamGroups[$teamNumber] = [
                    'championship_id' => $championshipId,
                    'group_id' => $groupId,
                    'priority' => $priority,
                ];
            }
        }

        $this->logDebug('resolveLeagues: Best groups per team: ' . json_encode(array_map(fn($g) => $g['championship_id'] . ':' . $g['group_id'], $teamGroups)));

        foreach ($teamGroups as $teamNumber => $groupInfo) {
            $championshipId = $groupInfo['championship_id'];
            $groupId = $groupInfo['group_id'];

            $this->logDebug("resolveLeagues: Team $teamNumber - using championship=$championshipId group=$groupId (priority={$groupInfo['priority']})");

            // Find the team in our database
            $teamId = $this->findTeam($seasonId, $teamNumber);
            if ($teamId === null) {
                $this->logDebug("resolveLeagues: Team $teamNumber not found in DB for season $seasonId");
                continue;
            }

            // Check if this team still has "Unbekannt" league
            $query = $this->db->getQuery(true)
                ->select('t.league_id, l.name as league_name')
                ->from($this->db->quoteName('#__ttclub_teams', 't'))
                ->join('LEFT', $this->db->quoteName('#__ttclub_leagues', 'l') . ' ON l.id = t.league_id')
                ->where('t.id = ' . $teamId);
            $this->db->setQuery($query);
            $team = $this->db->loadObject();

            if ($team === null || strtolower($team->league_name ?? '') !== 'unbekannt') {
                continue;
            }

            // Fetch the full league name from groupPage
            $leagueName = $this->fetchLeagueNameFromGroup($championshipId, $groupId);
            $this->logDebug("resolveLeagues: Team $teamNumber - groupPage returned: '$leagueName'");

            if ($leagueName === '' || strtolower($leagueName) === 'unbekannt') {
                continue;
            }

            // Update the team's league and store championship/group IDs
            $leagueId = $this->matchOrCreateLeague($leagueName, $now);
            $this->db->setQuery(
                $this->db->getQuery(true)
                    ->update($this->db->quoteName('#__ttclub_teams'))
                    ->set($this->db->quoteName('league_id') . ' = ' . $leagueId)
                    ->set($this->db->quoteName('championship_id') . ' = ' . $this->db->quote(urldecode($championshipId)))
                    ->set($this->db->quoteName('group_id') . ' = ' . $this->db->quote($groupId))
                    ->set($this->db->quoteName('modified') . ' = ' . $this->db->quote($now))
                    ->where('id = ' . $teamId)
            );
            $this->db->execute();
        }
    }

    /**
     * Import roster data from teamPortrait pages (Requirement 6.11).
     *
     * For each team that has championship_id and group_id set:
     * 1. Fetch the groupPage and extract teamtable IDs matching our club's teams
     * 2. Store the teamtable_id on the team record
     * 3. Fetch teamPortrait pages (hinrunde + rueckrunde) for each team
     * 4. Parse player listings and add missing roster entries
     */
    private function importFromTeamPortraits(array $processedSeasons, string $now, int &$totalCreated, int &$totalUnchanged): void
    {
        // Get the club name for matching team links on the groupPage
        $clubName = $this->getClubName();

        if ($clubName === '') {
            $this->logDebug('importFromTeamPortraits: No club_name set in club_ids table, skipping teamPortrait import.');
            return;
        }

        // Find all teams with championship_id and group_id set
        $query = $this->db->getQuery(true)
            ->select(['id', 'team_number', 'championship_id', 'group_id', 'season_id', 'age_class_id', 'teamtable_id'])
            ->from($this->db->quoteName('#__ttclub_teams'))
            ->where('club_id_source = ' . (int) $this->clubIdConfigId)
            ->where($this->db->quoteName('championship_id') . ' IS NOT NULL')
            ->where($this->db->quoteName('championship_id') . ' != ' . $this->db->quote(''))
            ->where($this->db->quoteName('group_id') . ' IS NOT NULL')
            ->where($this->db->quoteName('group_id') . ' != ' . $this->db->quote(''));
        $this->db->setQuery($query);
        $teams = $this->db->loadObjectList();

        if (empty($teams)) {
            $this->logDebug('importFromTeamPortraits: No teams with championship_id and group_id found.');
            return;
        }

        $this->logDebug('importFromTeamPortraits: Found ' . count($teams) . ' teams with group info.');

        // Group teams by championship_id + group_id to avoid fetching the same groupPage multiple times
        $groupPages = []; // key: "championship_id|group_id" => [{team}]
        foreach ($teams as $team) {
            $key = $team->championship_id . '|' . $team->group_id;
            $groupPages[$key][] = $team;
        }

        // For each unique group, fetch the groupPage and extract teamtable IDs
        foreach ($groupPages as $key => $groupTeams) {
            $championshipId = $groupTeams[0]->championship_id;
            $groupId = $groupTeams[0]->group_id;

            // Extract teamtable IDs from the groupPage ranking table
            $teamtableMap = $this->extractTeamtableIdsFromGroup($championshipId, $groupId, $clubName);

            if (empty($teamtableMap)) {
                $this->logDebug("importFromTeamPortraits: No teamtable IDs found for championship=$championshipId group=$groupId");
                continue;
            }

            $this->logDebug("importFromTeamPortraits: Found teamtable IDs: " . json_encode($teamtableMap));

            // Match teamtable IDs to our teams by team number
            foreach ($groupTeams as $team) {
                $teamNumber = (int) $team->team_number;
                $teamtableId = $teamtableMap[$teamNumber] ?? null;

                if ($teamtableId === null) {
                    $this->logDebug("importFromTeamPortraits: No teamtable match for team_number=$teamNumber in group $groupId");
                    continue;
                }

                // Store the teamtable_id on the team record
                if (($team->teamtable_id ?? '') !== $teamtableId) {
                    $this->db->setQuery(
                        $this->db->getQuery(true)
                            ->update($this->db->quoteName('#__ttclub_teams'))
                            ->set($this->db->quoteName('teamtable_id') . ' = ' . $this->db->quote($teamtableId))
                            ->set($this->db->quoteName('modified') . ' = ' . $this->db->quote($now))
                            ->where('id = ' . (int) $team->id)
                    );
                    $this->db->execute();
                }

                // Get half-season IDs for this team's season
                $hsQuery = $this->db->getQuery(true)
                    ->select(['id', 'half'])
                    ->from($this->db->quoteName('#__ttclub_half_seasons'))
                    ->where('season_id = ' . (int) $team->season_id);
                $this->db->setQuery($hsQuery);
                $halfSeasons = $this->db->loadObjectList();

                // Fetch teamPortrait for both hinrunde and rueckrunde
                foreach ($halfSeasons as $hs) {
                    $halfSeasonId = (int) $hs->id;
                    $half = (int) $hs->half;
                    $pageState = ($half === 1) ? 'vorrunde' : 'rueckrunde';

                    $portraitUrl = sprintf(
                        'https://%s.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/teamPortrait?teamtable=%s&pageState=%s&championship=%s&group=%s',
                        strtolower($this->federation),
                        urlencode($teamtableId),
                        $pageState,
                        urlencode($championshipId),
                        urlencode($groupId)
                    );

                    $portraitHtml = $this->fetchPage($portraitUrl);

                    if ($portraitHtml === null) {
                        $this->logDebug("importFromTeamPortraits: Failed to fetch teamPortrait for teamtable=$teamtableId pageState=$pageState");
                        continue;
                    }

                    // Parse players from the teamPortrait page (same format as clubPools)
                    $entries = $this->parseRosterHtml($portraitHtml);

                    // Debug: log a sample of the raw HTML table structure
                    if (str_contains($teamtableId, '4199381')) {
                        // List ALL tables on the page with their classes
                        $debugDoc = new \DOMDocument();
                        @$debugDoc->loadHTML($portraitHtml, LIBXML_NOERROR | LIBXML_NOWARNING);
                        $debugXpath = new \DOMXPath($debugDoc);
                        $allTables = $debugXpath->query("//table");
                        $tableInfo = [];
                        if ($allTables !== false) {
                            for ($ti = 0; $ti < min(10, $allTables->length); $ti++) {
                                $tbl = $allTables->item($ti);
                                $cls = $tbl->getAttribute('class');
                                $rowCount = $debugXpath->query('.//tr', $tbl)->length;
                                $firstRow = $debugXpath->query('.//tr[td]', $tbl);
                                $firstCellCount = 0;
                                $firstCellTexts = '';
                                if ($firstRow !== false && $firstRow->length > 0) {
                                    $cells = $debugXpath->query('.//td|.//th', $firstRow->item(0));
                                    $firstCellCount = $cells ? $cells->length : 0;
                                    $texts = [];
                                    if ($cells) {
                                        for ($xx = 0; $xx < min(5, $cells->length); $xx++) {
                                            $texts[] = json_encode(mb_substr(trim($cells->item($xx)->textContent), 0, 30));
                                        }
                                    }
                                    $firstCellTexts = implode(',', $texts);
                                }
                                $tableInfo[] = "table{$ti}[class='{$cls}' rows={$rowCount} cols={$firstCellCount} first=({$firstCellTexts})]";
                            }
                        }
                        $this->logDebug("teamPortrait ALL TABLES: " . implode(' | ', $tableInfo));
                    }

                    $this->logDebug("importFromTeamPortraits: teamtable=$teamtableId pageState=$pageState found " . count($entries) . " entries, HTML length=" . strlen($portraitHtml));

                    // Log first 3 entries for debugging
                    $sampleEntries = array_slice($entries, 0, 3);
                    $this->logDebug("importFromTeamPortraits: Sample entries: " . json_encode($sampleEntries, JSON_UNESCAPED_UNICODE));

                    if (empty($entries)) {
                        $this->logDebug("importFromTeamPortraits: No roster entries on teamPortrait for teamtable=$teamtableId pageState=$pageState. First 500 chars: " . substr($portraitHtml, 0, 500));
                        continue;
                    }

                    foreach ($entries as $entry) {
                        $positionInTeam = $entry['position']; // Y from "X.Y"
                        $entryTeamNumber = $entry['team_number']; // X from "X.Y"

                        // Compute composite position for ordering
                        $compositePosition = $entryTeamNumber * 100 + ($positionInTeam ?? 0);

                        // Match or create player
                        $playerId = $this->matchOrCreatePlayer($entry['first_name'], $entry['last_name'], $now);

                        // Record the player-to-club-ID association
                        $this->ensurePlayerClubAssociation($playerId, $this->clubIdConfigId);

                        // Only add if not already in this team's roster for this half-season
                        $exists = $this->rosterEntryExists($playerId, (int) $team->id, $halfSeasonId);
                        if (!$exists) {
                            $this->createRosterEntry($playerId, (int) $team->id, $halfSeasonId, $compositePosition, $now);
                            $totalCreated++;
                        } else {
                            $totalUnchanged++;
                        }
                    }

                    $this->logDebug("importFromTeamPortraits: Processed teamtable=$teamtableId pageState=$pageState for team_id=" . $team->id . " half_season_id=$halfSeasonId: created=$totalCreated unchanged=$totalUnchanged");
                }
            }
        }
    }

    /**
     * Extract teamtable IDs from the groupPage ranking table that match our club's teams.
     *
     * Parses the ranking table looking for teamPortrait links whose text contains the club name.
     * Returns a map of team_number => teamtable_id.
     *
     * @param string $championshipId The championship ID (already decoded, e.g., "SK Bz. KA 25/26")
     * @param string $groupId The group ID (e.g., "499592")
     * @param string $clubName The club name to match against link text
     * @return array<int, string> team_number => teamtable_id
     */
    private function extractTeamtableIdsFromGroup(string $championshipId, string $groupId, string $clubName): array
    {
        $url = sprintf(
            'https://%s.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/groupPage?championship=%s&group=%s',
            strtolower($this->federation),
            urlencode($championshipId),
            urlencode($groupId)
        );

        $html = $this->fetchPage($url);

        if ($html === null) {
            return [];
        }

        $doc = new \DOMDocument();
        @$doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($doc);

        $teamtableMap = [];

        // Find all links that point to teamPortrait with a teamtable parameter
        $links = $xpath->query("//a[contains(@href, 'teamPortrait') and contains(@href, 'teamtable=')]");

        if ($links === false || $links->length === 0) {
            return [];
        }

        $clubNameLower = mb_strtolower($clubName);
        // Also try shorter versions of the club name for flexible matching
        // "TTC Wöschbach 58 e.V." → try matching "TTC Wöschbach" (first two words)
        $clubNameParts = explode(' ', $clubName);
        $clubNameShort = mb_strtolower(implode(' ', array_slice($clubNameParts, 0, min(2, count($clubNameParts)))));

        $debugTexts = [];

        for ($i = 0; $i < $links->length; $i++) {
            $link = $links->item($i);
            $linkText = trim($link->textContent);
            $href = $link->getAttribute('href');
            $linkTextLower = mb_strtolower($linkText);

            $debugTexts[] = $linkText;

            // Check if this link's text contains our club name (full or short)
            $matches = str_contains($linkTextLower, $clubNameLower)
                || str_contains($linkTextLower, $clubNameShort)
                || str_contains($clubNameLower, $linkTextLower);

            if (!$matches) {
                continue;
            }

            // Extract teamtable ID from the href
            if (!preg_match('/teamtable=(\d+)/', $href, $m)) {
                continue;
            }

            $teamtableId = $m[1];
            $teamNumber = $this->extractTeamNumber($linkText);

            $teamtableMap[$teamNumber] = $teamtableId;
        }

        if (empty($teamtableMap)) {
            $this->logDebug("extractTeamtableIdsFromGroup: No match for clubName='$clubName' (short='$clubNameShort'). Link texts found: " . implode(', ', array_slice($debugTexts, 0, 10)));
        }

        return $teamtableMap;
    }

    /**
     * Get the club name from the club_ids table for the current club config.
     */
    private function getClubName(): string
    {
        if ($this->clubIdConfigId === null || $this->clubIdConfigId <= 0) {
            return $this->getClubNameFromParams();
        }

        $query = $this->db->getQuery(true)
            ->select('club_name')
            ->from($this->db->quoteName('#__ttclub_club_ids'))
            ->where('id = ' . (int) $this->clubIdConfigId);
        $this->db->setQuery($query);
        $name = trim((string) ($this->db->loadResult() ?? ''));

        // Fallback to component params if DB entry has no club_name
        if ($name === '') {
            $name = $this->getClubNameFromParams();
        }

        return $name;
    }

    /**
     * Get the club name from component params as fallback.
     */
    private function getClubNameFromParams(): string
    {
        $query = $this->db->getQuery(true)
            ->select('params')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_ttclub'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('component'));
        $this->db->setQuery($query);
        $paramsJson = $this->db->loadResult();
        $params = new \Joomla\Registry\Registry($paramsJson ?: '{}');

        // clicktt_club_name may contain underscores (URL format) — convert to spaces
        $name = str_replace('_', ' ', (string) $params->get('clicktt_club_name', ''));

        return trim($name);
    }

    /**
     * Simple debug logger — writes to import_logs for troubleshooting.
     */
    private function logDebug(string $message): void
    {
        $rec = (object) [
            'import_date' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'import_type' => 'debug',
            'records_created' => 0,
            'records_updated' => 0,
            'records_unchanged' => 0,
            'status' => 1,
            'message' => mb_substr($message, 0, 65535),
        ];
        $this->db->insertObject('#__ttclub_import_logs', $rec);
    }

    /**
     * Ensure a season record exists for the given start year and label. Returns season ID.
     */
    private function ensureSeasonExists(int $startYear, string $now, string $label = ''): int
    {
        $query = $this->db->getQuery(true)
            ->select('id')->from($this->db->quoteName('#__ttclub_seasons'))
            ->where('start_year = ' . $startYear)
            ->where('label = ' . $this->db->quote($label));
        $this->db->setQuery($query);
        $id = $this->db->loadResult();

        if ($id) {
            return (int) $id;
        }

        $rec = (object) ['start_year' => $startYear, 'label' => $label, 'published' => 1, 'created' => $now, 'modified' => $now, 'created_by' => 0, 'modified_by' => 0];
        $this->db->insertObject('#__ttclub_seasons', $rec, 'id');
        return (int) $rec->id;
    }

    /**
     * Ensure a half-season record exists. Returns half_season ID.
     */
    private function ensureHalfSeasonExists(int $seasonId, int $half): int
    {
        $query = $this->db->getQuery(true)
            ->select('id')->from($this->db->quoteName('#__ttclub_half_seasons'))
            ->where('season_id = ' . $seasonId)
            ->where('half = ' . $half);
        $this->db->setQuery($query);
        $id = $this->db->loadResult();

        if ($id) {
            return (int) $id;
        }

        $rec = (object) ['season_id' => $seasonId, 'half' => $half];
        $this->db->insertObject('#__ttclub_half_seasons', $rec, 'id');
        return (int) $rec->id;
    }

    // ---------------------------------------------------------------
    // New Import Flow: clubSearch → clubMeetings
    // ---------------------------------------------------------------

    /**
     * Resolve the click-tt internal club ID from the BaTTV (federation) club ID.
     *
     * Step 1: GET https://{federation}.click-tt.de/cgi-bin/WebObjects/ClickTTVBW.woa/wa/clubSearch?federation={federation}&searchFor={battvId}
     * Step 2: Find the link with text "Spielbetrieb und Ergebnisse"
     * Step 3: Extract club={clubId} from that link's URL
     *
     * @param int $battvId The federation-specific club ID (e.g., 445 for TTC Wöschbach)
     * @return int|null The click-tt internal club ID (e.g., 6658), or null on failure
     */
    public function resolveClickTtClubId(int $battvId): ?int
    {
        $url = sprintf(
            'https://%s.click-tt.de/cgi-bin/WebObjects/ClickTTVBW.woa/wa/clubSearch?federation=%s&searchFor=%d',
            strtolower($this->federation),
            $this->federation,
            $battvId
        );

        $html = $this->fetchPage($url);

        if ($html === null) {
            return null;
        }

        $doc = new \DOMDocument();
        @$doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($doc);

        // Find the link with text "Spielbetrieb und Ergebnisse"
        $links = $xpath->query("//a[contains(text(), 'Spielbetrieb und Ergebnisse')]");

        if ($links === false || $links->length === 0) {
            return null;
        }

        $href = $links->item(0)->getAttribute('href');

        // Extract club= parameter from the URL
        if (preg_match('/club=(\d+)/', $href, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Fetch all matches for the club in a given season via POST to clubMeetings.
     *
     * POST https://{federation}.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubMeetings
     * Body: searchTimeRange=13-6976&searchType=1&searchTimeRangeFrom=01.08.{startYear}&searchTimeRangeTo=31.07.{startYear+1}&selectedTeamId=WONoSelectionString&club={clubId}&searchMeetings=Suchen
     *
     * @param int $clickTtClubId The resolved click-tt internal club ID
     * @param int $startYear Season start year (e.g., 2025 for season 2025/26)
     * @return array|null Parsed match data or null on failure
     */
    public function fetchClubMeetings(int $clickTtClubId, int $startYear): ?array
    {
        $url = sprintf(
            'https://%s.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubMeetings',
            strtolower($this->federation)
        );

        $postBody = http_build_query([
            'searchTimeRange' => '13-6976',
            'searchType' => '1',
            'searchTimeRangeFrom' => sprintf('01.08.%d', $startYear),
            'searchTimeRangeTo' => sprintf('31.07.%d', $startYear + 1),
            'selectedTeamId' => 'WONoSelectionString',
            'club' => $clickTtClubId,
            'searchMeetings' => 'Suchen',
        ]);

        $html = $this->postPage($url, $postBody);

        if ($html === null) {
            return null;
        }

        return $this->parseClubMeetingsHtml($html, $clickTtClubId);
    }

    /**
     * Parse the clubMeetings response HTML.
     *
     * Extracts from the matches table:
     * - League name (e.g., "E Kr Li")
     * - Team name (e.g., "TTC Wöschbach II")
     * - championship_id from match report links (e.g., "SK+Bz.+KA+25%2F26")
     * - group ID from match report links (e.g., "499592")
     * - Match details (date, time, home, away, result)
     *
     * Match report link pattern:
     * https://{federation}.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubMeetingReport?meeting={id}&championship={championship_id}&club={club_id}&group={group_id}
     *
     * @return array<array{league: string, team_name: string, championship_id: string, group_id: string, matches: array}>
     */
    private function parseClubMeetingsHtml(string $html, int $clickTtClubId): array
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($doc);

        $teamData = []; // Keyed by team_name

        // Find the match table rows (skip header rows with <th>)
        $rows = $xpath->query("//table[contains(@class,'result-set')]//tr[td]");

        if ($rows === false || $rows->length === 0) {
            return [];
        }

        for ($i = 0; $i < $rows->length; $i++) {
            $row = $rows->item($i);
            $cells = $xpath->query('.//td', $row);

            if ($cells === false || $cells->length < 8) {
                continue;
            }

            // Column layout from click-tt.de clubMeetings:
            // 0: Tag (day, e.g. "Sa")
            // 1: Datum (date, e.g. "14.09.2025")
            // 2: Zeit (time, e.g. "19:30")
            // 3: Spiellokal (venue)
            // 4: Nr. (match number)
            // 5: Liga (league short name, e.g. "E Kr Li")
            // 6: Heimmannschaft (home team)
            // 7: Gastmannschaft (away team)
            // 8: Spiele/result (e.g. "9:0")

            $dateText = trim($cells->item(1)?->textContent ?? '');
            $timeText = trim($cells->item(2)?->textContent ?? '');
            $venue = trim($cells->item(3)?->textContent ?? '');
            $leagueName = trim($cells->item(5)?->textContent ?? '');
            $homeTeam = trim($cells->item(6)?->textContent ?? '');
            $awayTeam = trim($cells->item(7)?->textContent ?? '');
            $result = $cells->length >= 9 ? trim($cells->item(8)?->textContent ?? '') : '';

            // Extract match report link to get championship_id and group_id
            $reportLinks = $xpath->query('.//a[contains(@href, "clubMeetingReport")]', $row);
            $championshipId = '';
            $groupId = '';

            if ($reportLinks !== false && $reportLinks->length > 0) {
                $reportHref = $reportLinks->item(0)->getAttribute('href');

                if (preg_match('/championship=([^&]+)/', $reportHref, $m)) {
                    $championshipId = $m[1];
                }
                if (preg_match('/group=(\d+)/', $reportHref, $m)) {
                    $groupId = $m[1];
                }
            }

            // Determine which is our team
            $isHome = $this->isOwnClub($homeTeam);
            $ownTeamName = $isHome ? $homeTeam : $awayTeam;
            $opponent = $isHome ? $awayTeam : $homeTeam;

            $matchDate = $this->parseDate($dateText);

            if ($matchDate === null || $ownTeamName === '') {
                continue;
            }

            $matchEntry = [
                'date' => $matchDate,
                'time' => $this->parseTime($timeText),
                'opponent' => mb_substr($opponent, 0, 150),
                'venue' => mb_substr($venue, 0, 200),
                'home_away' => $isHome ? 1 : 2,
                'result' => $result !== '' ? mb_substr($result, 0, 20) : null,
            ];

            // Group by team name
            if (!isset($teamData[$ownTeamName])) {
                $teamData[$ownTeamName] = [
                    'league' => $leagueName,
                    'team_name' => $ownTeamName,
                    'championship_id' => $championshipId,
                    'group_id' => $groupId,
                    'team_number' => $this->extractTeamNumber($ownTeamName),
                    'matches' => [],
                ];
            }

            // Update championship/group if we found them
            if ($championshipId !== '' && $teamData[$ownTeamName]['championship_id'] === '') {
                $teamData[$ownTeamName]['championship_id'] = $championshipId;
            }
            if ($groupId !== '' && $teamData[$ownTeamName]['group_id'] === '') {
                $teamData[$ownTeamName]['group_id'] = $groupId;
            }
            if ($leagueName !== '' && $teamData[$ownTeamName]['league'] === '') {
                $teamData[$ownTeamName]['league'] = $leagueName;
            }

            $teamData[$ownTeamName]['matches'][] = $matchEntry;
        }

        return array_values($teamData);
    }

    /**
     * Full import using the new flow: clubSearch → clubMeetings → rosters.
     *
     * @param int $battvId The BaTTV federation club ID (e.g., 445)
     * @param int $startYear Season start year (e.g., 2025)
     * @return ImportResult
     */
    public function importViaClubMeetings(int $battvId, int $startYear): ImportResult
    {
        // Step 1: Resolve click-tt club ID
        $clickTtClubId = $this->resolveClickTtClubId($battvId);

        if ($clickTtClubId === null) {
            return new ImportResult(
                success: false,
                errorMessage: sprintf(
                    'Could not resolve click-tt club ID for BaTTV ID %d. URL: https://%s.click-tt.de/cgi-bin/WebObjects/ClickTTVBW.woa/wa/clubSearch?federation=%s&searchFor=%d',
                    $battvId, strtolower($this->federation), $this->federation, $battvId
                )
            );
        }

        // Step 2: Fetch all matches for the season
        $teamMatches = $this->fetchClubMeetings($clickTtClubId, $startYear);

        if ($teamMatches === null) {
            return new ImportResult(
                success: false,
                errorMessage: 'Failed to fetch club meetings from click-tt.de for club ID ' . $clickTtClubId
            );
        }

        if (empty($teamMatches)) {
            return new ImportResult(success: true, errorMessage: 'No matches found for the selected season.');
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $created = 0;
        $unchanged = 0;

        // Ensure season exists
        $seasonId = $this->ensureSeasonExists($startYear, $now);
        $halfSeason1Id = $this->ensureHalfSeasonExists($seasonId, 1);
        $halfSeason2Id = $this->ensureHalfSeasonExists($seasonId, 2);

        // Step 3: Process each team's data
        foreach ($teamMatches as $teamInfo) {
            $teamNumber = $teamInfo['team_number'];
            $championshipId = $teamInfo['championship_id'];
            $groupId = $teamInfo['group_id'];

            // Fetch the full league name from the groupPage using championship_id and group_id
            $leagueName = '';
            if ($championshipId !== '' && $groupId !== '') {
                $leagueName = $this->fetchLeagueNameFromGroup($championshipId, $groupId);
            }
            if ($leagueName === '') {
                // Fallback to the short name from the meetings table
                $leagueName = $teamInfo['league'] ?: 'Unbekannt';
            }

            // Determine if this is a cup competition → parallel season
            $parser = new ClickTtParser();
            $targetSeasonId = $seasonId;
            $seasonLabel = '';

            if ($championshipId !== '' && $parser->isCupCompetition(urldecode($championshipId))) {
                $seasonLabel = 'Pokal';
                $targetSeasonId = $this->ensureSeasonExists($startYear, $now, $seasonLabel);
                $this->ensureHalfSeasonExists($targetSeasonId, 1);
                $this->ensureHalfSeasonExists($targetSeasonId, 2);
            }

            // Ensure team exists
            $leagueId = $this->matchOrCreateLeague($leagueName, $now);
            $ageClassId = $this->matchOrCreateAgeClass('Erwachsene', $now);

            $teamId = $this->findTeam($targetSeasonId, $teamNumber, $ageClassId);
            if ($teamId === null) {
                $this->createTeam($targetSeasonId, $leagueId, $ageClassId, $teamNumber, $now);
                $teamId = $this->findTeam($targetSeasonId, $teamNumber, $ageClassId);
                $created++;
            }

            if ($teamId === null) {
                continue;
            }

            // Import schedule entries
            foreach ($teamInfo['matches'] as $match) {
                if (!$this->scheduleEntryExists($teamId, $targetSeasonId, $match['date'], $match['opponent'])) {
                    $this->createScheduleEntry($teamId, $targetSeasonId, $match, $now);
                    $created++;
                } else {
                    $unchanged++;
                }
            }
        }

        return new ImportResult(created: $created, updated: 0, unchanged: $unchanged);
    }

    /**
     * Fetch the full league name from the groupPage.
     *
     * URL: https://{federation}.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/groupPage?championship={championship_id}&group={group_id}
     *
     * The page title or first heading typically contains the full league name
     * (e.g., "Kreisliga Staffel 1" or "Bezirksliga Gruppe 2").
     *
     * @param string $championshipId URL-encoded championship (e.g., "SK+Bz.+KA+25%2F26")
     * @param string $groupId The group ID (e.g., "499592")
     * @return string The full league name, or empty string on failure
     */
    private function fetchLeagueNameFromGroup(string $championshipId, string $groupId): string
    {
        $url = sprintf(
            'https://%s.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/groupPage?championship=%s&group=%s',
            strtolower($this->federation),
            $championshipId,
            $groupId
        );

        $html = $this->fetchPage($url);

        if ($html === null) {
            return '';
        }

        $doc = new \DOMDocument();
        @$doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($doc);

        // The h1 on groupPage contains lines separated by <br>:
        // Line 1: "Spielklassen Bezirk Karlsruhe 2025/26" (championship)
        // Line 2: "Erwachsene Kreisliga Staffel 1" (league name we want)
        // Line 3: "Tabelle und Spielplan (Aktuell)"
        $h1 = $xpath->query("//h1");

        if ($h1 !== false && $h1->length > 0) {
            $h1Node = $h1->item(0);
            // Get text segments separated by <br> elements
            $segments = [];
            $currentText = '';

            foreach ($h1Node->childNodes as $child) {
                if ($child->nodeName === 'br') {
                    $trimmed = trim($currentText);
                    if ($trimmed !== '') {
                        $segments[] = $trimmed;
                    }
                    $currentText = '';
                } else {
                    $currentText .= $child->textContent;
                }
            }
            // Don't forget the last segment
            $trimmed = trim($currentText);
            if ($trimmed !== '') {
                $segments[] = $trimmed;
            }

            // The league name is typically the second segment
            // (first is championship name, third is "Tabelle und Spielplan")
            if (count($segments) >= 2) {
                $leagueName = $segments[1];
                // Verify it's not the "Tabelle und Spielplan" line
                if (!str_contains($leagueName, 'Spielplan') && !str_contains($leagueName, 'Tabelle')) {
                    return $leagueName;
                }
            }

            // Fallback: try first segment if it doesn't look like a championship header
            if (count($segments) >= 1 && !str_contains($segments[0], 'Spielklassen')) {
                return $segments[0];
            }
        }

        return '';
    }

    /**
     * HTTP POST request. Returns HTML string or null on failure.
     */
    protected function postPage(string $url, string $body): ?string
    {
        // Use cURL for POST requests (more reliable than file_get_contents for POSTs)
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
                    'User-Agent: Mozilla/5.0 (compatible; TtclubImport/1.0)',
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
                'header' => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: Mozilla/5.0 (compatible; TtclubImport/1.0)\r\nAccept: text/html\r\n",
                'content' => $body,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        if (isset($http_response_header) && is_array($http_response_header)) {
            $statusLine = $http_response_header[0] ?? '';
            if (preg_match('/\s(\d{3})\s/', $statusLine, $m) && (int) $m[1] >= 400) {
                return null;
            }
        }

        return $response;
    }
}
