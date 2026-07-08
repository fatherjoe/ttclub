<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use Fatherjoe\Component\Ttclub\Administrator\Table\SeasonTable;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use PHPUnit\Framework\TestCase;

/**
 * Property 15: Season deletion is prevented iff associated data exists.
 *
 * For any season, deletion succeeds if and only if the season has zero roster
 * assignments (via half_seasons) AND zero schedule entries. If either exists,
 * deletion must be prevented with an appropriate error message.
 *
 * **Validates: Requirements 4.6**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class SeasonDeletionGuardPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 15: Deletion is prevented when roster count > 0 or schedule count > 0,
     * and allowed only when both are zero.
     *
     * We generate random season IDs, random roster entry counts, and random schedule
     * entry counts. The SeasonTable::delete() issues two sequential queries:
     * 1. Count rosters via half_seasons join
     * 2. Count schedules referencing the season
     *
     * Deletion succeeds iff both counts are zero.
     *
     * **Validates: Requirements 4.6**
     */
    public function testSeasonDeletionPreventedIffAssociatedDataExists(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 10000),   // season ID (pk)
                Generators::choose(0, 50),       // number of roster entries via half_seasons
                Generators::choose(0, 50)        // number of schedule entries
            )
            ->then(function (int $seasonId, int $rosterCount, int $scheduleCount): void {
                // Track which loadResult call we're on
                $callIndex = 0;
                $results = [(string) $rosterCount, (string) $scheduleCount];

                // Build mock database infrastructure
                $query = $this->createMock(DatabaseQuery::class);
                $query->method('select')->willReturnSelf();
                $query->method('from')->willReturnSelf();
                $query->method('where')->willReturnSelf();
                $query->method('innerJoin')->willReturnSelf();

                $db = $this->createMock(DatabaseDriver::class);
                $db->method('getQuery')->willReturn($query);
                $db->method('quoteName')->willReturnCallback(
                    fn(string $name, ?string $alias = null) => $alias ? '`' . $name . '` AS `' . $alias . '`' : '`' . $name . '`'
                );
                $db->method('quote')->willReturnCallback(fn(string $text) => "'" . $text . "'");
                $db->method('setQuery')->willReturnSelf();
                $db->method('loadResult')->willReturnCallback(
                    function () use (&$callIndex, $results) {
                        return $results[$callIndex++] ?? '0';
                    }
                );

                // Create the SeasonTable instance with our mock DB
                $table = new SeasonTable($db);
                $table->id = $seasonId;

                $result = $table->delete($seasonId);

                $hasAssociatedData = ($rosterCount > 0 || $scheduleCount > 0);

                if (!$hasAssociatedData) {
                    $this->assertTrue(
                        $result,
                        "Deletion should succeed when zero roster entries and zero schedule entries exist for season ID $seasonId"
                    );
                } else {
                    $this->assertFalse(
                        $result,
                        "Deletion should be prevented when associated data exists for season ID $seasonId "
                        . "(rosters: $rosterCount, schedules: $scheduleCount)"
                    );
                    $this->assertNotEmpty(
                        $table->getError(),
                        "An error message should be set when deletion is prevented"
                    );

                    $errorMsg = strtolower($table->getError());
                    if ($rosterCount > 0) {
                        $this->assertStringContainsString(
                            'roster',
                            $errorMsg,
                            "Error message should mention roster when roster entries exist"
                        );
                    } else {
                        $this->assertStringContainsString(
                            'schedule',
                            $errorMsg,
                            "Error message should mention schedule when schedule entries exist"
                        );
                    }
                }
            });
    }
}
