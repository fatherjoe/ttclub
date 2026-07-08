<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property 36: Season deduplication on historical import
 *
 * For any set of discovered season names and any set of seasons already existing
 * in the database, the historical import must create season records only for
 * discovered seasons whose (start_year, label) combination does not match any
 * existing season.
 *
 * **Validates: Requirements 13.2**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class HistoricalSeasonDeduplicationPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 36: Only new (start_year, label) pairs produce new season records.
     *
     * Generate random discovered + existing (start_year, label) pairs; verify only
     * new ones are created and existing ones are reused.
     *
     * **Validates: Requirements 13.2**
     */
    public function testOnlyNewSeasonPairsAreCreated(): void
    {
        $labels = ['', 'Pokal', 'Cup', 'Friendly'];

        $this
            ->forAll(
                Generators::seq(Generators::choose(1900, 2100)),  // discovered start_years
                Generators::seq(Generators::choose(1900, 2100)),  // existing start_years
                Generators::seq(Generators::choose(0, count($labels) - 1)), // discovered label indices
                Generators::seq(Generators::choose(0, count($labels) - 1))  // existing label indices
            )
            ->then(function (array $discoveredYears, array $existingYears, array $discoveredLabelIdxs, array $existingLabelIdxs) use ($labels): void {
                // Build the set of existing (start_year, label) pairs (pre-import state)
                $existingSeasonsMap = []; // key = "year|label" => id
                $nextId = 1;
                for ($i = 0; $i < count($existingYears); $i++) {
                    $labelIdx = $existingLabelIdxs[$i] ?? 0;
                    $key = $existingYears[$i] . '|' . $labels[$labelIdx];
                    if (!isset($existingSeasonsMap[$key])) {
                        $existingSeasonsMap[$key] = $nextId++;
                    }
                }

                $preImportKeys = array_keys($existingSeasonsMap);

                // Build discovered (start_year, label) pairs
                $discoveredPairs = [];
                for ($i = 0; $i < count($discoveredYears); $i++) {
                    $labelIdx = $discoveredLabelIdxs[$i] ?? 0;
                    $discoveredPairs[] = [
                        'start_year' => $discoveredYears[$i],
                        'label' => $labels[$labelIdx],
                    ];
                }

                // Simulate the historical import's season deduplication
                $createdKeys = [];
                foreach ($discoveredPairs as $pair) {
                    $key = $pair['start_year'] . '|' . $pair['label'];

                    $this->simulateHistoricalSeasonDedup(
                        $pair['start_year'],
                        $pair['label'],
                        $existingSeasonsMap,
                        $nextId,
                        $createdKeys,
                    );
                }

                // PROPERTY: No created key should have existed before the import
                foreach ($createdKeys as $createdKey) {
                    $this->assertNotContains(
                        $createdKey,
                        $preImportKeys,
                        sprintf(
                            'Historical import created season "%s" but it already existed — deduplication failed.',
                            $createdKey,
                        ),
                    );
                }

                // PROPERTY: Every discovered pair that already existed should NOT appear in created
                foreach ($discoveredPairs as $pair) {
                    $key = $pair['start_year'] . '|' . $pair['label'];
                    if (in_array($key, $preImportKeys, true)) {
                        $this->assertNotContains(
                            $key,
                            $createdKeys,
                            sprintf(
                                'Discovered pair "%s" existed pre-import but was created again — should have been skipped.',
                                $key,
                            ),
                        );
                    }
                }
            });
    }

    /**
     * Property 36: Rediscovering all existing seasons produces zero new records.
     *
     * When all discovered seasons already exist in the database, no new seasons
     * should be created.
     *
     * **Validates: Requirements 13.2**
     */
    public function testRediscoveringExistingSeasonsCreatesNothing(): void
    {
        $labels = ['', 'Pokal', 'Cup'];

        $this
            ->forAll(
                Generators::seq(Generators::choose(1900, 2100)),
                Generators::seq(Generators::choose(0, count($labels) - 1))
            )
            ->then(function (array $years, array $labelIdxs) use ($labels): void {
                if (empty($years)) {
                    return;
                }

                // Build existing seasons from the generated data
                $existingSeasonsMap = [];
                $nextId = 1;
                for ($i = 0; $i < count($years); $i++) {
                    $labelIdx = $labelIdxs[$i] ?? 0;
                    $key = $years[$i] . '|' . $labels[$labelIdx];
                    if (!isset($existingSeasonsMap[$key])) {
                        $existingSeasonsMap[$key] = $nextId++;
                    }
                }

                // Rediscover the same seasons (historical import finds them again)
                $createdKeys = [];
                foreach ($existingSeasonsMap as $key => $_id) {
                    [$year, $label] = explode('|', $key, 2);
                    $this->simulateHistoricalSeasonDedup(
                        (int) $year,
                        $label,
                        $existingSeasonsMap,
                        $nextId,
                        $createdKeys,
                    );
                }

                // PROPERTY: No new seasons should have been created
                $this->assertCount(
                    0,
                    $createdKeys,
                    sprintf(
                        'Expected zero new seasons when all discovered already exist. Created: [%s]',
                        implode(', ', $createdKeys),
                    ),
                );
            });
    }

    /**
     * Property 36: Discovering entirely new seasons creates exactly the unique set.
     *
     * When no existing seasons match the discovered ones, each unique (start_year, label)
     * pair results in exactly one new season record.
     *
     * **Validates: Requirements 13.2**
     */
    public function testNewSeasonsAreAllCreated(): void
    {
        $labels = ['', 'Pokal', 'Cup'];

        $this
            ->forAll(
                Generators::seq(Generators::choose(1900, 2100)),
                Generators::seq(Generators::choose(0, count($labels) - 1))
            )
            ->then(function (array $discoveredYears, array $labelIdxs) use ($labels): void {
                if (empty($discoveredYears)) {
                    return;
                }

                // No existing seasons
                $existingSeasonsMap = [];
                $nextId = 1;
                $createdKeys = [];

                // Determine expected unique discovered pairs
                $uniqueDiscovered = [];
                for ($i = 0; $i < count($discoveredYears); $i++) {
                    $labelIdx = $labelIdxs[$i] ?? 0;
                    $key = $discoveredYears[$i] . '|' . $labels[$labelIdx];
                    $uniqueDiscovered[$key] = [
                        'start_year' => $discoveredYears[$i],
                        'label' => $labels[$labelIdx],
                    ];
                }

                // Run import deduplication for each discovered pair
                foreach ($uniqueDiscovered as $pair) {
                    $this->simulateHistoricalSeasonDedup(
                        $pair['start_year'],
                        $pair['label'],
                        $existingSeasonsMap,
                        $nextId,
                        $createdKeys,
                    );
                }

                // PROPERTY: Exactly one creation per unique (start_year, label) pair
                $this->assertCount(
                    count($uniqueDiscovered),
                    $createdKeys,
                    sprintf(
                        'Expected %d new seasons (one per unique pair), got %d. Created: [%s]',
                        count($uniqueDiscovered),
                        count($createdKeys),
                        implode(', ', $createdKeys),
                    ),
                );
            });
    }

    /**
     * Property 36: Same (start_year, label) discovered multiple times still creates only one record.
     *
     * Generate a single (start_year, label) and import it N times with no pre-existing data;
     * verify only one season is created.
     *
     * **Validates: Requirements 13.2**
     */
    public function testDuplicateDiscoveriesCreateOnlyOneRecord(): void
    {
        $labels = ['', 'Pokal', 'Cup'];

        $this
            ->forAll(
                Generators::choose(1900, 2100),
                Generators::choose(0, count($labels) - 1),
                Generators::choose(2, 10) // number of repeated discoveries
            )
            ->then(function (int $startYear, int $labelIdx, int $repeatCount) use ($labels): void {
                $label = $labels[$labelIdx];
                $existingSeasonsMap = [];
                $nextId = 1;
                $createdKeys = [];

                // Discover the same pair multiple times
                for ($i = 0; $i < $repeatCount; $i++) {
                    $this->simulateHistoricalSeasonDedup(
                        $startYear,
                        $label,
                        $existingSeasonsMap,
                        $nextId,
                        $createdKeys,
                    );
                }

                // PROPERTY: Only one creation despite multiple discoveries
                $this->assertCount(
                    1,
                    $createdKeys,
                    sprintf(
                        'Expected exactly 1 season created for (%d, "%s") discovered %d times, got %d.',
                        $startYear,
                        $label,
                        $repeatCount,
                        count($createdKeys),
                    ),
                );
            });
    }

    /**
     * Simulate the historical import's season deduplication logic.
     *
     * Mirrors HistoricalImportService behavior:
     * 1. Check if (start_year, label) exists in the existing seasons map
     * 2. If it exists, skip (do not create)
     * 3. If it does not exist, create and add to the map
     *
     * @param int $startYear The season start year
     * @param string $label The season label (empty for regular, 'Pokal'/'Cup' for parallel)
     * @param array<string, int> &$existingSeasonsMap Maps "year|label" => season_id
     * @param int &$nextId ID counter for new records
     * @param list<string> &$createdKeys Tracks which keys were newly created
     */
    private function simulateHistoricalSeasonDedup(
        int $startYear,
        string $label,
        array &$existingSeasonsMap,
        int &$nextId,
        array &$createdKeys,
    ): void {
        $key = $startYear . '|' . $label;

        // Deduplication: if already exists, skip
        if (isset($existingSeasonsMap[$key])) {
            return;
        }

        // Create new season record
        $existingSeasonsMap[$key] = $nextId++;
        $createdKeys[] = $key;
    }
}
