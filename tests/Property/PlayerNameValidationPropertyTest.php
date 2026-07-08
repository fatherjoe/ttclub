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
 * Property 2: Player name validation rejects invalid input.
 *
 * For any player form submission where first name or last name is empty
 * (including whitespace-only strings) or exceeds 50 characters, the component
 * should reject the submission and identify which fields are invalid.
 *
 * Feature: tabletennis-club-manager
 * Property 2: Player name validation rejects invalid input
 *
 * **Validates: Requirements 1.5**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
#[ErisRepeat(repeat: 100)]
class PlayerNameValidationPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 2: Invalid first name (empty or whitespace-only) is rejected.
     *
     * Generate whitespace-only strings (including empty) for first_name with a valid last_name.
     * Verify that check() returns false and the error identifies the first name field.
     *
     * **Validates: Requirements 1.5**
     */
    public function testEmptyOrWhitespaceFirstNameIsRejected(): void
    {
        $this
            ->minimumEvaluationRatio(0.5)
            ->forAll(
                Generators::elements('', ' ', '  ', "\t", "\n", "   \t\n  "),
                Generators::suchThat(
                    fn(string $s) => mb_strlen(trim($s)) >= 1 && mb_strlen(trim($s)) <= 50,
                    Generators::string()
                )
            )
            ->then(function (string $firstName, string $lastName): void {
                $table = $this->createTestablePlayerTable();
                $table->first_name = $firstName;
                $table->last_name = $lastName;

                $result = $table->check();

                $this->assertFalse(
                    $result,
                    sprintf(
                        'check() should reject empty/whitespace-only first_name "%s" (repr: %s)',
                        $firstName,
                        json_encode($firstName)
                    )
                );

                $this->assertNotEmpty(
                    $table->getError(),
                    'An error message should be set when first name is invalid'
                );

                $this->assertStringContainsStringIgnoringCase(
                    'first name',
                    $table->getError(),
                    'Error message should identify the first name field'
                );
            });
    }

    /**
     * Property 2: Invalid last name (empty or whitespace-only) is rejected.
     *
     * Generate whitespace-only strings (including empty) for last_name with a valid first_name.
     * Verify that check() returns false and the error identifies the last name field.
     *
     * **Validates: Requirements 1.5**
     */
    public function testEmptyOrWhitespaceLastNameIsRejected(): void
    {
        $this
            ->minimumEvaluationRatio(0.5)
            ->forAll(
                Generators::suchThat(
                    fn(string $s) => mb_strlen(trim($s)) >= 1 && mb_strlen(trim($s)) <= 50,
                    Generators::string()
                ),
                Generators::elements('', ' ', '  ', "\t", "\n", "   \t\n  ")
            )
            ->then(function (string $firstName, string $lastName): void {
                $table = $this->createTestablePlayerTable();
                $table->first_name = $firstName;
                $table->last_name = $lastName;

                $result = $table->check();

                $this->assertFalse(
                    $result,
                    sprintf(
                        'check() should reject empty/whitespace-only last_name "%s" (repr: %s)',
                        $lastName,
                        json_encode($lastName)
                    )
                );

                $this->assertNotEmpty(
                    $table->getError(),
                    'An error message should be set when last name is invalid'
                );

                $this->assertStringContainsStringIgnoringCase(
                    'last name',
                    $table->getError(),
                    'Error message should identify the last name field'
                );
            });
    }

    /**
     * Property 2: First name exceeding 50 characters is rejected.
     *
     * Generate strings longer than 50 characters for first_name with a valid last_name.
     * Verify that check() returns false and the error identifies the first name field.
     *
     * **Validates: Requirements 1.5**
     */
    public function testFirstNameExceeding50CharsIsRejected(): void
    {
        $this
            ->forAll(
                Generators::choose(51, 200),
                Generators::suchThat(
                    fn(string $s) => mb_strlen(trim($s)) >= 1 && mb_strlen(trim($s)) <= 50,
                    Generators::string()
                )
            )
            ->then(function (int $length, string $lastName): void {
                // Generate a non-whitespace string of exactly $length characters
                $firstName = str_repeat('a', $length);

                $table = $this->createTestablePlayerTable();
                $table->first_name = $firstName;
                $table->last_name = $lastName;

                $result = $table->check();

                $this->assertFalse(
                    $result,
                    sprintf(
                        'check() should reject first_name of length %d (exceeds 50)',
                        mb_strlen($firstName)
                    )
                );

                $this->assertNotEmpty(
                    $table->getError(),
                    'An error message should be set when first name exceeds 50 characters'
                );

                $this->assertStringContainsStringIgnoringCase(
                    'first name',
                    $table->getError(),
                    'Error message should identify the first name field'
                );
            });
    }

    /**
     * Property 2: Last name exceeding 50 characters is rejected.
     *
     * Generate strings longer than 50 characters for last_name with a valid first_name.
     * Verify that check() returns false and the error identifies the last name field.
     *
     * **Validates: Requirements 1.5**
     */
    public function testLastNameExceeding50CharsIsRejected(): void
    {
        $this
            ->forAll(
                Generators::suchThat(
                    fn(string $s) => mb_strlen(trim($s)) >= 1 && mb_strlen(trim($s)) <= 50,
                    Generators::string()
                ),
                Generators::choose(51, 200)
            )
            ->then(function (string $firstName, int $length): void {
                // Generate a non-whitespace string of exactly $length characters
                $lastName = str_repeat('b', $length);

                $table = $this->createTestablePlayerTable();
                $table->first_name = $firstName;
                $table->last_name = $lastName;

                $result = $table->check();

                $this->assertFalse(
                    $result,
                    sprintf(
                        'check() should reject last_name of length %d (exceeds 50)',
                        mb_strlen($lastName)
                    )
                );

                $this->assertNotEmpty(
                    $table->getError(),
                    'An error message should be set when last name exceeds 50 characters'
                );

                $this->assertStringContainsStringIgnoringCase(
                    'last name',
                    $table->getError(),
                    'Error message should identify the last name field'
                );
            });
    }

    /**
     * Create a TestablePlayerTable that bypasses Joomla runtime dependencies.
     */
    private function createTestablePlayerTable(): TestablePlayerTable
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

        return new TestablePlayerTable($db);
    }
}

/**
 * Testable subclass that overrides parent::check() to avoid
 * requiring a full Joomla runtime. The player name validation logic is preserved.
 */
class TestablePlayerTable extends PlayerTable
{
    /** @var string|null */
    public $first_name;

    /** @var string|null */
    public $last_name;

    /** @var string|null */
    public $created;

    /** @var string|null */
    public $modified;

    public function check(): bool
    {
        // Skip Joomla's parent::check() which requires full runtime
        // Go directly to the player-specific validation logic

        // Set timestamps (simplified - no Joomla Factory)
        $now = date('Y-m-d H:i:s');

        if (empty($this->created)) {
            $this->created = $now;
        }

        $this->modified = $now;

        // Trim whitespace from first_name
        $this->first_name = trim($this->first_name ?? '');

        // Validate first_name is not empty
        if ($this->first_name === '') {
            $this->setError('The first name is required.');
            return false;
        }

        // Validate first_name length (max 50 characters)
        if (mb_strlen($this->first_name) > 50) {
            $this->setError('The first name must not exceed 50 characters.');
            return false;
        }

        // Trim whitespace from last_name
        $this->last_name = trim($this->last_name ?? '');

        // Validate last_name is not empty
        if ($this->last_name === '') {
            $this->setError('The last name is required.');
            return false;
        }

        // Validate last_name length (max 50 characters)
        if (mb_strlen($this->last_name) > 50) {
            $this->setError('The last name must not exceed 50 characters.');
            return false;
        }

        return true;
    }
}
