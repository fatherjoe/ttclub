<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property 39: Schedule entry completeness from scraped data
 *
 * For any parsed schedule page, every extracted match entry must contain a non-null
 * date, opponent name, venue, and home/away indicator. The number of schedule entries
 * created must equal the number of match entries in the source HTML.
 *
 * **Validates: Requirements 13.5**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class ScheduleEntryCompletenessPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Generate a schedule HTML table from random match data.
     *
     * Each row contains: date, home team, away team, result, venue.
     * This mirrors the click-tt.de schedule table format that ClickTtParser expects.
     *
     * @param array<int, array{date: string, home: string, away: string, venue: string, result: string}> $matches
     * @return string The generated HTML
     */
    private function generateScheduleHtml(array $matches): string
    {
        $rows = '';
        foreach ($matches as $match) {
            $rows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars($match['date']),
                htmlspecialchars($match['home']),
                htmlspecialchars($match['away']),
                htmlspecialchars($match['result']),
                htmlspecialchars($match['venue']),
            );
        }

        return '<html><body><table class="result-set"><tbody>' . $rows . '</tbody></table></body></html>';
    }

    /**
     * Simulate the schedule import logic from HistoricalImportService.
     *
     * Takes parsed schedule entries (as returned by parseSchedule) and applies
     * the same validation logic as HistoricalImportService: entries with empty
     * match_date or empty opponent are skipped.
     *
     * @param array<int, array{match_date: string, match_time: ?string, opponent: string, venue: string, home_away: int, result: ?string}> $parsedEntries
     * @return array<int, array{match_date: string, match_time: ?string, opponent: string, venue: string, home_away: int, result: ?string}>
     */
    private function simulateScheduleImport(array $parsedEntries): array
    {
        $created = [];

        foreach ($parsedEntries as $entry) {
            $matchDate = $entry['match_date'] ?? '';
            $opponent = trim($entry['opponent'] ?? '');

            // HistoricalImportService skips entries with empty date or opponent
            if ($matchDate === '' || $opponent === '') {
                continue;
            }

            $created[] = [
                'match_date' => $matchDate,
                'match_time' => $entry['match_time'] ?? null,
                'opponent' => $opponent,
                'venue' => trim($entry['venue'] ?? ''),
                'home_away' => (int) ($entry['home_away'] ?? 1),
                'result' => $entry['result'] ?? null,
            ];
        }

        return $created;
    }

    /**
     * Property 39: Every schedule entry from a well-formed source has all required
     * fields (match_date, opponent, venue, home_away) populated and non-null.
     *
     * Generate random schedule entries that represent valid parsed data (as produced
     * by the parser); verify that after import simulation, all required fields are
     * present and non-null, and the count matches the source.
     *
     * **Validates: Requirements 13.5**
     */
    public function testScheduleEntriesHaveAllRequiredFields(): void
    {
        $opponents = ['TTC Waldheim', 'SV Rotation', 'TSV Bietigheim', 'SC Grün-Weiß', 'VfB Stuttgart II'];
        $venues = ['Sporthalle Am Park', 'Turnhalle Mitte', 'Sportforum', 'Vereinsheim Nord'];
        $homeAwayValues = [1, 2]; // 1=home, 2=away

        $this
            ->forAll(
                Generators::choose(1, 20), // number of schedule entries
                Generators::seq(Generators::choose(0, count($opponents) - 1)),  // opponent indices
                Generators::seq(Generators::choose(0, count($venues) - 1)),     // venue indices
                Generators::seq(Generators::choose(0, 1)),                       // home_away indices
                Generators::seq(Generators::choose(1, 28)),                      // days
                Generators::seq(Generators::choose(1, 12))                       // months
            )
            ->then(function (
                int $entryCount,
                array $opponentIdxs,
                array $venueIdxs,
                array $homeAwayIdxs,
                array $days,
                array $months
            ) use ($opponents, $venues, $homeAwayValues): void {
                // Build parsed schedule entries (simulating what the parser would return)
                $parsedEntries = [];

                for ($i = 0; $i < $entryCount; $i++) {
                    $opIdx = $opponentIdxs[$i] ?? 0;
                    $venIdx = $venueIdxs[$i] ?? 0;
                    $haIdx = $homeAwayIdxs[$i] ?? 0;
                    $day = $days[$i] ?? 1;
                    $month = $months[$i] ?? 1;

                    $matchDate = sprintf('2023-%02d-%02d', $month, $day);

                    $parsedEntries[] = [
                        'match_date' => $matchDate,
                        'match_time' => '19:30',
                        'opponent' => $opponents[$opIdx],
                        'venue' => $venues[$venIdx],
                        'home_away' => $homeAwayValues[$haIdx],
                        'result' => null,
                    ];
                }

                // Simulate the import
                $createdEntries = $this->simulateScheduleImport($parsedEntries);

                // Since all entries have valid date + opponent, count must match
                $this->assertCount(
                    $entryCount,
                    $createdEntries,
                    sprintf(
                        'Expected %d schedule entries created from %d source entries (all valid), got %d',
                        $entryCount,
                        $entryCount,
                        count($createdEntries)
                    )
                );

                // Verify all required fields are non-null and populated
                foreach ($createdEntries as $idx => $entry) {
                    $this->assertNotEmpty(
                        $entry['match_date'],
                        "Entry $idx: match_date must not be empty"
                    );
                    $this->assertNotEmpty(
                        $entry['opponent'],
                        "Entry $idx: opponent must not be empty"
                    );
                    $this->assertArrayHasKey(
                        'venue',
                        $entry,
                        "Entry $idx: venue key must exist"
                    );
                    $this->assertNotNull(
                        $entry['venue'],
                        "Entry $idx: venue must not be null"
                    );
                    $this->assertArrayHasKey(
                        'home_away',
                        $entry,
                        "Entry $idx: home_away key must exist"
                    );
                    $this->assertContains(
                        $entry['home_away'],
                        [1, 2],
                        "Entry $idx: home_away must be 1 (home) or 2 (away)"
                    );
                }
            });
    }

    /**
     * Property 39: Count of created schedule entries equals count of valid entries
     * in the source (entries with empty date or opponent are excluded).
     *
     * Generate a mix of valid and invalid entries; verify the created count matches
     * only the valid subset.
     *
     * **Validates: Requirements 13.5**
     */
    public function testCreatedCountMatchesValidSourceEntries(): void
    {
        $opponents = ['TTC Waldheim', 'SV Rotation', 'TSV Bietigheim'];
        $venues = ['Sporthalle Am Park', 'Turnhalle Mitte'];

        $this
            ->forAll(
                Generators::choose(0, 15),  // number of valid entries
                Generators::choose(0, 10),  // number of invalid entries (empty date or opponent)
                Generators::seq(Generators::choose(0, count($opponents) - 1)),
                Generators::seq(Generators::choose(0, count($venues) - 1)),
                Generators::seq(Generators::bool()) // true = empty date, false = empty opponent
            )
            ->then(function (
                int $validCount,
                int $invalidCount,
                array $opponentIdxs,
                array $venueIdxs,
                array $invalidTypes
            ) use ($opponents, $venues): void {
                $parsedEntries = [];

                // Add valid entries
                for ($i = 0; $i < $validCount; $i++) {
                    $opIdx = $opponentIdxs[$i] ?? 0;
                    $venIdx = $venueIdxs[$i] ?? 0;

                    $parsedEntries[] = [
                        'match_date' => sprintf('2023-%02d-%02d', ($i % 12) + 1, ($i % 28) + 1),
                        'match_time' => '20:00',
                        'opponent' => $opponents[$opIdx],
                        'venue' => $venues[$venIdx],
                        'home_away' => ($i % 2) + 1,
                        'result' => null,
                    ];
                }

                // Add invalid entries (missing date or opponent)
                for ($i = 0; $i < $invalidCount; $i++) {
                    $emptyDate = $invalidTypes[$i] ?? true;
                    $venIdx = $venueIdxs[$i] ?? 0;

                    $parsedEntries[] = [
                        'match_date' => $emptyDate ? '' : '2023-05-15',
                        'match_time' => null,
                        'opponent' => $emptyDate ? $opponents[0] : '',
                        'venue' => $venues[$venIdx],
                        'home_away' => 1,
                        'result' => null,
                    ];
                }

                // Shuffle to mix valid and invalid
                shuffle($parsedEntries);

                // Simulate the import
                $createdEntries = $this->simulateScheduleImport($parsedEntries);

                // Count must match only the valid entries
                $this->assertCount(
                    $validCount,
                    $createdEntries,
                    sprintf(
                        'Expected %d entries created (from %d valid + %d invalid source entries), got %d',
                        $validCount,
                        $validCount,
                        $invalidCount,
                        count($createdEntries)
                    )
                );
            });
    }

    /**
     * Property 39: End-to-end parsing from generated HTML produces entries with
     * all required fields populated and count matches source rows.
     *
     * Uses the actual ClickTtParser to parse generated schedule HTML and verifies
     * that output entries satisfy completeness requirements.
     *
     * **Validates: Requirements 13.5**
     */
    public function testParsedHtmlProducesCompleteEntries(): void
    {
        $opponents = ['TTC Waldheim', 'SV Rotation', 'TSV Bietigheim', 'SC Grün-Weiß'];
        $venues = ['Sporthalle Am Park', 'Turnhalle Mitte', 'Sportforum'];

        $this
            ->forAll(
                Generators::choose(1, 10), // number of match rows
                Generators::seq(Generators::choose(0, count($opponents) - 1)),
                Generators::seq(Generators::choose(0, count($venues) - 1)),
                Generators::seq(Generators::choose(1, 28)), // days
                Generators::seq(Generators::choose(1, 12))  // months
            )
            ->then(function (
                int $matchCount,
                array $opponentIdxs,
                array $venueIdxs,
                array $days,
                array $months
            ) use ($opponents, $venues): void {
                // Generate well-formed match rows for HTML
                $matchData = [];
                for ($i = 0; $i < $matchCount; $i++) {
                    $opIdx = $opponentIdxs[$i] ?? 0;
                    $venIdx = $venueIdxs[$i] ?? 0;
                    $day = $days[$i] ?? 1;
                    $month = $months[$i] ?? 9;

                    $matchData[] = [
                        'date' => sprintf('%02d.%02d.2023', $day, $month),
                        'home' => 'TTV Eigener Verein',
                        'away' => $opponents[$opIdx],
                        'result' => '',
                        'venue' => $venues[$venIdx],
                    ];
                }

                $html = $this->generateScheduleHtml($matchData);

                // Use the actual parser
                $parser = new \Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtParser();
                $parsedEntries = $parser->parseSchedule($html);

                // Simulate the import with the parsed entries
                $createdEntries = $this->simulateScheduleImport($parsedEntries);

                // Every created entry must have required fields non-null
                foreach ($createdEntries as $idx => $entry) {
                    $this->assertNotEmpty(
                        $entry['match_date'],
                        "Parsed entry $idx: match_date must not be empty"
                    );
                    $this->assertNotEmpty(
                        $entry['opponent'],
                        "Parsed entry $idx: opponent must not be empty"
                    );
                    $this->assertNotNull(
                        $entry['venue'],
                        "Parsed entry $idx: venue must not be null"
                    );
                    $this->assertContains(
                        $entry['home_away'],
                        [1, 2],
                        "Parsed entry $idx: home_away must be 1 or 2"
                    );
                }

                // Count of created entries must equal the source row count
                // (all generated rows are valid, so count must match)
                $this->assertCount(
                    $matchCount,
                    $createdEntries,
                    sprintf(
                        'Expected %d schedule entries from %d HTML rows, got %d',
                        $matchCount,
                        $matchCount,
                        count($createdEntries)
                    )
                );
            });
    }
}
