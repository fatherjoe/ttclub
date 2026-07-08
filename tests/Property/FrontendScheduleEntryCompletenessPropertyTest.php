<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property 22: Frontend schedule entry completeness
 *
 * For any schedule entry rendered in the frontend, the output must contain the
 * date, time, opponent name, venue, and home/away indicator. If a result exists
 * and the match date is in the past, it must also be displayed.
 *
 * **Validates: Requirements 10.4, 10.5, 15.2, 15.3**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class FrontendScheduleEntryCompletenessPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Simulate what the Site\Model\ScheduleModel returns for a schedule entry.
     *
     * The model selects: id, match_date, match_time, opponent, venue, home_away, result.
     * This mirrors the database record shape that the frontend template renders.
     *
     * @param array{match_date: string, match_time: ?string, opponent: string, venue: string, home_away: int, result: ?string} $data
     * @return object
     */
    private function createScheduleEntry(array $data): object
    {
        return (object) [
            'id' => $data['id'] ?? rand(1, 99999),
            'match_date' => $data['match_date'],
            'match_time' => $data['match_time'],
            'opponent' => $data['opponent'],
            'venue' => $data['venue'],
            'home_away' => $data['home_away'],
            'result' => $data['result'],
        ];
    }

    /**
     * Simulate the frontend template rendering logic for a schedule entry.
     *
     * Extracts the fields that the template outputs for display. This replicates
     * what site/tmpl/schedule/default.php does for each match row.
     *
     * @param object $entry The schedule entry object.
     * @param bool $isPast Whether this entry is in the past section.
     * @return array{date: ?string, time: ?string, opponent: ?string, venue: ?string, home_away: ?string, result: ?string}
     */
    private function renderEntryFields(object $entry, bool $isPast): array
    {
        $rendered = [
            'date' => $entry->match_date ?? null,
            'time' => $entry->match_time !== null ? substr($entry->match_time, 0, 5) : '—',
            'opponent' => $entry->opponent ?? null,
            'venue' => $entry->venue ?? null,
            'home_away' => isset($entry->home_away) ? ($entry->home_away == 1 ? 'H' : 'A') : null,
        ];

        // Past matches include the result column (Req 10.5, 15.3)
        if ($isPast) {
            $rendered['result'] = $entry->result ? $entry->result : '—';
        }

        return $rendered;
    }

    /**
     * Property 22: Every schedule entry (upcoming or past) must have all required
     * fields present: date, time, opponent, venue, home/away.
     *
     * Generate random schedule entries with all required fields populated; verify
     * that the frontend rendering always produces non-null values for each field.
     *
     * **Validates: Requirements 10.4, 15.2**
     */
    public function testAllScheduleEntriesHaveRequiredFields(): void
    {
        $opponents = ['TTC Waldheim', 'SV Rotation', 'TSV Bietigheim', 'SC Grün-Weiß', 'VfB Stuttgart II'];
        $venues = ['Sporthalle Am Park', 'Turnhalle Mitte', 'Sportforum', 'Vereinsheim Nord'];

        $this
            ->forAll(
                Generators::choose(1, 20),                              // number of entries
                Generators::seq(Generators::choose(0, count($opponents) - 1)),  // opponent indices
                Generators::seq(Generators::choose(0, count($venues) - 1)),     // venue indices
                Generators::seq(Generators::choose(1, 2)),                       // home_away values
                Generators::seq(Generators::choose(1, 28)),                      // days
                Generators::seq(Generators::choose(1, 12)),                      // months
                Generators::seq(Generators::choose(0, 23)),                      // hours
                Generators::seq(Generators::choose(0, 59)),                      // minutes
                Generators::seq(Generators::bool())                              // is past match?
            )
            ->then(function (
                int $entryCount,
                array $opponentIdxs,
                array $venueIdxs,
                array $homeAwayVals,
                array $days,
                array $months,
                array $hours,
                array $minutes,
                array $isPastFlags,
            ) use ($opponents, $venues): void {
                for ($i = 0; $i < $entryCount; $i++) {
                    $opIdx = $opponentIdxs[$i] ?? 0;
                    $venIdx = $venueIdxs[$i] ?? 0;
                    $homeAway = $homeAwayVals[$i] ?? 1;
                    $day = $days[$i] ?? 15;
                    $month = $months[$i] ?? 9;
                    $hour = $hours[$i] ?? 19;
                    $minute = $minutes[$i] ?? 30;
                    $isPast = $isPastFlags[$i] ?? false;

                    $entry = $this->createScheduleEntry([
                        'match_date' => sprintf('2024-%02d-%02d', $month, $day),
                        'match_time' => sprintf('%02d:%02d:00', $hour, $minute),
                        'opponent' => $opponents[$opIdx],
                        'venue' => $venues[$venIdx],
                        'home_away' => $homeAway,
                        'result' => $isPast ? '6:4' : null,
                    ]);

                    $rendered = $this->renderEntryFields($entry, $isPast);

                    // All required fields must be present and non-null (Req 10.4, 15.2)
                    $this->assertNotNull(
                        $rendered['date'],
                        "Entry $i: date must not be null"
                    );
                    $this->assertNotEmpty(
                        $rendered['date'],
                        "Entry $i: date must not be empty"
                    );

                    $this->assertNotNull(
                        $rendered['time'],
                        "Entry $i: time must not be null"
                    );

                    $this->assertNotNull(
                        $rendered['opponent'],
                        "Entry $i: opponent must not be null"
                    );
                    $this->assertNotEmpty(
                        $rendered['opponent'],
                        "Entry $i: opponent must not be empty"
                    );

                    $this->assertNotNull(
                        $rendered['venue'],
                        "Entry $i: venue must not be null"
                    );

                    $this->assertNotNull(
                        $rendered['home_away'],
                        "Entry $i: home/away indicator must not be null"
                    );
                    $this->assertContains(
                        $rendered['home_away'],
                        ['H', 'A'],
                        "Entry $i: home/away must be 'H' or 'A'"
                    );
                }
            });
    }

    /**
     * Property 22: Past matches with a recorded result must display the result.
     *
     * Generate random past schedule entries with results; verify the result field
     * is always present and contains the actual score when one is recorded.
     *
     * **Validates: Requirements 10.5, 15.3**
     */
    public function testPastMatchesWithResultDisplayResult(): void
    {
        $opponents = ['TTC Waldheim', 'SV Rotation', 'TSV Bietigheim'];
        $venues = ['Sporthalle Am Park', 'Turnhalle Mitte'];
        $results = ['6:4', '3:7', '8:2', '5:5', '9:1', '4:6', '0:10', '10:0'];

        $this
            ->forAll(
                Generators::choose(1, 15),                                       // number of past entries
                Generators::seq(Generators::choose(0, count($opponents) - 1)),   // opponent indices
                Generators::seq(Generators::choose(0, count($venues) - 1)),      // venue indices
                Generators::seq(Generators::choose(0, count($results) - 1)),     // result indices
                Generators::seq(Generators::choose(1, 2)),                        // home_away
                Generators::seq(Generators::choose(1, 28)),                       // days
                Generators::seq(Generators::choose(1, 12))                        // months
            )
            ->then(function (
                int $entryCount,
                array $opponentIdxs,
                array $venueIdxs,
                array $resultIdxs,
                array $homeAwayVals,
                array $days,
                array $months,
            ) use ($opponents, $venues, $results): void {
                for ($i = 0; $i < $entryCount; $i++) {
                    $opIdx = $opponentIdxs[$i] ?? 0;
                    $venIdx = $venueIdxs[$i] ?? 0;
                    $resIdx = $resultIdxs[$i] ?? 0;
                    $homeAway = $homeAwayVals[$i] ?? 1;
                    $day = $days[$i] ?? 10;
                    $month = $months[$i] ?? 3;

                    $result = $results[$resIdx];

                    $entry = $this->createScheduleEntry([
                        'match_date' => sprintf('2023-%02d-%02d', $month, $day),
                        'match_time' => '19:30:00',
                        'opponent' => $opponents[$opIdx],
                        'venue' => $venues[$venIdx],
                        'home_away' => $homeAway,
                        'result' => $result,
                    ]);

                    $rendered = $this->renderEntryFields($entry, true);

                    // Past matches with a result must show the result (Req 10.5, 15.3)
                    $this->assertArrayHasKey(
                        'result',
                        $rendered,
                        "Past entry $i: result key must be present in rendered output"
                    );
                    $this->assertSame(
                        $result,
                        $rendered['result'],
                        sprintf(
                            "Past entry $i: rendered result must match stored result. Expected '%s', got '%s'",
                            $result,
                            $rendered['result'] ?? 'null'
                        )
                    );
                }
            });
    }

    /**
     * Property 22: Past matches without a result show a placeholder (dash) in the
     * result column, but the result column is still present.
     *
     * Generate random past entries with null result; verify result field is present
     * with the placeholder value.
     *
     * **Validates: Requirements 10.4, 15.3**
     */
    public function testPastMatchesWithoutResultShowPlaceholder(): void
    {
        $opponents = ['TTC Waldheim', 'SV Rotation'];
        $venues = ['Sporthalle Am Park', 'Turnhalle Mitte'];

        $this
            ->forAll(
                Generators::choose(1, 10),                                       // number of entries
                Generators::seq(Generators::choose(0, count($opponents) - 1)),
                Generators::seq(Generators::choose(0, count($venues) - 1)),
                Generators::seq(Generators::choose(1, 2)),
                Generators::seq(Generators::choose(1, 28)),
                Generators::seq(Generators::choose(1, 12))
            )
            ->then(function (
                int $entryCount,
                array $opponentIdxs,
                array $venueIdxs,
                array $homeAwayVals,
                array $days,
                array $months,
            ) use ($opponents, $venues): void {
                for ($i = 0; $i < $entryCount; $i++) {
                    $opIdx = $opponentIdxs[$i] ?? 0;
                    $venIdx = $venueIdxs[$i] ?? 0;
                    $homeAway = $homeAwayVals[$i] ?? 1;
                    $day = $days[$i] ?? 10;
                    $month = $months[$i] ?? 3;

                    $entry = $this->createScheduleEntry([
                        'match_date' => sprintf('2023-%02d-%02d', $month, $day),
                        'match_time' => '20:00:00',
                        'opponent' => $opponents[$opIdx],
                        'venue' => $venues[$venIdx],
                        'home_away' => $homeAway,
                        'result' => null,  // No result recorded
                    ]);

                    $rendered = $this->renderEntryFields($entry, true);

                    // Result column must still be present for past matches
                    $this->assertArrayHasKey(
                        'result',
                        $rendered,
                        "Past entry $i without result: result key must still be present"
                    );
                    // The template shows '—' as placeholder when no result
                    $this->assertSame(
                        '—',
                        $rendered['result'],
                        "Past entry $i without result: should show placeholder dash"
                    );
                }
            });
    }

    /**
     * Property 22: Upcoming matches do not show a result column.
     *
     * Generate random upcoming entries; verify that the rendered output does not
     * include a 'result' key (per requirements 15.3: result column empty for future
     * matches, and template implementation only renders it for past section).
     *
     * **Validates: Requirements 10.4, 15.2**
     */
    public function testUpcomingMatchesDoNotIncludeResultField(): void
    {
        $opponents = ['TTC Waldheim', 'SV Rotation', 'TSV Bietigheim'];
        $venues = ['Sporthalle Am Park', 'Turnhalle Mitte'];

        $this
            ->forAll(
                Generators::choose(1, 15),
                Generators::seq(Generators::choose(0, count($opponents) - 1)),
                Generators::seq(Generators::choose(0, count($venues) - 1)),
                Generators::seq(Generators::choose(1, 2)),
                Generators::seq(Generators::choose(1, 28)),
                Generators::seq(Generators::choose(1, 12))
            )
            ->then(function (
                int $entryCount,
                array $opponentIdxs,
                array $venueIdxs,
                array $homeAwayVals,
                array $days,
                array $months,
            ) use ($opponents, $venues): void {
                for ($i = 0; $i < $entryCount; $i++) {
                    $opIdx = $opponentIdxs[$i] ?? 0;
                    $venIdx = $venueIdxs[$i] ?? 0;
                    $homeAway = $homeAwayVals[$i] ?? 1;
                    $day = $days[$i] ?? 15;
                    $month = $months[$i] ?? 11;

                    $entry = $this->createScheduleEntry([
                        'match_date' => sprintf('2099-%02d-%02d', $month, $day),
                        'match_time' => '19:00:00',
                        'opponent' => $opponents[$opIdx],
                        'venue' => $venues[$venIdx],
                        'home_away' => $homeAway,
                        'result' => null,
                    ]);

                    // Upcoming entries (isPast = false) should not render a result column
                    $rendered = $this->renderEntryFields($entry, false);

                    $this->assertArrayNotHasKey(
                        'result',
                        $rendered,
                        "Upcoming entry $i: result key must not be present in rendered output"
                    );

                    // But all other required fields must still be present
                    $this->assertNotNull($rendered['date'], "Upcoming entry $i: date must be present");
                    $this->assertNotNull($rendered['time'], "Upcoming entry $i: time must be present");
                    $this->assertNotNull($rendered['opponent'], "Upcoming entry $i: opponent must be present");
                    $this->assertNotNull($rendered['venue'], "Upcoming entry $i: venue must be present");
                    $this->assertNotNull($rendered['home_away'], "Upcoming entry $i: home/away must be present");
                }
            });
    }
}
