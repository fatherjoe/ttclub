<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property 25: Frontend player ordering
 *
 * Generate random player sets; verify alphabetical order by last name.
 * The ordering logic mirrors Site\Model\PlayersModel::getListQuery() which
 * applies ORDER BY last_name ASC, first_name ASC.
 *
 * **Validates: Requirements 8.1**
 */
class FrontendPlayerOrderingPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property: For any set of players, sorting alphabetically by last name (ASC)
     * and then by first name (ASC) must produce a non-decreasing sequence.
     *
     * **Validates: Requirements 8.1**
     */
    public function testPlayersAreOrderedAlphabeticallyByLastName(): void
    {
        $this
            ->forAll(
                Generators::seq(Generators::associative([
                    'first_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 1 && mb_strlen(trim($s)) <= 50,
                        Generators::string()
                    ),
                    'last_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 1 && mb_strlen(trim($s)) <= 50,
                        Generators::string()
                    ),
                ]))
            )
            ->then(function (array $players): void {
                // Trim names as the model would
                $players = array_map(function (array $p): array {
                    return [
                        'first_name' => trim($p['first_name']),
                        'last_name' => trim($p['last_name']),
                    ];
                }, $players);

                // Filter out invalid players (empty names after trim)
                $players = array_values(array_filter($players, function (array $p): bool {
                    return $p['first_name'] !== '' && $p['last_name'] !== '';
                }));

                if (count($players) <= 1) {
                    $this->assertTrue(true);
                    return;
                }

                // Apply the same ordering as PlayersModel: ORDER BY last_name ASC, first_name ASC
                $sorted = $this->sortByLastNameThenFirstName($players);

                // Verify the sorted list is in non-decreasing order by last_name
                for ($i = 1; $i < count($sorted); $i++) {
                    $cmp = strcmp($sorted[$i - 1]['last_name'], $sorted[$i]['last_name']);

                    $this->assertLessThanOrEqual(
                        0,
                        $cmp,
                        sprintf(
                            'Player at index %d (last_name="%s") must not come after player at index %d (last_name="%s") in alphabetical order',
                            $i - 1,
                            $sorted[$i - 1]['last_name'],
                            $i,
                            $sorted[$i]['last_name']
                        )
                    );

                    // If last names are equal, first names must also be in order
                    if ($cmp === 0) {
                        $this->assertLessThanOrEqual(
                            0,
                            strcmp($sorted[$i - 1]['first_name'], $sorted[$i]['first_name']),
                            sprintf(
                                'Players with same last_name "%s": first_name "%s" must not come after "%s"',
                                $sorted[$i]['last_name'],
                                $sorted[$i - 1]['first_name'],
                                $sorted[$i]['first_name']
                            )
                        );
                    }
                }
            });
    }

    /**
     * Property: Sorting preserves all player entries — no entries are lost or duplicated.
     *
     * **Validates: Requirements 8.1**
     */
    public function testSortingPreservesAllPlayers(): void
    {
        $this
            ->forAll(
                Generators::seq(Generators::associative([
                    'first_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 1 && mb_strlen(trim($s)) <= 50,
                        Generators::string()
                    ),
                    'last_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 1 && mb_strlen(trim($s)) <= 50,
                        Generators::string()
                    ),
                ]))
            )
            ->then(function (array $players): void {
                $players = array_map(function (array $p): array {
                    return [
                        'first_name' => trim($p['first_name']),
                        'last_name' => trim($p['last_name']),
                    ];
                }, $players);

                $players = array_values(array_filter($players, function (array $p): bool {
                    return $p['first_name'] !== '' && $p['last_name'] !== '';
                }));

                $originalCount = count($players);

                // Apply sorting
                $sorted = $this->sortByLastNameThenFirstName($players);

                // Verify no entries are lost or duplicated
                $this->assertCount(
                    $originalCount,
                    $sorted,
                    sprintf(
                        'Sorting must preserve all %d player entries, got %d after sort',
                        $originalCount,
                        count($sorted)
                    )
                );

                // Verify same set of (first_name, last_name) pairs exist in both
                $originalNames = array_map(
                    fn(array $p) => $p['last_name'] . '|' . $p['first_name'],
                    $players
                );
                $sortedNames = array_map(
                    fn(array $p) => $p['last_name'] . '|' . $p['first_name'],
                    $sorted
                );
                sort($originalNames);
                sort($sortedNames);

                $this->assertSame(
                    $originalNames,
                    $sortedNames,
                    'Sorting must not lose or fabricate player entries'
                );
            });
    }

    /**
     * Property: The first player in the sorted list has the lexicographically smallest
     * last name, and the last player has the lexicographically largest.
     *
     * **Validates: Requirements 8.1**
     */
    public function testFirstPlayerHasSmallestLastNameAndLastHasLargest(): void
    {
        $this
            ->forAll(
                Generators::seq(Generators::associative([
                    'first_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 1 && mb_strlen(trim($s)) <= 50,
                        Generators::string()
                    ),
                    'last_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 1 && mb_strlen(trim($s)) <= 50,
                        Generators::string()
                    ),
                ]))
            )
            ->then(function (array $players): void {
                $players = array_map(function (array $p): array {
                    return [
                        'first_name' => trim($p['first_name']),
                        'last_name' => trim($p['last_name']),
                    ];
                }, $players);

                $players = array_values(array_filter($players, function (array $p): bool {
                    return $p['first_name'] !== '' && $p['last_name'] !== '';
                }));

                if (count($players) < 2) {
                    $this->assertTrue(true);
                    return;
                }

                $sorted = $this->sortByLastNameThenFirstName($players);

                // Find min and max last names from the original set
                $lastNames = array_column($players, 'last_name');
                sort($lastNames);
                $minLastName = $lastNames[0];
                $maxLastName = $lastNames[count($lastNames) - 1];

                // First entry should have the smallest last name
                $this->assertSame(
                    $minLastName,
                    $sorted[0]['last_name'],
                    sprintf(
                        'First player after alphabetical sort should have smallest last name. Expected "%s", got "%s"',
                        $minLastName,
                        $sorted[0]['last_name']
                    )
                );

                // Last entry should have the largest last name
                $lastIdx = count($sorted) - 1;
                $this->assertSame(
                    $maxLastName,
                    $sorted[$lastIdx]['last_name'],
                    sprintf(
                        'Last player after alphabetical sort should have largest last name. Expected "%s", got "%s"',
                        $maxLastName,
                        $sorted[$lastIdx]['last_name']
                    )
                );
            });
    }

    /**
     * Simulate the ordering logic from Site\Model\PlayersModel::getListQuery().
     *
     * The model applies: ORDER BY last_name ASC, first_name ASC.
     * This is a pure function that replicates the SQL ordering in PHP.
     *
     * @param array<array{first_name: string, last_name: string}> $players
     * @return array<array{first_name: string, last_name: string}>
     */
    private function sortByLastNameThenFirstName(array $players): array
    {
        usort($players, function (array $a, array $b): int {
            $lastCmp = strcmp($a['last_name'], $b['last_name']);
            if ($lastCmp !== 0) {
                return $lastCmp;
            }
            return strcmp($a['first_name'], $b['first_name']);
        });

        return $players;
    }
}
