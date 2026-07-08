<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use Fatherjoe\Component\Ttclub\Administrator\Table\PlayerTable;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use PHPUnit\Framework\TestCase;

/**
 * Property 3: Player deletion is prevented iff roster assignments exist.
 *
 * For any player, deletion succeeds if and only if zero roster assignments
 * exist for that player. When roster entries do exist, deletion is prevented
 * and an appropriate error message is set.
 *
 * **Validates: Requirements 1.6, 1.7**
 */
class PlayerDeletionGuardPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 3: Deletion is prevented when roster count > 0 and allowed when roster count == 0.
     *
     * We generate random player IDs and random roster entry counts (0 or more).
     * When the roster count is 0, delete() must return true (deletion succeeds).
     * When the roster count is > 0, delete() must return false with an appropriate error message.
     *
     * **Validates: Requirements 1.6, 1.7**
     */
    public function testPlayerDeletionPreventedIffRosterAssignmentsExist(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 10000),   // player ID (pk)
                Generators::choose(0, 50)        // number of roster entries for this player
            )
            ->then(function (int $playerId, int $rosterCount): void {
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

                // Create the PlayerTable instance with our mock DB
                $table = new PlayerTable($db);
                $table->id = $playerId;

                $result = $table->delete($playerId);

                if ($rosterCount === 0) {
                    $this->assertTrue(
                        $result,
                        "Deletion should succeed when zero roster entries exist for player ID $playerId"
                    );
                } else {
                    $this->assertFalse(
                        $result,
                        "Deletion should be prevented when $rosterCount roster entry(ies) exist for player ID $playerId"
                    );
                    $this->assertNotEmpty(
                        $table->getError(),
                        "An error message should be set when deletion is prevented"
                    );
                    $this->assertStringContainsString(
                        'roster',
                        strtolower($table->getError()),
                        "Error message should mention roster"
                    );
                }
            });
    }
}
