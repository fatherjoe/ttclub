<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Service;

use Joomla\Database\DatabaseInterface;

/**
 * Service for importing player, roster, and schedule data from mytischtennis.de.
 *
 * Uses PHP's native HTTP functions for fetching pages and DOMDocument/DOMXPath
 * for HTML parsing. Player matching uses first_name + last_name as unique identifier.
 *
 * The executeFullImport() method iterates over all configured club IDs from the
 * #__ttclub_club_ids table, using each entry's federation field when constructing
 * click-tt.de URLs. Players are merged across club IDs using case-insensitive
 * first_name + last_name matching (no duplicates). Teams are associated with their
 * source club_id entry via the club_id_source field.
 */
class ImportService
{
    public function __construct(
        private readonly DatabaseInterface $db,
    ) {}

    /**
     * Execute a full import across all configured club IDs.
     *
     * Loads all entries from #__ttclub_club_ids and iterates over each one.
     * For each club ID entry:
     * - Uses the entry's `federation` field (not a global setting) for URL construction
     * - Fetches clubPools, clubPortraitTT, clubTeams
     * - Sets team.club_id_source to the club_ids entry ID
     * - Merges players across club IDs using first_name + last_name (case-insensitive)
     * - Logs per-club-ID import results
     *
     * @return FullImportResult Summary of all import operations
     */
    public function executeFullImport(): FullImportResult
    {
        $instances = ClickTtImportService::allFromDatabase($this->db);

        if (empty($instances)) {
            return new FullImportResult(
                totalCreated: 0,
                totalUpdated: 0,
                totalUnchanged: 0,
                success: false,
                errorMessage: 'No club IDs configured. Add entries in the Club IDs management page.',
                perClubResults: [],
            );
        }

        $totalCreated = 0;
        $totalUpdated = 0;
        $totalUnchanged = 0;
        $perClubResults = [];
        $allSuccess = true;

        foreach ($instances as $instance) {
            /** @var ClickTtImportService $service */
            $service = $instance['service'];
            $label = $instance['label'];
            $clubId = $instance['club_id'];
            $configId = $instance['config_id'] ?? null;

            $result = $service->discoverAndImportAll();

            $totalCreated += $result->created;
            $totalUpdated += $result->updated;
            $totalUnchanged += $result->unchanged;

            if (!$result->success) {
                $allSuccess = false;
            }

            $perClubResults[] = [
                'label' => $label,
                'club_id' => $clubId,
                'config_id' => $configId,
                'result' => $result,
            ];

            // Log per-club-ID result
            $this->logImportForClub($label, $clubId, $result);
        }

        $errorMessage = null;
        if (!$allSuccess) {
            $errors = [];
            foreach ($perClubResults as $pcr) {
                if (!$pcr['result']->success && $pcr['result']->errorMessage) {
                    $errors[] = sprintf('%s (ID %d): %s', $pcr['label'], $pcr['club_id'], $pcr['result']->errorMessage);
                }
            }
            $errorMessage = implode(' | ', $errors);
        }

        return new FullImportResult(
            totalCreated: $totalCreated,
            totalUpdated: $totalUpdated,
            totalUnchanged: $totalUnchanged,
            success: $allSuccess,
            errorMessage: $errorMessage,
            perClubResults: $perClubResults,
        );
    }

    /**
     * Import a parallel season from a provided click-tt.de URL.
     *
     * Parses the URL to extract federation and club ID, fetches the page,
     * derives the season label from the championship name, and imports
     * teams and rosters into the parallel season.
     *
     * Does NOT create local schedule records — schedule data is fetched live via ScheduleService.
     *
     * @param string $clickTtUrl A valid click-tt.de URL (e.g., clubPools or clubTeams page)
     * @return ImportResult Summary of import operation
     */
    public function importFromUrl(string $clickTtUrl): ImportResult
    {
        $parser = new ClickTtParser();

        // Parse the URL to extract federation, club ID, and params
        try {
            $urlParts = $parser->parseClickTtUrl($clickTtUrl);
        } catch (\InvalidArgumentException $e) {
            return new ImportResult(
                success: false,
                errorMessage: 'Invalid click-tt.de URL: ' . $e->getMessage(),
            );
        }

        $federation = $urlParts['federation'];
        $clubId = $urlParts['clubId'];
        $action = $urlParts['action'];
        $params = $urlParts['params'];

        if ($clubId === 0) {
            return new ImportResult(
                success: false,
                errorMessage: 'No club ID found in the URL. Please provide a URL with a club= parameter.',
            );
        }

        // Create a ClickTtImportService instance for this federation/club
        $importService = new ClickTtImportService($this->db, $federation, $clubId);

        // Determine the page to fetch — use the original URL
        $html = $this->fetchPage($clickTtUrl);

        if ($html === null) {
            return new ImportResult(
                success: false,
                errorMessage: 'Failed to fetch the page from click-tt.de. The URL may be invalid or unreachable.',
            );
        }

        // Derive the championship name from the page content to determine the season label
        $seasonLabel = $this->deriveSeasonLabelFromHtml($html, $params, $parser);

        // Determine the start year from URL params or page content
        $startYear = $this->deriveStartYearFromParams($params, $html);

        if ($startYear === 0) {
            return new ImportResult(
                success: false,
                errorMessage: 'Could not determine the season year from the URL or page content.',
            );
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        // Ensure the season exists (with the derived label for parallel seasons)
        $seasonId = $this->ensureSeasonExists($startYear, $now, $seasonLabel);
        $halfSeason1Id = $this->ensureHalfSeasonExists($seasonId, 1);
        $halfSeason2Id = $this->ensureHalfSeasonExists($seasonId, 2);

        // Determine which half-season to import based on displayTyp param or current month
        $halfSeasonId = $halfSeason1Id;
        if (isset($params['displayTyp']) && $params['displayTyp'] === 'rueckrunde') {
            $halfSeasonId = $halfSeason2Id;
        } else {
            // Default: use current month to decide
            $currentMonth = (int) date('n');
            if ($currentMonth >= 1 && $currentMonth <= 7) {
                $halfSeasonId = $halfSeason2Id;
            }
        }

        // Parse rosters from the fetched HTML (same format as clubPools)
        $totalCreated = 0;
        $totalUnchanged = 0;

        if ($action === 'clubPools' || str_contains($html, 'result-set')) {
            $rosterResult = $this->importRostersFromHtml($html, $seasonId, $halfSeasonId, $now);
            $totalCreated += $rosterResult['created'];
            $totalUnchanged += $rosterResult['unchanged'];
        }

        // Log the operation
        $this->logUrlImport($clickTtUrl, $seasonLabel, $startYear, $totalCreated, $totalUnchanged);

        return new ImportResult(
            created: $totalCreated,
            updated: 0,
            unchanged: $totalUnchanged,
        );
    }

    /**
     * Derive a season label from page HTML and URL params.
     *
     * Looks for the championship name in the HTML (e.g., "Pokal Bezirk Karlsruhe 2025/26")
     * and uses ClickTtParser::isCupCompetition() to determine if it's a cup.
     * Falls back to extracting from URL championship param.
     */
    private function deriveSeasonLabelFromHtml(string $html, array $params, ClickTtParser $parser): string
    {
        // Strategy 1: Check the championship parameter from the URL
        if (isset($params['championship']) && $params['championship'] !== '') {
            $championship = urldecode($params['championship']);

            if ($parser->isCupCompetition($championship)) {
                return $this->extractLabelFromChampionship($championship);
            }
        }

        // Strategy 2: Look for championship name in the HTML page title or headings
        $doc = new \DOMDocument();
        @$doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($doc);

        // Check page title
        $titleNodes = $xpath->query('//title');
        if ($titleNodes !== false && $titleNodes->length > 0) {
            $titleText = trim($titleNodes->item(0)->textContent);
            if ($parser->isCupCompetition($titleText)) {
                return $this->extractLabelFromChampionship($titleText);
            }
        }

        // Check h1/h2 headings
        $headings = $xpath->query('//h1|//h2');
        if ($headings !== false) {
            for ($i = 0; $i < $headings->length; $i++) {
                $headingText = trim($headings->item($i)->textContent);
                if ($parser->isCupCompetition($headingText)) {
                    return $this->extractLabelFromChampionship($headingText);
                }
            }
        }

        // Check championship select options (selected one)
        $selectedOptions = $xpath->query("//select[@name='championship']/option[@selected]");
        if ($selectedOptions !== false && $selectedOptions->length > 0) {
            $optionText = trim($selectedOptions->item(0)->textContent);
            if ($parser->isCupCompetition($optionText)) {
                return $this->extractLabelFromChampionship($optionText);
            }
            // Even if not a "cup" keyword match, use it as the label
            // since this is specifically a parallel season import via URL
            return $this->extractLabelFromChampionship($optionText);
        }

        // Strategy 3: Check section headers in the tables (championship group headers)
        $ths = $xpath->query("//table[contains(@class,'result-set')]//th");
        if ($ths !== false) {
            for ($i = 0; $i < $ths->length; $i++) {
                $thText = trim($ths->item($i)->textContent);
                if ($thText !== '' && $parser->isCupCompetition($thText)) {
                    return $this->extractLabelFromChampionship($thText);
                }
            }
        }

        // No label derived — this will be treated as a regular season import
        return '';
    }

    /**
     * Extract a concise label from a championship name.
     *
     * Examples:
     * - "Pokal Bezirk Karlsruhe 2025/26" → "Pokal"
     * - "Sommer-Team-Cup 2025" → "Sommer-Team-Cup"
     * - "Vereinspokal 2025/26" → "Pokal"
     *
     * @param string $championshipName The full championship name
     * @return string A concise label suitable for the season record
     */
    private function extractLabelFromChampionship(string $championshipName): string
    {
        $lower = mb_strtolower($championshipName);

        // Check for specific cup keywords and extract them
        if (str_contains($lower, 'sommer-team-cup') || str_contains($lower, 'sommer team cup')) {
            return 'Sommer-Team-Cup';
        }

        if (str_contains($lower, 'pokal')) {
            return 'Pokal';
        }

        if (str_contains($lower, 'cup')) {
            // Try to extract the cup name (word before "cup" or the cup word itself)
            if (preg_match('/(\S+[\-\s]?cup)/i', $championshipName, $matches)) {
                return trim($matches[1]);
            }
            return 'Cup';
        }

        // For other competitions, use a cleaned version (strip year and region suffixes)
        $label = preg_replace('/\s*\d{4}(\/\d{2})?\s*$/', '', $championshipName);
        $label = preg_replace('/\s+(Bezirk|Kreis|Verband)\s+\S+$/i', '', $label ?? $championshipName);

        return mb_substr(trim($label ?? $championshipName), 0, 50);
    }

    /**
     * Derive the start year from URL params or HTML page content.
     */
    private function deriveStartYearFromParams(array $params, string $html): int
    {
        // Strategy 1: seasonName param (e.g., "2025/26")
        if (isset($params['seasonName']) && preg_match('/^(\d{4})\//', $params['seasonName'], $m)) {
            return (int) $m[1];
        }

        // Strategy 2: championship param often contains the year (e.g., "SK Bz. KA 25/26")
        if (isset($params['championship'])) {
            $championship = urldecode($params['championship']);
            if (preg_match('/(\d{2})\/(\d{2})/', $championship, $m)) {
                $year = (int) $m[1];
                // Convert 2-digit year to 4-digit
                return $year >= 70 ? 1900 + $year : 2000 + $year;
            }
            if (preg_match('/(\d{4})/', $championship, $m)) {
                return (int) $m[1];
            }
        }

        // Strategy 3: Search the HTML for season indicators
        if (preg_match('/(\d{4})\/(\d{2})/', $html, $m)) {
            return (int) $m[1];
        }

        // Strategy 4: Fall back to current season year based on month
        $currentMonth = (int) date('n');
        $currentYear = (int) date('Y');

        if ($currentMonth >= 8) {
            return $currentYear;
        }

        return $currentYear - 1;
    }

    /**
     * Import roster entries from parsed HTML.
     *
     * @return array{created: int, unchanged: int}
     */
    private function importRostersFromHtml(string $html, int $seasonId, int $halfSeasonId, string $now): array
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($doc);

        $created = 0;
        $unchanged = 0;

        $rows = $xpath->query("//table[contains(@class,'result-set')]//tr");

        if ($rows === false || $rows->length === 0) {
            return ['created' => 0, 'unchanged' => 0];
        }

        for ($i = 0; $i < $rows->length; $i++) {
            $row = $rows->item($i);
            $cells = $xpath->query('.//td', $row);

            if ($cells === false || $cells->length < 3) {
                continue;
            }

            // First cell should contain position like "1.1", "2.3"
            $posText = trim($cells->item(0)?->textContent ?? '');

            if (!preg_match('/^(\d+)\.(\d+)$/', $posText, $posMatch)) {
                continue;
            }

            $teamNumber = (int) $posMatch[1];
            $position = (int) $posMatch[2];

            // Name is typically in the 3rd cell (after Q-TTR in 2nd)
            $nameText = trim($cells->item(2)?->textContent ?? '');

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

            $firstName = mb_substr($firstName, 0, 50);
            $lastName = mb_substr($lastName, 0, 50);

            // Ensure team exists
            $teamId = $this->findTeamByNumber($teamNumber, $seasonId);

            if ($teamId === null) {
                // Create the team with placeholder league
                $this->createTeamForUrlImport($teamNumber, $seasonId, $now);
                $teamId = $this->findTeamByNumber($teamNumber, $seasonId);
            }

            if ($teamId === null) {
                continue;
            }

            // Match or create player (case-insensitive first_name + last_name)
            $player = $this->findPlayerByName($firstName, $lastName);
            $playerId = $player !== null ? (int) $player->id : $this->insertPlayer($firstName, $lastName, $now);

            // Create roster entry if not exists
            $existingRoster = $this->findRosterEntry($playerId, $teamId, $halfSeasonId);

            if ($existingRoster === null) {
                $this->insertRosterEntryWithPosition($playerId, $teamId, $halfSeasonId, $position, $now);
                $created++;
            } else {
                $unchanged++;
            }
        }

        return ['created' => $created, 'unchanged' => $unchanged];
    }

    /**
     * Create a team record for URL-based imports with a placeholder league.
     */
    private function createTeamForUrlImport(int $teamNumber, int $seasonId, string $now): void
    {
        // Find or create placeholder league "Unbekannt"
        $query = $this->db->getQuery(true)
            ->select('id')
            ->from($this->db->quoteName('#__ttclub_leagues'))
            ->where('LOWER(name) = ' . $this->db->quote('unbekannt'));
        $this->db->setQuery($query);
        $leagueId = $this->db->loadResult();

        if (!$leagueId) {
            $rec = (object) ['name' => 'Unbekannt', 'published' => 1, 'created' => $now, 'modified' => $now, 'created_by' => 0, 'modified_by' => 0];
            $this->db->insertObject('#__ttclub_leagues', $rec, 'id');
            $leagueId = (int) $rec->id;
        }

        // Find or create default age class "Erwachsene"
        $query = $this->db->getQuery(true)
            ->select('id')
            ->from($this->db->quoteName('#__ttclub_age_classes'))
            ->where('LOWER(name) = ' . $this->db->quote('erwachsene'));
        $this->db->setQuery($query);
        $ageClassId = $this->db->loadResult();

        if (!$ageClassId) {
            $rec = (object) ['name' => 'Erwachsene', 'max_age' => null, 'published' => 1, 'created' => $now, 'modified' => $now];
            $this->db->insertObject('#__ttclub_age_classes', $rec, 'id');
            $ageClassId = (int) $rec->id;
        }

        $rec = (object) [
            'season_id' => $seasonId,
            'league_id' => (int) $leagueId,
            'age_class_id' => (int) $ageClassId,
            'team_number' => $teamNumber,
            'published' => 1,
            'created' => $now,
            'modified' => $now,
            'created_by' => 0,
            'modified_by' => 0,
        ];
        $this->db->insertObject('#__ttclub_teams', $rec);
    }

    /**
     * Insert a roster entry with position.
     */
    private function insertRosterEntryWithPosition(int $playerId, int $teamId, int $halfSeasonId, int $position, string $now): void
    {
        $record = (object) [
            'player_id' => $playerId,
            'team_id' => $teamId,
            'half_season_id' => $halfSeasonId,
            'position' => $position,
            'created' => $now,
        ];

        $this->db->insertObject('#__ttclub_rosters', $record);
    }

    /**
     * Ensure a season record exists. Returns the season ID.
     */
    private function ensureSeasonExists(int $startYear, string $now, string $label = ''): int
    {
        $query = $this->db->getQuery(true)
            ->select('id')
            ->from($this->db->quoteName('#__ttclub_seasons'))
            ->where('start_year = ' . $startYear)
            ->where('label = ' . $this->db->quote($label));
        $this->db->setQuery($query);
        $id = $this->db->loadResult();

        if ($id) {
            return (int) $id;
        }

        $rec = (object) [
            'start_year' => $startYear,
            'label' => $label,
            'published' => 1,
            'created' => $now,
            'modified' => $now,
            'created_by' => 0,
            'modified_by' => 0,
        ];
        $this->db->insertObject('#__ttclub_seasons', $rec, 'id');

        return (int) $rec->id;
    }

    /**
     * Ensure a half-season record exists. Returns the half_season ID.
     */
    private function ensureHalfSeasonExists(int $seasonId, int $half): int
    {
        $query = $this->db->getQuery(true)
            ->select('id')
            ->from($this->db->quoteName('#__ttclub_half_seasons'))
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

    /**
     * Log a URL-based import operation.
     */
    private function logUrlImport(string $url, string $seasonLabel, int $startYear, int $created, int $unchanged): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $label = $seasonLabel !== '' ? $seasonLabel . ' ' : '';
        $message = sprintf(
            'URL import (%s%d/%02d): %d created, %d unchanged. Source: %s',
            $label,
            $startYear,
            ($startYear + 1) % 100,
            $created,
            $unchanged,
            mb_substr($url, 0, 200)
        );

        $rec = (object) [
            'import_date' => $now,
            'import_type' => 'url_import',
            'records_created' => $created,
            'records_updated' => 0,
            'records_unchanged' => $unchanged,
            'status' => 1,
            'message' => mb_substr($message, 0, 65535),
        ];

        $this->db->insertObject('#__ttclub_import_logs', $rec);
    }

    /**
     * Log an import result for a specific club ID entry.
     */
    private function logImportForClub(string $label, int $clubId, ImportResult $result): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $message = sprintf(
            'Club "%s" (click-tt ID %d): %d created, %d updated, %d unchanged',
            $label,
            $clubId,
            $result->created,
            $result->updated,
            $result->unchanged
        );

        if (!$result->success && $result->errorMessage) {
            $message .= ' | Error: ' . $result->errorMessage;
        }

        $rec = (object) [
            'import_date' => $now,
            'import_type' => 'full_import_club_' . $clubId,
            'records_created' => $result->created,
            'records_updated' => $result->updated,
            'records_unchanged' => $result->unchanged,
            'status' => $result->success ? 1 : 0,
            'message' => mb_substr($message, 0, 65535),
        ];

        $this->db->insertObject('#__ttclub_import_logs', $rec);
    }

    /**
     * Import player data from mytischtennis.de.
     *
     * Scrapes player data from the club page, creates new player records
     * or updates existing ones matched by first_name + last_name.
     */
    public function importPlayers(string $clubUrl, int $seasonId): ImportResult
    {
        $html = $this->fetchPage($clubUrl . '/spieler');

        if ($html === null) {
            return new ImportResult(
                success: false,
                errorMessage: 'Failed to connect to mytischtennis.de or received invalid response.',
            );
        }

        $players = $this->parsePlayers($html);

        if ($players === []) {
            return new ImportResult(
                success: true,
                errorMessage: 'No player data found on the page.',
            );
        }

        $created = 0;
        $updated = 0;
        $unchanged = 0;

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($players as $player) {
            $firstName = trim($player['first_name']);
            $lastName = trim($player['last_name']);

            if ($firstName === '' || $lastName === '') {
                continue;
            }

            $existing = $this->findPlayerByName($firstName, $lastName);

            if ($existing === null) {
                // Create new player
                $this->insertPlayer($firstName, $lastName, $now);
                $created++;
            } else {
                // Player already exists — no updatable fields from scraping
                $unchanged++;
            }
        }

        return new ImportResult(
            created: $created,
            updated: $updated,
            unchanged: $unchanged,
        );
    }

    /**
     * Import roster data from mytischtennis.de.
     *
     * Scrapes team roster assignments and creates/updates roster entries
     * linking players to teams for the specified half-season.
     */
    public function importRosters(string $clubUrl, int $seasonId, int $halfSeasonId): ImportResult
    {
        $html = $this->fetchPage($clubUrl . '/mannschaften');

        if ($html === null) {
            return new ImportResult(
                success: false,
                errorMessage: 'Failed to connect to mytischtennis.de or received invalid response.',
            );
        }

        $rosters = $this->parseRosters($html);

        if ($rosters === []) {
            return new ImportResult(
                success: true,
                errorMessage: 'No roster data found on the page.',
            );
        }

        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($rosters as $roster) {
            $teamId = $this->findTeamByNumber((int) $roster['team_number'], $seasonId);

            if ($teamId === null) {
                continue;
            }

            foreach ($roster['players'] as $playerName) {
                $nameParts = $this->splitPlayerName($playerName);
                $player = $this->findPlayerByName($nameParts['first_name'], $nameParts['last_name']);

                if ($player === null) {
                    // Create the player first
                    $playerId = $this->insertPlayer($nameParts['first_name'], $nameParts['last_name'], $now);
                } else {
                    $playerId = (int) $player->id;
                }

                // Check if roster entry already exists
                $existingRoster = $this->findRosterEntry($playerId, $teamId, $halfSeasonId);

                if ($existingRoster === null) {
                    $this->insertRosterEntry($playerId, $teamId, $halfSeasonId, $now);
                    $created++;
                } else {
                    $unchanged++;
                }
            }
        }

        return new ImportResult(
            created: $created,
            updated: $updated,
            unchanged: $unchanged,
        );
    }

    /**
     * Import schedule data from mytischtennis.de.
     *
     * Scrapes match schedule data and creates/updates schedule entries
     * for teams in the specified season.
     */
    public function importSchedules(string $clubUrl, int $seasonId): ImportResult
    {
        $html = $this->fetchPage($clubUrl . '/spiele');

        if ($html === null) {
            return new ImportResult(
                success: false,
                errorMessage: 'Failed to connect to mytischtennis.de or received invalid response.',
            );
        }

        $schedules = $this->parseSchedules($html);

        if ($schedules === []) {
            return new ImportResult(
                success: true,
                errorMessage: 'No schedule data found on the page.',
            );
        }

        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($schedules as $match) {
            $teamId = $this->findTeamByNumber((int) $match['team_number'], $seasonId);

            if ($teamId === null) {
                continue;
            }

            $matchDate = $match['match_date'];
            $opponent = trim($match['opponent']);

            // Check if schedule entry already exists (same team, date, opponent)
            $existing = $this->findScheduleEntry($teamId, $seasonId, $matchDate, $opponent);

            if ($existing === null) {
                $this->insertScheduleEntry(
                    teamId: $teamId,
                    seasonId: $seasonId,
                    matchDate: $matchDate,
                    matchTime: $match['match_time'] ?? null,
                    opponent: $opponent,
                    venue: trim($match['venue'] ?? ''),
                    homeAway: (int) ($match['home_away'] ?? 1),
                    result: $match['result'] ?? null,
                    now: $now,
                );
                $created++;
            } else {
                // Update result if available and different
                $result = $match['result'] ?? null;
                if ($result !== null && $result !== ($existing->result ?? '')) {
                    $this->updateScheduleResult((int) $existing->id, $result, $now);
                    $updated++;
                } else {
                    $unchanged++;
                }
            }
        }

        return new ImportResult(
            created: $created,
            updated: $updated,
            unchanged: $unchanged,
        );
    }

    /**
     * Validate that the club identifier returns valid data from mytischtennis.de.
     */
    public function validateClubConnection(string $clubIdentifier): bool
    {
        if ($clubIdentifier === '') {
            return false;
        }

        $url = $this->buildClubUrl($clubIdentifier);
        $html = $this->fetchPage($url);

        if ($html === null) {
            return false;
        }

        // Check if the page contains recognisable club content
        $doc = new \DOMDocument();
        @$doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($doc);

        // Look for elements that indicate a valid club page
        $clubElements = $xpath->query('//div[contains(@class, "club")]|//h1|//div[contains(@class, "verein")]');

        return $clubElements !== false && $clubElements->length > 0;
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
    // HTML Parsing
    // ---------------------------------------------------------------

    /**
     * Parse player data from HTML.
     *
     * @return array<int, array{first_name: string, last_name: string}>
     */
    protected function parsePlayers(string $html): array
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($doc);

        $players = [];

        // mytischtennis.de player list typically uses table rows or list items
        $rows = $xpath->query('//table[contains(@class, "table")]//tbody//tr');

        if ($rows === false || $rows->length === 0) {
            // Fallback: try list-based markup
            $rows = $xpath->query('//div[contains(@class, "spieler")]//li|//ul[contains(@class, "player")]//li');
        }

        if ($rows === false) {
            return [];
        }

        foreach ($rows as $row) {
            $cells = $xpath->query('.//td', $row);

            if ($cells !== false && $cells->length >= 2) {
                $lastName = trim($cells->item(0)?->textContent ?? '');
                $firstName = trim($cells->item(1)?->textContent ?? '');
            } else {
                // Try to extract from text content with "Last, First" format
                $text = trim($row->textContent ?? '');
                $nameParts = $this->splitPlayerName($text);
                $firstName = $nameParts['first_name'];
                $lastName = $nameParts['last_name'];
            }

            if ($firstName !== '' && $lastName !== '') {
                $players[] = [
                    'first_name' => mb_substr($firstName, 0, 50),
                    'last_name' => mb_substr($lastName, 0, 50),
                ];
            }
        }

        return $players;
    }

    /**
     * Parse roster data from HTML.
     *
     * @return array<int, array{team_number: int, players: string[]}>
     */
    protected function parseRosters(string $html): array
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($doc);

        $rosters = [];

        // Look for team sections with roster lists
        $teamSections = $xpath->query(
            '//div[contains(@class, "mannschaft")]|//section[contains(@class, "team")]'
        );

        if ($teamSections === false || $teamSections->length === 0) {
            return [];
        }

        foreach ($teamSections as $section) {
            // Extract team number from heading
            $heading = $xpath->query('.//h2|.//h3|.//h4', $section);
            $teamNumber = 0;

            if ($heading !== false && $heading->length > 0) {
                $headingText = $heading->item(0)?->textContent ?? '';
                if (preg_match('/(\d+)/', $headingText, $matches)) {
                    $teamNumber = (int) $matches[1];
                }
            }

            if ($teamNumber === 0) {
                continue;
            }

            // Extract player names from list within the section
            $playerNodes = $xpath->query('.//li|.//tr/td[1]', $section);
            $players = [];

            if ($playerNodes !== false) {
                foreach ($playerNodes as $node) {
                    $name = trim($node->textContent ?? '');
                    if ($name !== '') {
                        $players[] = $name;
                    }
                }
            }

            if ($players !== []) {
                $rosters[] = [
                    'team_number' => $teamNumber,
                    'players' => $players,
                ];
            }
        }

        return $rosters;
    }

    /**
     * Parse schedule data from HTML.
     *
     * @return array<int, array{team_number: int, match_date: string, match_time: ?string, opponent: string, venue: string, home_away: int, result: ?string}>
     */
    protected function parseSchedules(string $html): array
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($doc);

        $schedules = [];

        // Look for schedule table rows
        $rows = $xpath->query(
            '//table[contains(@class, "table")]//tbody//tr|//table[contains(@class, "spiele")]//tbody//tr'
        );

        if ($rows === false || $rows->length === 0) {
            return [];
        }

        foreach ($rows as $row) {
            $cells = $xpath->query('.//td', $row);

            if ($cells === false || $cells->length < 5) {
                continue;
            }

            $dateText = trim($cells->item(0)?->textContent ?? '');
            $timeText = trim($cells->item(1)?->textContent ?? '');
            $homeTeam = trim($cells->item(2)?->textContent ?? '');
            $awayTeam = trim($cells->item(3)?->textContent ?? '');
            $result = trim($cells->item(4)?->textContent ?? '');

            // Determine home/away and opponent
            $isHome = $this->isOwnTeam($homeTeam);
            $opponent = $isHome ? $awayTeam : $homeTeam;
            $homeAway = $isHome ? 1 : 2;

            // Parse date (expected format: DD.MM.YYYY or YYYY-MM-DD)
            $matchDate = $this->parseDate($dateText);
            if ($matchDate === null) {
                continue;
            }

            // Parse time
            $matchTime = $this->parseTime($timeText);

            // Extract team number from home/away team name
            $ownTeamName = $isHome ? $homeTeam : $awayTeam;
            $teamNumber = $this->extractTeamNumber($ownTeamName);

            if ($teamNumber === 0 || $opponent === '') {
                continue;
            }

            $schedules[] = [
                'team_number' => $teamNumber,
                'match_date' => $matchDate,
                'match_time' => $matchTime,
                'opponent' => mb_substr($opponent, 0, 150),
                'venue' => '',
                'home_away' => $homeAway,
                'result' => $result !== '' ? mb_substr($result, 0, 20) : null,
            ];
        }

        return $schedules;
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
    // Date/time parsing utilities
    // ---------------------------------------------------------------

    /**
     * Parse a date string into YYYY-MM-DD format.
     */
    protected function parseDate(string $dateText): ?string
    {
        // Try DD.MM.YYYY (German format)
        if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})/', $dateText, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }

        // Try YYYY-MM-DD (ISO format)
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $dateText, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Parse a time string into HH:MM:SS format.
     */
    protected function parseTime(string $timeText): ?string
    {
        if (preg_match('/(\d{2}):(\d{2})/', $timeText, $matches)) {
            return $matches[1] . ':' . $matches[2] . ':00';
        }

        return null;
    }

    // ---------------------------------------------------------------
    // Team identification utilities
    // ---------------------------------------------------------------

    /**
     * Check if a team name belongs to our club (heuristic).
     */
    protected function isOwnTeam(string $teamName): bool
    {
        // This is a simplified check; the club name should come from config
        // For now, we check for common patterns that indicate the home team
        // In production, this would compare against the configured club name
        return true;
    }

    /**
     * Extract team number from a team name string.
     * E.g., "TTV Musterstadt II" → 2, "FC Example 3" → 3
     */
    protected function extractTeamNumber(string $teamName): int
    {
        // Try Arabic numerals at end
        if (preg_match('/\b(\d+)\s*$/', $teamName, $matches)) {
            return (int) $matches[1];
        }

        // Try Roman numerals
        $romanMap = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5,
                     'VI' => 6, 'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10];

        if (preg_match('/\b([IVX]+)\s*$/', $teamName, $matches)) {
            $roman = $matches[1];
            if (isset($romanMap[$roman])) {
                return $romanMap[$roman];
            }
        }

        // Default to team 1 if no number found
        return 1;
    }

    // ---------------------------------------------------------------
    // URL building
    // ---------------------------------------------------------------

    /**
     * Build a full club URL from a club identifier.
     */
    protected function buildClubUrl(string $clubIdentifier): string
    {
        // If already a full URL, return as-is
        if (str_starts_with($clubIdentifier, 'http://') || str_starts_with($clubIdentifier, 'https://')) {
            return $clubIdentifier;
        }

        // Build URL from identifier
        return 'https://www.mytischtennis.de/clicktt/verein/' . urlencode($clubIdentifier);
    }

    // ---------------------------------------------------------------
    // Database operations
    // ---------------------------------------------------------------

    /**
     * Find a player by first name + last name (case-insensitive match).
     */
    protected function findPlayerByName(string $firstName, string $lastName): ?object
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__ttclub_players'))
            ->where('LOWER(' . $this->db->quoteName('first_name') . ') = LOWER(' . $this->db->quote($firstName) . ')')
            ->where('LOWER(' . $this->db->quoteName('last_name') . ') = LOWER(' . $this->db->quote($lastName) . ')');

        $this->db->setQuery($query);

        $result = $this->db->loadObject();

        return $result ?: null;
    }

    /**
     * Insert a new player record.
     *
     * @return int The new player's ID.
     */
    protected function insertPlayer(string $firstName, string $lastName, string $now): int
    {
        $record = (object) [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'published' => 1,
            'created' => $now,
            'modified' => $now,
            'created_by' => 0,
            'modified_by' => 0,
        ];

        $this->db->insertObject('#__ttclub_players', $record, 'id');

        return (int) $record->id;
    }

    /**
     * Find a team by team number within a season.
     */
    protected function findTeamByNumber(int $teamNumber, int $seasonId): ?int
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__ttclub_teams'))
            ->where($this->db->quoteName('team_number') . ' = ' . $teamNumber)
            ->where($this->db->quoteName('season_id') . ' = ' . $seasonId);

        $this->db->setQuery($query);

        $result = $this->db->loadResult();

        return $result !== null ? (int) $result : null;
    }

    /**
     * Find an existing roster entry.
     */
    protected function findRosterEntry(int $playerId, int $teamId, int $halfSeasonId): ?object
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__ttclub_rosters'))
            ->where($this->db->quoteName('player_id') . ' = ' . $playerId)
            ->where($this->db->quoteName('team_id') . ' = ' . $teamId)
            ->where($this->db->quoteName('half_season_id') . ' = ' . $halfSeasonId);

        $this->db->setQuery($query);

        $result = $this->db->loadObject();

        return $result ?: null;
    }

    /**
     * Insert a new roster entry.
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

    /**
     * Find an existing schedule entry by team, season, date, and opponent.
     */
    protected function findScheduleEntry(int $teamId, int $seasonId, string $matchDate, string $opponent): ?object
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__ttclub_schedules'))
            ->where($this->db->quoteName('team_id') . ' = ' . $teamId)
            ->where($this->db->quoteName('season_id') . ' = ' . $seasonId)
            ->where($this->db->quoteName('match_date') . ' = ' . $this->db->quote($matchDate))
            ->where('LOWER(' . $this->db->quoteName('opponent') . ') = LOWER(' . $this->db->quote($opponent) . ')');

        $this->db->setQuery($query);

        $result = $this->db->loadObject();

        return $result ?: null;
    }

    /**
     * Insert a new schedule entry.
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
            'opponent' => $opponent,
            'venue' => $venue,
            'home_away' => $homeAway,
            'result' => $result,
            'published' => 1,
            'created' => $now,
            'modified' => $now,
            'created_by' => 0,
            'modified_by' => 0,
        ];

        $this->db->insertObject('#__ttclub_schedules', $record);
    }

    /**
     * Update the result of an existing schedule entry.
     */
    protected function updateScheduleResult(int $scheduleId, string $result, string $now): void
    {
        $record = (object) [
            'id' => $scheduleId,
            'result' => $result,
            'modified' => $now,
        ];

        $this->db->updateObject('#__ttclub_schedules', $record, 'id');
    }
}
