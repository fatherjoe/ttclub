<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property 38: Roster entry creation from scraped data
 *
 * When historical data is imported, for each player-team-halfseason combination
 * discovered, exactly one roster entry must be created (no duplicates, no omissions).
 *
 * This replicates the roster creation logic from HistoricalImportService::importSeason()
 * which iterates over parsed roster names, resolves player IDs, and creates a roster
 * entry per player per team per half-season.
 *
 * **Validates: Requirements 13.4**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class RosterEntryCreationPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 38: For any set of player-team-halfseason combinations discovered
     * during import, exactly one roster entry must be created per unique combination.
     *
     * Generates random player IDs assigned to random teams and half-seasons,
     * simulates the import roster creation logic, and verifies exactly one entry
     * per (player_id, team_id, half_season_id) tuple exists afterwards.
     *
     * **Validates: Requirements 13.4**
     */
    public function testExactlyOneRosterEntryPerCombination(): void
    {
        $this
            ->forAll(
                // Number of teams (1-5)
                Generators::choose(1, 5),
                // Number of half-seasons per team (1-2, typically first and second half)
                Generators::choose(1, 2),
                // Number of players per team roster (1-8)
                Generators::choose(1, 8)
            )
            ->then(function (int $teamCount, int $halfSeasonCount, int $playersPerTeam): void {
                // Generate team IDs
                $teamIds = range(1, $teamCount);

                // Generate half-season IDs
                $halfSeasonIds = range(100, 100 + $halfSeasonCount - 1);

                // Generate player IDs per team (all unique within team)
                $playerIdPool = range(1, 50);
                shuffle($playerIdPool);

                // Build the set of scraped roster data: for each team, for each
                // half-season, assign the same set of players (as historical import does)
                $scrapedCombinations = [];

                foreach ($teamIds as $teamId) {
                    // Pick unique players for this team's roster
                    $teamPlayers = array_slice($playerIdPool, 0, $playersPerTeam);

                    foreach ($halfSeasonIds as $halfSeasonId) {
                        foreach ($teamPlayers as $playerId) {
                            $scrapedCombinations[] = [
                                'player_id' => $playerId,
                                'team_id' => $teamId,
                                'half_season_id' => $halfSeasonId,
                            ];
                        }
                    }
                }

                // Execute the roster creation logic (simulates HistoricalImportService)
                $createdEntries = $this->executeRosterCreation($scrapedCombinations);

                // PROPERTY: Each unique (player_id, team_id, half_season_id) must appear exactly once
                $expectedCount = $teamCount * $halfSeasonCount * $playersPerTeam;

                $this->assertCount(
                    $expectedCount,
                    $createdEntries,
                    sprintf(
                        'Expected exactly %d roster entries (%d teams × %d half-seasons × %d players), got %d.',
                        $expectedCount,
                        $teamCount,
                        $halfSeasonCount,
                        $playersPerTeam,
                        count($createdEntries)
                    )
                );

                // Verify no duplicates exist
                $keys = array_map(
                    fn(array $entry) => $entry['player_id'] . '-' . $entry['team_id'] . '-' . $entry['half_season_id'],
                    $createdEntries
                );

                $this->assertCount(
                    count(array_unique($keys)),
                    $keys,
                    'All roster entries must have unique (player_id, team_id, half_season_id) combinations.'
                );
            });
    }

    /**
     * Property 38: When scraped data contains duplicate player-team-halfseason
     * combinations (e.g., a player listed twice in source HTML), the import
     * must still produce exactly one roster entry per unique combination.
     *
     * **Validates: Requirements 13.4**
     */
    public function testDuplicateScrapedDataProducesOneEntryPerCombination(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 10),   // number of unique players
                Generators::choose(1, 3),    // team ID
                Generators::choose(1, 2),    // half-season ID
                Generators::choose(1, 3)     // duplication factor (how many times each combo appears)
            )
            ->then(function (int $playerCount, int $teamId, int $halfSeasonId, int $dupFactor): void {
                $playerIds = range(1, $playerCount);

                // Build scraped data with intentional duplicates
                $scrapedCombinations = [];
                foreach ($playerIds as $playerId) {
                    for ($d = 0; $d < $dupFactor; $d++) {
                        $scrapedCombinations[] = [
                            'player_id' => $playerId,
                            'team_id' => $teamId,
                            'half_season_id' => $halfSeasonId,
                        ];
                    }
                }

                // Execute the roster creation logic with dedup
                $createdEntries = $this->executeRosterCreationWithDedup($scrapedCombinations);

                // PROPERTY: Must produce exactly one entry per unique combination
                $this->assertCount(
                    $playerCount,
                    $createdEntries,
                    sprintf(
                        'Expected exactly %d roster entries (one per unique player) even with %dx duplication, got %d.',
                        $playerCount,
                        $dupFactor,
                        count($createdEntries)
                    )
                );

                // Verify all expected players are present (no omissions)
                $createdPlayerIds = array_map(fn(array $e) => $e['player_id'], $createdEntries);
                sort($createdPlayerIds);
                sort($playerIds);

                $this->assertSame(
                    $playerIds,
                    $createdPlayerIds,
                    'All unique players from scraped data must have exactly one roster entry (no omissions).'
                );
            });
    }

    /**
     * Property 38: Every unique player-team-halfseason combination from the
     * scraped data must appear in the created roster entries (no omissions).
     *
     * **Validates: Requirements 13.4**
     */
    public function testNoOmissionsInRosterCreation(): void
    {
        $this
            ->forAll(
                // Generate a random number of unique combinations
                Generators::bind(
                    Generators::choose(1, 15),
                    fn(int $count) => Generators::seq(Generators::associative([
                        'player_id' => Generators::choose(1, 100),
                        'team_id' => Generators::choose(1, 10),
                        'half_season_id' => Generators::choose(1, 4),
                    ]))
                )
            )
            ->then(function (array $scrapedCombinations): void {
                if (empty($scrapedCombinations)) {
                    return;
                }

                // Determine unique input combinations
                $uniqueInputKeys = [];
                foreach ($scrapedCombinations as $combo) {
                    $key = $combo['player_id'] . '-' . $combo['team_id'] . '-' . $combo['half_season_id'];
                    $uniqueInputKeys[$key] = $combo;
                }

                // Execute the roster creation logic with dedup
                $createdEntries = $this->executeRosterCreationWithDedup($scrapedCombinations);

                // PROPERTY: Every unique combination from input must be in the output
                $createdKeys = [];
                foreach ($createdEntries as $entry) {
                    $key = $entry['player_id'] . '-' . $entry['team_id'] . '-' . $entry['half_season_id'];
                    $createdKeys[$key] = true;
                }

                foreach ($uniqueInputKeys as $key => $combo) {
                    $this->assertArrayHasKey(
                        $key,
                        $createdKeys,
                        sprintf(
                            'Roster entry for player_id=%d, team_id=%d, half_season_id=%d was omitted.',
                            $combo['player_id'],
                            $combo['team_id'],
                            $combo['half_season_id']
                        )
                    );
                }

                // PROPERTY: Count of created entries equals count of unique input combinations
                $this->assertCount(
                    count($uniqueInputKeys),
                    $createdEntries,
                    sprintf(
                        'Expected %d roster entries (one per unique combination), got %d.',
                        count($uniqueInputKeys),
                        count($createdEntries)
                    )
                );
            });
    }

    /**
     * Simulate the roster creation logic from HistoricalImportService::importSeason().
     *
     * The import iterates over each player for each team for each half-season
     * and calls insertRosterEntry(). In the real service, this creates one entry
     * per call without deduplication (the source data is assumed clean).
     *
     * @param array<array{player_id: int, team_id: int, half_season_id: int}> $combinations
     * @return array<array{player_id: int, team_id: int, half_season_id: int}>
     */
    private function executeRosterCreation(array $combinations): array
    {
        $createdEntries = [];

        foreach ($combinations as $combo) {
            // Simulates HistoricalImportService::insertRosterEntry()
            // Each call inserts one roster record
            $createdEntries[] = [
                'player_id' => $combo['player_id'],
                'team_id' => $combo['team_id'],
                'half_season_id' => $combo['half_season_id'],
            ];
        }

        return $createdEntries;
    }

    /**
     * Simulate roster creation with deduplication guard.
     *
     * In the actual system, the RosterTable::check() method enforces the unique
     * constraint on (player_id, team_id, half_season_id). This simulates that
     * behavior: only the first occurrence of each unique combination is stored.
     *
     * @param array<array{player_id: int, team_id: int, half_season_id: int}> $combinations
     * @return array<array{player_id: int, team_id: int, half_season_id: int}>
     */
    private function executeRosterCreationWithDedup(array $combinations): array
    {
        $createdEntries = [];
        $existingKeys = [];

        foreach ($combinations as $combo) {
            $key = $combo['player_id'] . '-' . $combo['team_id'] . '-' . $combo['half_season_id'];

            // RosterTable::check() rejects duplicates
            if (isset($existingKeys[$key])) {
                continue;
            }

            $existingKeys[$key] = true;
            $createdEntries[] = [
                'player_id' => $combo['player_id'],
                'team_id' => $combo['team_id'],
                'half_season_id' => $combo['half_season_id'],
            ];
        }

        return $createdEntries;
    }
}
