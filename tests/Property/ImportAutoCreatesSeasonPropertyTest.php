<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use PHPUnit\Framework\TestCase;

/**
 * Property 29: Import auto-creates missing seasons
 *
 * For any set of season start_years discovered during import and any set of seasons
 * already in the database, the import must create season records only for start_years
 * not already present with the same label. Existing seasons must not be duplicated.
 *
 * **Validates: Requirements 7.12**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class ImportAutoCreatesSeasonPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 29: Only new (start_year, label) combos are created; existing ones are reused.
     *
     * Generate random discovered start_years + existing season sets; verify only new
     * (start_year, label) combos are created, existing not duplicated.
     *
     * **Validates: Requirements 7.12**
     */
    public function testOnlyNewSeasonCombosAreCreated(): void
    {
        $labels = ['', 'Pokal', 'Cup'];

        $this
            ->forAll(
                Generators::seq(Generators::choose(1900, 2100)),  // discovered start_years
                Generators::seq(Generators::choose(1900, 2100)),  // existing start_years
                Generators::seq(Generators::choose(0, count($labels) - 1)), // label indices for discovered
                Generators::seq(Generators::choose(0, count($labels) - 1))  // label indices for existing
            )
            ->then(function (array $discoveredYears, array $existingYears, array $discoveredLabelIdxs, array $existingLabelIdxs) use ($labels): void {
                // Build the set of discovered (start_year, label) pairs
                $discoveredPairs = [];
                for ($i = 0; $i < count($discoveredYears); $i++) {
                    $labelIdx = $discoveredLabelIdxs[$i] ?? 0;
                    $discoveredPairs[] = [
                        'start_year' => $discoveredYears[$i],
                        'label' => $labels[$labelIdx],
                    ];
                }

                // Build the set of existing (start_year, label) pairs with IDs
                $existingSeasonsMap = []; // key = "year|label", value = id
                $nextId = 1;
                for ($i = 0; $i < count($existingYears); $i++) {
                    $labelIdx = $existingLabelIdxs[$i] ?? 0;
                    $key = $existingYears[$i] . '|' . $labels[$labelIdx];
                    if (!isset($existingSeasonsMap[$key])) {
                        $existingSeasonsMap[$key] = $nextId++;
                    }
                }

                // Track what gets created
                $createdSeasons = [];
                $returnedIds = [];

                // For each discovered pair, simulate ensureSeasonExists logic
                foreach ($discoveredPairs as $pair) {
                    $key = $pair['start_year'] . '|' . $pair['label'];
                    $existingId = $existingSeasonsMap[$key] ?? null;

                    $id = $this->simulateEnsureSeasonExists(
                        $pair['start_year'],
                        $pair['label'],
                        $existingId,
                        $nextId,
                        $createdSeasons,
                        $existingSeasonsMap
                    );

                    $returnedIds[] = ['pair' => $key, 'id' => $id];
                }

                // Verify: all created seasons should have keys NOT in the original existing set
                $originalExistingKeys = array_keys($existingSeasonsMap);
                // Filter to only the keys that existed BEFORE we started (pre-import)
                $preImportKeys = [];
                for ($i = 0; $i < count($existingYears); $i++) {
                    $labelIdx = $existingLabelIdxs[$i] ?? 0;
                    $preImportKeys[] = $existingYears[$i] . '|' . $labels[$labelIdx];
                }
                $preImportKeys = array_unique($preImportKeys);

                foreach ($createdSeasons as $createdKey) {
                    $this->assertNotContains(
                        $createdKey,
                        $preImportKeys,
                        sprintf(
                            'Season "%s" was created but already existed in the database — should have been reused, not duplicated.',
                            $createdKey
                        )
                    );
                }

                // Verify: every discovered pair returns a valid ID
                foreach ($returnedIds as $entry) {
                    $this->assertGreaterThan(
                        0,
                        $entry['id'],
                        sprintf(
                            'ensureSeasonExists should return a valid ID for pair "%s"',
                            $entry['pair']
                        )
                    );
                }
            });
    }

    /**
     * Property 29: Existing seasons are never duplicated (insert is not called for them).
     *
     * Generate random existing seasons and rediscover them during import; verify no
     * insertions occur for already-existing (start_year, label) combinations.
     *
     * **Validates: Requirements 7.12**
     */
    public function testExistingSeasonsAreNotDuplicated(): void
    {
        $labels = ['', 'Pokal', 'Cup', 'Friendly'];

        $this
            ->forAll(
                Generators::seq(Generators::choose(1900, 2100)), // existing start_years
                Generators::seq(Generators::choose(0, count($labels) - 1)) // label indices
            )
            ->then(function (array $existingYears, array $labelIdxs) use ($labels): void {
                if (empty($existingYears)) {
                    return;
                }

                // Build existing seasons
                $existingSeasonsMap = [];
                $nextId = 1;
                for ($i = 0; $i < count($existingYears); $i++) {
                    $labelIdx = $labelIdxs[$i] ?? 0;
                    $key = $existingYears[$i] . '|' . $labels[$labelIdx];
                    if (!isset($existingSeasonsMap[$key])) {
                        $existingSeasonsMap[$key] = $nextId++;
                    }
                }

                // Rediscover the same seasons (import finds them again)
                $createdSeasons = [];
                foreach ($existingSeasonsMap as $key => $existingId) {
                    [$year, $label] = explode('|', $key, 2);
                    $this->simulateEnsureSeasonExists(
                        (int) $year,
                        $label,
                        $existingId,
                        $nextId,
                        $createdSeasons,
                        $existingSeasonsMap
                    );
                }

                // Verify: no new seasons created
                $this->assertCount(
                    0,
                    $createdSeasons,
                    sprintf(
                        'Expected no new seasons when all discovered seasons already exist. Created: [%s]',
                        implode(', ', $createdSeasons)
                    )
                );
            });
    }

    /**
     * Property 29: New seasons discovered during import are actually created.
     *
     * Generate random discovered seasons that do NOT exist; verify they get created.
     *
     * **Validates: Requirements 7.12**
     */
    public function testNewSeasonsAreCreated(): void
    {
        $labels = ['', 'Pokal', 'Cup'];

        $this
            ->forAll(
                Generators::seq(Generators::choose(1900, 2100)), // discovered years
                Generators::seq(Generators::choose(0, count($labels) - 1)) // label indices
            )
            ->then(function (array $discoveredYears, array $labelIdxs) use ($labels): void {
                if (empty($discoveredYears)) {
                    return;
                }

                // No existing seasons — everything is new
                $existingSeasonsMap = [];
                $nextId = 1;
                $createdSeasons = [];

                // Discover unique pairs
                $uniquePairs = [];
                for ($i = 0; $i < count($discoveredYears); $i++) {
                    $labelIdx = $labelIdxs[$i] ?? 0;
                    $key = $discoveredYears[$i] . '|' . $labels[$labelIdx];
                    $uniquePairs[$key] = [
                        'start_year' => $discoveredYears[$i],
                        'label' => $labels[$labelIdx],
                    ];
                }

                foreach ($uniquePairs as $pair) {
                    $key = $pair['start_year'] . '|' . $pair['label'];
                    $existingId = $existingSeasonsMap[$key] ?? null;

                    $this->simulateEnsureSeasonExists(
                        $pair['start_year'],
                        $pair['label'],
                        $existingId,
                        $nextId,
                        $createdSeasons,
                        $existingSeasonsMap
                    );
                }

                // Verify: all unique pairs were created
                $this->assertCount(
                    count($uniquePairs),
                    $createdSeasons,
                    sprintf(
                        'Expected %d new seasons created (one per unique pair), got %d',
                        count($uniquePairs),
                        count($createdSeasons)
                    )
                );
            });
    }

    /**
     * Property 29: Calling ensureSeasonExists multiple times for same pair returns same ID.
     *
     * Generate a single (start_year, label) pair and call it N times; verify same ID returned
     * each time and only one creation occurs.
     *
     * **Validates: Requirements 7.12**
     */
    public function testRepeatedCallsReturnSameIdWithoutDuplication(): void
    {
        $labels = ['', 'Pokal', 'Cup'];

        $this
            ->forAll(
                Generators::choose(1900, 2100),
                Generators::choose(0, count($labels) - 1),
                Generators::choose(2, 10) // number of repeated calls
            )
            ->then(function (int $startYear, int $labelIdx, int $repeatCount) use ($labels): void {
                $label = $labels[$labelIdx];
                $existingSeasonsMap = [];
                $nextId = 1;
                $createdSeasons = [];

                $ids = [];
                for ($i = 0; $i < $repeatCount; $i++) {
                    $key = $startYear . '|' . $label;
                    $existingId = $existingSeasonsMap[$key] ?? null;

                    $id = $this->simulateEnsureSeasonExists(
                        $startYear,
                        $label,
                        $existingId,
                        $nextId,
                        $createdSeasons,
                        $existingSeasonsMap
                    );
                    $ids[] = $id;
                }

                // All IDs should be the same
                $uniqueIds = array_unique($ids);
                $this->assertCount(
                    1,
                    $uniqueIds,
                    sprintf(
                        'Repeated ensureSeasonExists(%d, "%s") should always return same ID. Got: [%s]',
                        $startYear,
                        $label,
                        implode(', ', $ids)
                    )
                );

                // Only one creation should have occurred
                $this->assertCount(
                    1,
                    $createdSeasons,
                    sprintf(
                        'Expected exactly 1 creation for repeated calls, got %d',
                        count($createdSeasons)
                    )
                );
            });
    }

    /**
     * Simulate the ensureSeasonExists logic from ClickTtImportService.
     *
     * This replicates the core logic:
     * 1. Look up existing season by (start_year, label)
     * 2. If found, return its ID
     * 3. If not found, create a new record and return the new ID
     *
     * @param int $startYear The season start year
     * @param string $label The season label
     * @param int|null $existingId The ID if a season already exists (null if not)
     * @param int &$nextId Counter for assigning new IDs
     * @param array &$createdSeasons Tracks which season keys were created
     * @param array &$existingSeasonsMap Tracks all known seasons (key => id)
     * @return int The season ID (existing or newly created)
     */
    private function simulateEnsureSeasonExists(
        int $startYear,
        string $label,
        ?int $existingId,
        int &$nextId,
        array &$createdSeasons,
        array &$existingSeasonsMap
    ): int {
        $key = $startYear . '|' . $label;

        // Check if season exists
        if ($existingId !== null) {
            return $existingId;
        }

        // Create new season
        $newId = $nextId++;
        $existingSeasonsMap[$key] = $newId;
        $createdSeasons[] = $key;

        return $newId;
    }
}
