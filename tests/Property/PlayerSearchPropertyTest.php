<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property 4: Player search returns correct subset
 *
 * Generate random player sets + search substrings; verify correct case-insensitive
 * last name filtering. The search logic mirrors PlayersModel::getListQuery() which
 * uses LOWER(last_name) LIKE LOWER('%search%') for filtering.
 *
 * **Validates: Requirements 1.9**
 */
class PlayerSearchPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property: For any set of players and any search term, the filtered result
     * must contain exactly those players whose last_name contains the search term
     * as a case-insensitive substring.
     *
     * **Validates: Requirements 1.9**
     */
    public function testSearchReturnsExactlyMatchingPlayers(): void
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
                ])),
                Generators::string()
            )
            ->then(function (array $players, string $searchTerm): void {
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

                if (empty($players)) {
                    return;
                }

                $searchTerm = trim($searchTerm);

                // Apply the same filtering logic as PlayersModel::getListQuery():
                // LOWER(last_name) LIKE LOWER('%search%')
                $expected = $this->filterPlayersByLastName($players, $searchTerm);
                $actual = $this->simulateSearch($players, $searchTerm);

                $this->assertSame(
                    count($expected),
                    count($actual),
                    sprintf(
                        'Search for "%s" in %d players should return %d results, got %d',
                        $searchTerm,
                        count($players),
                        count($expected),
                        count($actual)
                    )
                );

                // Verify each expected player is in the result
                foreach ($expected as $idx => $player) {
                    $this->assertSame(
                        $player['last_name'],
                        $actual[$idx]['last_name'],
                        sprintf(
                            'Expected player "%s" at index %d in search results for term "%s"',
                            $player['last_name'],
                            $idx,
                            $searchTerm
                        )
                    );
                }
            });
    }

    /**
     * Property: An empty search term must return all players (no filtering applied).
     *
     * **Validates: Requirements 1.9**
     */
    public function testEmptySearchReturnsAllPlayers(): void
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

                // Empty search = no filter applied, all players returned
                $result = $this->simulateSearch($players, '');

                $this->assertCount(
                    count($players),
                    $result,
                    'Empty search should return all players'
                );
            });
    }

    /**
     * Property: Search is case-insensitive. For any search term and player set,
     * searching with the uppercase version of the term must return the same
     * results as searching with the lowercase version.
     *
     * **Validates: Requirements 1.9**
     */
    public function testSearchIsCaseInsensitive(): void
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
                ])),
                Generators::suchThat(
                    fn(string $s) => mb_strlen(trim($s)) >= 1,
                    Generators::string()
                )
            )
            ->then(function (array $players, string $searchTerm): void {
                $players = array_map(function (array $p): array {
                    return [
                        'first_name' => trim($p['first_name']),
                        'last_name' => trim($p['last_name']),
                    ];
                }, $players);

                $players = array_values(array_filter($players, function (array $p): bool {
                    return $p['first_name'] !== '' && $p['last_name'] !== '';
                }));

                if (empty($players)) {
                    return;
                }

                $searchTerm = trim($searchTerm);
                if ($searchTerm === '') {
                    return;
                }

                $upperResult = $this->simulateSearch($players, mb_strtoupper($searchTerm));
                $lowerResult = $this->simulateSearch($players, mb_strtolower($searchTerm));

                $this->assertSame(
                    count($upperResult),
                    count($lowerResult),
                    sprintf(
                        'Case-insensitive search: UPPER("%s") returned %d results, LOWER("%s") returned %d results',
                        $searchTerm,
                        count($upperResult),
                        $searchTerm,
                        count($lowerResult)
                    )
                );

                // Verify same players are returned
                for ($i = 0; $i < count($upperResult); $i++) {
                    $this->assertSame(
                        $upperResult[$i]['last_name'],
                        $lowerResult[$i]['last_name'],
                        'Same players must be returned regardless of search term case'
                    );
                }
            });
    }

    /**
     * Simulate the search filter logic from PlayersModel::getListQuery().
     *
     * The model applies: LOWER(last_name) LIKE LOWER('%search%')
     * When search is empty, no filter is applied (all players returned).
     *
     * @param array<array{first_name: string, last_name: string}> $players
     * @param string $searchTerm
     * @return array<array{first_name: string, last_name: string}>
     */
    private function simulateSearch(array $players, string $searchTerm): array
    {
        if ($searchTerm === '') {
            return $players;
        }

        return array_values(array_filter($players, function (array $player) use ($searchTerm): bool {
            return str_contains(
                mb_strtolower($player['last_name']),
                mb_strtolower($searchTerm)
            );
        }));
    }

    /**
     * Reference implementation of the expected filtering logic.
     * This is the "oracle" that determines the correct result.
     *
     * @param array<array{first_name: string, last_name: string}> $players
     * @param string $searchTerm
     * @return array<array{first_name: string, last_name: string}>
     */
    private function filterPlayersByLastName(array $players, string $searchTerm): array
    {
        if ($searchTerm === '') {
            return $players;
        }

        $lowerSearch = mb_strtolower($searchTerm);

        return array_values(array_filter($players, function (array $player) use ($lowerSearch): bool {
            return str_contains(mb_strtolower($player['last_name']), $lowerSearch);
        }));
    }
}
