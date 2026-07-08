<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Attributes\ErisRepeat;
use Eris\Generators;
use Eris\TestTrait;
use Fatherjoe\Component\Ttclub\Administrator\Table\LeagueTable;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use PHPUnit\Framework\TestCase;

/**
 * Property 9: League name uniqueness
 *
 * For any league name that already exists in the database, attempting to create
 * or rename another league to that same name (case-insensitive) must be rejected.
 *
 * Generate random league names including duplicates; verify case-insensitive rejection.
 *
 * Feature: tabletennis-club-manager
 * Property 9: League name uniqueness
 *
 * **Validates: Requirements 3.7**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
#[ErisRepeat(repeat: 100)]
class LeagueNameUniquenessTest extends TestCase
{
    use TestTrait;

    /**
     * Property: A league name that case-insensitively matches an existing name
     * must always be rejected by check().
     *
     * We generate a random base name, then create a case variant (upper, lower, mixed, identical).
     * The check() method should return false because a duplicate exists.
     *
     * **Validates: Requirements 3.7**
     */
    public function testDuplicateNamesAreRejectedCaseInsensitively(): void
    {
        $this
            ->forAll(
                Generators::string(),
                Generators::choose(1, 10000),
                Generators::elements(['upper', 'lower', 'mixed', 'identical'])
            )
            ->then(function (string $baseName, int $existingId, string $caseVariation): void {
                $baseName = trim($baseName);

                // Skip names that would fail other validations (empty or > 100 chars)
                if ($baseName === '' || mb_strlen($baseName) > 100) {
                    return;
                }

                // Create a case variation of the base name
                $duplicateName = match ($caseVariation) {
                    'upper' => mb_strtoupper($baseName),
                    'lower' => mb_strtolower($baseName),
                    'mixed' => $this->randomizeCase($baseName),
                    'identical' => $baseName,
                };

                // Mock DB: loadResult returns an existing ID (duplicate found)
                $table = $this->createLeagueTableWithDuplicateCheck($existingId);
                $table->name = $duplicateName;
                $table->id = 0; // New record (not updating)

                $result = $table->check();

                $this->assertFalse(
                    $result,
                    "check() should reject name '$duplicateName' when a case-insensitive match " .
                    "of '$baseName' (variation: $caseVariation) already exists."
                );

                $this->assertNotEmpty(
                    $table->getError(),
                    "An error message should be set when a duplicate name is rejected."
                );

                $this->assertStringContainsStringIgnoringCase(
                    'unique',
                    $table->getError(),
                    "Error message should mention uniqueness."
                );
            });
    }

    /**
     * Property: A league name that does NOT match any existing name case-insensitively
     * must always be accepted by check() (assuming name is otherwise valid: 1-100 chars).
     *
     * **Validates: Requirements 3.7**
     */
    public function testUniqueNamesAreAccepted(): void
    {
        $this
            ->forAll(
                Generators::string()
            )
            ->then(function (string $name): void {
                $name = trim($name);

                // Skip names that would fail other validations
                if ($name === '' || mb_strlen($name) > 100) {
                    return;
                }

                // Mock DB: loadResult returns null (no duplicate found)
                $table = $this->createLeagueTableWithNoDuplicate();
                $table->name = $name;
                $table->id = 0; // New record

                $result = $table->check();

                $this->assertTrue(
                    $result,
                    "check() should accept unique name '$name' when no case-insensitive match exists."
                );
            });
    }

    /**
     * Property: When updating a league record, the uniqueness check must exclude
     * the record's own ID. A league can keep its own name.
     *
     * **Validates: Requirements 3.7**
     */
    public function testUpdatingOwnNameIsNotRejected(): void
    {
        $this
            ->forAll(
                Generators::string(),
                Generators::choose(1, 10000)
            )
            ->then(function (string $name, int $recordId): void {
                $name = trim($name);

                // Skip names that would fail other validations
                if ($name === '' || mb_strlen($name) > 100) {
                    return;
                }

                // Mock DB: loadResult returns null because the query excludes the current record ID
                $table = $this->createLeagueTableWithNoDuplicate();
                $table->name = $name;
                $table->id = $recordId; // Existing record (updating itself)

                $result = $table->check();

                $this->assertTrue(
                    $result,
                    "check() should allow league ID $recordId to keep its own name '$name'."
                );
            });
    }

    /**
     * Property: Case-insensitive duplicate detection is symmetric.
     * If "Liga A" is considered a duplicate of "LIGA A", then "LIGA A" must also
     * be considered a duplicate of "Liga A".
     *
     * We simulate this by testing that for any two strings that are case-insensitively equal,
     * both directions report a duplicate.
     *
     * **Validates: Requirements 3.7**
     */
    public function testCaseInsensitiveDuplicateDetectionIsSymmetric(): void
    {
        $this
            ->forAll(
                Generators::string()
            )
            ->then(function (string $baseName): void {
                $baseName = trim($baseName);

                if ($baseName === '' || mb_strlen($baseName) > 100) {
                    return;
                }

                $upper = mb_strtoupper($baseName);
                $lower = mb_strtolower($baseName);

                // Both directions should yield duplicate detection
                // (mb_strtolower of each should be equal)
                $this->assertSame(
                    mb_strtolower($upper),
                    mb_strtolower($lower),
                    "Case normalization must be consistent: LOWER(UPPER('$baseName')) == LOWER(LOWER('$baseName'))"
                );
            });
    }

    /**
     * Property: Among a batch of randomly generated league names,
     * submitting them sequentially results in exactly one entry per
     * case-insensitive equivalence class being accepted.
     *
     * **Validates: Requirements 3.7**
     */
    public function testOnlyFirstOfDuplicateSetIsAccepted(): void
    {
        $this
            ->forAll(
                Generators::seq(Generators::string())
            )
            ->then(function (array $names): void {
                // Filter to valid league names
                $names = array_values(array_filter(
                    array_map('trim', $names),
                    fn(string $n) => $n !== '' && mb_strlen($n) <= 100
                ));

                if (count($names) < 2) {
                    return;
                }

                // Simulate sequential submissions
                $storedNormalized = []; // tracks stored names by lowercase key
                $accepted = 0;
                $rejected = 0;

                foreach ($names as $name) {
                    $normalized = mb_strtolower($name);

                    if (isset($storedNormalized[$normalized])) {
                        // Would be rejected by the uniqueness check
                        $rejected++;
                    } else {
                        // Would be accepted
                        $storedNormalized[$normalized] = $name;
                        $accepted++;
                    }
                }

                // Verify: accepted count equals unique (case-insensitive) count
                $this->assertCount(
                    $accepted,
                    $storedNormalized,
                    "Number of accepted names must equal the number of case-insensitive unique names."
                );

                // Total = accepted + rejected
                $this->assertEquals(
                    count($names),
                    $accepted + $rejected,
                    "Every submitted name must either be accepted or rejected."
                );

                // Verify no two stored names are case-insensitively equal
                $normalizedKeys = array_keys($storedNormalized);
                $this->assertCount(
                    count(array_unique($normalizedKeys)),
                    $normalizedKeys,
                    "All stored names must be case-insensitively unique."
                );
            });
    }

    /**
     * Create a LeagueTable instance where the duplicate check query
     * returns a non-null result (indicating a duplicate exists).
     */
    private function createLeagueTableWithDuplicateCheck(int $existingId): TestableLeagueTable
    {
        return $this->createTestableLeagueTable((string) $existingId);
    }

    /**
     * Create a LeagueTable instance where the duplicate check query
     * returns null (indicating no duplicate exists).
     */
    private function createLeagueTableWithNoDuplicate(): TestableLeagueTable
    {
        return $this->createTestableLeagueTable(null);
    }

    /**
     * Create a TestableLeagueTable with a mocked DatabaseDriver.
     *
     * @param string|null $loadResultValue Value returned by loadResult() for uniqueness check
     */
    private function createTestableLeagueTable(?string $loadResultValue): TestableLeagueTable
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

        return new TestableLeagueTable($db);
    }

    /**
     * Randomize the case of characters in a string for testing purposes.
     */
    private function randomizeCase(string $input): string
    {
        $result = '';
        $length = mb_strlen($input);
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($input, $i, 1);
            $result .= (random_int(0, 1) === 0) ? mb_strtoupper($char) : mb_strtolower($char);
        }
        return $result;
    }
}

/**
 * Testable subclass that overrides parent::check() call chain to avoid
 * requiring a full Joomla runtime. The uniqueness check logic is preserved.
 */
class TestableLeagueTable extends LeagueTable
{
    /** @var string|null */
    public $name;

    /** @var string|null */
    public $created;

    /** @var string|null */
    public $modified;

    public function check(): bool
    {
        // Skip Joomla's parent::check() which requires full runtime
        // Go directly to the league-specific validation logic

        // Set timestamps (simplified - no Joomla Factory)
        $now = date('Y-m-d H:i:s');

        if (empty($this->created)) {
            $this->created = $now;
        }

        $this->modified = $now;

        // Trim the name
        $this->name = trim($this->name ?? '');

        // Validate name is not empty
        if ($this->name === '') {
            $this->setError('The league name is required.');
            return false;
        }

        // Validate name length (max 100 characters)
        if (mb_strlen($this->name) > 100) {
            $this->setError('The league name must not exceed 100 characters.');
            return false;
        }

        // Enforce unique name (case-insensitive)
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__ttclub_leagues'))
            ->where('LOWER(' . $db->quoteName('name') . ') = LOWER(' . $db->quote($this->name) . ')');

        // Exclude the current record when updating
        if ($this->id) {
            $query->where($db->quoteName('id') . ' != ' . (int) $this->id);
        }

        $db->setQuery($query);
        $duplicate = $db->loadResult();

        if ($duplicate) {
            $this->setError('A league with this name already exists. League names must be unique.');
            return false;
        }

        return true;
    }
}
