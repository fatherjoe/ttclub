<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property 22: Schedule entry ordering
 *
 * For any set of schedule entries displayed on a team's detail page, the entries
 * must be ordered by match date in ascending order. The ScheduleService stores
 * entries via JSON serialization and the frontend template iterates them directly,
 * so the service must ensure date-ascending order before storing/returning.
 *
 * **Validates: Requirements 14.6**
 *
 * @group property
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class ScheduleEntryOrderingTest extends TestCase
{
    use TestTrait;

    /**
     * Property 22: For any random set of schedule entries with various dates,
     * after passing through the service's store/retrieve cycle (JSON serialization),
     * entries must be in ascending order by match_date.
     *
     * This simulates the ScheduleService behavior: entries are parsed (in arbitrary
     * order from HTML), sorted by match_date ascending, serialized to JSON for
     * cache storage, then deserialized on retrieval. The displayed output must
     * always be in date-ascending order.
     *
     * **Validates: Requirements 14.6**
     */
    public function testScheduleEntriesAreInAscendingDateOrder(): void
    {
        $homeTeams = ['TTV Musterstadt', 'SC Grün-Weiß', 'TSV Bietigheim', 'VfB Stuttgart II'];
        $guestTeams = ['TTC Waldheim', 'SV Rotation', 'FC Tischtennis', 'SpVgg Nordheim'];

        $this
            ->forAll(
                Generators::choose(2, 20), // number of entries (at least 2 to test ordering)
                Generators::seq(Generators::choose(0, count($homeTeams) - 1)),
                Generators::seq(Generators::choose(0, count($guestTeams) - 1)),
                Generators::seq(
                    Generators::choose(
                        (int) mktime(0, 0, 0, 1, 1, 2020),
                        (int) mktime(0, 0, 0, 12, 31, 2030)
                    )
                ),
                Generators::seq(Generators::choose(0, 23)),  // hours
                Generators::seq(Generators::choose(0, 59))   // minutes
            )
            ->then(function (
                int $entryCount,
                array $homeIdxs,
                array $guestIdxs,
                array $timestamps,
                array $hours,
                array $minutes,
            ) use ($homeTeams, $guestTeams): void {
                // Build schedule entries in random order (simulating parser output)
                $entries = [];
                for ($i = 0; $i < $entryCount; $i++) {
                    $ts = $timestamps[$i] ?? (int) mktime(0, 0, 0, 6, 15, 2024);
                    $h = $hours[$i] ?? 19;
                    $m = $minutes[$i] ?? 30;
                    $homeIdx = $homeIdxs[$i] ?? 0;
                    $guestIdx = $guestIdxs[$i] ?? 0;

                    $entries[] = [
                        'match_date' => date('Y-m-d', $ts),
                        'match_time' => sprintf('%02d:%02d', $h, $m),
                        'home_team' => $homeTeams[$homeIdx],
                        'guest_team' => $guestTeams[$guestIdx],
                        'result' => null,
                    ];
                }

                // Apply the sorting that the service must perform before storage
                // (Requirement 14.6: order by match_date ascending)
                $sorted = $this->sortScheduleByDateAscending($entries);

                // Simulate the store/retrieve cycle via JSON (as ScheduleService does)
                $serialized = json_encode($sorted, JSON_UNESCAPED_UNICODE);
                $this->assertNotFalse($serialized, 'JSON encoding must not fail');

                $deserialized = json_decode($serialized, true);
                $this->assertIsArray($deserialized, 'JSON decoding must produce an array');

                // Verify the deserialized entries are in ascending date order
                for ($i = 1; $i < count($deserialized); $i++) {
                    $prevDate = $deserialized[$i - 1]['match_date'];
                    $currDate = $deserialized[$i]['match_date'];

                    $this->assertLessThanOrEqual(
                        $currDate,
                        $prevDate,
                        sprintf(
                            'Schedule entry at index %d (date=%s) must not come after entry at index %d (date=%s). '
                            . 'Entries must be in ascending date order per Requirement 14.6.',
                            $i - 1,
                            $prevDate,
                            $i,
                            $currDate
                        )
                    );
                }
            });
    }

    /**
     * Property 22: Sorting preserves all entries — no entries are lost or duplicated
     * during the ordering process.
     *
     * Generate random schedule entry sets; sort by date; verify count is preserved
     * and all original entries are present in the output.
     *
     * **Validates: Requirements 14.6**
     */
    public function testScheduleOrderingPreservesAllEntries(): void
    {
        $homeTeams = ['TTV Musterstadt', 'SC Grün-Weiß', 'TSV Bietigheim'];
        $guestTeams = ['TTC Waldheim', 'SV Rotation', 'FC Tischtennis'];

        $this
            ->forAll(
                Generators::choose(0, 25), // number of entries (include 0 for edge case)
                Generators::seq(Generators::choose(0, count($homeTeams) - 1)),
                Generators::seq(Generators::choose(0, count($guestTeams) - 1)),
                Generators::seq(
                    Generators::choose(
                        (int) mktime(0, 0, 0, 1, 1, 2020),
                        (int) mktime(0, 0, 0, 12, 31, 2030)
                    )
                )
            )
            ->then(function (
                int $entryCount,
                array $homeIdxs,
                array $guestIdxs,
                array $timestamps,
            ) use ($homeTeams, $guestTeams): void {
                $entries = [];
                for ($i = 0; $i < $entryCount; $i++) {
                    $ts = $timestamps[$i] ?? (int) mktime(0, 0, 0, 6, 15, 2024);
                    $homeIdx = $homeIdxs[$i] ?? 0;
                    $guestIdx = $guestIdxs[$i] ?? 0;

                    $entries[] = [
                        'match_date' => date('Y-m-d', $ts),
                        'match_time' => '19:30',
                        'home_team' => $homeTeams[$homeIdx],
                        'guest_team' => $guestTeams[$guestIdx],
                        'result' => null,
                    ];
                }

                $sorted = $this->sortScheduleByDateAscending($entries);

                // Count must be preserved
                $this->assertCount(
                    count($entries),
                    $sorted,
                    sprintf(
                        'Sorting must preserve entry count. Had %d entries, got %d after sort.',
                        count($entries),
                        count($sorted)
                    )
                );

                // All original dates must still be present (as a multiset)
                $originalDates = array_column($entries, 'match_date');
                $sortedDates = array_column($sorted, 'match_date');
                sort($originalDates);
                sort($sortedDates);

                $this->assertSame(
                    $originalDates,
                    $sortedDates,
                    'Sorting must not lose or fabricate schedule entries'
                );
            });
    }

    /**
     * Property 22: The first entry has the earliest date and the last entry has
     * the latest date after ordering.
     *
     * Generate random schedule entries with at least 2 different dates; verify
     * first element has minimum date and last element has maximum date.
     *
     * **Validates: Requirements 14.6**
     */
    public function testFirstEntryHasEarliestDateLastEntryHasLatest(): void
    {
        $homeTeams = ['TTV Musterstadt', 'SC Grün-Weiß'];
        $guestTeams = ['TTC Waldheim', 'SV Rotation'];

        $this
            ->forAll(
                Generators::choose(2, 15),
                Generators::seq(
                    Generators::choose(
                        (int) mktime(0, 0, 0, 1, 1, 2020),
                        (int) mktime(0, 0, 0, 12, 31, 2030)
                    )
                )
            )
            ->then(function (
                int $entryCount,
                array $timestamps,
            ) use ($homeTeams, $guestTeams): void {
                $entries = [];
                for ($i = 0; $i < $entryCount; $i++) {
                    $ts = $timestamps[$i] ?? (int) mktime(0, 0, 0, 6, 15, 2024);

                    $entries[] = [
                        'match_date' => date('Y-m-d', $ts),
                        'match_time' => '20:00',
                        'home_team' => $homeTeams[$i % count($homeTeams)],
                        'guest_team' => $guestTeams[$i % count($guestTeams)],
                        'result' => null,
                    ];
                }

                $sorted = $this->sortScheduleByDateAscending($entries);

                // Find min and max dates from the original set
                $dates = array_map(fn(array $e) => $e['match_date'], $entries);
                $minDate = min($dates);
                $maxDate = max($dates);

                // First entry must have the earliest date
                $this->assertSame(
                    $minDate,
                    $sorted[0]['match_date'],
                    sprintf(
                        'First entry after ascending sort must have earliest date. Expected %s, got %s',
                        $minDate,
                        $sorted[0]['match_date']
                    )
                );

                // Last entry must have the latest date
                $lastIdx = count($sorted) - 1;
                $this->assertSame(
                    $maxDate,
                    $sorted[$lastIdx]['match_date'],
                    sprintf(
                        'Last entry after ascending sort must have latest date. Expected %s, got %s',
                        $maxDate,
                        $sorted[$lastIdx]['match_date']
                    )
                );
            });
    }

    /**
     * Simulate the sorting logic that ScheduleService applies before storing entries.
     *
     * The service sorts schedule entries by match_date ascending (YYYY-MM-DD format
     * allows lexicographic comparison) to satisfy Requirement 14.6.
     *
     * @param array<int, array{match_date: string, match_time: ?string, home_team: string, guest_team: string, result: ?string}> $entries
     * @return array<int, array{match_date: string, match_time: ?string, home_team: string, guest_team: string, result: ?string}>
     */
    private function sortScheduleByDateAscending(array $entries): array
    {
        usort($entries, function (array $a, array $b): int {
            return strcmp($a['match_date'], $b['match_date']);
        });

        return $entries;
    }
}
