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
 * Property 6: Team deletion is prevented iff roster assignments exist.
 *
 * For any team, deletion succeeds if and only if the team has zero roster entries.
 * If roster entries exist, deletion must be prevented.
 *
 * **Validates: Requirements 2.6**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class TeamDeletionGuardPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 6: Deletion is prevented when roster count > 0 and allowed when roster count == 0.
     *
     * We generate random team IDs and random roster entry counts (0 or more).
     * When the roster count is 0, delete() must return true (deletion succeeds).
     * When the roster count is > 0, delete() must return false with an appropriate error message.
     *
     * **Validates: Requirements 2.6**
     */
    public function testTeamDeletionPreventedIffRosterAssignmentsExist(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 10000),   // team ID (pk)
                Generators::choose(0, 50)        // number of roster entries for this team
            )
            ->then(function (int $teamId, int $rosterCount): void {
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
                $db->method('loadResult')->willReturn((string) $rosterCount);

                // Create the TeamTable instance with our mock DB
                $table = new TeamTable($db);
                $table->id = $teamId;

                $result = $table->delete($teamId);

                if ($rosterCount === 0) {
                    $this->assertTrue(
                        $result,
                        "Deletion should succeed when zero roster entries exist for team ID $teamId"
                    );
                } else {
                    $this->assertFalse(
                        $result,
                        "Deletion should be prevented when $rosterCount roster entry(ies) exist for team ID $teamId"
                    );
                    $this->assertNotEmpty(
                        $table->getError(),
                        "An error message should be set when deletion is prevented"
                    );
                    $this->assertStringContainsString(
                        'roster',
                        strtolower($table->getError()),
                        "Error message should mention roster/players"
                    );
                }
            });
    }
}
