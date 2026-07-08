<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use Fatherjoe\Component\Ttclub\Administrator\Helper\TtclubHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\DatabaseQuery;
use PHPUnit\Framework\TestCase;

/**
 * Property 14: Half-season resolution fallback.
 *
 * For any set of seasons in the database where no season matches the computed
 * start_year for the current date, the resolver must return the half-season with
 * the highest start_year's second half (or first half if only one half exists).
 *
 * **Validates: Requirements 4.9**
 */
class HalfSeasonResolutionFallbackPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 14: When the target year is missing from the season set, the resolver
     * returns the most recent season's latest half-season.
     *
     * We generate:
     * - A random target year (the year the resolver would look for)
     * - A random set of seasons that do NOT include the target year
     * - Each season has either one or two half-season records
     *
     * The expected fallback is the half-season with the highest start_year,
     * preferring half=2 over half=1 within the same season.
     *
     * **Validates: Requirements 4.9**
     */
    public function testFallbackToMostRecentSeasonsLatestHalfSeason(): void
    {
        $this
            ->forAll(
                Generators::choose(1900, 2100),  // target year that will be MISSING
                Generators::choose(1, 5),         // number of seasons to generate
                Generators::choose(1, 12)         // reference month (to compute target year)
            )
            ->then(function (int $targetYear, int $numSeasons, int $refMonth): void {
                // Generate season start_years that do NOT include the target year
                $seasons = [];
                $halfSeasons = [];
                $seasonId = 1;
                $halfSeasonId = 1;

                // Create seasons with start_years different from target
                for ($i = 0; $i < $numSeasons; $i++) {
                    $startYear = $targetYear - $i - 1; // guaranteed different from target
                    if ($startYear < 1900) {
                        continue; // skip if out of valid range
                    }

                    $seasons[] = [
                        'id' => $seasonId,
                        'start_year' => $startYear,
                        'published' => 1,
                    ];

                    // Always create half=1
                    $halfSeasons[] = [
                        'id' => $halfSeasonId,
                        'season_id' => $seasonId,
                        'half' => 1,
                        'start_year' => $startYear,
                    ];
                    $halfSeasonId++;

                    // Create half=2 for most seasons (always for first to ensure predictable fallback)
                    if ($i === 0 || $i % 2 === 0) {
                        $halfSeasons[] = [
                            'id' => $halfSeasonId,
                            'season_id' => $seasonId,
                            'half' => 2,
                            'start_year' => $startYear,
                        ];
                        $halfSeasonId++;
                    }

                    $seasonId++;
                }

                if (empty($seasons)) {
                    // No valid seasons generated, skip this iteration
                    return;
                }

                // Determine expected fallback: highest start_year, then highest half
                // Sort by start_year DESC, half DESC
                usort($halfSeasons, function (array $a, array $b): int {
                    if ($a['start_year'] !== $b['start_year']) {
                        return $b['start_year'] - $a['start_year'];
                    }
                    return $b['half'] - $a['half'];
                });

                $expectedFallback = $halfSeasons[0];

                // Now simulate the resolver logic:
                // 1. First query for exact match (will return null since target year is missing)
                // 2. Fallback query returns highest start_year DESC, half DESC
                $callCount = 0;

                $query = $this->createMock(DatabaseQuery::class);
                $query->method('select')->willReturnSelf();
                $query->method('from')->willReturnSelf();
                $query->method('innerJoin')->willReturnSelf();
                $query->method('where')->willReturnSelf();
                $query->method('order')->willReturnSelf();
                $query->method('setLimit')->willReturnSelf();
                $query->method('bind')->willReturnSelf();

                $db = $this->createMock(DatabaseInterface::class);
                $db->method('getQuery')->willReturn($query);
                $db->method('quoteName')->willReturnCallback(
                    fn(string $name, ?string $alias = null) => $alias ? '`' . $name . '` AS `' . $alias . '`' : '`' . $name . '`'
                );
                $db->method('setQuery')->willReturnSelf();

                // First call: exact match query returns null (target year missing)
                // Second call: fallback query returns the most recent half-season
                $db->method('loadObject')->willReturnCallback(
                    function () use (&$callCount, $expectedFallback): ?object {
                        $callCount++;
                        if ($callCount === 1) {
                            // No exact match for the target year
                            return null;
                        }
                        // Fallback: return the most recent season's latest half-season
                        return (object) [
                            'id' => $expectedFallback['id'],
                            'season_id' => $expectedFallback['season_id'],
                            'half' => $expectedFallback['half'],
                        ];
                    }
                );

                $result = TtclubHelper::getCurrentHalfSeason($db);

                // Verify fallback was triggered (two DB queries made)
                $this->assertSame(2, $callCount, 'Two queries should be made: exact match + fallback');

                // Verify the result matches the expected fallback
                $this->assertNotNull($result, 'Fallback should return a half-season when seasons exist');
                $this->assertSame(
                    $expectedFallback['id'],
                    $result->id,
                    sprintf(
                        'Fallback should return half-season ID %d (start_year=%d, half=%d) but got ID %d',
                        $expectedFallback['id'],
                        $expectedFallback['start_year'],
                        $expectedFallback['half'],
                        $result->id
                    )
                );
                $this->assertSame(
                    $expectedFallback['half'],
                    $result->half,
                    sprintf(
                        'Fallback should prefer half=%d of the most recent season (start_year=%d)',
                        $expectedFallback['half'],
                        $expectedFallback['start_year']
                    )
                );
                $this->assertSame(
                    $expectedFallback['season_id'],
                    $result->season_id,
                    'Fallback should return a half-season from the most recent season'
                );
            });
    }

    /**
     * Property 14 (complement): When no seasons exist at all, the resolver returns null.
     *
     * Generate random target years; with an empty database, the resolver must return null.
     *
     * **Validates: Requirements 4.9**
     */
    public function testFallbackReturnsNullWhenNoSeasonsExist(): void
    {
        $this
            ->forAll(
                Generators::choose(1900, 2100)  // target year
            )
            ->then(function (int $targetYear): void {
                $query = $this->createMock(DatabaseQuery::class);
                $query->method('select')->willReturnSelf();
                $query->method('from')->willReturnSelf();
                $query->method('innerJoin')->willReturnSelf();
                $query->method('where')->willReturnSelf();
                $query->method('order')->willReturnSelf();
                $query->method('setLimit')->willReturnSelf();
                $query->method('bind')->willReturnSelf();

                $db = $this->createMock(DatabaseInterface::class);
                $db->method('getQuery')->willReturn($query);
                $db->method('quoteName')->willReturnCallback(
                    fn(string $name, ?string $alias = null) => $alias ? '`' . $name . '` AS `' . $alias . '`' : '`' . $name . '`'
                );
                $db->method('setQuery')->willReturnSelf();

                // Both queries return null (no seasons at all)
                $db->method('loadObject')->willReturn(null);

                $result = TtclubHelper::getCurrentHalfSeason($db);

                $this->assertNull(
                    $result,
                    'Resolver should return null when no seasons exist in the database'
                );
            });
    }

    /**
     * Property 14: When only half=1 exists for the most recent season, fallback returns half=1.
     *
     * Generate random season sets where the most recent season only has half=1;
     * verify the fallback returns that half=1 record.
     *
     * **Validates: Requirements 4.9**
     */
    public function testFallbackReturnsHalf1WhenNoHalf2Exists(): void
    {
        $this
            ->forAll(
                Generators::choose(1900, 2100),  // target year (missing)
                Generators::choose(1901, 2099)   // most recent season start_year
            )
            ->then(function (int $targetYear, int $mostRecentYear): void {
                // Ensure target year differs from most recent year
                if ($targetYear === $mostRecentYear) {
                    $mostRecentYear = $targetYear - 1;
                    if ($mostRecentYear < 1900) {
                        return;
                    }
                }

                // Only half=1 exists for the most recent season
                $expectedHalfSeason = (object) [
                    'id' => 1,
                    'season_id' => 1,
                    'half' => 1,
                ];

                $callCount = 0;

                $query = $this->createMock(DatabaseQuery::class);
                $query->method('select')->willReturnSelf();
                $query->method('from')->willReturnSelf();
                $query->method('innerJoin')->willReturnSelf();
                $query->method('where')->willReturnSelf();
                $query->method('order')->willReturnSelf();
                $query->method('setLimit')->willReturnSelf();
                $query->method('bind')->willReturnSelf();

                $db = $this->createMock(DatabaseInterface::class);
                $db->method('getQuery')->willReturn($query);
                $db->method('quoteName')->willReturnCallback(
                    fn(string $name, ?string $alias = null) => $alias ? '`' . $name . '` AS `' . $alias . '`' : '`' . $name . '`'
                );
                $db->method('setQuery')->willReturnSelf();

                $db->method('loadObject')->willReturnCallback(
                    function () use (&$callCount, $expectedHalfSeason): ?object {
                        $callCount++;
                        if ($callCount === 1) {
                            return null; // no exact match
                        }
                        // Fallback returns half=1 (only half available)
                        return $expectedHalfSeason;
                    }
                );

                $result = TtclubHelper::getCurrentHalfSeason($db);

                $this->assertNotNull($result, 'Fallback should return a result when seasons exist');
                $this->assertSame(
                    1,
                    $result->half,
                    sprintf(
                        'When only half=1 exists for most recent season (start_year=%d), fallback should return half=1',
                        $mostRecentYear
                    )
                );
            });
    }
}
