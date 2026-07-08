<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use Fatherjoe\Component\Ttclub\Administrator\Table\TeamTable;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use PHPUnit\Framework\TestCase;

/**
 * Property 7: Team league immutability.
 *
 * For any existing team (id > 0), attempting to change the league_id must be
 * rejected by the check() method. The league assignment is fixed for the entire
 * season once the team record has been created.
 *
 * **Validates: Requirements 2.8**
 */
class TeamLeagueImmutabilityPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 7: League changes on existing teams are always rejected.
     *
     * We generate random team IDs, original league IDs, and different new league IDs.
     * For an existing team record (id > 0), check() must return false when the
     * league_id differs from the stored original, and must set an appropriate error.
     *
     * **Validates: Requirements 2.8**
     */
    public function testLeagueChangeOnExistingTeamIsRejected(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 10000),   // team ID (existing record)
                Generators::choose(1, 500),      // original league_id stored in DB
                Generators::choose(1, 500),      // season_id (valid)
                Generators::choose(1, 100),      // age_class_id (valid)
                Generators::choose(1, 20)        // team_number (valid)
            )
            ->then(function (int $teamId, int $originalLeagueId, int $seasonId, int $ageClassId, int $teamNumber): void {
                // Generate a different league_id (guaranteed different from original)
                $newLeagueId = $originalLeagueId === 500 ? $originalLeagueId - 1 : $originalLeagueId + 1;

                // Build mock database infrastructure
                $query = $this->createMock(DatabaseQuery::class);
                $query->method('select')->willReturnSelf();
                $query->method('from')->willReturnSelf();
                $query->method('where')->willReturnSelf();

                $db = $this->createMock(DatabaseDriver::class);
                $db->method('getQuery')->willReturn($query);
                $db->method('quoteName')->willReturnCallback(fn(string $name) => '`' . $name . '`');
                $db->method('quote')->willReturnCallback(fn(string $text) => "'" . $text . "'");
                $db->method('setQuery')->willReturnSelf();
                // The DB returns the original league_id when queried
                $db->method('loadResult')->willReturn((string) $originalLeagueId);

                // Create the TeamTable instance with mock DB
                $table = new TeamTable($db);
                $table->id = $teamId;
                $table->season_id = $seasonId;
                $table->league_id = $newLeagueId; // Attempted change
                $table->age_class_id = $ageClassId;
                $table->team_number = $teamNumber;

                $result = $table->check();

                $this->assertFalse(
                    $result,
                    "check() should reject league change from $originalLeagueId to $newLeagueId on existing team ID $teamId"
                );
                $this->assertNotEmpty(
                    $table->getError(),
                    "An error message should be set when league change is rejected"
                );
                $this->assertStringContainsStringIgnoringCase(
                    'league',
                    $table->getError(),
                    "Error message should mention league"
                );
            });
    }

    /**
     * Property 7 (complement): Keeping the same league_id on an existing team is allowed.
     *
     * When league_id remains unchanged, check() must pass (assuming all other fields
     * are valid). This confirms the immutability check only blocks actual changes.
     *
     * **Validates: Requirements 2.8**
     */
    public function testKeepingSameLeagueOnExistingTeamIsAllowed(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 10000),   // team ID (existing record)
                Generators::choose(1, 500),      // league_id (same for both stored and attempted)
                Generators::choose(1, 500),      // season_id (valid)
                Generators::choose(1, 100),      // age_class_id (valid)
                Generators::choose(1, 20)        // team_number (valid)
            )
            ->then(function (int $teamId, int $leagueId, int $seasonId, int $ageClassId, int $teamNumber): void {
                // Build mock database infrastructure
                $query = $this->createMock(DatabaseQuery::class);
                $query->method('select')->willReturnSelf();
                $query->method('from')->willReturnSelf();
                $query->method('where')->willReturnSelf();

                $db = $this->createMock(DatabaseDriver::class);
                $db->method('getQuery')->willReturn($query);
                $db->method('quoteName')->willReturnCallback(fn(string $name) => '`' . $name . '`');
                $db->method('quote')->willReturnCallback(fn(string $text) => "'" . $text . "'");
                $db->method('setQuery')->willReturnSelf();
                // The DB returns the same league_id (no change)
                $db->method('loadResult')->willReturn((string) $leagueId);

                // Create the TeamTable instance with mock DB
                $table = new TeamTable($db);
                $table->id = $teamId;
                $table->season_id = $seasonId;
                $table->league_id = $leagueId; // Same as stored — no change
                $table->age_class_id = $ageClassId;
                $table->team_number = $teamNumber;

                $result = $table->check();

                $this->assertTrue(
                    $result,
                    "check() should allow saving team ID $teamId when league_id $leagueId remains unchanged"
                );
            });
    }
}
