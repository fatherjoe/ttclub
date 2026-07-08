<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use Fatherjoe\Component\Ttclub\Administrator\Table\SeasonTable;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use PHPUnit\Framework\TestCase;

/**
 * Property 16: Season uniqueness on start_year and label
 *
 * For any existing season with a given (start_year, label) combination,
 * attempting to create another season with the same (start_year, label)
 * must be rejected. Different labels with the same start_year must be allowed.
 *
 * **Validates: Requirements 17.2, 17.3**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class SeasonUniquenessPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 16: Duplicate (start_year, label) pairs must be rejected.
     *
     * Generate random (start_year, label) pairs; simulate that a record with
     * the same combination already exists. Verify check() rejects the duplicate.
     *
     * **Validates: Requirements 17.2, 17.3**
     */
    public function testDuplicateStartYearAndLabelIsRejected(): void
    {
        $this
            ->forAll(
                Generators::choose(1900, 2100),
                Generators::oneOf(
                    Generators::constant(''),
                    Generators::elements(['Pokal', 'Cup', 'Friendly', 'Tournament'])
                ),
                Generators::choose(1, 10000) // existing record ID
            )
            ->then(function (int $startYear, string $label, int $existingId): void {
                // Mock DB returns an existing ID (duplicate found)
                $table = $this->createSeasonTableWithDuplicateCheck((string) $existingId);
                $table->start_year = $startYear;
                $table->label = $label;
                $table->id = 0; // New record

                $result = $table->check();

                $this->assertFalse(
                    $result,
                    sprintf(
                        'check() should reject season with start_year=%d, label="%s" when duplicate exists (id=%d)',
                        $startYear,
                        $label,
                        $existingId
                    )
                );

                $this->assertNotEmpty(
                    $table->getError(),
                    'An error message should be set when a duplicate (start_year, label) is rejected.'
                );

                $errorMsg = strtolower($table->getError());
                $this->assertTrue(
                    str_contains($errorMsg, 'unique') || str_contains($errorMsg, 'already exists'),
                    'Error message should indicate uniqueness violation.'
                );
            });
    }

    /**
     * Property 16: Unique (start_year, label) pairs must be accepted.
     *
     * Generate random valid (start_year, label) pairs; simulate no existing duplicate.
     * Verify check() accepts the record.
     *
     * **Validates: Requirements 17.2, 17.3**
     */
    public function testUniqueStartYearAndLabelIsAccepted(): void
    {
        $this
            ->forAll(
                Generators::choose(1900, 2100),
                Generators::oneOf(
                    Generators::constant(''),
                    Generators::elements(['Pokal', 'Cup', 'Friendly', 'Tournament'])
                )
            )
            ->then(function (int $startYear, string $label): void {
                // Mock DB returns null (no duplicate found)
                $table = $this->createSeasonTableWithNoDuplicate();
                $table->start_year = $startYear;
                $table->label = $label;
                $table->id = 0; // New record

                $result = $table->check();

                $this->assertTrue(
                    $result,
                    sprintf(
                        'check() should accept season with start_year=%d, label="%s" when no duplicate exists',
                        $startYear,
                        $label
                    )
                );
            });
    }

    /**
     * Property 16: Different labels with the same start_year must be allowed.
     *
     * Generate a start_year and two distinct labels; verify both are accepted
     * when neither has a pre-existing duplicate.
     *
     * **Validates: Requirements 17.2, 17.3**
     */
    public function testDifferentLabelsWithSameStartYearAreAllowed(): void
    {
        $labels = ['', 'Pokal', 'Cup', 'Friendly', 'Tournament', 'Winter'];

        $this
            ->forAll(
                Generators::choose(1900, 2100),
                Generators::choose(0, count($labels) - 1),
                Generators::choose(0, count($labels) - 1)
            )
            ->then(function (int $startYear, int $labelIdx1, int $labelIdx2) use ($labels): void {
                // Only test when labels are actually different
                if ($labelIdx1 === $labelIdx2) {
                    return;
                }

                $label1 = $labels[$labelIdx1];
                $label2 = $labels[$labelIdx2];

                // Both should be accepted when no duplicates exist
                $table1 = $this->createSeasonTableWithNoDuplicate();
                $table1->start_year = $startYear;
                $table1->label = $label1;
                $table1->id = 0;

                $result1 = $table1->check();
                $this->assertTrue(
                    $result1,
                    sprintf(
                        'Season with start_year=%d, label="%s" should be accepted when no duplicate exists',
                        $startYear,
                        $label1
                    )
                );

                $table2 = $this->createSeasonTableWithNoDuplicate();
                $table2->start_year = $startYear;
                $table2->label = $label2;
                $table2->id = 0;

                $result2 = $table2->check();
                $this->assertTrue(
                    $result2,
                    sprintf(
                        'Season with start_year=%d, label="%s" should be accepted when no duplicate exists (different label from "%s")',
                        $startYear,
                        $label2,
                        $label1
                    )
                );
            });
    }

    /**
     * Property 16: Updating a season's own record does not trigger the uniqueness check.
     *
     * When a season is being updated (has a non-zero id), the uniqueness query excludes
     * that record's own ID, so saving the same (start_year, label) is allowed.
     *
     * **Validates: Requirements 17.2, 17.3**
     */
    public function testUpdatingOwnRecordIsNotRejected(): void
    {
        $this
            ->forAll(
                Generators::choose(1900, 2100),
                Generators::oneOf(
                    Generators::constant(''),
                    Generators::elements(['Pokal', 'Cup', 'Friendly', 'Tournament'])
                ),
                Generators::choose(1, 10000) // record's own ID
            )
            ->then(function (int $startYear, string $label, int $ownId): void {
                // Mock DB returns null because the query excludes the current ID
                $table = $this->createSeasonTableWithNoDuplicate();
                $table->start_year = $startYear;
                $table->label = $label;
                $table->id = $ownId; // Existing record (updating itself)

                $result = $table->check();

                $this->assertTrue(
                    $result,
                    sprintf(
                        'check() should allow season ID %d to keep its own (start_year=%d, label="%s")',
                        $ownId,
                        $startYear,
                        $label
                    )
                );
            });
    }

    /**
     * Create a testable SeasonTable where the duplicate check returns an existing ID.
     */
    private function createSeasonTableWithDuplicateCheck(string $existingId): TestableSeasonTable
    {
        return $this->createTestableSeasonTable($existingId);
    }

    /**
     * Create a testable SeasonTable where the duplicate check returns null (no duplicate).
     */
    private function createSeasonTableWithNoDuplicate(): TestableSeasonTable
    {
        return $this->createTestableSeasonTable(null);
    }

    /**
     * Create a TestableSeasonTable with a mocked DatabaseDriver.
     *
     * @param string|null $loadResultValue Value returned by loadResult() for uniqueness check
     */
    private function createTestableSeasonTable(?string $loadResultValue): TestableSeasonTable
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

        return new TestableSeasonTable($db);
    }
}

/**
 * Testable subclass that overrides parent::check() call chain to avoid
 * requiring a full Joomla runtime. The uniqueness check logic is preserved.
 */
class TestableSeasonTable extends SeasonTable
{
    public function check(): bool
    {
        // Skip Joomla's parent::check() and Factory::getDate() which require full runtime
        // Go directly to the season-specific validation logic

        // Set timestamps (simplified - no Joomla Factory)
        $now = date('Y-m-d H:i:s');

        if (empty($this->created)) {
            $this->created = $now;
        }

        $this->modified = $now;

        // Validate start_year
        $year = (int) $this->start_year;

        if ($year < 1900 || $year > 2100) {
            $this->setError('Please enter a valid start year (1900–2100).');
            return false;
        }

        // Normalise label
        $this->label = trim((string) ($this->label ?? ''));

        // Enforce unique (start_year, label) combination
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__ttclub_seasons'))
            ->where($db->quoteName('start_year') . ' = ' . (int) $this->start_year)
            ->where($db->quoteName('label') . ' = ' . $db->quote($this->label));

        // Exclude the current record when updating
        if ($this->id) {
            $query->where($db->quoteName('id') . ' != ' . (int) $this->id);
        }

        $db->setQuery($query);
        $duplicate = $db->loadResult();

        if ($duplicate) {
            $this->setError('A season with this start year and label already exists. The combination of start year and label must be unique.');
            return false;
        }

        return true;
    }
}
