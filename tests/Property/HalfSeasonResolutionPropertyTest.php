<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use Fatherjoe\Component\Ttclub\Administrator\Service\HalfSeasonResolver;
use PHPUnit\Framework\TestCase;

/**
 * Property 13: Half-season resolution by calendar month
 *
 * Generate random months (1–12) + random years; verify half=1 for months 8–12
 * with start_year=year, half=2 for months 1–7 with start_year=year-1.
 *
 * **Validates: Requirements 4.8, 8.7, 9.2**
 */
class HalfSeasonResolutionPropertyTest extends TestCase
{
    use TestTrait;

    private HalfSeasonResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new HalfSeasonResolver();
    }

    /**
     * Property: For months 8–12, getHalfForDate returns 1 and
     * getStartYearForDate returns the same year.
     *
     * **Validates: Requirements 4.8, 8.7, 9.2**
     */
    public function testFirstHalfResolutionForMonthsAugustToDecember(): void
    {
        $this
            ->forAll(
                Generators::choose(8, 12),       // month in first half range
                Generators::choose(1900, 2100)   // year
            )
            ->then(function (int $month, int $year): void {
                $date = new \DateTimeImmutable(sprintf('%04d-%02d-15', $year, $month));

                $half = $this->resolver->getHalfForDate($date);
                $startYear = $this->resolver->getStartYearForDate($date);

                $this->assertSame(
                    1,
                    $half,
                    sprintf(
                        'Month %d (Aug-Dec) should resolve to half=1, got half=%d',
                        $month,
                        $half
                    )
                );

                $this->assertSame(
                    $year,
                    $startYear,
                    sprintf(
                        'Month %d in year %d (Aug-Dec) should have start_year=%d, got %d',
                        $month,
                        $year,
                        $year,
                        $startYear
                    )
                );
            });
    }

    /**
     * Property: For months 1–7, getHalfForDate returns 2 and
     * getStartYearForDate returns year - 1.
     *
     * **Validates: Requirements 4.8, 8.7, 9.2**
     */
    public function testSecondHalfResolutionForMonthsJanuaryToJuly(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 7),        // month in second half range
                Generators::choose(1901, 2100)   // year (min 1901 so start_year >= 1900)
            )
            ->then(function (int $month, int $year): void {
                $date = new \DateTimeImmutable(sprintf('%04d-%02d-15', $year, $month));

                $half = $this->resolver->getHalfForDate($date);
                $startYear = $this->resolver->getStartYearForDate($date);

                $this->assertSame(
                    2,
                    $half,
                    sprintf(
                        'Month %d (Jan-Jul) should resolve to half=2, got half=%d',
                        $month,
                        $half
                    )
                );

                $this->assertSame(
                    $year - 1,
                    $startYear,
                    sprintf(
                        'Month %d in year %d (Jan-Jul) should have start_year=%d, got %d',
                        $month,
                        $year,
                        $year - 1,
                        $startYear
                    )
                );
            });
    }

    /**
     * Property: For any month (1–12) and any year, the half is always 1 or 2,
     * and months partition cleanly into two groups with no overlap.
     *
     * **Validates: Requirements 4.8, 8.7, 9.2**
     */
    public function testHalfSeasonPartitionIsExhaustiveAndExclusive(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 12),       // any month
                Generators::choose(1900, 2100)   // any year
            )
            ->then(function (int $month, int $year): void {
                $date = new \DateTimeImmutable(sprintf('%04d-%02d-15', $year, $month));

                $half = $this->resolver->getHalfForDate($date);
                $startYear = $this->resolver->getStartYearForDate($date);

                // Half must be exactly 1 or 2
                $this->assertContains(
                    $half,
                    [1, 2],
                    sprintf('Half must be 1 or 2, got %d for month %d', $half, $month)
                );

                // start_year must be within valid bounds
                $this->assertGreaterThanOrEqual(
                    1899,
                    $startYear,
                    'start_year should not be below 1899'
                );
                $this->assertLessThanOrEqual(
                    2100,
                    $startYear,
                    'start_year should not exceed 2100'
                );

                // Consistency: if half=1, start_year=year; if half=2, start_year=year-1
                if ($half === 1) {
                    $this->assertSame($year, $startYear);
                } else {
                    $this->assertSame($year - 1, $startYear);
                }
            });
    }
}
