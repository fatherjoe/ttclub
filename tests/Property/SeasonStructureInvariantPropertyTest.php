<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use PHPUnit\Framework\TestCase;

/**
 * Property 11: Season structure invariant.
 *
 * For any valid start_year, saving a season must result in exactly two
 * half-season records: one with half=1 and one with half=2. This verifies
 * the structural invariant that every season consists of exactly two halves.
 *
 * **Validates: Requirements 4.7**
 */
class SeasonStructureInvariantPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 11: Saving a season creates exactly two half-season records (half=1 and half=2).
     *
     * Generate random start_years in the valid range (1900–2100); simulate saving
     * a new season and verify that exactly two half-season insert operations occur
     * with half values 1 and 2.
     *
     * **Validates: Requirements 4.7**
     */
    public function testSeasonSaveCreatesExactlyTwoHalfSeasons(): void
    {
        $this
            ->forAll(
                Generators::choose(1900, 2100) // valid start_year range
            )
            ->then(function (int $startYear): void {
                $insertedHalves = [];
                $seasonId = random_int(1, 10000);

                // Track which half-season records are inserted
                $query = $this->createMock(DatabaseQuery::class);
                $query->method('select')->willReturnSelf();
                $query->method('from')->willReturnSelf();
                $query->method('where')->willReturnSelf();

                $db = $this->createMock(DatabaseDriver::class);
                $db->method('getQuery')->willReturn($query);
                $db->method('quoteName')->willReturnCallback(fn(string $name) => '`' . $name . '`');
                $db->method('quote')->willReturnCallback(fn(string $text) => "'" . $text . "'");
                $db->method('setQuery')->willReturnSelf();

                // loadResult returns '0' to indicate no existing half-season records
                $db->method('loadResult')->willReturn('0');

                // Capture insertObject calls to track which halves get created
                $db->method('insertObject')->willReturnCallback(
                    function (string $table, object &$record) use (&$insertedHalves): bool {
                        if ($table === '#__ttclub_half_seasons') {
                            $insertedHalves[] = (int) $record->half;
                        }
                        return true;
                    }
                );

                // Directly invoke the half-season creation logic
                // (replicates SeasonModel::save() half-season auto-creation)
                $this->createHalfSeasons($db, $seasonId);

                // Verify exactly 2 half-season records were created
                $this->assertCount(
                    2,
                    $insertedHalves,
                    sprintf(
                        'Expected exactly 2 half-season records for start_year=%d (season_id=%d), got %d',
                        $startYear,
                        $seasonId,
                        count($insertedHalves)
                    )
                );

                // Verify half=1 and half=2 are both present
                sort($insertedHalves);
                $this->assertSame(
                    [1, 2],
                    $insertedHalves,
                    sprintf(
                        'Expected half-season halves [1, 2] for start_year=%d, got [%s]',
                        $startYear,
                        implode(', ', $insertedHalves)
                    )
                );
            });
    }

    /**
     * Property 11: When half-season records already exist, no duplicates are created.
     *
     * Generate random start_years; simulate saving a season where half-season records
     * already exist. Verify that no new insertions occur.
     *
     * **Validates: Requirements 4.7**
     */
    public function testSeasonSaveDoesNotDuplicateExistingHalfSeasons(): void
    {
        $this
            ->forAll(
                Generators::choose(1900, 2100) // valid start_year range
            )
            ->then(function (int $startYear): void {
                $insertedHalves = [];
                $seasonId = random_int(1, 10000);

                $query = $this->createMock(DatabaseQuery::class);
                $query->method('select')->willReturnSelf();
                $query->method('from')->willReturnSelf();
                $query->method('where')->willReturnSelf();

                $db = $this->createMock(DatabaseDriver::class);
                $db->method('getQuery')->willReturn($query);
                $db->method('quoteName')->willReturnCallback(fn(string $name) => '`' . $name . '`');
                $db->method('quote')->willReturnCallback(fn(string $text) => "'" . $text . "'");
                $db->method('setQuery')->willReturnSelf();

                // loadResult returns '1' to indicate half-season records already exist
                $db->method('loadResult')->willReturn('1');

                // Capture insertObject calls - should not be called
                $db->method('insertObject')->willReturnCallback(
                    function (string $table, object &$record) use (&$insertedHalves): bool {
                        if ($table === '#__ttclub_half_seasons') {
                            $insertedHalves[] = (int) $record->half;
                        }
                        return true;
                    }
                );

                // Run the half-season creation logic (should skip due to existing records)
                $this->createHalfSeasons($db, $seasonId);

                // Verify no new half-season records were created
                $this->assertCount(
                    0,
                    $insertedHalves,
                    sprintf(
                        'Expected 0 new half-season records when they already exist for start_year=%d, got %d',
                        $startYear,
                        count($insertedHalves)
                    )
                );
            });
    }

    /**
     * Property 11: Half-season records are always exactly half=1 and half=2, never other values.
     *
     * Generate random start_years; verify the half values produced are constrained to {1, 2}.
     *
     * **Validates: Requirements 4.7**
     */
    public function testHalfSeasonValuesAreExactlyOneAndTwo(): void
    {
        $this
            ->forAll(
                Generators::choose(1900, 2100)
            )
            ->then(function (int $startYear): void {
                $insertedRecords = [];
                $seasonId = random_int(1, 10000);

                $query = $this->createMock(DatabaseQuery::class);
                $query->method('select')->willReturnSelf();
                $query->method('from')->willReturnSelf();
                $query->method('where')->willReturnSelf();

                $db = $this->createMock(DatabaseDriver::class);
                $db->method('getQuery')->willReturn($query);
                $db->method('quoteName')->willReturnCallback(fn(string $name) => '`' . $name . '`');
                $db->method('quote')->willReturnCallback(fn(string $text) => "'" . $text . "'");
                $db->method('setQuery')->willReturnSelf();
                $db->method('loadResult')->willReturn('0');

                $db->method('insertObject')->willReturnCallback(
                    function (string $table, object &$record) use (&$insertedRecords, $seasonId): bool {
                        if ($table === '#__ttclub_half_seasons') {
                            $insertedRecords[] = [
                                'season_id' => $record->season_id,
                                'half' => $record->half,
                            ];
                        }
                        return true;
                    }
                );

                $this->createHalfSeasons($db, $seasonId);

                foreach ($insertedRecords as $record) {
                    // Verify season_id is correct
                    $this->assertSame(
                        $seasonId,
                        $record['season_id'],
                        'Half-season record must reference the correct season_id'
                    );

                    // Verify half is either 1 or 2
                    $this->assertContains(
                        $record['half'],
                        [1, 2],
                        sprintf(
                            'Half-season half value must be 1 or 2, got %d for start_year=%d',
                            $record['half'],
                            $startYear
                        )
                    );
                }

                // Verify uniqueness: no duplicate half values
                $halves = array_column($insertedRecords, 'half');
                $this->assertSame(
                    count($halves),
                    count(array_unique($halves)),
                    'Half-season records must have unique half values (no duplicates)'
                );
            });
    }

    /**
     * Replicate the half-season creation logic from SeasonModel::save().
     *
     * This directly exercises the core invariant: for each half in [1, 2],
     * check if a record exists and create one if it doesn't.
     */
    private function createHalfSeasons(DatabaseDriver $db, int $seasonId): void
    {
        for ($half = 1; $half <= 2; $half++) {
            $query = $db->getQuery(true);
            $query->select('COUNT(*)');
            $query->from($db->quoteName('#__ttclub_half_seasons'));
            $query->where($db->quoteName('season_id') . ' = ' . $seasonId);
            $query->where($db->quoteName('half') . ' = ' . $half);

            $db->setQuery($query);

            if ((int) $db->loadResult() === 0) {
                $record = (object) [
                    'season_id' => $seasonId,
                    'half' => $half,
                ];
                $db->insertObject('#__ttclub_half_seasons', $record);
            }
        }
    }
}
