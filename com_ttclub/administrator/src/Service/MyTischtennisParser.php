<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Service;

/**
 * Parser implementation for mytischtennis.de HTML structure.
 *
 * Handles the specific DOM layout used by mytischtennis.de for season archives,
 * team listings, rosters, and match schedules. Uses DOMDocument/DOMXPath for extraction.
 */
class MyTischtennisParser implements SeasonParserInterface
{
    /**
     * Parse season archive HTML from mytischtennis.de.
     *
     * mytischtennis.de lists past seasons as links in a navigation or dropdown element,
     * typically within a select element or an unordered list with season-specific URLs.
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

        // Strategy 1: Look for a season selector (select/option elements)
        $options = $xpath->query(
            '//select[contains(@class, "saison") or contains(@name, "saison") or contains(@id, "saison")]//option'
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

        // Strategy 2: Look for season links in navigation/list elements
        $links = $xpath->query(
            '//ul[contains(@class, "saison") or contains(@class, "season")]//a'
            . '|//nav[contains(@class, "saison") or contains(@class, "season")]//a'
            . '|//div[contains(@class, "saison") or contains(@class, "season")]//a'
        );

        if ($links !== false && $links->length > 0) {
            foreach ($links as $link) {
                $name = trim($link->textContent ?? '');
                $url = trim($link->getAttribute('href') ?? '');

                if ($name !== '' && $url !== '') {
                    $seasons[] = ['name' => $name, 'url' => $this->normalizeUrl($url)];
                }
            }

            if ($seasons !== []) {
                return $seasons;
            }
        }

        // Strategy 3: Fallback — look for any links containing season-like patterns (YYYY/YY)
        $allLinks = $xpath->query('//a[contains(@href, "saison") or contains(@href, "season")]');

        if ($allLinks !== false) {
            foreach ($allLinks as $link) {
                $name = trim($link->textContent ?? '');
                $url = trim($link->getAttribute('href') ?? '');

                if ($name !== '' && $url !== '' && $this->looksLikeSeasonName($name)) {
                    $seasons[] = ['name' => $name, 'url' => $this->normalizeUrl($url)];
                }
            }
        }

        return $seasons;
    }

    /**
     * Parse team listings from a mytischtennis.de season page.
     *
     * Teams are typically listed in a table with columns for team number/name,
     * league, and age class/category.
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

        // Strategy 1: Table-based team listing
        $rows = $xpath->query(
            '//table[contains(@class, "mannschaft") or contains(@class, "team")]//tbody//tr'
        );

        if ($rows !== false && $rows->length > 0) {
            foreach ($rows as $row) {
                $team = $this->extractTeamFromTableRow($xpath, $row);

                if ($team !== null) {
                    $teams[] = $team;
                }
            }

            if ($teams !== []) {
                return $teams;
            }
        }

        // Strategy 2: Section/div-based team listing
        $sections = $xpath->query(
            '//div[contains(@class, "mannschaft")]|//section[contains(@class, "team")]'
        );

        if ($sections !== false && $sections->length > 0) {
            foreach ($sections as $section) {
                $team = $this->extractTeamFromSection($xpath, $section);

                if ($team !== null) {
                    $teams[] = $team;
                }
            }
        }

        return $teams;
    }

    /**
     * Parse a team roster page from mytischtennis.de.
     *
     * Player names appear in table rows or list items, typically in "Last, First" format.
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

        // Strategy 1: Table-based roster (most common on mytischtennis.de)
        $rows = $xpath->query(
            '//table[contains(@class, "table") or contains(@class, "spieler") or contains(@class, "roster")]//tbody//tr'
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

        // Strategy 2: List-based roster
        $items = $xpath->query(
            '//ul[contains(@class, "spieler") or contains(@class, "roster") or contains(@class, "player")]//li'
            . '|//div[contains(@class, "spieler") or contains(@class, "roster")]//li'
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
     * Parse a schedule page from mytischtennis.de.
     *
     * Match entries appear in table rows with date, time, home team, away team, and result columns.
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

        // Look for schedule table rows
        $rows = $xpath->query(
            '//table[contains(@class, "table") or contains(@class, "spiele") or contains(@class, "schedule")]//tbody//tr'
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
     * Normalize a URL (ensure it is absolute for mytischtennis.de).
     */
    private function normalizeUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            return 'https://www.mytischtennis.de' . $url;
        }

        return 'https://www.mytischtennis.de/' . $url;
    }

    /**
     * Check if a string looks like a season name (e.g., "2019/20", "2023/24").
     */
    private function looksLikeSeasonName(string $text): bool
    {
        return (bool) preg_match('/\d{4}\/\d{2}/', $text);
    }

    /**
     * Extract team data from a table row element.
     *
     * @return array{team_number: int, league: string, age_class: string}|null
     */
    private function extractTeamFromTableRow(\DOMXPath $xpath, \DOMNode $row): ?array
    {
        $cells = $xpath->query('.//td', $row);

        if ($cells === false || $cells->length < 2) {
            return null;
        }

        $teamText = trim($cells->item(0)?->textContent ?? '');
        $league = trim($cells->item(1)?->textContent ?? '');
        $ageClass = $cells->length >= 3 ? trim($cells->item(2)?->textContent ?? '') : 'Herren';

        $teamNumber = $this->extractTeamNumber($teamText);

        if ($teamNumber === 0 || $league === '') {
            return null;
        }

        return [
            'team_number' => $teamNumber,
            'league' => $league,
            'age_class' => $ageClass !== '' ? $ageClass : 'Herren',
        ];
    }

    /**
     * Extract team data from a div/section element.
     *
     * @return array{team_number: int, league: string, age_class: string}|null
     */
    private function extractTeamFromSection(\DOMXPath $xpath, \DOMNode $section): ?array
    {
        // Extract team number from heading
        $heading = $xpath->query('.//h2|.//h3|.//h4', $section);
        $teamText = '';

        if ($heading !== false && $heading->length > 0) {
            $teamText = trim($heading->item(0)?->textContent ?? '');
        }

        $teamNumber = $this->extractTeamNumber($teamText);

        if ($teamNumber === 0) {
            return null;
        }

        // Extract league from a sub-element or data attribute
        $leagueNode = $xpath->query(
            './/*[contains(@class, "liga") or contains(@class, "league")]',
            $section
        );
        $league = '';

        if ($leagueNode !== false && $leagueNode->length > 0) {
            $league = trim($leagueNode->item(0)?->textContent ?? '');
        }

        // Extract age class
        $ageClassNode = $xpath->query(
            './/*[contains(@class, "alter") or contains(@class, "age") or contains(@class, "klasse")]',
            $section
        );
        $ageClass = 'Herren';

        if ($ageClassNode !== false && $ageClassNode->length > 0) {
            $ageClass = trim($ageClassNode->item(0)?->textContent ?? '');
        }

        return [
            'team_number' => $teamNumber,
            'league' => $league,
            'age_class' => $ageClass,
        ];
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

        // On mytischtennis.de, player name is typically in the first or second cell
        // Check for "Last, First" pattern in first cell
        $firstCell = trim($cells->item(0)?->textContent ?? '');

        if ($firstCell !== '' && !is_numeric($firstCell) && !$this->isHeaderOrNavText($firstCell)) {
            return $firstCell;
        }

        // If first cell is a number (position), try second cell
        if ($cells->length >= 2 && is_numeric($firstCell)) {
            $secondCell = trim($cells->item(1)?->textContent ?? '');

            if ($secondCell !== '') {
                return $secondCell;
            }
        }

        return '';
    }

    /**
     * Extract a match entry from a table row.
     *
     * @return array{match_date: string, match_time: ?string, opponent: string, venue: string, home_away: int, result: ?string}|null
     */
    private function extractMatchFromRow(\DOMXPath $xpath, \DOMNode $row): ?array
    {
        $cells = $xpath->query('.//td', $row);

        if ($cells === false || $cells->length < 4) {
            return null;
        }

        $dateText = trim($cells->item(0)?->textContent ?? '');
        $timeText = trim($cells->item(1)?->textContent ?? '');
        $homeTeam = trim($cells->item(2)?->textContent ?? '');
        $awayTeam = trim($cells->item(3)?->textContent ?? '');
        $result = $cells->length >= 5 ? trim($cells->item(4)?->textContent ?? '') : '';
        $venue = $cells->length >= 6 ? trim($cells->item(5)?->textContent ?? '') : '';

        // Parse date
        $matchDate = $this->parseDate($dateText);

        if ($matchDate === null) {
            return null;
        }

        // Parse time
        $matchTime = $this->parseTime($timeText);

        // Determine home/away (convention: our club is home if listed first)
        // For historical import we treat the first team as home
        $homeAway = 1; // default home
        $opponent = $awayTeam;

        if ($homeTeam === '' && $awayTeam === '') {
            return null;
        }

        // If only one team column present, treat as opponent
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
        // Try DD.MM.YYYY (German format)
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

        return null;
    }

    /**
     * Check if text appears to be a navigation or header element rather than player data.
     */
    private function isHeaderOrNavText(string $text): bool
    {
        $lowerText = mb_strtolower($text);
        $headerPatterns = ['name', 'spieler', 'position', 'nr.', '#', 'mannschaft', 'team'];

        foreach ($headerPatterns as $pattern) {
            if ($lowerText === $pattern) {
                return true;
            }
        }

        return false;
    }
}
