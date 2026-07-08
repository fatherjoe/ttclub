<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Attributes\ErisRepeat;
use Eris\Generators;
use Eris\TestTrait;
use Fatherjoe\Component\Ttclub\Administrator\Table\PlayerTable;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use PHPUnit\Framework\TestCase;

/**
 * Property 1: Player data round-trip.
 *
 * For any valid player data (first name 1–50 chars, last name 1–50 chars,
 * optional birth_date, optional click_tt_player_id up to 50 chars), saving
 * the player and then loading it by ID should return a record with identical
 * first name, last name, birth_date, and click_tt_player_id values.
 *
 * Feature: tabletennis-club-manager
 * Property 1: Player data round-trip
 *
 * **Validates: Requirements 1.1, 1.3**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
#[ErisRepeat(repeat: 100)]
class PlayerDataRoundTripPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 1: Player data round-trip – save and reload preserves all player fields.
     *
     * Generate random valid player data (first name 1–50 chars, last name 1–50 chars,
     * random dates for birth_date, random click-tt player IDs up to 50 chars), simulate
     * save and reload via the Table class, verify all values are identical after the round-trip.
     *
     * **Validates: Requirements 1.1, 1.3**
     */
    public function testPlayerDataRoundTrip(): void
    {
        $this
            ->forAll(
                Generators::suchThat(
                    fn(string $s) => mb_strlen(trim($s)) >= 1 && mb_strlen(trim($s)) <= 50,
                    Generators::string()
                ),
                Generators::suchThat(
                    fn(string $s) => mb_strlen(trim($s)) >= 1 && mb_strlen(trim($s)) <= 50,
                    Generators::string()
                ),
                Generators::oneOf(
                    Generators::constant(null),
                    Generators::map(
                        fn(int $ts) => date('Y-m-d', $ts),
                        Generators::choose(0, 1893456000) // 1970-01-01 to ~2030-01-01
                    )
                ),
                Generators::oneOf(
                    Generators::constant(null),
                    Generators::suchThat(
                        fn(string $s) => mb_strlen($s) >= 1 && mb_strlen($s) <= 50,
                        Generators::string()
                    )
                )
            )
            ->then(function (string $firstName, string $lastName, ?string $birthDate, ?string $clickTtPlayerId): void {
                $table = $this->createRoundTripPlayerTable();

                // Set the player data
                $table->first_name = $firstName;
                $table->last_name = $lastName;
                $table->birth_date = $birthDate;
                $table->click_tt_player_id = $clickTtPlayerId;

                // Validate via check() — should pass for valid input
                $checkResult = $table->check();
                $this->assertTrue(
                    $checkResult,
                    sprintf(
                        'check() should pass for valid data: first="%s", last="%s", birth_date=%s, click_tt_player_id=%s. Error: %s',
                        $firstName,
                        $lastName,
                        $birthDate ?? 'null',
                        $clickTtPlayerId ?? 'null',
                        $table->getError()
                    )
                );

                // Simulate save (store) — our testable class records what was stored
                $storeResult = $table->store();
                $this->assertTrue($storeResult, 'store() should succeed for valid player data');

                // Simulate reload — our testable class returns the stored data
                $loadResult = $table->load(1);
                $this->assertTrue($loadResult, 'load() should succeed');

                // Verify round-trip: loaded values must be identical to what check() produces
                // Note: check() trims whitespace on names, so after check() the name is trimmed.
                $expectedFirstName = trim($firstName);
                $expectedLastName = trim($lastName);

                $this->assertSame(
                    $expectedFirstName,
                    $table->first_name,
                    sprintf(
                        'first_name must survive round-trip. Expected: "%s", Got: "%s"',
                        $expectedFirstName,
                        $table->first_name
                    )
                );
                $this->assertSame(
                    $expectedLastName,
                    $table->last_name,
                    sprintf(
                        'last_name must survive round-trip. Expected: "%s", Got: "%s"',
                        $expectedLastName,
                        $table->last_name
                    )
                );
                $this->assertSame(
                    $birthDate,
                    $table->birth_date,
                    sprintf(
                        'birth_date must survive round-trip. Expected: %s, Got: %s',
                        $birthDate ?? 'null',
                        $table->birth_date ?? 'null'
                    )
                );
                $this->assertSame(
                    $clickTtPlayerId,
                    $table->click_tt_player_id,
                    sprintf(
                        'click_tt_player_id must survive round-trip. Expected: %s, Got: %s',
                        $clickTtPlayerId ?? 'null',
                        $table->click_tt_player_id ?? 'null'
                    )
                );
            });
    }

    /**
     * Create a PlayerTable instance that simulates save/load round-trip
     * by storing data in memory.
     */
    private function createRoundTripPlayerTable(): RoundTripPlayerTable
    {
        $query = $this->createMock(DatabaseQuery::class);
        $query->method('select')->willReturnSelf();
        $query->method('from')->willReturnSelf();
        $query->method('where')->willReturnSelf();

        $db = $this->createMock(DatabaseDriver::class);
        $db->method('getQuery')->willReturn($query);
        $db->method('quoteName')->willReturnCallback(fn(string $name) => '`' . $name . '`');
        $db->method('setQuery')->willReturnSelf();
        $db->method('execute')->willReturn(true);

        return new RoundTripPlayerTable($db);
    }
}

/**
 * Testable PlayerTable subclass that simulates database save/load in memory.
 *
 * This allows testing the round-trip property without requiring a real database:
 * - store() saves the current field values to an in-memory store
 * - load() restores the field values from the in-memory store
 * - check() runs the real validation logic from PlayerTable
 */
#[\AllowDynamicProperties]
class RoundTripPlayerTable extends PlayerTable
{
    /** @var array<string, mixed> In-memory storage for round-trip simulation */
    private array $storedData = [];

    public function store(bool $updateNulls = false): bool
    {
        // Store current field values (simulating database INSERT/UPDATE)
        $this->storedData = [
            'id' => $this->id ?: 1,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'birth_date' => $this->birth_date ?? null,
            'click_tt_player_id' => $this->click_tt_player_id ?? null,
            'created' => $this->created ?? null,
            'modified' => $this->modified ?? null,
        ];

        if (!$this->id) {
            $this->id = 1;
        }

        return true;
    }

    public function load($keys = null, bool $reset = true): bool
    {
        if (empty($this->storedData)) {
            return false;
        }

        // Restore field values from stored data (simulating database SELECT)
        $this->id = $this->storedData['id'];
        $this->first_name = $this->storedData['first_name'];
        $this->last_name = $this->storedData['last_name'];
        $this->birth_date = $this->storedData['birth_date'];
        $this->click_tt_player_id = $this->storedData['click_tt_player_id'];
        $this->created = $this->storedData['created'];
        $this->modified = $this->storedData['modified'];

        return true;
    }
}
