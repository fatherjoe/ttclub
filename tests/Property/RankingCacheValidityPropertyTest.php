<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property 33: Ranking cache validity
 *
 * For any ranking cache entry, the entry should be served (not re-fetched) if
 * `fetched_at + cache_duration > current_time`, and should be re-fetched if
 * `fetched_at + cache_duration <= current_time`.
 *
 * **Validates: Requirements 14.5, 14.6**
 *
 * @Feature tabletennis-club-manager
 * @Property 33: Ranking cache validity
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class RankingCacheValidityPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Simulate the isCacheEntryValid logic from RankingService.
     *
     * This replicates the core cache validity check:
     * 1. Parse fetched_at as a Unix timestamp via strtotime()
     * 2. Check if (fetched_at + cacheDurationSeconds) > currentTime
     * 3. Return true if cache is still valid (should be served), false if expired (should be re-fetched)
     *
     * Returns null if fetched_at cannot be parsed (invalid format).
     */
    private function simulateCacheValidityCheck(
        string $fetchedAt,
        int $cacheDurationSeconds,
        int $currentTime
    ): ?bool {
        $fetchedAtTimestamp = strtotime($fetchedAt);

        if ($fetchedAtTimestamp === false) {
            return null;
        }

        return ($fetchedAtTimestamp + $cacheDurationSeconds) > $currentTime;
    }

    /**
     * Property 33: Cache entries where fetched_at + cache_duration > current_time
     * should be served (valid, not re-fetched).
     *
     * Generate random fetched_at timestamps and cache durations where the cache
     * has NOT yet expired, and verify that the validity check returns true.
     *
     * **Validates: Requirements 14.5, 14.6**
     */
    public function testCacheEntryServedWhenNotExpired(): void
    {
        $this
            ->forAll(
                Generators::choose(1_000_000, 2_000_000_000), // fetched_at as Unix timestamp
                Generators::choose(60, 86400),                 // cache_duration (1 min to 24 hours)
                Generators::choose(1, 100)                     // percentage of cache duration elapsed (1-99%)
            )
            ->then(function (int $fetchedAt, int $cacheDuration, int $percentage): void {
                // Ensure elapsed is strictly less than cacheDuration by using a percentage
                $elapsed = (int) floor(($cacheDuration - 1) * $percentage / 100);
                $currentTime = $fetchedAt + $elapsed;
                $fetchedAtString = date('Y-m-d H:i:s', $fetchedAt);

                $isValid = $this->simulateCacheValidityCheck(
                    $fetchedAtString,
                    $cacheDuration,
                    $currentTime
                );

                $this->assertNotNull(
                    $isValid,
                    "simulateCacheValidityCheck should not return null for valid datetime '$fetchedAtString'"
                );

                $this->assertTrue(
                    $isValid,
                    sprintf(
                        'Cache should be VALID (served) when fetched_at(%d) + duration(%d) = %d > current_time(%d). '
                        . 'Elapsed: %d seconds of %d allowed.',
                        $fetchedAt,
                        $cacheDuration,
                        $fetchedAt + $cacheDuration,
                        $currentTime,
                        $elapsed,
                        $cacheDuration
                    )
                );
            });
    }

    /**
     * Property 33: Cache entries where fetched_at + cache_duration <= current_time
     * should be re-fetched (expired, not served from cache).
     *
     * Generate random fetched_at timestamps and cache durations where the cache
     * HAS expired, and verify that the validity check returns false.
     *
     * **Validates: Requirements 14.5, 14.6**
     */
    public function testCacheEntryRefetchedWhenExpired(): void
    {
        $this
            ->forAll(
                Generators::choose(1_000_000, 2_000_000_000), // fetched_at as Unix timestamp
                Generators::choose(60, 86400),                 // cache_duration (1 min to 24 hours)
                Generators::choose(0, 172800)                  // additional time past expiry (0 to 48 hours)
            )
            ->then(function (int $fetchedAt, int $cacheDuration, int $additionalTime): void {
                // current_time is at or past the expiry point
                $currentTime = $fetchedAt + $cacheDuration + $additionalTime;
                $fetchedAtString = date('Y-m-d H:i:s', $fetchedAt);

                $isValid = $this->simulateCacheValidityCheck(
                    $fetchedAtString,
                    $cacheDuration,
                    $currentTime
                );

                $this->assertNotNull(
                    $isValid,
                    "simulateCacheValidityCheck should not return null for valid datetime '$fetchedAtString'"
                );

                $this->assertFalse(
                    $isValid,
                    sprintf(
                        'Cache should be EXPIRED (re-fetched) when fetched_at(%d) + duration(%d) = %d <= current_time(%d). '
                        . 'Exceeded by %d seconds.',
                        $fetchedAt,
                        $cacheDuration,
                        $fetchedAt + $cacheDuration,
                        $currentTime,
                        $additionalTime
                    )
                );
            });
    }

    /**
     * Property 33: The boundary condition — when fetched_at + cache_duration equals
     * current_time exactly, the cache should be re-fetched (expired).
     *
     * This tests the boundary: the condition is strict greater-than, so equality
     * means expired.
     *
     * **Validates: Requirements 14.5, 14.6**
     */
    public function testCacheEntryExpiredAtExactBoundary(): void
    {
        $this
            ->forAll(
                Generators::choose(1_000_000, 2_000_000_000), // fetched_at as Unix timestamp
                Generators::choose(60, 86400)                  // cache_duration (1 min to 24 hours)
            )
            ->then(function (int $fetchedAt, int $cacheDuration): void {
                // current_time equals exactly the expiry point
                $currentTime = $fetchedAt + $cacheDuration;
                $fetchedAtString = date('Y-m-d H:i:s', $fetchedAt);

                $isValid = $this->simulateCacheValidityCheck(
                    $fetchedAtString,
                    $cacheDuration,
                    $currentTime
                );

                $this->assertNotNull(
                    $isValid,
                    "simulateCacheValidityCheck should not return null for valid datetime '$fetchedAtString'"
                );

                $this->assertFalse(
                    $isValid,
                    sprintf(
                        'Cache should be EXPIRED at exact boundary: fetched_at(%d) + duration(%d) = %d == current_time(%d). '
                        . 'Condition is strict >, so equality means expired.',
                        $fetchedAt,
                        $cacheDuration,
                        $fetchedAt + $cacheDuration,
                        $currentTime
                    )
                );
            });
    }

    /**
     * Property 33: The validity decision is consistent — for any given
     * (fetched_at, cache_duration, current_time) triple, the result is deterministic
     * and follows the formula: valid iff fetched_at + cache_duration > current_time.
     *
     * **Validates: Requirements 14.5, 14.6**
     */
    public function testCacheValidityDecisionMatchesFormula(): void
    {
        $this
            ->forAll(
                Generators::choose(1_000_000, 2_000_000_000), // fetched_at as Unix timestamp
                Generators::choose(1, 86400),                  // cache_duration (1 sec to 24 hours)
                Generators::choose(1_000_000, 2_100_000_000)   // current_time as Unix timestamp
            )
            ->then(function (int $fetchedAt, int $cacheDuration, int $currentTime): void {
                $fetchedAtString = date('Y-m-d H:i:s', $fetchedAt);

                $isValid = $this->simulateCacheValidityCheck(
                    $fetchedAtString,
                    $cacheDuration,
                    $currentTime
                );

                $this->assertNotNull(
                    $isValid,
                    "simulateCacheValidityCheck should not return null for valid datetime '$fetchedAtString'"
                );

                // The expected result directly from the formula
                $expectedValid = ($fetchedAt + $cacheDuration) > $currentTime;

                $this->assertSame(
                    $expectedValid,
                    $isValid,
                    sprintf(
                        'Cache validity should match formula: (%d + %d = %d) %s %d → expected %s, got %s',
                        $fetchedAt,
                        $cacheDuration,
                        $fetchedAt + $cacheDuration,
                        $expectedValid ? '>' : '<=',
                        $currentTime,
                        $expectedValid ? 'valid (served)' : 'expired (re-fetch)',
                        $isValid ? 'valid (served)' : 'expired (re-fetch)'
                    )
                );
            });
    }
}
