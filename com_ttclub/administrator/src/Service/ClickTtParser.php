<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Service;

/**
 * Parser implementation for click-tt.de HTML structure.
 *
 * Handles the specific DOM layout used by click-tt.de for season archives,
 * team listings, rosters, and match schedules. Uses DOMDocument/DOMXPath for extraction.
 *
 * click-tt.de uses a different page structure than mytischtennis.de, with
 * content typically wrapped in specific container classes and IDs.
 */
class ClickTtParser implements SeasonParserInterface
{
    /**
     * Parse season archive HTML from click-tt.de.
     *
     * click-tt.de lists seasons in a navigation sidebar or dropdown,
     * often using links with "staffel" or "saison" in the URL.
     *
     * @param string $html Raw HTML content of the season archive page
     * @return array<int, array{name: string, url: string}>
     */
    public function parseSeasonArchive(string $html): array
    {
        $doc = $this->loadHtml($html);

        if ($doc === null) {
            return [];
        }

        $xpath = new \DOMXPath($doc);
        $seasons = [];

        // Strategy 1: Look for season selector (select/option elements)
        $options = $xpath->query(
            '//select[contains(@name, "saison") or contains(@name, "Saison") or contains(@id, "saison")]//option'
            . '|//select[contains(@class, "season-select")]//option'
        );

        if ($options !== false && $options->length > 0) {
            foreach ($options as $option) {
                $name = trim($option->textContent ?? '');
                $url = trim($option->getAttribute('value') ?? '');

                if ($name !== '' && $url !== '') {
                    $seasons[] = ['name' => $name, 'url' => $this->normalizeUrl($url)];
                }
            }

            if ($seasons !== []) {
                return $seasons;
            }
        }

        // Strategy 2: Look for season links in content area
        $links = $xpath->query(
            '//div[contains(@id, "content") or contains(@class, "content")]//a[contains(@href, "saison") or contains(@href, "Saison")]'
            . '|//div[contains(@class, "navigation")]//a[contains(@href, "saison")]'
            . '|//ul[contains(@class, "breadcrumb") or contains(@class, "nav")]//a[contains(@href, "saison")]'
        );

        if ($links !== false && $links->length > 0) {
            foreach ($links as $link) {
                $name = trim($link->textContent ?? '');
                $url = trim($link->getAttribute('href') ?? '');

                if ($name !== '' && $url !== '' && $this->looksLikeSeasonName($name)) {
                    $seasons[] = ['name' => $name, 'url' => $this->normalizeUrl($url)];
                }
            }

            if ($seasons !== []) {
                return $seasons;
            }
        }

        // Strategy 3: Fallback — scan all links for season-like patterns
        $allLinks = $xpath->query('//a');

        if ($allLinks !== false) {
            foreach ($allLinks as $link) {
                $name = trim($link->textContent ?? '');
                $url = trim($link->getAttribute('href') ?? '');

                if ($name !== '' && $url !== '' && $this->looksLikeSeasonName($name)) {
                    // Avoid duplicate URLs
                    $normalizedUrl = $this->normalizeUrl($url);
                    $isDuplicate = false;

                    foreach ($seasons as $existing) {
                        if ($existing['url'] === $normalizedUrl) {
                            $isDuplicate = true;
                            break;
                        }
                    }

                    if (!$isDuplicate) {
                        $seasons[] = ['name' => $name, 'url' => $normalizedUrl];
                    }
                }
            }
        }

        return $seasons;
    }

    /**
     * Parse team listings from a click-tt.de season page.
     *
     * click-tt.de typically displays teams in a grouped table structure
     * organized by age class with league information in separate columns or sub-sections.
     *
     * @param string $html Raw HTML content of the teams page
     * @return array<int, array{team_number: int, league: string, age_class: string}>
     */
    public function parseTeams(string $html): array
    {
        $doc = $this->loadHtml($html);

        if ($doc === null) {
            return [];
        }

        $xpath = new \DOMXPath($doc);
        $teams = [];

        // Strategy 1: Table with team rows (click-tt uses "staffel" for league groupings)
        $rows = $xpath->query(
            '//table[contains(@class, "result-set") or contains(@class, "table")]//tbody//tr'
            . '|//table[contains(@id, "teams") or contains(@id, "mannschaften")]//tbody//tr'
        );

        if ($rows !== false && $rows->length > 0) {
            $currentAgeClass = 'Herren';

            foreach ($rows as $row) {
                // Check if this row is an age-class header
                $headerCell = $xpath->query('.//th|.//td[contains(@class, "header") or contains(@class, "group")]', $row);

                if ($headerCell !== false && $headerCell->length > 0) {
                    $headerText = trim($headerCell->item(0)?->textContent ?? '');

                    if ($this->isAgeClassHeader($headerText)) {
                        $currentAgeClass = $headerText;
                        continue;
                    }
                }

                $team = $this->extractTeamFromRow($xpath, $row, $currentAgeClass);

                if ($team !== null) {
                    $teams[] = $team;
                }
            }

            if ($teams !== []) {
                return $teams;
            }
        }

        // Strategy 2: Div/section-based layout grouped by age class
        $sections = $xpath->query(
            '//div[contains(@class, "staffel") or contains(@class, "mannschaft") or contains(@class, "team-group")]'
        );

        if ($sections !== false && $sections->length > 0) {
            foreach ($sections as $section) {
                $ageClass = $this->extractAgeClassFromSection($xpath, $section);
                $teamNodes = $xpath->query('.//a[contains(@href, "mannschaft")]|.//li|.//tr', $section);

                if ($teamNodes === false) {
                    continue;
                }

                foreach ($teamNodes as $teamNode) {
                    $teamText = trim($teamNode->textContent ?? '');
                    $teamNumber = $this->extractTeamNumber($teamText);

                    if ($teamNumber === 0) {
                        continue;
                    }

                    // Try to find league info in the same row/element
                    $league = $this->extractLeagueFromNode($xpath, $teamNode);

                    $teams[] = [
                        'team_number' => $teamNumber,
                        'league' => $league,
                        'age_class' => $ageClass,
                    ];
                }
            }
        }

        return $teams;
    }

    /**
     * Parse a team roster page from click-tt.de.
     *
     * click-tt.de shows player lists in tabular format with position numbers.
     *
     * @param string $html Raw HTML content of the roster page
     * @return array<int, string> Array of player names
     */
    public function parseRoster(string $html): array
    {
        $doc = $this->loadHtml($html);

        if ($doc === null) {
            return [];
        }

        $xpath = new \DOMXPath($doc);
        $players = [];

        // Strategy 1: Table-based roster (click-tt standard)
        $rows = $xpath->query(
            '//table[contains(@class, "result-set") or contains(@class, "table")]//tbody//tr'
            . '|//table[contains(@class, "spieler") or contains(@class, "aufstellung")]//tbody//tr'
        );

        if ($rows !== false && $rows->length > 0) {
            foreach ($rows as $row) {
                $name = $this->extractPlayerNameFromRow($xpath, $row);

                if ($name !== '') {
                    $players[] = $name;
                }
            }

            if ($players !== []) {
                return $players;
            }
        }

        // Strategy 2: List-based layout
        $items = $xpath->query(
            '//div[contains(@class, "aufstellung") or contains(@class, "roster")]//li'
            . '|//ul[contains(@class, "player") or contains(@class, "spieler")]//li'
        );

        if ($items !== false && $items->length > 0) {
            foreach ($items as $item) {
                $name = trim($item->textContent ?? '');

                if ($name !== '' && !$this->isHeaderOrNavText($name)) {
                    $players[] = $name;
                }
            }
        }

        return $players;
    }

    /**
     * Parse a schedule page from click-tt.de.
     *
     * click-tt.de displays match schedules in table format with date, time,
     * home team, away team, venue, and result columns.
     *
     * @param string $html Raw HTML content of the schedule page
     * @return array<int, array{match_date: string, match_time: ?string, opponent: string, venue: string, home_away: int, result: ?string}>
     */
    public function parseSchedule(string $html): array
    {
        $doc = $this->loadHtml($html);

        if ($doc === null) {
            return [];
        }

        $xpath = new \DOMXPath($doc);
        $matches = [];

        // Look for schedule table rows (click-tt uses "result-set" class)
        $rows = $xpath->query(
            '//table[contains(@class, "result-set") or contains(@class, "schedule") or contains(@class, "spiele")]//tbody//tr'
            . '|//table[contains(@class, "table")]//tbody//tr[td]'
        );

        if ($rows === false || $rows->length === 0) {
            return [];
        }

        foreach ($rows as $row) {
            $match = $this->extractMatchFromRow($xpath, $row);

            if ($match !== null) {
                $matches[] = $match;
            }
        }

        return $matches;
    }

    /**
     * Parse ranking table HTML from click-tt.de.
     *
     * Extracts league standings from the groupPage HTML. The ranking table
     * typically contains: position, team name, matches played, wins, draws, losses, points.
     *
     * @param string $html Raw HTML content of the ranking/group page
     * @return array<int, array{position: int, team_name: string, matches: int, wins: int, draws: int, losses: int, points: string}>
     */
    public function parseRankingTable(string $html): array
    {
        $doc = $this->loadHtml($html);

        if ($doc === null) {
            return [];
        }

        $xpath = new \DOMXPath($doc);
        $ranking = [];

        // click-tt.de ranking tables use "result-set" class or standard table layout
        $rows = $xpath->query(
            '//table[contains(@class, "result-set")]//tbody//tr'
            . '|//table[contains(@class, "table")]//tbody//tr'
        );

        if ($rows === false || $rows->length === 0) {
            return [];
        }

        foreach ($rows as $row) {
            $cells = $xpath->query('.//td', $row);

            if ($cells === false || $cells->length < 7) {
                continue;
            }

            $posText = trim($cells->item(0)?->textContent ?? '');
            $teamName = trim($cells->item(1)?->textContent ?? '');
            $matchesText = trim($cells->item(2)?->textContent ?? '');
            $winsText = trim($cells->item(3)?->textContent ?? '');
            $drawsText = trim($cells->item(4)?->textContent ?? '');
            $lossesText = trim($cells->item(5)?->textContent ?? '');
            $pointsText = trim($cells->item(6)?->textContent ?? '');

            // Position must be numeric
            if (!is_numeric($posText) || $teamName === '') {
                continue;
            }

            $ranking[] = [
                'position' => (int) $posText,
                'team_name' => $teamName,
                'matches' => (int) $matchesText,
                'wins' => (int) $winsText,
                'draws' => (int) $drawsText,
                'losses' => (int) $lossesText,
                'points' => $pointsText,
            ];
        }

        return $ranking;
    }

    /**
     * Parse clubMeetings response to extract schedule entries for a specific team.
     *
     * Filters the match table rows by team name and returns structured schedule data.
     * A row matches if the team name appears as the home team or the guest team.
     *
     * @param string $html Raw HTML content of the clubMeetings response
     * @param string $teamName The team name to filter by (partial match, case-insensitive)
     * @return array<int, array{match_date: string, match_time: ?string, home_team: string, guest_team: string, result: ?string}>
     */
    public function parseScheduleForTeam(string $html, string $teamName): array
    {
        $doc = $this->loadHtml($html);

        if ($doc === null) {
            return [];
        }

        $xpath = new \DOMXPath($doc);
        $matches = [];

        // click-tt.de clubMeetings response uses "result-set" class tables
        $rows = $xpath->query(
            '//table[contains(@class, "result-set")]//tbody//tr'
            . '|//table[contains(@class, "table")]//tbody//tr[td]'
        );

        if ($rows === false || $rows->length === 0) {
            return [];
        }

        $teamNameLower = mb_strtolower(trim($teamName));

        foreach ($rows as $row) {
            $cells = $xpath->query('.//td', $row);

            if ($cells === false || $cells->length < 4) {
                continue;
            }

            // clubMeetings table typically has columns:
            // Date | Time | Home Team | Guest Team | Result | ...
            $dateText = trim($cells->item(0)?->textContent ?? '');
            $timeText = trim($cells->item(1)?->textContent ?? '');
            $homeTeam = trim($cells->item(2)?->textContent ?? '');
            $guestTeam = trim($cells->item(3)?->textContent ?? '');
            $result = $cells->length >= 5 ? trim($cells->item(4)?->textContent ?? '') : '';

            // If the second cell doesn't look like a time, adjust column offsets
            $matchDate = $this->parseDate($dateText);
            $matchTime = $this->parseTime($timeText);

            if ($matchDate === null) {
                // Try combined date/time in first cell
                $matchDate = $this->parseDate($dateText);
                $matchTime = $this->parseTime($dateText);

                if ($matchDate === null) {
                    continue;
                }

                // Shift columns: col0=date(+time), col1=home, col2=away, col3=result
                $homeTeam = $timeText; // was reading col1
                $guestTeam = $homeTeam; // need to re-read
                // Re-read from correct offsets
                $homeTeam = trim($cells->item(1)?->textContent ?? '');
                $guestTeam = trim($cells->item(2)?->textContent ?? '');
                $result = $cells->length >= 4 ? trim($cells->item(3)?->textContent ?? '') : '';
            }

            if ($homeTeam === '' || $guestTeam === '') {
                continue;
            }

            // Filter by team name (case-insensitive partial match)
            $homeTeamLower = mb_strtolower($homeTeam);
            $guestTeamLower = mb_strtolower($guestTeam);

            if (!str_contains($homeTeamLower, $teamNameLower) && !str_contains($guestTeamLower, $teamNameLower)) {
                continue;
            }

            $matches[] = [
                'match_date' => $matchDate,
                'match_time' => $matchTime,
                'home_team' => $homeTeam,
                'guest_team' => $guestTeam,
                'result' => $result !== '' ? $result : null,
            ];
        }

        return $matches;
    }

    /**
     * Determine if a championship name indicates a cup/Pokal competition.
     *
     * @param string $championshipName The championship name to check
     * @return bool True if the name indicates a cup competition
     */
    public function isCupCompetition(string $championshipName): bool
    {
        $lower = mb_strtolower($championshipName);

        $cupIndicators = ['pokal', 'cup', 'bezirkspokal', 'kreispokal', 'verbandspokal'];

        foreach ($cupIndicators as $indicator) {
            if (str_contains($lower, $indicator)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse position notation "X.Y" into team number and position.
     *
     * The notation "X.Y" encodes a player's team assignment where X is the team number
     * and Y is the player's position within that team. Both X and Y must be positive integers.
     *
     * @param string $notation The position notation string in "X.Y" format
     * @return array{teamNumber: int, position: int}
     * @throws \InvalidArgumentException If the notation does not match the expected format
     */
    public function parsePositionNotation(string $notation): array
    {
        if (!preg_match('/^(\d+)\.(\d+)$/', trim($notation), $matches)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid position notation "%s": expected format "X.Y" with positive integers', $notation)
            );
        }

        $teamNumber = (int) $matches[1];
        $position = (int) $matches[2];

        if ($teamNumber < 1 || $position < 1) {
            throw new \InvalidArgumentException(
                sprintf('Invalid position notation "%s": both team number and position must be positive integers', $notation)
            );
        }

        return [
            'teamNumber' => $teamNumber,
            'position' => $position,
        ];
    }

    /**
     * Parse a click-tt.de URL to extract federation, club ID, and action parameters.
     *
     * Supports nuLigaTTDE URL patterns like:
     * - https://battv.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubPools?club=6658&...
     * - https://battv.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubTeams?club=6658&championship=...
     *
     * Also supports ClickTTVBW patterns:
     * - https://battv.click-tt.de/cgi-bin/WebObjects/ClickTTVBW.woa/wa/clubSearch?...
     *
     * @param string $url The click-tt.de URL to parse
     * @return array{federation: string, clubId: int, action: string, params: array<string, string>}
     * @throws \InvalidArgumentException If URL is not a valid click-tt.de URL
     */
    public function parseClickTtUrl(string $url): array
    {
        $url = trim($url);

        if ($url === '') {
            throw new \InvalidArgumentException('URL must not be empty.');
        }

        // Validate it's a click-tt.de domain
        $parsed = parse_url($url);

        if ($parsed === false || !isset($parsed['host'])) {
            throw new \InvalidArgumentException('Invalid URL format: ' . $url);
        }

        $host = $parsed['host'];

        // Extract federation from subdomain (e.g., "battv" from "battv.click-tt.de")
        if (!preg_match('/^([a-zA-Z]+)\.click-tt\.de$/', $host, $hostMatch)) {
            throw new \InvalidArgumentException(
                'Not a valid click-tt.de URL. Expected format: https://{federation}.click-tt.de/...'
            );
        }

        $federation = $hostMatch[1];

        // Extract the action from the path
        // Path pattern: /cgi-bin/WebObjects/{app}.woa/wa/{action}
        $path = $parsed['path'] ?? '';
        $action = '';

        if (preg_match('#/wa/(\w+)$#', $path, $pathMatch)) {
            $action = $pathMatch[1];
        }

        if ($action === '') {
            throw new \InvalidArgumentException(
                'Could not extract action from URL path. Expected pattern: .../wa/{action}'
            );
        }

        // Parse query parameters
        $queryString = $parsed['query'] ?? '';
        $params = [];
        parse_str($queryString, $params);

        // Extract club ID from query params
        $clubId = 0;

        if (isset($params['club']) && is_numeric($params['club'])) {
            $clubId = (int) $params['club'];
        }

        return [
            'federation' => $federation,
            'clubId' => $clubId,
            'action' => $action,
            'params' => array_map(fn($v) => is_array($v) ? implode(',', $v) : (string) $v, $params),
        ];
    }

    // ---------------------------------------------------------------
    // Internal helpers
    // ---------------------------------------------------------------

    /**
     * Load HTML into a DOMDocument, suppressing parser warnings.
     */
    private function loadHtml(string $html): ?\DOMDocument
    {
        if (trim($html) === '') {
            return null;
        }

        $doc = new \DOMDocument();
        @$doc->loadHTML($html, \LIBXML_NOERROR | \LIBXML_NOWARNING);

        return $doc;
    }

    /**
     * Normalize a URL (ensure it is absolute for click-tt.de).
     */
    private function normalizeUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            return 'https://www.click-tt.de' . $url;
        }

        return 'https://www.click-tt.de/' . $url;
    }

    /**
     * Check if a string looks like a season name (e.g., "2019/20", "2023/24").
     */
    private function looksLikeSeasonName(string $text): bool
    {
        return (bool) preg_match('/\d{4}\/\d{2}/', $text);
    }

    /**
     * Check if text represents an age class header.
     */
    private function isAgeClassHeader(string $text): bool
    {
        $lowerText = mb_strtolower($text);
        $patterns = ['herren', 'damen', 'jugend', 'jungen', 'mädchen', 'schüler', 'senioren'];

        foreach ($patterns as $pattern) {
            if (str_contains($lowerText, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract team data from a table row.
     *
     * @return array{team_number: int, league: string, age_class: string}|null
     */
    private function extractTeamFromRow(\DOMXPath $xpath, \DOMNode $row, string $currentAgeClass): ?array
    {
        $cells = $xpath->query('.//td', $row);

        if ($cells === false || $cells->length < 2) {
            return null;
        }

        // click-tt typically has: Team Name | League/Staffel | (optional more columns)
        $teamText = trim($cells->item(0)?->textContent ?? '');
        $league = trim($cells->item(1)?->textContent ?? '');

        // Check if there's a specific age class column
        $ageClass = $currentAgeClass;

        if ($cells->length >= 3) {
            $possibleAgeClass = trim($cells->item(2)?->textContent ?? '');

            if ($this->isAgeClassHeader($possibleAgeClass)) {
                $ageClass = $possibleAgeClass;
            }
        }

        $teamNumber = $this->extractTeamNumber($teamText);

        if ($teamNumber === 0 || $league === '') {
            return null;
        }

        return [
            'team_number' => $teamNumber,
            'league' => $league,
            'age_class' => $ageClass,
        ];
    }

    /**
     * Extract age class label from a section heading.
     */
    private function extractAgeClassFromSection(\DOMXPath $xpath, \DOMNode $section): string
    {
        $heading = $xpath->query('.//h2|.//h3|.//h4|.//*[contains(@class, "header")]', $section);

        if ($heading !== false && $heading->length > 0) {
            $text = trim($heading->item(0)?->textContent ?? '');

            if ($this->isAgeClassHeader($text)) {
                return $text;
            }
        }

        return 'Herren';
    }

    /**
     * Try to extract league information from a node or its context.
     */
    private function extractLeagueFromNode(\DOMXPath $xpath, \DOMNode $node): string
    {
        // Look for league info in adjacent elements or data attributes
        $leagueNode = $xpath->query(
            './/span[contains(@class, "liga") or contains(@class, "league") or contains(@class, "staffel")]',
            $node
        );

        if ($leagueNode !== false && $leagueNode->length > 0) {
            return trim($leagueNode->item(0)?->textContent ?? '');
        }

        // Try sibling cells in a table row context
        $sibling = $node->nextSibling;

        while ($sibling !== null) {
            if ($sibling->nodeType === \XML_ELEMENT_NODE) {
                $text = trim($sibling->textContent ?? '');

                if ($text !== '' && $this->looksLikeLeagueName($text)) {
                    return $text;
                }
            }

            $sibling = $sibling->nextSibling;
        }

        return '';
    }

    /**
     * Check if text looks like a league name (contains common league terms).
     */
    private function looksLikeLeagueName(string $text): bool
    {
        $lowerText = mb_strtolower($text);
        $patterns = ['liga', 'klasse', 'kreisliga', 'bezirksliga', 'oberliga', 'verbandsliga', 'landesliga'];

        foreach ($patterns as $pattern) {
            if (str_contains($lowerText, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract a player name from a table row.
     */
    private function extractPlayerNameFromRow(\DOMXPath $xpath, \DOMNode $row): string
    {
        $cells = $xpath->query('.//td', $row);

        if ($cells === false || $cells->length === 0) {
            return '';
        }

        // click-tt format: typically position number in first cell, name in second
        $firstCell = trim($cells->item(0)?->textContent ?? '');

        if (is_numeric($firstCell) && $cells->length >= 2) {
            $nameCell = trim($cells->item(1)?->textContent ?? '');

            if ($nameCell !== '' && !$this->isHeaderOrNavText($nameCell)) {
                return $nameCell;
            }
        }

        // Fallback: first non-numeric, non-header cell
        for ($i = 0; $i < $cells->length; $i++) {
            $cellText = trim($cells->item($i)?->textContent ?? '');

            if ($cellText !== '' && !is_numeric($cellText) && !$this->isHeaderOrNavText($cellText)) {
                return $cellText;
            }
        }

        return '';
    }

    /**
     * Extract a match entry from a table row.
     *
     * click-tt.de format typically: Date | Time | Home | Away | Result | Venue
     *
     * @return array{match_date: string, match_time: ?string, opponent: string, venue: string, home_away: int, result: ?string}|null
     */
    private function extractMatchFromRow(\DOMXPath $xpath, \DOMNode $row): ?array
    {
        $cells = $xpath->query('.//td', $row);

        if ($cells === false || $cells->length < 4) {
            return null;
        }

        // click-tt may combine date+time in one cell or separate them
        $firstCell = trim($cells->item(0)?->textContent ?? '');
        $offset = 0;

        // Check if first cell contains both date and time
        $matchDate = $this->parseDate($firstCell);

        if ($matchDate !== null) {
            $matchTime = $this->parseTime($firstCell);

            // If second cell looks like a time, use it
            if ($matchTime === null && $cells->length >= 5) {
                $secondCell = trim($cells->item(1)?->textContent ?? '');
                $matchTime = $this->parseTime($secondCell);

                if ($matchTime !== null) {
                    $offset = 1;
                }
            }
        } else {
            return null;
        }

        $homeTeam = trim($cells->item(1 + $offset)?->textContent ?? '');
        $awayTeam = trim($cells->item(2 + $offset)?->textContent ?? '');
        $result = (3 + $offset) < $cells->length ? trim($cells->item(3 + $offset)?->textContent ?? '') : '';
        $venue = (4 + $offset) < $cells->length ? trim($cells->item(4 + $offset)?->textContent ?? '') : '';

        // Determine home/away
        $homeAway = 1;
        $opponent = $awayTeam;

        if ($homeTeam === '' && $awayTeam === '') {
            return null;
        }

        if ($homeTeam !== '' && $awayTeam === '') {
            $opponent = $homeTeam;
            $homeAway = 2;
        }

        if ($opponent === '') {
            return null;
        }

        return [
            'match_date' => $matchDate,
            'match_time' => $matchTime,
            'opponent' => mb_substr($opponent, 0, 150),
            'venue' => mb_substr($venue, 0, 200),
            'home_away' => $homeAway,
            'result' => $result !== '' ? mb_substr($result, 0, 20) : null,
        ];
    }

    /**
     * Extract a team number from a team name string.
     * Supports Arabic numerals and Roman numerals I–X.
     */
    private function extractTeamNumber(string $teamName): int
    {
        // Try Arabic numerals at end
        if (preg_match('/\b(\d+)\s*$/', $teamName, $matches)) {
            return (int) $matches[1];
        }

        // Try Roman numerals at end
        $romanMap = [
            'I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5,
            'VI' => 6, 'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10,
        ];

        if (preg_match('/\b([IVX]+)\s*$/', $teamName, $matches)) {
            $roman = $matches[1];

            if (isset($romanMap[$roman])) {
                return $romanMap[$roman];
            }
        }

        // If the text contains only a number, use it
        if (preg_match('/^\s*(\d+)\s*$/', $teamName, $matches)) {
            return (int) $matches[1];
        }

        // Default to 1 if a team name is present but no number
        if (trim($teamName) !== '') {
            return 1;
        }

        return 0;
    }

    /**
     * Parse a date string into YYYY-MM-DD format.
     */
    private function parseDate(string $dateText): ?string
    {
        // Try DD.MM.YYYY (German format — most common on click-tt.de)
        if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})/', $dateText, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }

        // Try DD.MM.YY (short German format)
        if (preg_match('/(\d{2})\.(\d{2})\.(\d{2})/', $dateText, $matches)) {
            $year = (int) $matches[3] >= 70 ? '19' . $matches[3] : '20' . $matches[3];

            return $year . '-' . $matches[2] . '-' . $matches[1];
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
    private function parseTime(string $timeText): ?string
    {
        if (preg_match('/(\d{1,2}):(\d{2})/', $timeText, $matches)) {
            return str_pad($matches[1], 2, '0', \STR_PAD_LEFT) . ':' . $matches[2] . ':00';
        }

        // click-tt sometimes uses "Uhr" suffix
        if (preg_match('/(\d{1,2})\.(\d{2})\s*Uhr/i', $timeText, $matches)) {
            return str_pad($matches[1], 2, '0', \STR_PAD_LEFT) . ':' . $matches[2] . ':00';
        }

        return null;
    }

    /**
     * Check if text appears to be a navigation or header element rather than player data.
     */
    private function isHeaderOrNavText(string $text): bool
    {
        $lowerText = mb_strtolower($text);
        $headerPatterns = ['name', 'spieler', 'position', 'nr.', '#', 'mannschaft', 'team', 'platz', 'rang'];

        foreach ($headerPatterns as $pattern) {
            if ($lowerText === $pattern) {
                return true;
            }
        }

        return false;
    }
}
