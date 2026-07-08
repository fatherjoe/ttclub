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
 * Property 12: Season display name derivation
 *
 * For any season with a given start_year (1900–2100) and label, the derived display name must equal:
 * - If label is empty: "{start_year}/{(start_year+1) mod 100 zero-padded}"
 * - If label is non-empty: "{label} {start_year}/{(start_year+1) mod 100 zero-padded}"
 *
 * Formula: sprintf('%s%d/%02d', $label ? $label . ' ' : '', $startYear, ($startYear + 1) % 100)
 *
 * **Validates: Requirements 4.2, 17.4**
 */
class SeasonDisplayNamePropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 12: Season display name without label produces "YYYY/YY" format.
     *
     * Generate random start_years (1900–2100) with empty label; verify format matches YYYY/YY.
     *
     * **Validates: Requirements 4.2, 17.4**
     */
    public function testDisplayNameWithoutLabel(): void
    {
        $this
            ->forAll(
                Generators::choose(1900, 2100)
            )
            ->then(function (int $startYear): void {
                $table = $this->createSeasonTable();
                $table->start_year = $startYear;
                $table->label = '';

                $displayName = $table->getDisplayName();

                $expectedEndYearShort = ($startYear + 1) % 100;
                $expected = sprintf('%d/%02d', $startYear, $expectedEndYearShort);

                $this->assertSame(
                    $expected,
                    $displayName,
                    sprintf(
                        'Display name for start_year=%d with no label should be "%s", got "%s"',
                        $startYear,
                        $expected,
                        $displayName
                    )
                );
            });
    }

    /**
     * Property 12: Season display name with label produces "Label YYYY/YY" format.
     *
     * Generate random start_years (1900–2100) + random non-empty labels; verify format
     * matches "label YYYY/YY".
     *
     * **Validates: Requirements 4.2, 17.4**
     */
    public function testDisplayNameWithLabel(): void
    {
        $this
            ->forAll(
                Generators::choose(1900, 2100),
                Generators::suchThat(
                    fn(string $s) => trim($s) !== '' && mb_strlen(trim($s)) <= 50,
                    Generators::string()
                )
            )
            ->then(function (int $startYear, string $label): void {
                $table = $this->createSeasonTable();
                $table->start_year = $startYear;
                $table->label = $label;

                $displayName = $table->getDisplayName();

                $trimmedLabel = trim($label);
                $expectedEndYearShort = ($startYear + 1) % 100;
                $expected = sprintf('%s %d/%02d', $trimmedLabel, $startYear, $expectedEndYearShort);

                $this->assertSame(
                    $expected,
                    $displayName,
                    sprintf(
                        'Display name for start_year=%d with label="%s" should be "%s", got "%s"',
                        $startYear,
                        $label,
                        $expected,
                        $displayName
                    )
                );
            });
    }

    /**
     * Property 12: Year wraparound edge case - start_year=2099 produces "2099/00".
     *
     * Verify that the modulo arithmetic correctly wraps the end year.
     *
     * **Validates: Requirements 4.2, 17.4**
     */
    public function testDisplayNameYearWraparound(): void
    {
        $this
            ->forAll(
                Generators::elements([1999, 2099, 1900])
            )
            ->then(function (int $startYear): void {
                $table = $this->createSeasonTable();
                $table->start_year = $startYear;
                $table->label = '';

                $displayName = $table->getDisplayName();

                $expectedEndYearShort = ($startYear + 1) % 100;
                $expected = sprintf('%d/%02d', $startYear, $expectedEndYearShort);

                $this->assertSame(
                    $expected,
                    $displayName,
                    sprintf(
                        'Display name for wraparound year %d should be "%s", got "%s"',
                        $startYear,
                        $expected,
                        $displayName
                    )
                );
            });
    }

    /**
     * Property 12: Display name format is consistent regardless of label + year combination.
     *
     * Generate random start_years + random labels (including empty); verify the general formula
     * sprintf('%s%d/%02d', $label ? $label . ' ' : '', $startYear, ($startYear + 1) % 100)
     * always holds.
     *
     * **Validates: Requirements 4.2, 17.4**
     */
    public function testDisplayNameGeneralFormula(): void
    {
        $this
            ->forAll(
                Generators::choose(1900, 2100),
                Generators::oneOf(
                    Generators::constant(''),
                    Generators::suchThat(
                        fn(string $s) => trim($s) !== '' && mb_strlen(trim($s)) <= 50,
                        Generators::string()
                    )
                )
            )
            ->then(function (int $startYear, string $label): void {
                $table = $this->createSeasonTable();
                $table->start_year = $startYear;
                $table->label = $label;

                $displayName = $table->getDisplayName();

                $trimmedLabel = trim($label);
                $expectedEndYearShort = ($startYear + 1) % 100;
                $expected = sprintf(
                    '%s%d/%02d',
                    $trimmedLabel !== '' ? $trimmedLabel . ' ' : '',
                    $startYear,
                    $expectedEndYearShort
                );

                $this->assertSame(
                    $expected,
                    $displayName,
                    sprintf(
                        'General formula failed for start_year=%d, label="%s": expected "%s", got "%s"',
                        $startYear,
                        $label,
                        $expected,
                        $displayName
                    )
                );
            });
    }

    /**
     * Create a SeasonTable instance with a mocked database driver.
     */
    private function createSeasonTable(): SeasonTable
    {
        $query = $this->createMock(DatabaseQuery::class);
        $query->method('select')->willReturnSelf();
        $query->method('from')->willReturnSelf();
        $query->method('where')->willReturnSelf();

        $db = $this->createMock(DatabaseDriver::class);
        $db->method('getQuery')->willReturn($query);
        $db->method('quoteName')->willReturnCallback(fn(string $name) => '`' . $name . '`');
        $db->method('setQuery')->willReturnSelf();

        return new SeasonTable($db);
    }
}
