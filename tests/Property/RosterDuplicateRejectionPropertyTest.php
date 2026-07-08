<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use Fatherjoe\Component\Ttclub\Administrator\Table\RosterTable;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use PHPUnit\Framework\TestCase;

/**
 * Property 17: Roster duplicate rejection
 *
 * Generate existing roster entries + duplicate attempts; verify rejection.
 * The unique constraint is on (player_id, team_id, half_season_id).
 *
 * **Validates: Requirements 5.9**
 */
class RosterDuplicateRejectionPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property: A roster entry with the same (player_id, team_id, half_season_id) as
     * an existing entry must always be rejected by check().
     *
     * **Validates: Requirements 5.9**
     */
    public function testDuplicateRosterEntryIsRejected(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 10000),  // player_id
                Generators::choose(1, 10000),  // team_id
                Generators::choose(1, 10000),  // half_season_id
                Generators::choose(1, 99999)   // existing record ID (duplicate found)
            )
            ->then(function (int $playerId, int $teamId, int $halfSeasonId, int $existingId): void {
                // Mock DB: loadResult returns a non-null ID (duplicate exists)
                $table = $this->createRosterTableWithDuplicateCheck((string) $existingId);
                $table->id = 0; // New record
                $table->player_id = $playerId;
                $table->team_id = $teamId;
                $table->half_season_id = $halfSeasonId;

                $result = $table->check();

                $this->assertFalse(
                    $result,
                    "check() should reject a duplicate roster entry for player_id=$playerId, " .
                    "team_id=$teamId, half_season_id=$halfSeasonId when one already exists."
                );

                $this->assertNotEmpty(
                    $table->getError(),
                    "An error message should be set when a duplicate roster entry is rejected."
                );

                $this->assertStringContainsStringIgnoringCase(
                    'already',
                    $table->getError(),
                    "Error message should indicate the player is already assigned."
                );
            });
    }

    /**
     * Property: A roster entry with a unique (player_id, team_id, half_season_id)
     * combination must always be accepted by check().
     *
     * **Validates: Requirements 5.9**
     */
    public function testUniqueRosterEntryIsAccepted(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 10000),  // player_id
                Generators::choose(1, 10000),  // team_id
                Generators::choose(1, 10000)   // half_season_id
            )
            ->then(function (int $playerId, int $teamId, int $halfSeasonId): void {
                // Mock DB: loadResult returns null (no duplicate found)
                $table = $this->createRosterTableWithDuplicateCheck(null);
                $table->id = 0; // New record
                $table->player_id = $playerId;
                $table->team_id = $teamId;
                $table->half_season_id = $halfSeasonId;

                $result = $table->check();

                $this->assertTrue(
                    $result,
                    "check() should accept a unique roster entry for player_id=$playerId, " .
                    "team_id=$teamId, half_season_id=$halfSeasonId when no duplicate exists."
                );
            });
    }

    /**
     * Property: When updating an existing roster record, the uniqueness check must
     * exclude the record's own ID. An existing entry can be re-saved with the same
     * (player_id, team_id, half_season_id).
     *
     * **Validates: Requirements 5.9**
     */
    public function testUpdatingOwnRecordIsNotRejected(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 10000),  // player_id
                Generators::choose(1, 10000),  // team_id
                Generators::choose(1, 10000),  // half_season_id
                Generators::choose(1, 99999)   // record's own ID
            )
            ->then(function (int $playerId, int $teamId, int $halfSeasonId, int $recordId): void {
                // Mock DB: loadResult returns null because the query excludes the current record ID
                $table = $this->createRosterTableWithDuplicateCheck(null);
                $table->id = $recordId; // Existing record (updating itself)
                $table->player_id = $playerId;
                $table->team_id = $teamId;
                $table->half_season_id = $halfSeasonId;

                $result = $table->check();

                $this->assertTrue(
                    $result,
                    "check() should allow roster entry ID $recordId to re-save with the same " .
                    "player_id=$playerId, team_id=$teamId, half_season_id=$halfSeasonId."
                );
            });
    }

    /**
     * Create a RosterTable instance with a mocked DatabaseDriver.
     *
     * @param string|null $loadResultValue Value returned by loadResult() for uniqueness check
     */
    private function createRosterTableWithDuplicateCheck(?string $loadResultValue): RosterTable
    {
        $query = $this->createMock(DatabaseQuery::class);
        $query->method('select')->willReturnSelf();
        $query->method('from')->willReturnSelf();
        $query->method('where')->willReturnSelf();

        $db = $this->createMock(DatabaseDriver::class);
        $db->method('getQuery')->willReturn($query);
        $db->method('quoteName')->willReturnCallback(fn(string $name) => '`' . $name . '`');
        $db->method('quote')->willReturnCallback(fn(string $val) => "'" . $val . "'");
        $db->method('setQuery')->willReturnSelf();
        $db->method('loadResult')->willReturn($loadResultValue);

        return new RosterTable($db);
    }
}
