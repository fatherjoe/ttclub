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
 * Property 5: Team required field validation.
 *
 * For any combination of missing/present required fields (season_id, league_id,
 * age_class_id, team_number), the TeamTable check() method must reject the record
 * when any required field is missing or zero, and must identify which fields are missing
 * in the error message.
 *
 * **Validates: Requirements 2.5, 2.7, 2.11**
 */
class TeamRequiredFieldValidationPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 5: When all required fields are present and non-zero, check() passes.
     *
     * Generate random positive integers for all four required fields;
     * verify that check() returns true.
     *
     * **Validates: Requirements 2.5, 2.7, 2.11**
     */
    public function testAllRequiredFieldsPresentPasses(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 10000), // season_id
                Generators::choose(1, 10000), // league_id
                Generators::choose(1, 10000), // age_class_id
                Generators::choose(1, 100)    // team_number
            )
            ->then(function (int $seasonId, int $leagueId, int $ageClassId, int $teamNumber): void {
                $table = $this->createTeamTable();

                $table->season_id = $seasonId;
                $table->league_id = $leagueId;
                $table->age_class_id = $ageClassId;
                $table->team_number = $teamNumber;

                $result = $table->check();

                $this->assertTrue(
                    $result,
                    sprintf(
                        'check() should pass when all required fields present: season_id=%d, league_id=%d, age_class_id=%d, team_number=%d. Error: %s',
                        $seasonId,
                        $leagueId,
                        $ageClassId,
                        $teamNumber,
                        $table->getError()
                    )
                );
            });
    }

    /**
     * Property 5: When any combination of required fields is missing/zero, check() rejects
     * and identifies the missing fields.
     *
     * Generate random bitmasks (1–15) representing which fields are missing/zero;
     * for each combination, verify that check() returns false and the error message
     * identifies each missing field.
     *
     * **Validates: Requirements 2.5, 2.7, 2.11**
     */
    public function testMissingRequiredFieldsRejectedWithIdentification(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 15),    // bitmask: which fields are missing (1=season, 2=league, 4=age_class, 8=team_number)
                Generators::choose(1, 10000), // valid season_id when present
                Generators::choose(1, 10000), // valid league_id when present
                Generators::choose(1, 10000), // valid age_class_id when present
                Generators::choose(1, 100)    // valid team_number when present
            )
            ->then(function (int $missingMask, int $seasonId, int $leagueId, int $ageClassId, int $teamNumber): void {
                $table = $this->createTeamTable();

                // Set fields based on bitmask: bit set = field is missing (zero/null)
                $table->season_id = ($missingMask & 1) ? 0 : $seasonId;
                $table->league_id = ($missingMask & 2) ? 0 : $leagueId;
                $table->age_class_id = ($missingMask & 4) ? 0 : $ageClassId;
                $table->team_number = ($missingMask & 8) ? 0 : $teamNumber;

                $result = $table->check();

                $this->assertFalse(
                    $result,
                    sprintf(
                        'check() should fail when required fields are missing (mask=%d): season_id=%s, league_id=%s, age_class_id=%s, team_number=%s',
                        $missingMask,
                        $table->season_id,
                        $table->league_id,
                        $table->age_class_id,
                        $table->team_number
                    )
                );

                $error = strtolower($table->getError());
                $this->assertNotEmpty($error, 'Error message should be set when validation fails');

                // Verify each missing field is identified in the error message
                if ($missingMask & 1) {
                    $this->assertStringContainsString(
                        'season',
                        $error,
                        "Error should identify missing 'season' field (mask=$missingMask)"
                    );
                }
                if ($missingMask & 2) {
                    $this->assertStringContainsString(
                        'league',
                        $error,
                        "Error should identify missing 'league' field (mask=$missingMask)"
                    );
                }
                if ($missingMask & 4) {
                    $this->assertStringContainsString(
                        'age class',
                        $error,
                        "Error should identify missing 'age class' field (mask=$missingMask)"
                    );
                }
                if ($missingMask & 8) {
                    $this->assertStringContainsString(
                        'team number',
                        $error,
                        "Error should identify missing 'team number' field (mask=$missingMask)"
                    );
                }
            });
    }

    /**
     * Create a TeamTable instance with a mocked database driver.
     * The mock ensures check() can run without hitting a real database.
     * Since we never set an id on the table, the league immutability check is skipped.
     */
    private function createTeamTable(): TeamTable
    {
        $query = $this->createMock(DatabaseQuery::class);
        $query->method('select')->willReturnSelf();
        $query->method('from')->willReturnSelf();
        $query->method('where')->willReturnSelf();

        $db = $this->createMock(DatabaseDriver::class);
        $db->method('getQuery')->willReturn($query);
        $db->method('quoteName')->willReturnCallback(fn(string $name) => '`' . $name . '`');
        $db->method('quote')->willReturnCallback(fn(string $text) => "'" . $text . "'");
        $db->method('setQuery')->willReturnSelf();
        $db->method('loadResult')->willReturn('0');
        $db->method('execute')->willReturn(true);

        return new TeamTable($db);
    }
}
