<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use Fatherjoe\Component\Ttclub\Administrator\Table\LeagueTable;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use PHPUnit\Framework\TestCase;

/**
 * Property 10: League deletion is prevented iff teams reference it.
 *
 * For any league, deletion succeeds if and only if zero teams reference that league.
 * When teams do reference the league, deletion is prevented and an appropriate error
 * message is set.
 *
 * Feature: tabletennis-club-manager
 * Property 10: League deletion is prevented iff teams reference it
 *
 * **Validates: Requirements 3.6**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class LeagueDeletionGuardPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 10: Deletion is prevented when team count > 0 and allowed when team count == 0.
     *
     * We generate random league IDs and random team reference counts (0 or more).
     * When the team count is 0, delete() must return true (deletion succeeds).
     * When the team count is > 0, delete() must return false with an appropriate error message.
     *
     * **Validates: Requirements 3.6**
     */
    public function testLeagueDeletionPreventedIffTeamsReferenceIt(): void
    {
        $this
            ->minimumEvaluationRatio(0.5)
            ->forAll(
                Generators::choose(1, 10000),   // league ID (pk)
                Generators::choose(0, 50)        // number of teams referencing this league
            )
            ->then(function (int $leagueId, int $teamCount): void {
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
                $db->method('loadResult')->willReturn((string) $teamCount);

                // Create the LeagueTable instance with our mock DB
                $table = new LeagueTable($db);
                $table->id = $leagueId;

                $result = $table->delete($leagueId);

                if ($teamCount === 0) {
                    $this->assertTrue(
                        $result,
                        "Deletion should succeed when zero teams reference league ID $leagueId"
                    );
                } else {
                    $this->assertFalse(
                        $result,
                        "Deletion should be prevented when $teamCount team(s) reference league ID $leagueId"
                    );
                    $this->assertNotEmpty(
                        $table->getError(),
                        "An error message should be set when deletion is prevented"
                    );
                    $this->assertStringContainsString(
                        'team',
                        strtolower($table->getError()),
                        "Error message should mention teams"
                    );
                }
            });
    }
}
