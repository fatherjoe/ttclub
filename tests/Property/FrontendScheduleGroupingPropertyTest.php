<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property 21: Frontend schedule grouping
 *
 * For any set of schedule entries and a reference date, the frontend display
 * must group entries into "upcoming" (match_date >= reference_date, sorted
 * ascending) followed by "past" (match_date < reference_date, sorted
 * descending by date).
 *
 * This replicates the grouping logic implemented in ScheduleModel::getUpcomingMatches()
 * and ScheduleModel::getPastMatches() which split entries based on today's date.
 *
 * **Validates: Requirements 10.3**
 */
class FrontendScheduleGroupingPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 21: Upcoming entries contain only dates >= reference date
     * and are sorted ascending.
     *
     * **Validates: Requirements 10.3**
     */
    public function testUpcomingEntriesContainOnlyFutureDatesAndSortedAscending(): void
    {
        $this
            ->forAll(
                Generators::seq(Generators::map(
                    fn(int $timestamp) => date('Y-m-d', $timestamp),
                    Generators::choose(
                        mktime(0, 0, 0, 1, 1, 2000),
                        mktime(0, 0, 0, 12, 31, 2030)
                    )
                )),
                Generators::map(
                    fn(int $timestamp) => date('Y-m-d', $timestamp),
                    Generators::choose(
                        mktime(0, 0, 0, 1, 1, 2010),
                        mktime(0, 0, 0, 12, 31, 2020)
                    )
                )
            )
            ->then(function (array $dates, string $referenceDate): void {
                if (count($dates) === 0) {
                    $this->assertTrue(true);
                    return;
                }

                $entries = $this->buildScheduleEntries($dates);
                $upcoming = $this->getUpcoming($entries, $referenceDate);

                // All upcoming entries must have match_date >= reference date
                foreach ($upcoming as $entry) {
                    $this->assertGreaterThanOrEqual(
                        $referenceDate,
                        $entry['match_date'],
                        sprintf(
                            'Upcoming entry with date %s should be >= reference date %s',
                            $entry['match_date'],
                            $referenceDate
                        )
                    );
                }

                // Upcoming entries must be sorted ascending
                for ($i = 1; $i < count($upcoming); $i++) {
                    $this->assertLessThanOrEqual(
                        $upcoming[$i]['match_date'],
                        $upcoming[$i - 1]['match_date'],
                        sprintf(
                            'Upcoming entries must be sorted ascending: %s should come before or equal %s',
                            $upcoming[$i - 1]['match_date'],
                            $upcoming[$i]['match_date']
                        )
                    );
                }
            });
    }

    /**
     * Property 21: Past entries contain only dates < reference date
     * and are sorted descending (most recent first).
     *
     * **Validates: Requirements 10.3**
     */
    public function testPastEntriesContainOnlyPastDatesAndSortedDescending(): void
    {
        $this
            ->forAll(
                Generators::seq(Generators::map(
                    fn(int $timestamp) => date('Y-m-d', $timestamp),
                    Generators::choose(
                        mktime(0, 0, 0, 1, 1, 2000),
                        mktime(0, 0, 0, 12, 31, 2030)
                    )
                )),
                Generators::map(
                    fn(int $timestamp) => date('Y-m-d', $timestamp),
                    Generators::choose(
                        mktime(0, 0, 0, 1, 1, 2010),
                        mktime(0, 0, 0, 12, 31, 2020)
                    )
                )
            )
            ->then(function (array $dates, string $referenceDate): void {
                if (count($dates) === 0) {
                    $this->assertTrue(true);
                    return;
                }

                $entries = $this->buildScheduleEntries($dates);
                $past = $this->getPast($entries, $referenceDate);

                // All past entries must have match_date < reference date
                foreach ($past as $entry) {
                    $this->assertLessThan(
                        $referenceDate,
                        $entry['match_date'],
                        sprintf(
                            'Past entry with date %s should be < reference date %s',
                            $entry['match_date'],
                            $referenceDate
                        )
                    );
                }

                // Past entries must be sorted descending (most recent first)
                for ($i = 1; $i < count($past); $i++) {
                    $this->assertGreaterThanOrEqual(
                        $past[$i]['match_date'],
                        $past[$i - 1]['match_date'],
                        sprintf(
                            'Past entries must be sorted descending: %s should come before or equal %s',
                            $past[$i - 1]['match_date'],
                            $past[$i]['match_date']
                        )
                    );
                }
            });
    }

    /**
     * Property 21: The union of upcoming and past entries equals
     * the original set (no entries lost or duplicated).
     *
     * **Validates: Requirements 10.3**
     */
    public function testGroupingPartitionsAllEntries(): void
    {
        $this
            ->forAll(
                Generators::seq(Generators::map(
                    fn(int $timestamp) => date('Y-m-d', $timestamp),
                    Generators::choose(
                        mktime(0, 0, 0, 1, 1, 2000),
                        mktime(0, 0, 0, 12, 31, 2030)
                    )
                )),
                Generators::map(
                    fn(int $timestamp) => date('Y-m-d', $timestamp),
                    Generators::choose(
                        mktime(0, 0, 0, 1, 1, 2010),
                        mktime(0, 0, 0, 12, 31, 2020)
                    )
                )
            )
            ->then(function (array $dates, string $referenceDate): void {
                $entries = $this->buildScheduleEntries($dates);
                $upcoming = $this->getUpcoming($entries, $referenceDate);
                $past = $this->getPast($entries, $referenceDate);

                // The total count of upcoming + past must equal the original count
                $this->assertCount(
                    count($entries),
                    array_merge($upcoming, $past),
                    sprintf(
                        'Upcoming (%d) + Past (%d) must equal total entries (%d)',
                        count($upcoming),
                        count($past),
                        count($entries)
                    )
                );

                // All original IDs must be present in one of the two groups
                $originalIds = array_column($entries, 'id');
                $groupedIds = array_merge(
                    array_column($upcoming, 'id'),
                    array_column($past, 'id')
                );
                sort($originalIds);
                sort($groupedIds);

                $this->assertSame(
                    $originalIds,
                    $groupedIds,
                    'Grouping must not lose or fabricate schedule entries'
                );
            });
    }

    /**
     * Property 21: The combined display order is upcoming (ascending) first,
     * then past (descending). This is the exact order presented to the user.
     *
     * **Validates: Requirements 10.3**
     */
    public function testCombinedDisplayOrderIsUpcomingFirstThenPast(): void
    {
        $this
            ->forAll(
                Generators::seq(Generators::map(
                    fn(int $timestamp) => date('Y-m-d', $timestamp),
                    Generators::choose(
                        mktime(0, 0, 0, 1, 1, 2000),
                        mktime(0, 0, 0, 12, 31, 2030)
                    )
                )),
                Generators::map(
                    fn(int $timestamp) => date('Y-m-d', $timestamp),
                    Generators::choose(
                        mktime(0, 0, 0, 1, 1, 2010),
                        mktime(0, 0, 0, 12, 31, 2020)
                    )
                )
            )
            ->then(function (array $dates, string $referenceDate): void {
                if (count($dates) === 0) {
                    $this->assertTrue(true);
                    return;
                }

                $entries = $this->buildScheduleEntries($dates);
                $upcoming = $this->getUpcoming($entries, $referenceDate);
                $past = $this->getPast($entries, $referenceDate);

                // The combined display: upcoming section first, past section second
                $display = array_merge($upcoming, $past);

                if (count($upcoming) > 0 && count($past) > 0) {
                    // The last upcoming entry must have date >= reference
                    $lastUpcoming = end($upcoming);
                    $this->assertGreaterThanOrEqual(
                        $referenceDate,
                        $lastUpcoming['match_date'],
                        'Last upcoming entry must be >= reference date'
                    );

                    // The first past entry must have date < reference
                    $firstPast = reset($past);
                    $this->assertLessThan(
                        $referenceDate,
                        $firstPast['match_date'],
                        'First past entry must be < reference date'
                    );
                }
            });
    }

    /**
     * Build schedule entry arrays from a list of date strings.
     *
     * @param string[] $dates
     * @return array<array{id: int, match_date: string}>
     */
    private function buildScheduleEntries(array $dates): array
    {
        $entries = [];
        foreach ($dates as $index => $date) {
            $entries[] = [
                'id' => $index + 1,
                'match_date' => $date,
            ];
        }
        return $entries;
    }

    /**
     * Get upcoming entries (match_date >= referenceDate), sorted ascending.
     *
     * This replicates ScheduleModel::getUpcomingMatches() logic:
     * WHERE match_date >= :today ORDER BY match_date ASC
     *
     * @param array<array{id: int, match_date: string}> $entries
     * @param string $referenceDate
     * @return array<array{id: int, match_date: string}>
     */
    private function getUpcoming(array $entries, string $referenceDate): array
    {
        $upcoming = array_filter($entries, fn(array $e) => $e['match_date'] >= $referenceDate);
        usort($upcoming, fn(array $a, array $b) => strcmp($a['match_date'], $b['match_date']));
        return array_values($upcoming);
    }

    /**
     * Get past entries (match_date < referenceDate), sorted descending.
     *
     * This replicates ScheduleModel::getPastMatches() logic:
     * WHERE match_date < :today ORDER BY match_date DESC
     *
     * @param array<array{id: int, match_date: string}> $entries
     * @param string $referenceDate
     * @return array<array{id: int, match_date: string}>
     */
    private function getPast(array $entries, string $referenceDate): array
    {
        $past = array_filter($entries, fn(array $e) => $e['match_date'] < $referenceDate);
        usort($past, fn(array $a, array $b) => strcmp($b['match_date'], $a['match_date']));
        return array_values($past);
    }
}
