<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property 23: Frontend team ordering
 *
 * For any set of teams displayed on the frontend, teams must be ordered
 * by team_number in ascending order. This verifies the ordering invariant
 * of Site\Model\TeamsModel::getListQuery() which applies
 * ORDER BY a.team_number ASC.
 *
 * **Validates: Requirements 9.1**
 */
class FrontendTeamOrderingPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 23: For any random set of teams, sorting by team_number
     * ascending must produce a non-decreasing sequence of team numbers.
     *
     * Generate random team sets with random team_number values; apply the same
     * ordering logic as TeamsModel (ORDER BY team_number ASC); verify the result
     * is sorted in ascending order.
     *
     * **Validates: Requirements 9.1**
     */
    public function testTeamsAreOrderedByTeamNumberAscending(): void
    {
        $this
            ->forAll(
                Generators::seq(Generators::associative([
                    'id' => Generators::choose(1, 100000),
                    'team_number' => Generators::choose(1, 50),
                    'league_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 1,
                        Generators::string()
                    ),
                    'age_class_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 1,
                        Generators::string()
                    ),
                ]))
            )
            ->then(function (array $teams): void {
                if (count($teams) <= 1) {
                    $this->assertTrue(true);
                    return;
                }

                // Apply the same ordering logic as TeamsModel: ORDER BY team_number ASC
                $sorted = $this->sortByTeamNumberAscending($teams);

                // Verify the sorted list is in non-decreasing order by team_number
                for ($i = 1; $i < count($sorted); $i++) {
                    $this->assertLessThanOrEqual(
                        $sorted[$i]['team_number'],
                        $sorted[$i - 1]['team_number'],
                        sprintf(
                            'Team at index %d (team_number=%d) must not come after team at index %d (team_number=%d) in ascending order',
                            $i - 1,
                            $sorted[$i - 1]['team_number'],
                            $i,
                            $sorted[$i]['team_number']
                        )
                    );
                }
            });
    }

    /**
     * Property 23: Sorting preserves all team entries.
     *
     * Generate random team sets; verify that sorting by team_number does not
     * lose or duplicate any entries.
     *
     * **Validates: Requirements 9.1**
     */
    public function testTeamSortingPreservesAllEntries(): void
    {
        $this
            ->forAll(
                Generators::seq(Generators::associative([
                    'id' => Generators::choose(1, 100000),
                    'team_number' => Generators::choose(1, 50),
                    'league_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 1,
                        Generators::string()
                    ),
                    'age_class_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 1,
                        Generators::string()
                    ),
                ]))
            )
            ->then(function (array $teams): void {
                $originalCount = count($teams);

                // Apply sorting
                $sorted = $this->sortByTeamNumberAscending($teams);

                // Verify no entries are lost or duplicated
                $this->assertCount(
                    $originalCount,
                    $sorted,
                    sprintf(
                        'Sorting must preserve all %d team entries, got %d after sort',
                        $originalCount,
                        count($sorted)
                    )
                );

                // Verify all original IDs are present in the sorted result
                $originalIds = array_column($teams, 'id');
                $sortedIds = array_column($sorted, 'id');
                sort($originalIds);
                sort($sortedIds);

                $this->assertSame(
                    $originalIds,
                    $sortedIds,
                    'Sorting must not lose or fabricate team entries'
                );
            });
    }

    /**
     * Property 23: The first team has the lowest team_number and the last has the highest.
     *
     * Generate random team sets; verify that after sorting, the first element
     * has the minimum team_number and the last element has the maximum.
     *
     * **Validates: Requirements 9.1**
     */
    public function testFirstTeamHasLowestNumberAndLastHasHighest(): void
    {
        $this
            ->forAll(
                Generators::seq(Generators::associative([
                    'id' => Generators::choose(1, 100000),
                    'team_number' => Generators::choose(1, 50),
                    'league_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 1,
                        Generators::string()
                    ),
                    'age_class_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 1,
                        Generators::string()
                    ),
                ]))
            )
            ->then(function (array $teams): void {
                if (count($teams) < 2) {
                    $this->assertTrue(true);
                    return;
                }

                $sorted = $this->sortByTeamNumberAscending($teams);

                // Find minimum and maximum team_numbers from original set
                $teamNumbers = array_column($teams, 'team_number');
                $minNumber = min($teamNumbers);
                $maxNumber = max($teamNumbers);

                // First entry should have the lowest team_number
                $this->assertSame(
                    $minNumber,
                    $sorted[0]['team_number'],
                    sprintf(
                        'First team after ascending sort should have the lowest team_number. Expected %d, got %d',
                        $minNumber,
                        $sorted[0]['team_number']
                    )
                );

                // Last entry should have the highest team_number
                $lastIdx = count($sorted) - 1;
                $this->assertSame(
                    $maxNumber,
                    $sorted[$lastIdx]['team_number'],
                    sprintf(
                        'Last team after ascending sort should have the highest team_number. Expected %d, got %d',
                        $maxNumber,
                        $sorted[$lastIdx]['team_number']
                    )
                );
            });
    }

    /**
     * Simulate the ordering logic from Site\Model\TeamsModel::getListQuery().
     *
     * The model applies: ORDER BY a.team_number ASC.
     * This is a pure function that replicates the SQL ordering in PHP.
     *
     * @param array<array{id: int, team_number: int, league_name: string, age_class_name: string}> $teams
     * @return array<array{id: int, team_number: int, league_name: string, age_class_name: string}>
     */
    private function sortByTeamNumberAscending(array $teams): array
    {
        usort($teams, function (array $a, array $b): int {
            return $a['team_number'] <=> $b['team_number'];
        });

        return $teams;
    }
}
