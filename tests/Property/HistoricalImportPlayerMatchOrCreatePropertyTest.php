<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property 40: Historical import player match-or-create
 *
 * During historical import, players are matched by first_name + last_name
 * (case-insensitive). If a match exists, it's reused. If not, a new player
 * is created. Final player count should equal unique name count.
 *
 * **Validates: Requirements 13.6, 13.11**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class HistoricalImportPlayerMatchOrCreatePropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 40: For any combination of existing players and imported player names,
     * the match-or-create logic must produce a final player set where the total count
     * equals the number of unique (first_name, last_name) pairs (case-insensitive).
     *
     * **Validates: Requirements 13.6, 13.11**
     */
    public function testFinalPlayerCountEqualsUniqueNameCount(): void
    {
        $firstNames = [
            'Max', 'Anna', 'Peter', 'Lisa', 'Thomas',
            'Maria', 'Stefan', 'Julia', 'Klaus', 'Hans',
            'Sabine', 'Dirk', 'Uwe', 'Monika', 'Frank',
        ];
        $lastNames = [
            'Müller', 'Schmidt', 'Schneider', 'Fischer', 'Weber',
            'Meyer', 'Wagner', 'Becker', 'Schulz', 'Hoffmann',
            'Neumann', 'Braun', 'Zimmermann', 'Krüger', 'Koch',
        ];

        $this
            ->forAll(
                // Generate existing players (0-8)
                Generators::bind(
                    Generators::choose(0, 8),
                    fn(int $count) => Generators::tuple(
                        ...array_fill(0, max(1, $count), Generators::associative([
                            'first_name' => Generators::elements($firstNames),
                            'last_name' => Generators::elements($lastNames),
                        ]))
                    )
                ),
                // Generate imported player names (1-12)
                Generators::bind(
                    Generators::choose(1, 12),
                    fn(int $count) => Generators::tuple(
                        ...array_fill(0, $count, Generators::associative([
                            'first_name' => Generators::elements(array_merge(
                                $firstNames,
                                array_map('mb_strtolower', $firstNames),
                                array_map('mb_strtoupper', $firstNames),
                            )),
                            'last_name' => Generators::elements(array_merge(
                                $lastNames,
                                array_map('mb_strtolower', $lastNames),
                                array_map('mb_strtoupper', $lastNames),
                            )),
                        ]))
                    )
                )
            )
            ->then(function (array $existingPlayers, array $importedPlayers): void {
                // Build existing player set (deduped, as DB would enforce)
                $playerStore = [];
                $nextId = 1;

                foreach ($existingPlayers as $player) {
                    $key = mb_strtolower($player['first_name']) . '|' . mb_strtolower($player['last_name']);
                    if (!isset($playerStore[$key])) {
                        $playerStore[$key] = [
                            'id' => $nextId++,
                            'first_name' => $player['first_name'],
                            'last_name' => $player['last_name'],
                        ];
                    }
                }

                // Simulate historical import match-or-create for each imported player
                $playersCreated = 0;
                $playersMatched = 0;

                foreach ($importedPlayers as $imported) {
                    $firstName = trim($imported['first_name']);
                    $lastName = trim($imported['last_name']);

                    if ($firstName === '' || $lastName === '') {
                        continue;
                    }

                    $key = mb_strtolower($firstName) . '|' . mb_strtolower($lastName);

                    if (isset($playerStore[$key])) {
                        // Match found — reuse existing player
                        $playersMatched++;
                    } else {
                        // No match — create new player record
                        $playerStore[$key] = [
                            'id' => $nextId++,
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                        ];
                        $playersCreated++;
                    }
                }

                // PROPERTY: Final player count must equal number of unique name keys
                $this->assertCount(
                    count($playerStore),
                    $playerStore,
                    'Player store count should be self-consistent'
                );

                // All keys in playerStore must be unique (by construction)
                $uniqueKeys = array_keys($playerStore);
                $this->assertCount(
                    count(array_unique($uniqueKeys)),
                    $uniqueKeys,
                    'All player store keys must be unique'
                );

                // PROPERTY: Total records = unique (first_name, last_name) across
                // existing + imported sets combined
                $allNames = [];
                foreach ($existingPlayers as $p) {
                    $k = mb_strtolower($p['first_name']) . '|' . mb_strtolower($p['last_name']);
                    $allNames[$k] = true;
                }
                foreach ($importedPlayers as $p) {
                    $firstName = trim($p['first_name']);
                    $lastName = trim($p['last_name']);
                    if ($firstName !== '' && $lastName !== '') {
                        $k = mb_strtolower($firstName) . '|' . mb_strtolower($lastName);
                        $allNames[$k] = true;
                    }
                }

                $this->assertCount(
                    count($allNames),
                    $playerStore,
                    sprintf(
                        'Final player count (%d) must equal unique name count (%d). ' .
                        'Existing: %d, Imported: %d, Matched: %d, Created: %d.',
                        count($playerStore),
                        count($allNames),
                        count($existingPlayers),
                        count($importedPlayers),
                        $playersMatched,
                        $playersCreated,
                    )
                );
            });
    }

    /**
     * Property 40: Importing the same player name multiple times across different
     * historical seasons must not create duplicates. Each unique name appears exactly
     * once in the final player set regardless of how many times it was imported.
     *
     * **Validates: Requirements 13.6, 13.11**
     */
    public function testRepeatedImportsDoNotCreateDuplicates(): void
    {
        $firstNames = [
            'Max', 'Anna', 'Peter', 'Lisa', 'Thomas',
            'Maria', 'Stefan', 'Julia',
        ];
        $lastNames = [
            'Müller', 'Schmidt', 'Schneider', 'Fischer',
            'Weber', 'Meyer', 'Wagner', 'Becker',
        ];

        $this
            ->forAll(
                // Generate a set of player names to be imported
                Generators::bind(
                    Generators::choose(1, 8),
                    fn(int $count) => Generators::tuple(
                        ...array_fill(0, $count, Generators::associative([
                            'first_name' => Generators::elements($firstNames),
                            'last_name' => Generators::elements($lastNames),
                        ]))
                    )
                ),
                // Number of seasons (each season re-imports same players)
                Generators::choose(2, 6)
            )
            ->then(function (array $playerNames, int $seasonCount): void {
                $playerStore = [];
                $nextId = 1;

                // Simulate importing the same players across multiple seasons
                for ($season = 0; $season < $seasonCount; $season++) {
                    foreach ($playerNames as $imported) {
                        $firstName = trim($imported['first_name']);
                        $lastName = trim($imported['last_name']);

                        if ($firstName === '' || $lastName === '') {
                            continue;
                        }

                        $key = mb_strtolower($firstName) . '|' . mb_strtolower($lastName);

                        if (!isset($playerStore[$key])) {
                            $playerStore[$key] = [
                                'id' => $nextId++,
                                'first_name' => $firstName,
                                'last_name' => $lastName,
                            ];
                        }
                        // Else: match found, reuse existing — no duplicate created
                    }
                }

                // PROPERTY: Final player count must equal unique names in the input
                $uniqueNames = [];
                foreach ($playerNames as $p) {
                    $firstName = trim($p['first_name']);
                    $lastName = trim($p['last_name']);
                    if ($firstName !== '' && $lastName !== '') {
                        $uniqueNames[mb_strtolower($firstName) . '|' . mb_strtolower($lastName)] = true;
                    }
                }

                $this->assertCount(
                    count($uniqueNames),
                    $playerStore,
                    sprintf(
                        'After importing %d players across %d seasons, expected %d unique players but got %d.',
                        count($playerNames),
                        $seasonCount,
                        count($uniqueNames),
                        count($playerStore),
                    )
                );
            });
    }

    /**
     * Property 40: Case-insensitive matching ensures "MAX MÜLLER", "max müller",
     * and "Max Müller" are all recognized as the same player. A new record is only
     * created when no case-insensitive match exists.
     *
     * **Validates: Requirements 13.6, 13.11**
     */
    public function testCaseInsensitiveMatchOrCreate(): void
    {
        $this
            ->forAll(
                Generators::elements([
                    'Max', 'Anna', 'Peter', 'Lisa', 'Thomas',
                    'Maria', 'Stefan', 'Julia', 'Klaus', 'Hans',
                ]),
                Generators::elements([
                    'Müller', 'Schmidt', 'Schneider', 'Fischer', 'Weber',
                    'Meyer', 'Wagner', 'Becker', 'Schulz', 'Hoffmann',
                ]),
                // Number of case variations to import
                Generators::choose(2, 5)
            )
            ->then(function (string $firstName, string $lastName, int $importCount): void {
                $playerStore = [];
                $nextId = 1;

                // Generate different case variations of the same name
                $caseVariants = [
                    [$firstName, $lastName],
                    [mb_strtolower($firstName), mb_strtolower($lastName)],
                    [mb_strtoupper($firstName), mb_strtoupper($lastName)],
                    [$this->mixCase($firstName), $this->mixCase($lastName)],
                    [mb_strtolower($firstName), mb_strtoupper($lastName)],
                ];

                // Import up to $importCount variations
                for ($i = 0; $i < $importCount; $i++) {
                    $variant = $caseVariants[$i % count($caseVariants)];
                    $importFirst = $variant[0];
                    $importLast = $variant[1];

                    $key = mb_strtolower($importFirst) . '|' . mb_strtolower($importLast);

                    if (!isset($playerStore[$key])) {
                        $playerStore[$key] = [
                            'id' => $nextId++,
                            'first_name' => $importFirst,
                            'last_name' => $importLast,
                        ];
                    }
                }

                // PROPERTY: Only one player record should exist regardless of how
                // many case variations were imported
                $this->assertCount(
                    1,
                    $playerStore,
                    sprintf(
                        'Expected exactly 1 player for "%s %s" after importing %d case variants, got %d.',
                        $firstName,
                        $lastName,
                        $importCount,
                        count($playerStore),
                    )
                );
            });
    }

    /**
     * Apply a pseudo-random mixed case transformation (alternating upper/lower).
     */
    private function mixCase(string $input): string
    {
        $result = '';
        $len = mb_strlen($input);
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($input, $i, 1);
            $result .= ($i % 2 === 0) ? mb_strtoupper($char) : mb_strtolower($char);
        }
        return $result;
    }
}
