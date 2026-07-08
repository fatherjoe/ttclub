<?php

declare(strict_types=1);

namespace Tests\Unit\Administrator\Service;

use Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtParser;
use Fatherjoe\Component\Ttclub\Administrator\Service\RankingService;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\DatabaseQuery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Fatherjoe\Component\Ttclub\Administrator\Service\RankingService
 */
class RankingServiceTest extends TestCase
{
    public function testIsCacheValidReturnsFalseWhenNoCache(): void
    {
        $db = $this->createMockDb(loadObjectReturn: null);
        $parser = new ClickTtParser();

        $service = new RankingService($db, $parser, 3600);

        self::assertFalse($service->isCacheValid(1, 1));
    }

    public function testIsCacheValidReturnsTrueWhenCacheFresh(): void
    {
        $freshTime = date('Y-m-d H:i:s', time() - 1800); // 30 min ago
        $cached = (object) ['fetched_at' => $freshTime];

        $db = $this->createMockDb(loadObjectReturn: $cached);
        $parser = new ClickTtParser();

        $service = new RankingService($db, $parser, 3600);

        self::assertTrue($service->isCacheValid(1, 1));
    }

    public function testIsCacheValidReturnsFalseWhenCacheExpired(): void
    {
        $expiredTime = date('Y-m-d H:i:s', time() - 7200); // 2 hours ago
        $cached = (object) ['fetched_at' => $expiredTime];

        $db = $this->createMockDb(loadObjectReturn: $cached);
        $parser = new ClickTtParser();

        $service = new RankingService($db, $parser, 3600);

        self::assertFalse($service->isCacheValid(1, 1));
    }

    public function testIsCacheValidRespectsCustomDuration(): void
    {
        // 10 minutes ago with 5 minute cache = expired
        $tenMinAgo = date('Y-m-d H:i:s', time() - 600);
        $cached = (object) ['fetched_at' => $tenMinAgo];

        $db = $this->createMockDb(loadObjectReturn: $cached);
        $parser = new ClickTtParser();

        $service = new RankingService($db, $parser, 300); // 5 min cache

        self::assertFalse($service->isCacheValid(1, 1));
    }

    public function testIsCacheValidWithZeroDurationAlwaysExpires(): void
    {
        $justNow = date('Y-m-d H:i:s', time() - 1); // 1 second ago
        $cached = (object) ['fetched_at' => $justNow];

        $db = $this->createMockDb(loadObjectReturn: $cached);
        $parser = new ClickTtParser();

        $service = new RankingService($db, $parser, 0); // zero cache

        self::assertFalse($service->isCacheValid(1, 1));
    }

    public function testGetRankingReturnsNullWhenNoCacheAndFetchFails(): void
    {
        $db = $this->createMockDb(loadObjectReturn: null);
        $parser = new ClickTtParser();

        // Create a subclass that simulates fetch failure
        $service = new class($db, $parser, 3600) extends RankingService {
            protected function fetchPage(string $url): ?string
            {
                return null; // Simulate network failure
            }
        };

        self::assertNull($service->getRanking(1, 1));
    }

    public function testGetRankingReturnsCachedDataWhenValid(): void
    {
        $rankingData = [
            ['position' => 1, 'team_name' => 'TTV Musterstadt', 'matches' => 10, 'wins' => 8, 'draws' => 1, 'losses' => 1, 'points' => '17:3'],
            ['position' => 2, 'team_name' => 'SV Opponent', 'matches' => 10, 'wins' => 7, 'draws' => 2, 'losses' => 1, 'points' => '16:4'],
        ];
        $freshTime = date('Y-m-d H:i:s', time() - 100); // recent
        $cached = (object) [
            'fetched_at' => $freshTime,
            'ranking_html' => json_encode($rankingData),
        ];

        // Mock DB to return cached data + club names
        $query = $this->createMock(DatabaseQuery::class);
        $query->method('select')->willReturn($query);
        $query->method('from')->willReturn($query);
        $query->method('where')->willReturn($query);
        $query->method('innerJoin')->willReturn($query);
        $query->method('order')->willReturn($query);
        $query->method('delete')->willReturn($query);

        $db = $this->createMock(DatabaseInterface::class);
        $db->method('getQuery')->willReturn($query);
        $db->method('quoteName')->willReturnCallback(fn($n) => '`' . $n . '`');
        $db->method('setQuery')->willReturn($db);
        $db->method('quote')->willReturnCallback(fn($v) => "'" . $v . "'");

        // First call: loadObject for cached ranking; Second call: loadColumn for club names
        $db->method('loadObject')->willReturn($cached);
        $db->method('loadColumn')->willReturn(['TTV Musterstadt']);

        $parser = new ClickTtParser();
        $service = new RankingService($db, $parser, 3600);

        $result = $service->getRanking(1, 1);

        self::assertNotNull($result);
        self::assertCount(2, $result);
        self::assertTrue($result[0]['is_own_team']);
        self::assertFalse($result[1]['is_own_team']);
    }

    public function testInvalidateCacheExecutesDeleteQuery(): void
    {
        $query = $this->createMock(DatabaseQuery::class);
        $query->method('delete')->willReturn($query);
        $query->method('where')->willReturn($query);

        $db = $this->createMock(DatabaseInterface::class);
        $db->method('getQuery')->willReturn($query);
        $db->method('quoteName')->willReturnCallback(fn($n) => '`' . $n . '`');
        $db->method('setQuery')->willReturn($db);
        $db->expects(self::once())->method('execute')->willReturn(true);

        $parser = new ClickTtParser();
        $service = new RankingService($db, $parser, 3600);

        $service->invalidateCache(1, 1);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function createMockDb(?object $loadObjectReturn): DatabaseInterface
    {
        $query = $this->createMock(DatabaseQuery::class);
        $query->method('select')->willReturn($query);
        $query->method('from')->willReturn($query);
        $query->method('where')->willReturn($query);
        $query->method('innerJoin')->willReturn($query);
        $query->method('order')->willReturn($query);
        $query->method('delete')->willReturn($query);

        $db = $this->createMock(DatabaseInterface::class);
        $db->method('getQuery')->willReturn($query);
        $db->method('quoteName')->willReturnCallback(fn($n) => '`' . $n . '`');
        $db->method('setQuery')->willReturn($db);
        $db->method('loadObject')->willReturn($loadObjectReturn);
        $db->method('loadColumn')->willReturn([]);
        $db->method('quote')->willReturnCallback(fn($v) => "'" . $v . "'");

        return $db;
    }
}
