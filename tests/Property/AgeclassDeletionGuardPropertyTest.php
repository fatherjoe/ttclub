<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use Fatherjoe\Component\Ttclub\Administrator\Table\AgeclassTable;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use PHPUnit\Framework\TestCase;

/**
 * Property 8: Age class deletion is prevented iff teams reference it.
 *
 * For any age class, deletion succeeds if and only if zero teams reference that age class.
 *
 * @group property
 *
 * Feature: tabletennis-club-manager
 * Property 8: Age class deletion is prevented iff teams reference it
 *
 * **Validates: Requirements 2.13**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class AgeclassDeletionGuardPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 8: Deletion is prevented when team count > 0 and allowed when team count == 0.
     *
     * We generate random age class IDs and random team reference counts (0 or more).
     * When the team count is 0, delete() must succeed (parent::delete returns true).
     * When the team count is > 0, delete() must return false with an appropriate error message.
     *
     * **Validates: Requirements 2.13**
     */
    public function testAgeclassDeletionPreventedIffTeamsReferenceIt(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 10000),   // age class ID (pk)
                Generators::choose(0, 50)        // number of teams referencing this age class
            )
            ->then(function (int $ageClassId, int $teamCount): void {
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

                // Create the AgeclassTable instance with our mock DB
                $table = new AgeclassTable($db);
                $table->id = $ageClassId;

                $result = $table->delete($ageClassId);

                if ($teamCount === 0) {
                    $this->assertTrue(
                        $result,
                        "Deletion should succeed when zero teams reference age class ID $ageClassId"
                    );
                } else {
                    $this->assertFalse(
                        $result,
                        "Deletion should be prevented when $teamCount team(s) reference age class ID $ageClassId"
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
