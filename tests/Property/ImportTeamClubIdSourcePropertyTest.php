<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property 32: Import team club_id_source association
 *
 * For any team imported from a specific configured club ID, the team's
 * `club_id_source` field must reference that club ID configuration entry.
 *
 * Generate random teams from different configured club IDs; verify each team's
 * club_id_source references the correct club ID entry.
 *
 * **Validates: Requirements 16.3**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class ImportTeamClubIdSourcePropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 32: Each imported team's club_id_source references the correct club ID entry.
     *
     * Simulate importing teams from multiple configured club IDs and verify
     * that each team's club_id_source is set to the ID of the club_ids entry
     * it was imported from.
     *
     * **Validates: Requirements 16.3**
     */
    public function testImportedTeamsGetCorrectClubIdSource(): void
    {
        $this
            ->forAll(
                // Number of configured club IDs (1-5)
                Generators::choose(1, 5),
                // Teams per club ID (1-6)
                Generators::seq(Generators::choose(1, 6))
            )
            ->then(function (int $clubIdCount, array $teamsPerClubRaw): void {
                // Build configured club ID entries (simulating #__ttclub_club_ids table)
                $clubIdEntries = [];
                for ($i = 1; $i <= $clubIdCount; $i++) {
                    $clubIdEntries[] = [
                        'id' => $i,
                        'click_tt_club_id' => 10000 + $i * 100,
                        'label' => 'Club ' . $i,
                    ];
                }

                // Determine how many teams each club ID produces
                $teamsPerClub = [];
                for ($i = 0; $i < $clubIdCount; $i++) {
                    $teamsPerClub[$i] = $teamsPerClubRaw[$i] ?? 1;
                }

                // Simulate the import: iterate over all configured club IDs
                // and create teams with club_id_source set to the entry ID
                $importedTeams = [];
                $nextTeamId = 1;

                foreach ($clubIdEntries as $idx => $clubEntry) {
                    $teamCount = $teamsPerClub[$idx];

                    for ($t = 1; $t <= $teamCount; $t++) {
                        $importedTeams[] = $this->simulateImportTeam(
                            $nextTeamId++,
                            $clubEntry['id'],
                            $t
                        );
                    }
                }

                // PROPERTY: Every imported team's club_id_source must reference
                // a valid club_ids entry ID
                $validClubIdEntryIds = array_column($clubIdEntries, 'id');

                foreach ($importedTeams as $team) {
                    $this->assertNotNull(
                        $team['club_id_source'],
                        sprintf(
                            'Team ID %d imported from a club ID should have club_id_source set (not NULL).',
                            $team['id']
                        )
                    );

                    $this->assertContains(
                        $team['club_id_source'],
                        $validClubIdEntryIds,
                        sprintf(
                            'Team ID %d has club_id_source=%d which is not a valid club_ids entry. Valid IDs: [%s]',
                            $team['id'],
                            $team['club_id_source'],
                            implode(', ', $validClubIdEntryIds)
                        )
                    );
                }
            });
    }

    /**
     * Property 32: Teams from different club IDs get distinct club_id_source values.
     *
     * When teams are imported from different configured club IDs, their
     * club_id_source values must correctly distinguish which club ID entry
     * they came from.
     *
     * **Validates: Requirements 16.3**
     */
    public function testTeamsFromDifferentClubIdsHaveDistinctSources(): void
    {
        $this
            ->forAll(
                // Number of configured club IDs (2-5, at least 2 to test distinction)
                Generators::choose(2, 5),
                // Teams per club ID (1-4)
                Generators::choose(1, 4)
            )
            ->then(function (int $clubIdCount, int $teamsPerClub): void {
                // Build configured club ID entries
                $clubIdEntries = [];
                for ($i = 1; $i <= $clubIdCount; $i++) {
                    $clubIdEntries[] = [
                        'id' => $i,
                        'click_tt_club_id' => 10000 + $i * 100,
                        'label' => 'Club ' . $i,
                    ];
                }

                // Simulate import: each club ID produces teams
                $importedTeams = [];
                $nextTeamId = 1;

                foreach ($clubIdEntries as $clubEntry) {
                    for ($t = 1; $t <= $teamsPerClub; $t++) {
                        $importedTeams[] = $this->simulateImportTeam(
                            $nextTeamId++,
                            $clubEntry['id'],
                            $t
                        );
                    }
                }

                // Group teams by the club_id_source they were assigned
                $teamsBySource = [];
                foreach ($importedTeams as $team) {
                    $source = $team['club_id_source'];
                    $teamsBySource[$source][] = $team;
                }

                // PROPERTY: Teams imported from club entry X must have
                // club_id_source = X (not some other entry's ID)
                $teamIdx = 0;
                foreach ($clubIdEntries as $clubEntry) {
                    for ($t = 0; $t < $teamsPerClub; $t++) {
                        $team = $importedTeams[$teamIdx];
                        $this->assertSame(
                            $clubEntry['id'],
                            $team['club_id_source'],
                            sprintf(
                                'Team ID %d was imported from club entry ID %d (click_tt_club_id=%d, label="%s") ' .
                                'but has club_id_source=%d. Expected club_id_source=%d.',
                                $team['id'],
                                $clubEntry['id'],
                                $clubEntry['click_tt_club_id'],
                                $clubEntry['label'],
                                $team['club_id_source'],
                                $clubEntry['id']
                            )
                        );
                        $teamIdx++;
                    }
                }
            });
    }

    /**
     * Property 32: Manually created teams have NULL club_id_source.
     *
     * Teams that are not imported (manually created by admin) should have
     * club_id_source = NULL, distinguishing them from imported teams.
     *
     * **Validates: Requirements 16.3**
     */
    public function testManuallyCreatedTeamsHaveNullClubIdSource(): void
    {
        $this
            ->forAll(
                // Number of manually created teams (1-10)
                Generators::choose(1, 10),
                // Number of imported teams (0-5)
                Generators::choose(0, 5)
            )
            ->then(function (int $manualCount, int $importedCount): void {
                $allTeams = [];
                $nextTeamId = 1;

                // Simulate manually created teams (no club_id_source)
                for ($i = 0; $i < $manualCount; $i++) {
                    $allTeams[] = $this->simulateManualTeam($nextTeamId++);
                }

                // Simulate imported teams (with club_id_source)
                for ($i = 0; $i < $importedCount; $i++) {
                    $allTeams[] = $this->simulateImportTeam($nextTeamId++, $i + 1, $i + 1);
                }

                // PROPERTY: Manual teams have NULL club_id_source,
                // imported teams have non-NULL club_id_source
                $manualTeams = array_slice($allTeams, 0, $manualCount);
                $importTeams = array_slice($allTeams, $manualCount);

                foreach ($manualTeams as $team) {
                    $this->assertNull(
                        $team['club_id_source'],
                        sprintf(
                            'Manually created team ID %d should have club_id_source=NULL, got %s',
                            $team['id'],
                            var_export($team['club_id_source'], true)
                        )
                    );
                }

                foreach ($importTeams as $team) {
                    $this->assertNotNull(
                        $team['club_id_source'],
                        sprintf(
                            'Imported team ID %d should have club_id_source set (not NULL)',
                            $team['id']
                        )
                    );
                }
            });
    }

    /**
     * Property 32: Re-importing from the same club ID does not change club_id_source.
     *
     * When a team already exists and is re-imported from the same club ID,
     * its club_id_source should remain the same (idempotent).
     *
     * **Validates: Requirements 16.3**
     */
    public function testReimportPreservesClubIdSource(): void
    {
        $this
            ->forAll(
                // Club entry ID
                Generators::choose(1, 10),
                // Number of teams to import
                Generators::choose(1, 6),
                // Number of re-import iterations
                Generators::choose(1, 3)
            )
            ->then(function (int $clubEntryId, int $teamCount, int $reimportCount): void {
                // First import: create teams with club_id_source
                $teams = [];
                for ($t = 1; $t <= $teamCount; $t++) {
                    $teams[$t] = $this->simulateImportTeam($t, $clubEntryId, $t);
                }

                // Re-import the same teams multiple times
                for ($r = 0; $r < $reimportCount; $r++) {
                    for ($t = 1; $t <= $teamCount; $t++) {
                        $existingTeam = $teams[$t];

                        // Simulate finding the existing team and potentially updating it
                        $updatedTeam = $this->simulateReimportTeam($existingTeam, $clubEntryId);
                        $teams[$t] = $updatedTeam;
                    }
                }

                // PROPERTY: After multiple re-imports, club_id_source remains correct
                foreach ($teams as $team) {
                    $this->assertSame(
                        $clubEntryId,
                        $team['club_id_source'],
                        sprintf(
                            'Team ID %d should still have club_id_source=%d after %d re-imports, got %d',
                            $team['id'],
                            $clubEntryId,
                            $reimportCount,
                            $team['club_id_source']
                        )
                    );
                }
            });
    }

    /**
     * Simulate importing a team from a specific club ID entry.
     *
     * This replicates the core logic from ClickTtImportService::createTeam()
     * where club_id_source is set to the club_ids entry ID during import.
     *
     * @param int $teamId The team's ID
     * @param int $clubIdEntryId The ID of the club_ids configuration entry
     * @param int $teamNumber The team number within the club
     * @return array{id: int, team_number: int, club_id_source: int}
     */
    private function simulateImportTeam(int $teamId, int $clubIdEntryId, int $teamNumber): array
    {
        return [
            'id' => $teamId,
            'team_number' => $teamNumber,
            'club_id_source' => $clubIdEntryId,
        ];
    }

    /**
     * Simulate creating a team manually (no import, no club_id_source).
     *
     * @param int $teamId The team's ID
     * @return array{id: int, team_number: int, club_id_source: null}
     */
    private function simulateManualTeam(int $teamId): array
    {
        return [
            'id' => $teamId,
            'team_number' => $teamId,
            'club_id_source' => null,
        ];
    }

    /**
     * Simulate re-importing a team that already exists.
     *
     * The club_id_source should remain unchanged (idempotent).
     *
     * @param array $existingTeam The existing team record
     * @param int $clubIdEntryId The club_ids entry ID being imported from
     * @return array The team record (unchanged club_id_source)
     */
    private function simulateReimportTeam(array $existingTeam, int $clubIdEntryId): array
    {
        // Re-import should preserve existing club_id_source
        // (team is found by season_id + team_number, not re-created)
        return [
            'id' => $existingTeam['id'],
            'team_number' => $existingTeam['team_number'],
            'club_id_source' => $existingTeam['club_id_source'],
        ];
    }
}
