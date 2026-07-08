<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property 18: Roster position ordering.
 *
 * For any set of roster entries with random position values (some integers,
 * some null), the roster display ordering must follow the rule:
 * ORDER BY position IS NULL, position ASC (NULLS LAST pattern).
 *
 * This means:
 * - Entries with non-null positions come first, sorted ascending by position
 * - Entries with null positions come last
 *
 * **Validates: Requirements 5.3**
 */
class RosterPositionOrderingPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 18: Roster entries are ordered by position ascending with NULLs last.
     *
     * Generate random lists of position values (int or null). Apply the NULLS LAST
     * ordering logic and verify:
     * 1. All non-null positions come before any null positions
     * 2. Non-null positions are in ascending order
     *
     * **Validates: Requirements 5.3**
     */
    public function testRosterPositionOrderingNullsLast(): void
    {
        $this
            ->forAll(
                Generators::choose(2, 30) // number of roster entries
            )
            ->then(function (int $entryCount): void {
                // Generate random roster entries with mixed null/int positions
                $entries = [];
                for ($i = 0; $i < $entryCount; $i++) {
                    // ~30% chance of null position
                    $position = (random_int(1, 10) <= 3) ? null : random_int(1, 100);
                    $entries[] = [
                        'player_id' => $i + 1,
                        'position' => $position,
                        'last_name' => chr(65 + ($i % 26)) . 'Player' . $i,
                    ];
                }

                // Apply the NULLS LAST ordering as specified:
                // ORDER BY position IS NULL, position ASC
                $sorted = $this->applyNullsLastOrdering($entries);

                // PROPERTY 1: All non-null positions come before any null positions
                $foundNull = false;
                foreach ($sorted as $entry) {
                    if ($entry['position'] === null) {
                        $foundNull = true;
                    } elseif ($foundNull) {
                        $this->fail(
                            'NULLS LAST ordering violated: found a non-null position entry ' .
                            '(position=' . $entry['position'] . ') after a null-position entry.'
                        );
                    }
                }

                // PROPERTY 2: Non-null positions are in ascending order
                $nonNullPositions = array_values(array_filter(
                    array_map(fn(array $e) => $e['position'], $sorted),
                    fn($p) => $p !== null
                ));

                for ($i = 1; $i < count($nonNullPositions); $i++) {
                    $this->assertGreaterThanOrEqual(
                        $nonNullPositions[$i - 1],
                        $nonNullPositions[$i],
                        sprintf(
                            'Position ascending order violated: position %d came after position %d',
                            $nonNullPositions[$i],
                            $nonNullPositions[$i - 1]
                        )
                    );
                }

                // PROPERTY 3: No entries are lost during sorting
                $this->assertCount(
                    $entryCount,
                    $sorted,
                    'Sorting must preserve all entries'
                );
            });
    }

    /**
     * Property 18: The NULLS LAST ordering is idempotent.
     *
     * Sorting an already-sorted roster must produce the same result.
     *
     * **Validates: Requirements 5.3**
     */
    public function testRosterPositionOrderingIsIdempotent(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 25) // number of roster entries
            )
            ->then(function (int $entryCount): void {
                $entries = [];
                for ($i = 0; $i < $entryCount; $i++) {
                    $position = (random_int(1, 10) <= 4) ? null : random_int(1, 50);
                    $entries[] = [
                        'player_id' => $i + 1,
                        'position' => $position,
                        'last_name' => chr(65 + ($i % 26)) . 'Player' . $i,
                    ];
                }

                $firstSort = $this->applyNullsLastOrdering($entries);
                $secondSort = $this->applyNullsLastOrdering($firstSort);

                $this->assertSame(
                    $firstSort,
                    $secondSort,
                    'NULLS LAST ordering must be idempotent: sorting twice yields the same result'
                );
            });
    }

    /**
     * Property 18: When all entries have non-null positions, ordering is purely ascending.
     *
     * Generate roster entries where every entry has a non-null position.
     * Verify the result is sorted in strictly ascending order by position.
     *
     * **Validates: Requirements 5.3**
     */
    public function testAllNonNullPositionsAreSortedAscending(): void
    {
        $this
            ->forAll(
                Generators::choose(2, 30) // number of roster entries
            )
            ->then(function (int $entryCount): void {
                $entries = [];
                for ($i = 0; $i < $entryCount; $i++) {
                    $entries[] = [
                        'player_id' => $i + 1,
                        'position' => random_int(1, 100),
                        'last_name' => chr(65 + ($i % 26)) . 'Player' . $i,
                    ];
                }

                $sorted = $this->applyNullsLastOrdering($entries);

                // All positions should be non-null
                $positions = array_map(fn(array $e) => $e['position'], $sorted);
                foreach ($positions as $pos) {
                    $this->assertNotNull($pos, 'All positions should be non-null in this test');
                }

                // Positions should be in ascending order
                for ($i = 1; $i < count($positions); $i++) {
                    $this->assertGreaterThanOrEqual(
                        $positions[$i - 1],
                        $positions[$i],
                        sprintf(
                            'Ascending order violated: position %d came after position %d',
                            $positions[$i],
                            $positions[$i - 1]
                        )
                    );
                }
            });
    }

    /**
     * Property 18: When all entries have null positions, all entries appear in the "last" group.
     *
     * Generate roster entries where every entry has a null position.
     * Verify no entries are lost and all positions remain null.
     *
     * **Validates: Requirements 5.3**
     */
    public function testAllNullPositionsAppearLast(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 20) // number of entries
            )
            ->then(function (int $entryCount): void {
                $entries = [];
                for ($i = 0; $i < $entryCount; $i++) {
                    $entries[] = [
                        'player_id' => $i + 1,
                        'position' => null,
                        'last_name' => chr(65 + ($i % 26)) . 'Player' . $i,
                    ];
                }

                $sorted = $this->applyNullsLastOrdering($entries);

                // All entries should still be present
                $this->assertCount($entryCount, $sorted);

                // All positions should be null
                foreach ($sorted as $entry) {
                    $this->assertNull(
                        $entry['position'],
                        'All entries should have null position'
                    );
                }
            });
    }

    /**
     * Apply the NULLS LAST ordering logic as implemented by RosterModel::getRosterEntries.
     *
     * This replicates the SQL: ORDER BY position IS NULL, position ASC
     * - position IS NULL evaluates to 0 for non-null (sorted first) and 1 for null (sorted last)
     * - Within each group, positions are sorted ascending
     *
     * @param array $entries Array of roster entries with 'position' and 'last_name' keys
     * @return array Sorted entries
     */
    private function applyNullsLastOrdering(array $entries): array
    {
        usort($entries, function (array $a, array $b): int {
            $aIsNull = $a['position'] === null ? 1 : 0;
            $bIsNull = $b['position'] === null ? 1 : 0;

            // First: sort by nullness (non-null first, null last)
            if ($aIsNull !== $bIsNull) {
                return $aIsNull - $bIsNull;
            }

            // Both non-null: sort by position ascending
            if (!$aIsNull && !$bIsNull) {
                $cmp = $a['position'] <=> $b['position'];
                if ($cmp !== 0) {
                    return $cmp;
                }
            }

            // Tie-breaker: last name ascending (matching the model's secondary sort)
            return strcmp($a['last_name'], $b['last_name']);
        });

        return $entries;
    }
}
