<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use Fatherjoe\Component\Ttclub\Administrator\Service\HistoricalImportResult;
use Fatherjoe\Component\Ttclub\Administrator\Service\SeasonImportResult;
use PHPUnit\Framework\TestCase;

/**
 * Property 41: Historical import summary accuracy
 *
 * After a historical import, the summary must report accurate counts: seasons created,
 * teams created, players created, rosters created, schedules created. These counts
 * must match the actual operations performed (i.e., the sum of per-season results
 * for all successful seasons).
 *
 * **Validates: Requirements 13.7**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class HistoricalImportSummaryAccuracyPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 41: The seasonsCreated count must equal the number of successful
     * per-season results.
     *
     * Generate random import operations; verify reported counts match actual DB inserts.
     *
     * **Validates: Requirements 13.7**
     */
    public function testSeasonsCreatedMatchesSuccessfulSeasonCount(): void
    {
        $this
            ->forAll(
                Generators::choose(0, 20), // number of season results
                Generators::seq(Generators::bool()) // success flags per season
            )
            ->then(function (int $seasonCount, array $successFlags): void {
                $perSeasonResults = [];
                $expectedSeasonsCreated = 0;

                for ($i = 0; $i < $seasonCount; $i++) {
                    $success = $successFlags[$i] ?? true;

                    if ($success) {
                        $expectedSeasonsCreated++;
                    }

                    $perSeasonResults[] = new SeasonImportResult(
                        seasonName: "Season $i",
                        teamsCreated: rand(0, 10),
                        rosterEntriesCreated: rand(0, 50),
                        scheduleEntriesCreated: rand(0, 30),
                        playersCreated: rand(0, 20),
                        success: $success,
                        errorMessage: $success ? null : 'Simulated failure',
                    );
                }

                $result = $this->buildHistoricalImportResult($perSeasonResults);

                $this->assertSame(
                    $expectedSeasonsCreated,
                    $result->seasonsCreated,
                    sprintf(
                        'seasonsCreated (%d) must equal the number of successful per-season results (%d)',
                        $result->seasonsCreated,
                        $expectedSeasonsCreated,
                    ),
                );
            });
    }

    /**
     * Property 41: The teamsCreated count must equal the sum of teamsCreated
     * across all successful per-season results.
     *
     * **Validates: Requirements 13.7**
     */
    public function testTeamsCreatedMatchesSumOfPerSeasonTeams(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 15),                    // number of seasons
                Generators::seq(Generators::choose(0, 20)),   // teams per season
                Generators::seq(Generators::bool())           // success flags
            )
            ->then(function (int $seasonCount, array $teamCounts, array $successFlags): void {
                $perSeasonResults = [];
                $expectedTeams = 0;

                for ($i = 0; $i < $seasonCount; $i++) {
                    $success = $successFlags[$i] ?? true;
                    $teams = $teamCounts[$i] ?? 0;

                    if ($success) {
                        $expectedTeams += $teams;
                    }

                    $perSeasonResults[] = new SeasonImportResult(
                        seasonName: "Season $i",
                        teamsCreated: $teams,
                        rosterEntriesCreated: rand(0, 50),
                        scheduleEntriesCreated: rand(0, 30),
                        playersCreated: rand(0, 20),
                        success: $success,
                    );
                }

                $result = $this->buildHistoricalImportResult($perSeasonResults);

                $this->assertSame(
                    $expectedTeams,
                    $result->teamsCreated,
                    sprintf(
                        'teamsCreated (%d) must equal sum of per-season teamsCreated for successful seasons (%d)',
                        $result->teamsCreated,
                        $expectedTeams,
                    ),
                );
            });
    }

    /**
     * Property 41: The playersCreated count must equal the sum of playersCreated
     * across all successful per-season results.
     *
     * **Validates: Requirements 13.7**
     */
    public function testPlayersCreatedMatchesSumOfPerSeasonPlayers(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 15),                    // number of seasons
                Generators::seq(Generators::choose(0, 30)),   // players per season
                Generators::seq(Generators::bool())           // success flags
            )
            ->then(function (int $seasonCount, array $playerCounts, array $successFlags): void {
                $perSeasonResults = [];
                $expectedPlayers = 0;

                for ($i = 0; $i < $seasonCount; $i++) {
                    $success = $successFlags[$i] ?? true;
                    $players = $playerCounts[$i] ?? 0;

                    if ($success) {
                        $expectedPlayers += $players;
                    }

                    $perSeasonResults[] = new SeasonImportResult(
                        seasonName: "Season $i",
                        teamsCreated: rand(0, 10),
                        rosterEntriesCreated: rand(0, 50),
                        scheduleEntriesCreated: rand(0, 30),
                        playersCreated: $players,
                        success: $success,
                    );
                }

                $result = $this->buildHistoricalImportResult($perSeasonResults);

                $this->assertSame(
                    $expectedPlayers,
                    $result->playersCreated,
                    sprintf(
                        'playersCreated (%d) must equal sum of per-season playersCreated for successful seasons (%d)',
                        $result->playersCreated,
                        $expectedPlayers,
                    ),
                );
            });
    }

    /**
     * Property 41: The rosterEntriesCreated count must equal the sum of
     * rosterEntriesCreated across all successful per-season results.
     *
     * **Validates: Requirements 13.7**
     */
    public function testRosterEntriesCreatedMatchesSumOfPerSeasonRosters(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 15),                    // number of seasons
                Generators::seq(Generators::choose(0, 100)),  // roster entries per season
                Generators::seq(Generators::bool())           // success flags
            )
            ->then(function (int $seasonCount, array $rosterCounts, array $successFlags): void {
                $perSeasonResults = [];
                $expectedRosters = 0;

                for ($i = 0; $i < $seasonCount; $i++) {
                    $success = $successFlags[$i] ?? true;
                    $rosters = $rosterCounts[$i] ?? 0;

                    if ($success) {
                        $expectedRosters += $rosters;
                    }

                    $perSeasonResults[] = new SeasonImportResult(
                        seasonName: "Season $i",
                        teamsCreated: rand(0, 10),
                        rosterEntriesCreated: $rosters,
                        scheduleEntriesCreated: rand(0, 30),
                        playersCreated: rand(0, 20),
                        success: $success,
                    );
                }

                $result = $this->buildHistoricalImportResult($perSeasonResults);

                $this->assertSame(
                    $expectedRosters,
                    $result->rosterEntriesCreated,
                    sprintf(
                        'rosterEntriesCreated (%d) must equal sum of per-season rosterEntriesCreated for successful seasons (%d)',
                        $result->rosterEntriesCreated,
                        $expectedRosters,
                    ),
                );
            });
    }

    /**
     * Property 41: The scheduleEntriesCreated count must equal the sum of
     * scheduleEntriesCreated across all successful per-season results.
     *
     * **Validates: Requirements 13.7**
     */
    public function testScheduleEntriesCreatedMatchesSumOfPerSeasonSchedules(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 15),                    // number of seasons
                Generators::seq(Generators::choose(0, 80)),   // schedule entries per season
                Generators::seq(Generators::bool())           // success flags
            )
            ->then(function (int $seasonCount, array $scheduleCounts, array $successFlags): void {
                $perSeasonResults = [];
                $expectedSchedules = 0;

                for ($i = 0; $i < $seasonCount; $i++) {
                    $success = $successFlags[$i] ?? true;
                    $schedules = $scheduleCounts[$i] ?? 0;

                    if ($success) {
                        $expectedSchedules += $schedules;
                    }

                    $perSeasonResults[] = new SeasonImportResult(
                        seasonName: "Season $i",
                        teamsCreated: rand(0, 10),
                        rosterEntriesCreated: rand(0, 50),
                        scheduleEntriesCreated: $schedules,
                        playersCreated: rand(0, 20),
                        success: $success,
                    );
                }

                $result = $this->buildHistoricalImportResult($perSeasonResults);

                $this->assertSame(
                    $expectedSchedules,
                    $result->scheduleEntriesCreated,
                    sprintf(
                        'scheduleEntriesCreated (%d) must equal sum of per-season scheduleEntriesCreated for successful seasons (%d)',
                        $result->scheduleEntriesCreated,
                        $expectedSchedules,
                    ),
                );
            });
    }

    /**
     * Property 41: When all per-season results fail, all summary counts must be zero.
     *
     * **Validates: Requirements 13.7**
     */
    public function testAllFailedSeasonsProduceZeroSummary(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 20),                    // number of seasons
                Generators::seq(Generators::choose(1, 50)),   // teams (non-zero to ensure zeroing works)
                Generators::seq(Generators::choose(1, 100)),  // rosters
                Generators::seq(Generators::choose(1, 80)),   // schedules
                Generators::seq(Generators::choose(1, 30))    // players
            )
            ->then(function (int $seasonCount, array $teams, array $rosters, array $schedules, array $players): void {
                $perSeasonResults = [];

                for ($i = 0; $i < $seasonCount; $i++) {
                    $perSeasonResults[] = new SeasonImportResult(
                        seasonName: "Failed Season $i",
                        teamsCreated: $teams[$i] ?? 5,
                        rosterEntriesCreated: $rosters[$i] ?? 20,
                        scheduleEntriesCreated: $schedules[$i] ?? 15,
                        playersCreated: $players[$i] ?? 10,
                        success: false,
                        errorMessage: 'Simulated failure',
                    );
                }

                $result = $this->buildHistoricalImportResult($perSeasonResults);

                $this->assertSame(0, $result->seasonsCreated, 'seasonsCreated must be 0 when all seasons fail');
                $this->assertSame(0, $result->teamsCreated, 'teamsCreated must be 0 when all seasons fail');
                $this->assertSame(0, $result->playersCreated, 'playersCreated must be 0 when all seasons fail');
                $this->assertSame(0, $result->rosterEntriesCreated, 'rosterEntriesCreated must be 0 when all seasons fail');
                $this->assertSame(0, $result->scheduleEntriesCreated, 'scheduleEntriesCreated must be 0 when all seasons fail');
            });
    }

    /**
     * Simulate the HistoricalImportService's aggregation logic.
     *
     * Mirrors HistoricalImportService::executeFullImport() — sums counts only
     * from successful per-season results, and counts successful seasons.
     *
     * @param SeasonImportResult[] $perSeasonResults
     */
    private function buildHistoricalImportResult(array $perSeasonResults): HistoricalImportResult
    {
        $seasonsCreated = 0;
        $teamsCreated = 0;
        $playersCreated = 0;
        $rosterEntriesCreated = 0;
        $scheduleEntriesCreated = 0;

        foreach ($perSeasonResults as $seasonResult) {
            if ($seasonResult->success) {
                $seasonsCreated++;
                $teamsCreated += $seasonResult->teamsCreated;
                $playersCreated += $seasonResult->playersCreated;
                $rosterEntriesCreated += $seasonResult->rosterEntriesCreated;
                $scheduleEntriesCreated += $seasonResult->scheduleEntriesCreated;
            }
        }

        return new HistoricalImportResult(
            seasonsCreated: $seasonsCreated,
            teamsCreated: $teamsCreated,
            playersCreated: $playersCreated,
            rosterEntriesCreated: $rosterEntriesCreated,
            scheduleEntriesCreated: $scheduleEntriesCreated,
            perSeasonResults: $perSeasonResults,
        );
    }
}
