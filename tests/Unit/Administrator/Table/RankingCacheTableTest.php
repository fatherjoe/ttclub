<?php

declare(strict_types=1);

namespace Tests\Unit\Administrator\Table;

use Fatherjoe\Component\Ttclub\Administrator\Table\RankingCacheTable;
use Joomla\Database\DatabaseDriver;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Fatherjoe\Component\Ttclub\Administrator\Table\RankingCacheTable
 */
class RankingCacheTableTest extends TestCase
{
    private RankingCacheTable $table;

    protected function setUp(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $this->table = new RankingCacheTable($db);
    }

    public function testTableNameIsCorrect(): void
    {
        // Access protected property via reflection
        $reflection = new \ReflectionClass($this->table);
        $tblProp = $reflection->getProperty('_tbl');
        self::assertSame('#__ttclub_ranking_cache', $tblProp->getValue($this->table));
    }

    public function testKeyNameIsId(): void
    {
        $reflection = new \ReflectionClass($this->table);
        $keyProp = $reflection->getProperty('_tbl_key');
        self::assertSame('id', $keyProp->getValue($this->table));
    }

    public function testCheckPassesWithValidData(): void
    {
        $this->table->team_id = 1;
        $this->table->half_season_id = 2;
        $this->table->ranking_html = '<table>...</table>';
        $this->table->fetched_at = '2025-01-15 10:00:00';

        self::assertTrue($this->table->check());
    }

    public function testCheckFailsWithoutTeamId(): void
    {
        $this->table->team_id = 0;
        $this->table->half_season_id = 2;
        $this->table->ranking_html = '<table>...</table>';
        $this->table->fetched_at = '2025-01-15 10:00:00';

        self::assertFalse($this->table->check());
        self::assertStringContainsString('team ID', $this->table->getError());
    }

    public function testCheckFailsWithoutHalfSeasonId(): void
    {
        $this->table->team_id = 1;
        $this->table->half_season_id = 0;
        $this->table->ranking_html = '<table>...</table>';
        $this->table->fetched_at = '2025-01-15 10:00:00';

        self::assertFalse($this->table->check());
        self::assertStringContainsString('half-season ID', $this->table->getError());
    }

    public function testCheckFailsWithoutRankingHtml(): void
    {
        $this->table->team_id = 1;
        $this->table->half_season_id = 2;
        $this->table->ranking_html = '';
        $this->table->fetched_at = '2025-01-15 10:00:00';

        self::assertFalse($this->table->check());
        self::assertStringContainsString('Ranking HTML', $this->table->getError());
    }

    public function testCheckFailsWithoutFetchedAt(): void
    {
        $this->table->team_id = 1;
        $this->table->half_season_id = 2;
        $this->table->ranking_html = '<table>...</table>';
        $this->table->fetched_at = '';

        self::assertFalse($this->table->check());
        self::assertStringContainsString('fetched_at', $this->table->getError());
    }
}
